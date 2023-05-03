<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBattLevel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */    
    public function up()
    {
        Schema::connection('mysql_root')->table('node_data', function (Blueprint $table) {
            $table->float('bp')->after('bv');
        });
        Schema::connection('mysql_root')->table('node_data_meters', function (Blueprint $table) {
            $table->float('bp')->after('batt_volt');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->table('node_data', function (Blueprint $table) {
            $table->dropColumn('bp');
        });
        Schema::connection('mysql_root')->table('node_data_meters', function (Blueprint $table) {
            $table->dropColumn('bp');
        });
    }
}
