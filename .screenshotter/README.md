# Screenshotter

A Playwright-driven walkthrough of the Snipe-IT UI that produces PNG screenshots for docs, marketing, or reference use. The script logs in, navigates through canonical pages and interactions, and writes screenshots to an out-of-repo directory (`.screenshotter/screenshots/` by default, gitignored). What you do with the resulting PNGs is up to you.

## Requirements

- Node 18+ (uses `node:util.parseArgs`).
- Playwright and Chromium. Both are already installed as dev dependencies for this repo, so `npm install` is enough to have them available.
- A running Snipe-IT install to point at. Herd or `php artisan serve` both work. Default target is `https://snipe-it.test`.
- Credentials for a superuser on that install. Default is `admin` / `password`, which is what the demo seeder creates.

## Data caution (read this)

The script captures whatever is live in the connected database, in full. **DO NOT** run it against a production install, a staging environment that mirrors production, or any database that contains real customer data, real user PII, uploaded avatars or documents, license keys, IP addresses, employee numbers, or anything else you would not personally publish in a public forum.

Once a screenshot exists on your disk it is one drag-and-drop away from GitHub, Discord, Reddit, a support ticket, a hosted docs site, a Slack message, a bug report attachment, or any other place where you might casually share a screenshot to explain something. Screenshots pulled from a real install have been the source of real data leaks in real projects. Do not become the next one. Stick to demo-seeded installs unless you have personally reviewed the data on that install.

## Usage

Point it at any Snipe-IT install and it will screenshot whatever is there. In practice you want a freshly seeded demo install (see the caution above) so the shots are reproducible and safe to publish.

```bash
# Recommended: reseed first so the shots reflect canonical demo data
php artisan migrate:fresh --seed
npm run screenshotter

# Also fine, if you know what is in your local DB and it is safe to capture
npm run screenshotter
```

Environment overrides for all the knobs:

```bash
BASE_URL=https://staging.example.com \
USERNAME=snipe \
PASSWORD=secret \
OUT=/tmp/snipe-shots \
HEADLESS=false \
VIEWPORT_WIDTH=1920 VIEWPORT_HEIGHT=1080 \
npm run screenshotter
```

`HEADLESS=false` runs the browser visibly so you can watch the walkthrough, which is useful when adding new blocks and debugging selectors.

Full runs wipe the walkthrough shots at the start of every run so stale images never mix with fresh ones. Only `.screenshotter/README.md`, `.screenshotter/src/screenshotter.mjs`, and the `.screenshotter/screenshots/adhoc/` subdirectory (see ad-hoc mode below) are preserved.

## Side effects on the database

The walkthrough posts each edit form after screenshotting it (to capture the post-save UI, whether that is a success callout or a validation-error state) so a full run writes back to the connected database. In practice this means:

- Every first-class object gets one no-op update per viewer, which appends an `action_logs` row per submission.
- Any observer/notification/webhook wired to an update event fires as if a real edit happened. On a demo install this is usually fine, but if the install has outbound webhooks pointed at a real endpoint (Slack, an internal service, etc.) those fire too.
- No data is intentionally changed (the forms are submitted with the values already on the page), but "unchanged" is not the same as "no side effects."

For a demo-seeded local install this is expected and fine. For anything else, do not run the full walkthrough (see the data caution above), or disable the submit step:

```bash
# Skip the write-back and the `-edit-submitted` shots
SUBMIT_FORMS=false npm run screenshotter
```

With `SUBMIT_FORMS=false` the walkthrough is read-only: no form posts, no `action_logs` entries, no observer/webhook fires. Trade-off is you lose the post-save UI captures.

## Narrowing to specific sections

