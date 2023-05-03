<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWeatherstationFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_root')->table('fields', function (Blueprint $table) {
            $table->text('wl_station_name')->nullable();
            $table->text('wl_product_number')->nullable();
            $table->longtext('wl_station_data')->nullable();
            $table->longtext('wl_sensor_data')->nullable();
            $table->dateTime('wl_last_updated')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->table('fields', function (Blueprint $table) {
            $table->dropColumn('wl_station_name');
            $table->dropColumn('wl_product_number');
            $table->dropColumn('wl_station_data');
            $table->dropColumn('wl_sensor_data');
            $table->dropColumn('wl_last_updated');
        });
    }
}
