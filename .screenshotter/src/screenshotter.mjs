#!/usr/bin/env node
/**
 * Screenshotter: Playwright-driven UI walkthrough that writes PNGs to
 * `.screenshotter/screenshots/`. See `.screenshotter/README.md` for a
 * full how-to, requirements, and data-caution notes.
 *
 * Usage:
 *   npm run screenshotter
 *   node .screenshotter/src/screenshotter.mjs
 *
 * Env overrides:
 *   BASE_URL         (default: https://snipe-it.test)
 *   USERNAME         (default: admin)
 *   PASSWORD         (default: password)
 *   OUT              (default: .screenshotter/screenshots, gitignored)
 *   HEADLESS         (default: true; set to "false" to watch it run)
 *   VIEWPORT_WIDTH   (default: 1840)
 *   VIEWPORT_HEIGHT  (default: 900)
 *   FRAME            (default: true; wrap each shot in local browser chrome.
 *                     Set FRAME=false to skip the frame post-processing.)
 *   ALL_ROUTES       (default: true; set to "false" to skip the full-app sweep)
 *   TABLE_PAGE_SIZE  (default: 10; caps bootstrap-table rows per shot)
 *   SUBMIT_FORMS     (default: true; posts each edit form after the
 *                     `-edit` shot to capture the `-edit-submitted` state.
 *                     Set SUBMIT_FORMS=false to skip the writes.)
 *   TABS             (default: false; opt in with TABS=true to walk each
 *                     view page's Bootstrap tabs and capture each tab's
 *                     content as `{section}/{user}-view-tab-{slug}`.)
 *
 * Section filter (skip everything except the named sections):
 *   node .screenshotter/src/screenshotter.mjs --section assets,licenses
 *
 *   Sections match the directory name under screenshots/, e.g.
 *   `assets`, `licenses`, `users`, `dashboard`, `settings`, `reports`,
 *   `custom-fields`, `fieldsets`, `maintenance-types`, etc. Managers
 *   are included automatically when their section is in the filter.
 *   Other sections' shots from prior runs are preserved (not wiped).
 *
 * Ad-hoc single-shot mode (skips the full walkthrough):
 *   node .screenshotter/src/screenshotter.mjs --one <path> [--as <username>] [--name <slug>]
 *
 *   Examples:
 *     node .screenshotter/src/screenshotter.mjs --one /hardware
 *     node .screenshotter/src/screenshotter.mjs --one /hardware/create --as assetmgr
 *     node .screenshotter/src/screenshotter.mjs --one /licenses/5 --as licensemgr --name license-detail
 *
 * DATA WARNING: this script captures whatever is currently rendering in
 * the connected database, in full. Do not run against production,
 * staging that mirrors production, or any DB containing real customer
 * data, PII, uploaded avatars/documents, or license keys. A freshly
 * seeded demo DB is the safe and reproducible default.
 */

import {chromium} from 'playwright';
import {mkdir, readdir, readFile, rm, writeFile} from 'node:fs/promises';
import {dirname, resolve} from 'node:path';
import {spawnSync} from 'node:child_process';
import {parseArgs} from 'node:util';

const BASE_URL = process.env.BASE_URL ?? 'https://snipe-it.test';
const USERNAME = process.env.USERNAME ?? 'admin';
const PASSWORD = process.env.PASSWORD ?? 'password';
const OUT = resolve(process.env.OUT ?? '.screenshotter/screenshots');
const HEADLESS = process.env.HEADLESS !== 'false';
const VIEWPORT = {
    width: Number(process.env.VIEWPORT_WIDTH ?? 1840),
    height: Number(process.env.VIEWPORT_HEIGHT ?? 900),
};
// Framing is on by default because the raw shots look bare without
// browser chrome. Set FRAME=false to skip the frame post-processing.
const FRAME = process.env.FRAME !== 'false';
const ALL_ROUTES = process.env.ALL_ROUTES !== 'false';
const TABLE_PAGE_SIZE = Number(process.env.TABLE_PAGE_SIZE ?? 10);
// Whether to submit edit forms after screenshotting them (to capture the
// success callout / validation-error state). Default on, since that's
// the whole point of the "-edit-submitted" shot. Set SUBMIT_FORMS=false
// to skip the write-back and avoid the resulting action_log entries,
// observer/notification/webhook fires, etc.
const SUBMIT_FORMS = process.env.SUBMIT_FORMS !== 'false';
// Whether to walk each view page's Bootstrap tabs and shoot each tab
// pane. Default OFF because tabs multiply the shot count per view page
// (a page with 5 tabs adds 4 extra shots per user). Set TABS=true to
// opt in when you want the full sweep.
const TABS = process.env.TABS === 'true';

