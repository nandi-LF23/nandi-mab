<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLockCoordsField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_root')->table('hardware_config', function (Blueprint $table) {
            // Coordinates Locked
            $table->tinyInteger('coords_locked')->after('lng')->nullable();
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
            $table->dropColumn('coords_locked');
        });
    }
}
