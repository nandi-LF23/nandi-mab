<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeHardwareManagementDeviceType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection('mysql_root')->statement("ALTER TABLE hardware_management MODIFY COLUMN device_type ENUM('Soil Moisture','Tank Level','Wells','Water Meter') NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('mysql_root')->statement("ALTER TABLE hardware_management MODIFY COLUMN device_type ENUM('Soil Moisture','Water Meter','Wells','Wells (V1)') NOT NULL");
    }
}
