<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeHardwareConfigNodeType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection('mysql_root')->statement("ALTER TABLE hardware_config MODIFY COLUMN node_type ENUM('Soil Moisture','Wells','Water Meter','Tank Level') NOT NULL");
        // Run update to new enum value afterwards
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('mysql_root')->statement("ALTER TABLE hardware_config MODIFY COLUMN node_type ENUM('Soil Moisture','Wells','Wells (V1)') NOT NULL");
        // Run update to old enum value afterwards
    }
}
