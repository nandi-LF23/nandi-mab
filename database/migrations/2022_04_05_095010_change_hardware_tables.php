<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeHardwareTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_root')->table('hardware_config', function (Blueprint $table) {
            // Rename
            $table->renameColumn('probe_make',   'hardware_management_id'); // better foreign key naming
            $table->renameColumn('meter_make',   'node_make'); // better name - Currently used to store the Node's Manufacturer name anyway
            $table->renameColumn('integrations', 'integration_opts'); // better name
        });

        Schema::connection('mysql_root')->table('hardware_config', function (Blueprint $table) {
            // Remove (To be added to hardware_management table)
            $table->dropColumn('pipe_diameter');     // recreate as diameter
            $table->dropColumn('pulse_weight');
            $table->dropColumn('measurement_type');
            $table->dropColumn('application_type');
            $table->dropColumn('user_search');
            $table->dropColumn('email');
        });

        Schema::connection('mysql_root')->table('hardware_management', function (Blueprint $table) {
            // Remove
            $table->dropColumn('device_make_sm');
            $table->dropColumn('device_make_wm');
            $table->dropColumn('device_type_wm');
            $table->dropColumn('diameter');
        });

        Schema::connection('mysql_root')->table('hardware_management', function (Blueprint $table) {
            // Add
            $table->float('diameter');
            $table->float('pulse_weight');
            $table->string('measurement_type', 32)->nullable();
            $table->string('application_type', 64)->nullable();

            // can house 'Mechanical','MagFlo','Ultrasonic' + Other Categories (more generic)
            // (replaces device_type_wm)
            $table->string('device_category',  64)->nullable(); 
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
