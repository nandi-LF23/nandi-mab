<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPerimeterFieldToFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // add perimeter long text field for housing JSON data (GeoJSON)
        Schema::connection('mysql_root')->table('fields', function (Blueprint $table) {
            $table->longText('perimeter')->nullable();
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
            $table->dropColumn('perimeter');
        });
    }
}
