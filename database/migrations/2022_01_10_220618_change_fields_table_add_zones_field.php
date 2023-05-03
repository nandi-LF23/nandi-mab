<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeFieldsTableAddZonesField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // add zones long text field for housing JSON data (GeoJSON Field Zones)
        Schema::connection('mysql_root')->table('fields', function (Blueprint $table) {
            $table->longText('zones')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->table('fields', function (Blueprint $table) {
            $table->dropColumn('zones');
        });
    }
}
