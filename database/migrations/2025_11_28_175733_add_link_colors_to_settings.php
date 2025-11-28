<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $setting = DB::table('settings')->select(['skin'])->first();


        Schema::table('settings', function (Blueprint $table) {
            $table->string('link_dark_color')->after('header_color')->nullable()->default(null);
            $table->string('link_light_color')->after('header_color')->nullable()->default(null);
        });



        $link_dark_color = '#36aff5';
        $link_light_color = '#3c8dbc';

        if ($setting) {
            switch ($setting->skin) {
                case 'green':
                    $link_dark_color = '#00a65a';
                    $link_light_color = '#00a65a';
                case 'green-dark':
                    $link_dark_color = '#00a65a';
                    $link_light_color = '#00a65a';
                case 'red':
                    $link_dark_color = '#dd4b39';
                    $link_light_color = '#dd4b39';
                case 'red-dark':
                    $link_dark_color = '#dd4b39';
                    $link_light_color = '#dd4b39';
                case 'orange':
                    $link_dark_color = '#FF851B';
                    $link_light_color = '#FF851B';
                case 'orange-dark':
                    $link_dark_color = '#FF8C00';
                    $link_light_color = '#FF8C00';
                case 'black':
                    $link_dark_color = '#111';
                    $link_light_color = '#111';
                case 'black-dark':
                    $link_dark_color = '#111';
                    $link_light_color = '#111';
                case 'purple':
                    $link_dark_color = '#605ca8';
                    $link_light_color = '#605ca8';
                case 'purple-dark':
                    $link_dark_color = '#605ca8';
                    $link_light_color = '#605ca8';
                case 'yellow':
                    $link_dark_color = '#f39c12';
                    $link_light_color = '#f39c12';
                case 'yellow-dark':
                    $link_dark_color = '#f39c12';
                    $link_light_color = '#f39c12';
                case 'contrast':
                    $link_dark_color = '#86cbf2';
                    $link_light_color = '#084d73';
            }


            DB::table('settings')->update(['link_light_color' => $link_light_color, 'link_dark_color' => $link_dark_color]);
        }




    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function ($table) {
            $table->dropColumn('link_dark_color');
            $table->dropColumn('link_light_color');
        });
    }
};
