<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddZoneFieldToHardwareConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_root')->table('hardware_config', function (Blueprint $table) {
            // Zone field
            $table->tinyText('zone')->after('commissioning_date')->nullable();
        });

        // Constraints + Indexes
        Schema::connection('mysql_root')->table('hardware_config', function (Blueprint $table) {
            $table->index([DB::raw('zone(255)')]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->table('hardware_config', function (Blueprint $table) {
            $table->dropIndex(['zone']);
            $table->dropColumn('zone');
        });
    }
}