const {values: cli} = parseArgs({
    options: {
        one: {type: 'string'},
        as: {type: 'string'},
        name: {type: 'string'},
        section: {type: 'string'},
    },
    strict: false,
});
const ONE_SHOT = cli.one ?? null;
// Comma-separated list of section names to include; when set, everything
// else (other first-class objects, standalone pages like dashboard/
// settings/reports, the all-routes sweep, and any manager whose section
// isn't in the list) is skipped. Case-insensitive, whitespace-trimmed.
const SECTION_FILTER = cli.section
    ? new Set(cli.section.split(',').map((s) => s.trim().toLowerCase()).filter(Boolean))
    : null;
const includesSection = (name) => !SECTION_FILTER || SECTION_FILTER.has(name.toLowerCase());

const RUN_TIMESTAMP = (() => {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}-${pad(d.getHours())}${pad(d.getMinutes())}${pad(d.getSeconds())}`;
})();

console.log(`Base URL:  ${BASE_URL}`);
console.log(`Login as:  ${cli.as ?? USERNAME}`);
console.log(`Output:    ${OUT}`);
console.log(`Viewport:  ${VIEWPORT.width}x${VIEWPORT.height}`);
console.log(`Framing:   ${FRAME ? 'local (generic-light)' : 'off'}`);
console.log(`Submit:    ${SUBMIT_FORMS ? 'on (edit forms are posted after shot)' : 'off (edit forms are not posted)'}`);
console.log(`Tabs:      ${TABS ? 'on (view pages walk each tab)' : 'off (view pages shoot base only)'}`);
if (SECTION_FILTER) console.log(`Sections:  ${[...SECTION_FILTER].join(', ')}`);
if (ONE_SHOT) console.log(`Mode:      ad-hoc single shot (${ONE_SHOT})`);
console.log('');

// Wipe walkthrough shots so a full run never leaves stale images mixed
// with fresh ones. Preserve `adhoc/` so historical one-off images stick
// around. The script itself lives outside OUT (in .screenshotter/src/)
// so it can never accidentally be included in the wipe.
//
// When --section is used we only wipe the section dirs we're about to
// regenerate; other sections' shots from previous runs are preserved.
// Ad-hoc mode itself never wipes anything.
await mkdir(OUT, {recursive: true});
if (!ONE_SHOT) {
    const entries = await readdir(OUT, {withFileTypes: true});
    for (const entry of entries) {
        if (entry.name === 'adhoc') continue;
        if (SECTION_FILTER && !SECTION_FILTER.has(entry.name.toLowerCase())) continue;
        await rm(`${OUT}/${entry.name}`, {recursive: true, force: true});
    }
}

const browser = await chromium.launch({headless: HEADLESS});
const context = await browser.newContext({
    viewport: VIEWPORT,
    // Herd's local .test domains use self-signed certs.
    ignoreHTTPSErrors: true,
});

// Hide dev-only UI chrome from every page. Debugbar/Telescope/Clockwork
// would otherwise land in the middle of shots.
await context.addInitScript(() => {
    const inject = () => {
        if (document.getElementById('__screenshotter-hide')) return;
        const style = document.createElement('style');
        style.id = '__screenshotter-hide';
        style.textContent = `
            #phpdebugbar, #phpdebugbar-openhandler,
            .phpdebugbar, .phpdebugbar-openhandler,
            #telescope-toolbar, .telescope-toolbar,
            #clockwork, #clockwork-toolbar,
            [data-clockwork], iframe[src*="clockwork"] {
                display: none !important;
            }
        `;
        (document.head || document.documentElement).appendChild(style);
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inject);
    } else {
        inject();
    }
});

const page = await context.newPage();
let shotCount = 0;

/**
 * Take a screenshot and log it. Auto-creates parent dirs for names with
 * slashes. Appends the run timestamp. When FRAME=true, post-processes
 * through the local frame template.
 */
async function shot(name, opts = {}) {
    const path = `${OUT}/${name}-${RUN_TIMESTAMP}.png`;
    await mkdir(dirname(path), {recursive: true});
    const {element, ...playwrightOpts} = opts;
    await page.waitForLoadState('networkidle').catch(() => {});
    if (element) {
        await element.screenshot({path, ...playwrightOpts});
    } else {
        await page.screenshot({path, fullPage: true, ...playwrightOpts});
    }
    shotCount++;
    if (FRAME) {
        await frameLocally(path);
        console.log(`  ✓ ${name}-${RUN_TIMESTAMP}.png (framed)`);
    } else {
        console.log(`  ✓ ${name}-${RUN_TIMESTAMP}.png`);
    }
}

/**
 * Wrap a PNG in a browser-chrome frame mimicking browserframe.com's
 * generic-light style. Runs entirely local via an inline HTML template.
 */
let framePage = null;
async function frameLocally(imagePath) {
    if (!framePage) framePage = await context.newPage();
    const fp = framePage;
    const buf = await readFile(imagePath);
    const dims = pngSize(buf);
    const dataUri = 'data:image/png;base64,' + buf.toString('base64');

    // Symmetric padding so the box-shadow (radiating in all directions)
    // has room to render without clipping on any edge.
    const PADDING_X = 70;
    const PADDING_TOP = 70;
    const PADDING_BOTTOM = 70;
    const CHROME_HEIGHT = 44;
    const outerWidth = dims.width + PADDING_X * 2;
    const outerHeight = dims.height + CHROME_HEIGHT + PADDING_TOP + PADDING_BOTTOM;

    await fp.setViewportSize({width: outerWidth, height: outerHeight});
    await fp.setContent(`
<!doctype html>
<html>
<head>
<style>
  html, body { margin: 0; padding: 0; background: transparent; }
  body {
    padding: ${PADDING_TOP}px ${PADDING_X}px ${PADDING_BOTTOM}px ${PADDING_X}px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  }
  .frame {
    width: ${dims.width}px;
    background: #ffffff;
    border-radius: 10px;
    /* Ambient shadow radiating on all four sides. */
    box-shadow: 0 0 50px rgba(102, 102, 102, 0.5), 0 0 0 1px rgba(0, 0, 0, 0.06);
    overflow: hidden;
  }
  .chrome {
    height: ${CHROME_HEIGHT}px;
    background: linear-gradient(#f6f6f6, #ececec);
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    padding: 0 14px;
    box-sizing: border-box;
  }
  .dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 8px;
    box-shadow: inset 0 0 0 0.5px rgba(0, 0, 0, 0.15);
  }
  .dot.red { background: #ff5f57; }
  .dot.yellow { background: #febc2e; }
  .dot.green { background: #28c840; }
  .shot { display: block; width: ${dims.width}px; height: ${dims.height}px; }
</style>
</head>
<body>
  <div class="frame">
    <div class="chrome">
      <span class="dot red"></span>
      <span class="dot yellow"></span>
      <span class="dot green"></span>
    </div>
    <img class="shot" src="${dataUri}"/>
  </div>
</body>
</html>
    `);

    await fp.evaluate(() => {
        const img = document.querySelector('img.shot');
        if (img.complete) return;
        return new Promise((resolve) => {
            img.addEventListener('load', resolve, {once: true});
            img.addEventListener('error', resolve, {once: true});
        });
    });

    const framed = await fp.screenshot({fullPage: true, omitBackground: true});
    await writeFile(imagePath, framed);
}

/**
 * Read a PNG's width and height from its IHDR chunk (bytes 16-24).
 */
function pngSize(buf) {
    return {width: buf.readUInt32BE(16), height: buf.readUInt32BE(20)};
}

/**
 * Cap any bootstrap-table on the current page to TABLE_PAGE_SIZE rows.
 * Safe to call on non-table pages.
 */
async function capPagination() {
    const shrank = await page.evaluate((pageSize) => {
        if (!window.jQuery || !window.jQuery.fn.bootstrapTable) return false;
        const $tables = window.jQuery('table.snipe-table');
        if (!$tables.length) return false;
        $tables.bootstrapTable('refreshOptions', {pageSize});
        return true;
    }, TABLE_PAGE_SIZE).catch(() => false);
    if (shrank) {
        await page.waitForSelector('.bootstrap-table:not(:has(.fixed-table-loading[style*="display: block"]))', {timeout: 15_000}).catch(() => {});
        await page.waitForTimeout(300);
    }
    return shrank;
}

/**
 * Wait for a bootstrap-table to finish its initial render, then cap
 * pagination.
 */
async function waitForTable() {
    await page.waitForSelector('.bootstrap-table:not(:has(.fixed-table-loading[style*="display: block"]))', {timeout: 15_000}).catch(() => {});
    await page.waitForTimeout(300);
    await capPagination();
}

/**
 * Return the id of the first non-actions-column entity link in the
 * current index table. Used by view/edit shots to avoid hardcoded ids
 * that change on reseed.
 */
async function getFirstEntityId(segment) {
    const href = await page.locator(`table.snipe-table tbody a[href*="/${segment}/"]`).filter({
        hasNot: page.locator('[href*="/edit"], [href*="/checkout"], [href*="/checkin"], [href*="/clone"], [href*="/delete"], [href*="/restore"]'),
    }).first().getAttribute('href').catch(() => null);
    if (!href) return null;
    const m = href.match(new RegExp(`/${segment}/(\\d+)(?:$|[/?#])`));
    return m ? m[1] : null;
}

/**
 * Take index + view + edit shots for one first-class object, filed
 * under `{name}/{viewer}-{page}` so a section directory holds all
 * viewers side by side for easy comparison.
 *
 * View pages get an extra pass that walks any Bootstrap `.nav-tabs`
 * present on the page and captures each tab's content
 * (`{name}/{viewer}-view-tab-{slug}`).
 *
 * Edit pages get an extra shot after submitting the form
 * (`{name}/{viewer}-edit-submitted`) to capture whatever the app renders
 * post-save, whether that's the success callout on the redirect target
 * or the validation-error state on the same page.
 */
async function shootIndexViewEdit({segment, name, hasView = true, hasEdit = true, viewer}) {
    const who = viewer ?? USERNAME;
    console.log(`→ ${name} (as ${who})`);
    await page.goto(`${BASE_URL}/${segment}`);
    await waitForTable();
    await shot(`${name}/${who}-${name}-index`);

    if (!hasView && !hasEdit) return;

    const id = await getFirstEntityId(segment);
    if (!id) {
        console.log(`  ! no rows in ${name}, skipping view/edit`);
        return;
    }

    if (hasView) {
        await page.goto(`${BASE_URL}/${segment}/${id}`);
        await page.waitForLoadState('networkidle').catch(() => {});
        await shot(`${name}/${who}-${name}-view`);
        await walkTabs(`${name}/${who}-${name}-view`);
    }
    if (hasEdit) {
        await page.goto(`${BASE_URL}/${segment}/${id}/edit`);
        await page.waitForLoadState('networkidle').catch(() => {});
        await shot(`${name}/${who}-${name}-edit`);
        await submitEditForm(`${name}/${who}-${name}-edit-submitted`);
    }
}

/**
 * If the current page has `.nav-tabs a[data-toggle=tab]`, click through
 * each tab (except the one that's already active, which is the state
 * the base shot already captured), wait a beat for the pane transition,
 * and screenshot. Skips silently on pages with no tabs.
 */
async function walkTabs(basename) {
    if (!TABS) return;
    const tabs = await page.locator('.nav-tabs a[data-toggle="tab"]:visible').all();
    if (tabs.length <= 1) return;

    for (const tab of tabs) {
        // Skip the tab that's already showing when we arrived. Its content
        // is what the base view shot captured, so re-shooting is wasteful.
        const isActive = await tab.evaluate((el) => el.closest('li')?.classList.contains('active') ?? false).catch(() => false);
        if (isActive) continue;

        const rawLabel = ((await tab.textContent()) || '').trim();
        if (!rawLabel) continue;
        const slug = rawLabel.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        if (!slug) continue;

        await tab.click().catch(() => {});
        await page.waitForTimeout(400); // let the pane fade-in complete
        await waitForTable(); // some tabs house bootstrap-tables of their own
        await shot(`${basename}-tab-${slug}`);
    }
}

/**
 * Submit the primary form on the current page (typically an edit form)
 * and screenshot whatever renders afterward. Waits for network to
 * settle on the resulting page so callouts/toast messages are fully
 * rendered. Skips silently if there's no obvious submit button, or if
 * SUBMIT_FORMS=false was passed in the environment.
 */
async function submitEditForm(outName) {
    if (!SUBMIT_FORMS) return;
    // Find the primary Save button of the edit form:
    //   - Exclude the topbar search form (#topSearchButton) which is the
    //     first `[type=submit]` on every page and would fire an empty
    //     asset-tag lookup ("Warning: Asset with tag not found.") if
    //     naively clicked.
    //   - Exclude the hidden logout form's submit that also lives in the
    //     header.
    //   - Prefer `.last()`: on Snipe-IT edit forms the Save button is
    //     rendered at the bottom of the form, after any inline widget
    //     submit buttons the form might contain.
    // Refactored forms wrap up with `<x-box.footer />` which always
    // renders the Save as `<button id="submit_button">`. Target that
    // first because it's a stable semantic hook that can't collide
    // with the topbar search or any inline widget submit.
    let submit = page.locator('#submit_button:visible').first();
    if (!(await submit.count().catch(() => 0))) {
        // Legacy forms that haven't been refactored to <x-box.footer />
        // fall back to a visible, non-topbar submit button. `.last()`
        // because Snipe-IT edit forms put Save at the bottom of the
        // form, after any inline widget submits.
        submit = page
            .locator('form button[type="submit"]:visible, form input[type="submit"]:visible')
            .filter({hasNot: page.locator('#topSearchButton')})
            .last();
    }

    // Extra guard: the locator above can still match nothing if the
    // page has no form. Check count before clicking.
    if (!(await submit.count().catch(() => 0))) return;

    // If the resolved element is inside the top navbar (some layouts
    // wrap the search form in `<header>` or `.main-header`), bail out
    // rather than fire the wrong submit.
    const insideHeader = await submit.evaluate(
        (el) => !!el.closest('header, nav, .main-header, .navbar')
    ).catch(() => false);
    if (insideHeader) return;

    await submit.click().catch(() => {});
    // Either the server redirects (success) or re-renders with errors.
    // Wait for the response and then for the new page to settle.
    await page.waitForLoadState('networkidle', {timeout: 15_000}).catch(() => {});
    await shot(outName);
}

/**
 * Log in as a specific user. Clears cookies first so repeated calls
 * cleanly switch accounts.
 */
async function loginAs(username, password = PASSWORD) {
    await context.clearCookies();
    await page.goto(`${BASE_URL}/login`);
    await page.fill('#username', username);
    await page.fill('#password-field', password);
    await Promise.all([
        page.waitForURL((url) => !url.pathname.endsWith('/login')),
        page.click('button[type=submit]'),
    ]);
    await page.waitForLoadState('networkidle').catch(() => {});
}

/**
 * Block-scoped session helper: log in as username, run fn, any shots
 * inside get captured under that session.
 */
async function asUser(username, fn, password) {
    console.log(`→ session: ${username}`);
    await loginAs(username, password);
    await fn();
}

/**
 * For one resource-manager, shoot the four canonical pages (index,
 * view, edit, create) scoped strictly to the resource they manage.
 * Shots land in `${name}/{username}-{page}` alongside the admin shots.
 */
async function shootManager({username, segment, name}) {
    await asUser(username, async () => {
        await page.goto(`${BASE_URL}/${segment}`);
        await waitForTable();
        await shot(`${name}/${username}-${name}-index`);

        const id = await getFirstEntityId(segment);
        if (id) {
            await page.goto(`${BASE_URL}/${segment}/${id}`);
            await page.waitForLoadState('networkidle').catch(() => {});
            await shot(`${name}/${username}-${name}-view`);
            await walkTabs(`${name}/${username}-${name}-view`);

            await page.goto(`${BASE_URL}/${segment}/${id}/edit`);
            await page.waitForLoadState('networkidle').catch(() => {});
            await shot(`${name}/${username}-${name}-edit`);
            await submitEditForm(`${name}/${username}-${name}-edit-submitted`);
        } else {
            console.log(`  ! ${username}: no rows in ${name}, skipping view/edit`);
        }

        await page.goto(`${BASE_URL}/${segment}/create`);
        await page.waitForLoadState('networkidle').catch(() => {});
        await shot(`${name}/${username}-${name}-create`);
    });
}

// -------------------------------------------------------------------
// Ad-hoc single-shot mode (short-circuit)
// -------------------------------------------------------------------
if (ONE_SHOT) {
    const asUsername = cli.as ?? USERNAME;
    const uri = ONE_SHOT.replace(/^\//, '');
    const baseName = cli.name ?? (uri === '' ? 'root' : uri.replace(/[/?&=]/g, '__'));
    const outName = `adhoc/${asUsername}-${baseName}`;

    console.log(`→ logging in as ${asUsername}`);
    await loginAs(asUsername, PASSWORD);

    console.log(`→ ${BASE_URL}/${uri}`);
    await page.goto(`${BASE_URL}/${uri}`, {waitUntil: 'domcontentloaded'});
    await page.waitForLoadState('networkidle').catch(() => {});
    await capPagination();
    await shot(outName);

    console.log(`Done. 1 screenshot written to ${OUT}/${outName}-${RUN_TIMESTAMP}.png`);
    await browser.close();
    process.exit(0);
}

// -------------------------------------------------------------------
// Full walkthrough
// -------------------------------------------------------------------

console.log('→ logging in');
await loginAs(USERNAME, PASSWORD);

// First-class objects: index + view + edit for each.
// `hasView: false` skips the view shot for entities without a detail page.
const firstClassObjects = [
    {segment: 'hardware', name: 'assets'},
    {segment: 'licenses', name: 'licenses'},
    {segment: 'accessories', name: 'accessories'},
    {segment: 'consumables', name: 'consumables'},
    {segment: 'components', name: 'components'},
    {segment: 'users', name: 'users'},
    {segment: 'models', name: 'models'},
    {segment: 'categories', name: 'categories'},
    {segment: 'manufacturers', name: 'manufacturers'},
    {segment: 'suppliers', name: 'suppliers'},
    {segment: 'locations', name: 'locations'},
    {segment: 'departments', name: 'departments'},
    {segment: 'kits', name: 'kits'},
    {segment: 'companies', name: 'companies'},
    {segment: 'statuslabels', name: 'statuslabels'},
    {segment: 'depreciations', name: 'depreciations'},
    {segment: 'fields', name: 'custom-fields', hasView: false},
    {segment: 'fields/fieldsets', name: 'fieldsets'},
    {segment: 'maintenance-types', name: 'maintenance-types', hasView: false},
];
for (const obj of firstClassObjects) {
    if (!includesSection(obj.name)) continue;
    await shootIndexViewEdit(obj);
}

// Extras: create form + status-dropdown interaction on assets.
if (includesSection('assets')) {
    console.log('→ assets extras');
    await page.goto(`${BASE_URL}/hardware/create`);
    await shot(`assets/${USERNAME}-assets-create`);

    await page.locator('#status_select_id + .select2').first().click().catch(async () => {
        await page.locator('label[for="status_id"] ~ .select2, label:has-text("Status") ~ .select2').first().click();
    });
    await page.waitForSelector('.select2-dropdown', {timeout: 5_000}).catch(() => {});
    await shot(`assets/${USERNAME}-assets-create-status-dropdown`, {fullPage: false});
}

// Extra create forms for users and licenses (bundled with those sections
// so `--section users` includes the users create shot).
if (includesSection('users')) {
    console.log('→ users extras');
    await page.goto(`${BASE_URL}/users/create`);
    await shot(`users/${USERNAME}-users-create`);
}
if (includesSection('licenses')) {
    console.log('→ licenses extras');
    await page.goto(`${BASE_URL}/licenses/create`);
    await shot(`licenses/${USERNAME}-licenses-create`);
}

// Standalone singleton sections. These don't fit the index/view/edit
// shape so they're their own config; each entry is a section name plus
// a list of pages to hit within that section.
const standaloneSections = [
    {name: 'dashboard', pages: [{path: '/', suffix: 'index'}]},
    {name: 'settings', pages: [
        {path: '/admin', suffix: 'index'},
        {path: '/admin/settings', suffix: 'general'},
    ]},
    {name: 'reports', pages: [{path: '/reports', suffix: 'index'}]},
];
for (const section of standaloneSections) {
    if (!includesSection(section.name)) continue;
    console.log(`→ ${section.name}`);
    for (const p of section.pages) {
        await page.goto(`${BASE_URL}${p.path}`);
        await shot(`${section.name}/${USERNAME}-${section.name}-${p.suffix}`);
    }
}

// Resource-manager perspectives: same-page shots as each scoped user.
// Shots land in the same section directory as admin shots (via the
// `${name}/{username}-{page}` naming) so you can compare side by side.
const managers = [
    {username: 'assetmgr', segment: 'hardware', name: 'assets'},
    {username: 'licensemgr', segment: 'licenses', name: 'licenses'},
    {username: 'accessorymgr', segment: 'accessories', name: 'accessories'},
    {username: 'consumablemgr', segment: 'consumables', name: 'consumables'},
    {username: 'componentmgr', segment: 'components', name: 'components'},
    {username: 'usermgr', segment: 'users', name: 'users'},
];
for (const mgr of managers) {
    if (!includesSection(mgr.name)) continue;
    await shootManager(mgr);
}

// -------------------------------------------------------------------
// All-routes superuser sweep
// -------------------------------------------------------------------
if (ALL_ROUTES && !SECTION_FILTER) {
    console.log('→ all-routes sweep (superuser)');

    const proc = spawnSync('php', ['artisan', 'route:list', '--json'], {
        encoding: 'utf8',
        maxBuffer: 32 * 1024 * 1024,
    });
    if (proc.status !== 0) {
        console.log(`  ! route:list failed: ${proc.stderr}`);
    } else {
        const routes = JSON.parse(proc.stdout);
        const skipPrefixes = [
            '_debugbar', 'api/', 'livewire/', 'livewire-', 'telescope', 'horizon',
            'oauth/', 'sanctum/', '_ignition', 'sso/',
            'password/', 'auth/logout', 'logout', 'login',
            'saml/', 'scim/', 'google/',
            'setup/', 'setup', 'health',
            'test-email',
        ];
        const skipContains = ['/export', 'export.'];
        const skipSuffixes = ['.js', '.map', '.json', '.xml', '.csv', '.pdf'];

        const usable = routes.filter((r) => {
            if (!r.method || !r.method.includes('GET')) return false;
            const uri = (r.uri || '').replace(/^\//, '');
            if (uri.includes('{')) return false;
            if (uri === '') return false;
            if (skipPrefixes.some((p) => uri === p.replace(/\/$/, '') || uri.startsWith(p))) return false;
            if (skipContains.some((c) => uri.includes(c))) return false;
            if (skipSuffixes.some((s) => uri.endsWith(s))) return false;
            return true;
        });
        console.log(`  ${usable.length} parameter-free GET routes to sweep`);

        await loginAs(USERNAME, PASSWORD);

        for (const r of usable) {
            const uri = r.uri.replace(/^\//, '');
            const slug = uri === '' ? '_root' : uri.replace(/[/?&=]/g, '__');
            try {
                await page.goto(`${BASE_URL}/${uri}`, {waitUntil: 'domcontentloaded', timeout: 20_000});
                await page.waitForLoadState('networkidle', {timeout: 10_000}).catch(() => {});
                await capPagination();
                await shot(`all-routes/${slug}`);
            } catch (e) {
                console.log(`  ! ${uri}: ${e.message.split('\n')[0]}`);
            }
        }
    }
}

console.log('');
console.log(`Done. ${shotCount} screenshots written to ${OUT}`);
await browser.close();
