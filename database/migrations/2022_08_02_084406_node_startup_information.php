<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NodeStartupInformation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_root')->create('node_startup_information', function (Blueprint $table) {
            $table->id();
            $table->text('boot_msg'); //01 03 or 01 04
            $table->text('node_id'); //eg 0x000d129c
            $table->date('date_time'); // 02-08-2022 00:33:18
            $table->text('batt_voltage'); // eg 3476
            $table->text('sdi_address'); // eg 0 or 1
            $table->text('probe_type'); //e.g AquaChckACCSDI
            $table->text('probe_firmware'); //032 or 32
            $table->text('probe_serial'); //0000056696 or 56696
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->drop('node_startup_information');
    }
}
