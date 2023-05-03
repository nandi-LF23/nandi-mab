<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBvLattLngToNutrientsData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add bV (Battery Voltage, Latt (Latitude) and Lng (Longitude) Fields to nutrients_data table
        Schema::connection('mysql_root')->table('nutrients_data', function (Blueprint $table) {
            $table->float('bv')->nullable()->default(0);
            $table->float('latt')->nullable()->default(0);
            $table->float('lng')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->table('nutrients_data', function (Blueprint $table) {
            $table->dropColumn('bv');
            $table->dropColumn('latt');
            $table->dropColumn('lng');
        });
    }
}
