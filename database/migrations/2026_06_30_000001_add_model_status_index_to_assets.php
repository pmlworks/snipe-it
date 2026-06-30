<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composite index covering the two hot query shapes on `assets` that drive
 * the category detail view and the per-model availability counts:
 *
 *   SELECT count(*) FROM assets
 *     INNER JOIN models ON models.id = assets.model_id
 *    WHERE models.category_id = ?
 *      AND assets.status_id IN (...)
 *      AND assets.deleted_at IS NULL
 *      AND models.deleted_at IS NULL;
 *
 *   SELECT count(*) FROM assets
 *    WHERE assets.model_id = ?
 *      AND assets.assigned_to IS NULL
 *      AND assets.status_id IN (...)
 *      AND assets.deleted_at IS NULL;
 *
 * Today MySQL uses the existing (deleted_at, model_id) index, finds the
 * candidate rows for a single model, then post-filters by status_id —
 * which on a customer with thousands of assets in a single model means
 * reading thousands of rows just to discard most of them.
 *
 * Leading with model_id (high-selectivity equality), then deleted_at
 * (equality null), then status_id (IN range) lets the optimizer satisfy
 * the whole filter from the index seek. The existing (deleted_at,
 * model_id) and (deleted_at, status_id) indexes stay in place because
 * other call sites (raw `where deleted_at = ? and status_id = ?` lookups
 * without a model_id) still benefit from them.
 *
 * Tradeoff: one extra btree per asset write. Snipe-IT inserts are bursty
 * (manual edit / import) rather than sustained — the read win at the
 * category page is far larger than the per-write cost.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->index(['model_id', 'deleted_at', 'status_id'], 'assets_model_deleted_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex('assets_model_deleted_status_index');
        });
    }
};
