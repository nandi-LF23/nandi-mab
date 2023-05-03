<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeTypeEnumsToVarchar extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // hardware_config
        DB::connection('mysql_root')->statement("ALTER TABLE hardware_config MODIFY COLUMN node_type VARCHAR(128) NOT NULL");
        Schema::connection('mysql_root')->table('hardware_config', function (Blueprint $table) {
            $table->index('node_type');
        });

        // hardware_management
        DB::connection('mysql_root')->statement("ALTER TABLE hardware_management MODIFY COLUMN device_type VARCHAR(128) NOT NULL");
        DB::connection('mysql_root')->statement("ALTER TABLE hardware_management MODIFY COLUMN device_length VARCHAR(128) NOT NULL");
        Schema::connection('mysql_root')->table('hardware_management', function (Blueprint $table) {
            $table->index('device_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // hardware_config
        DB::connection('mysql_root')->statement(
            "ALTER TABLE hardware_config MODIFY COLUMN node_type ENUM('Soil Moisture','Tank Level','Wells','Water Meter') NOT NULL"
        );
        Schema::connection('mysql_root')->table('hardware_config', function (Blueprint $table) {
            $table->dropIndex(['node_type']);
        });

        // hardware_management
        DB::connection('mysql_root')->statement(
            "ALTER TABLE hardware_management MODIFY COLUMN device_type ENUM('Soil Moisture','Tank Level','Wells','Water Meter') NOT NULL"
        );
        DB::connection('mysql_root')->statement(
            "ALTER TABLE hardware_management MODIFY COLUMN device_length ENUM('200mm','300mm','400mm','500mm','600mm','700mm','800mm','900mm','1000mm','1100mm','1200mm','1300mm','1400mm','1500mm')"
        );
        Schema::connection('mysql_root')->table('hardware_management', function (Blueprint $table) {
            $table->dropIndex(['device_type']);
        });
    }
}