Use `--section <name>` to regenerate just one or more sections instead of the whole walkthrough. Section names match the directory under `screenshots/`, and the section filter also selects which resource-managers run (managers whose section isn't in the filter are skipped).

```bash
# Just the assets section (admin + assetmgr)
npm run screenshotter -- --section assets

# Comma-separated for multiple
npm run screenshotter -- --section settings,reports,dashboard

# Standalone sections work too
npm run screenshotter -- --section dashboard
```

Sections available: `assets`, `licenses`, `accessories`, `consumables`, `components`, `users`, `models`, `categories`, `manufacturers`, `suppliers`, `locations`, `departments`, `kits`, `companies`, `statuslabels`, `depreciations`, `custom-fields`, `fieldsets`, `maintenance-types`, `dashboard`, `settings`, `reports`.

When `--section` is set, other sections' shots from prior runs are preserved (not wiped), and the `all-routes` sweep is skipped since it's not tied to any section.

## Walking view-page tabs

Off by default. Set `TABS=true` to include a shot of every Bootstrap tab pane on view pages (asset view alone has ~10 tabs: Licenses, Components, Maintenances, Audits, Notes, Files, and so on). Shot names are `{section}/{user}-view-tab-{slug}`.

```bash
# Include all tabs
TABS=true npm run screenshotter

# Just assets, with tabs
TABS=true npm run screenshotter -- --section assets
```

Skipped silently on pages without any tabs.

## Browser-chrome framing

Every generated screenshot is wrapped in a styled browser chrome by default (rounded corners, gray titlebar with three traffic-light dots, soft ambient drop shadow radiating on all four sides). Set `FRAME=false` to skip the frame post-processing and get raw viewport shots instead.

```bash
# Framed (default)
npm run screenshotter

# Raw shots, no frame
FRAME=false npm run screenshotter
```

Framing is done entirely locally via an inline HTML template plus a Playwright screenshot of the composed result. No external services are called, no image content leaves your machine.

## Ad-hoc single-shot mode

Skip the full walkthrough and capture just one URL as a specific user. Useful for regenerating one stale image without re-running the whole ~5-minute sweep, or grabbing an off-catalog page for a one-off.

```bash
# Just a URL, using the default USERNAME (admin)
node .screenshotter/src/screenshotter.mjs --one /hardware

# As a specific role
node .screenshotter/src/screenshotter.mjs --one /hardware/create --as assetmgr

# Framed, with a custom output name
FRAME=true node .screenshotter/src/screenshotter.mjs --one /licenses/5 --as licensemgr --name license-detail
```

Ad-hoc shots land in `.screenshotter/screenshots/adhoc/{username}-{name}-{timestamp}.png`. The `adhoc/` directory is deliberately preserved across full walkthrough runs so historical one-off images stick around, and every ad-hoc shot carries a timestamp so repeated captures of the same URL never overwrite each other.

Arguments:

- `--one <path>` (required) URL path to shoot, with or without a leading slash.
- `--as <username>` (default: `USERNAME` env, which defaults to `admin`) user to log in as. Any seeded user works: `admin`, `snipe`, `assetmgr`, `licensemgr`, `accessorymgr`, `consumablemgr`, `componentmgr`, `usermgr`, etc.
- `--name <slug>` (default: URL path with `/` replaced by `__`) filename slug. The timestamp is appended automatically.

## What a full run produces

Every generated PNG follows the naming convention `{section}/{username}-{page}-{timestamp}.png` so alphabetical sort groups shots by section, then by role, then by run. Example section directory contents after a run as `admin` plus the resource managers:

```
.screenshotter/screenshots/assets/
├── admin-index-2026-07-21-113043.png
├── admin-view-2026-07-21-113043.png
├── admin-edit-2026-07-21-113043.png
├── admin-create-2026-07-21-113043.png
├── admin-create-status-dropdown-2026-07-21-113043.png
├── assetmgr-index-2026-07-21-113043.png
├── assetmgr-view-2026-07-21-113043.png
├── assetmgr-edit-2026-07-21-113043.png
└── assetmgr-create-2026-07-21-113043.png
```

Sections covered by the default walkthrough: assets, licenses, accessories, consumables, components, users, models, categories, manufacturers, suppliers, locations, departments, kits, companies, statuslabels, depreciations, dashboard, settings, reports. Plus the resource-manager perspectives (assetmgr, licensemgr, accessorymgr, consumablemgr, componentmgr, usermgr) scoped to just what each manager can access. Plus a superuser sweep of every parameter-free GET route in the app, filed under `all-routes/`, for visual gut-check purposes (set `ALL_ROUTES=false` to skip that pass).

## How to add a new screenshot

Each block in the script is intentionally explicit. Adding a new page or interaction means adding a small block that navigates, waits, and calls the `shot(name)` helper.

```js
await page.goto(`${BASE_URL}/consumables`);
await waitForTable();
await shot(`consumables/${USERNAME}-index`);
```

For interaction shots (dropdown open, modal open, mid-flow state), click the trigger, wait for the target element to appear, then screenshot. The script uses `page.waitForLoadState('networkidle')` inside the `shot()` helper to defuse AdminLTE's async rendering, and a `waitForTable()` helper waits for bootstrap-table's loading overlay to clear before shooting a table page.

If your new block covers a first-class object (list plus detail plus edit page), you can also just add an entry to the `firstClassObjects` config array near the top of the walkthrough section and the loop will produce the three shots for free.

## Shooting the same page as different users

The script has an `asUser(username, fn)` block helper that clears cookies, logs in as the named user, runs the callback, then any shots inside the block are captured under that session.

```js
await asUser('viewer', async () => {
    await page.goto(`${BASE_URL}/hardware`);
    await waitForTable();
    await shot(`assets/viewer-index`);
});
```

Users referenced in these blocks must exist in the seeded database. The demo seeders ship a small set of named users at varying permission levels (see the "resource-manager perspectives" section above), and the alternate-account username needs to match one of them (or one you add).

## Workflow expectation

When a PR adds or meaningfully changes a user-visible screen, modal, form, dropdown, or interaction, add or update the corresponding block in `.screenshotter/src/screenshotter.mjs` in the same PR. If a downstream doc references a specific screenshot filename and the script's block for it is removed, the doc visibly breaks the next time it is regenerated, which is the intended feedback loop.

## Implementation notes

The script uses the `.mjs` extension rather than `.js` so it always runs as an ES module regardless of what the root `package.json` says. The alternative was to add `"type": "module"` to `package.json`, which would flip every other `.js` file in the repo to ESM at the same time and is a much larger change than this script warrants.
