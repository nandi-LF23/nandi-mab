<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParserColumnToDataformats extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_root')->table('dataformats', function (Blueprint $table) {
            // Zone field
            $table->longtext('parser')->after('spec')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->table('dataformats', function (Blueprint $table) {
            $table->dropColumn('parser');
        });
    }
}
