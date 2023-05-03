<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAmbientTempFieldToNutrientsData extends Migration
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
            $table->float('ambient_temp')->nullable()->default(0);
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
            $table->dropColumn('ambient_temp');
        });
    }
}
