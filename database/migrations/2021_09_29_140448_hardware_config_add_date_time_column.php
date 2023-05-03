<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HardwareConfigAddDateTimeColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_root')->table('hardware_config', function (Blueprint $table) {
            $table->dateTime('date_time')->nullable();
            $table->index('date_time');
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
            $table->dropIndex('date_time');
            $table->dropColumn('date_time');
        });
    }
}
