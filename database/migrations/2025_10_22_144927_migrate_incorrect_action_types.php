<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        /**
         * So the concern here is that the following statement _could_ take a long time - the action_logs table is not indexed
         * against the action_type column (and shouldn't be) so this could take a few beats. But, still, we're not talking about
         * a particularly wide table or anything; we've certainly heard about a couple of times where people had a few million
         * action_logs but, again, not too many more than that.
         *
         * But @snipe has mentioned multiple times that in some older migrations, trying to run an UPDATE in batch, there were
         * memory issues.
         *
         * I've investigated and it looks like we've rarely or never done a 'batch update' the way we do below. I'm pretty sure
         * it will be fine (famous last words...)
         * */

        DB::table('action_logs')->where('action_type', 'request_canceled')->update(['action_type' => 'request canceled']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // no down migration for this one
    }
};
