<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NutrientsDataAddDateReportedIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_root')->table('nutrients_data', function (Blueprint $table) {
            $table->index('date_reported');
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
            $table->dropIndex('date_reported');
        });
    }
}
