<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBpFieldToNutrientsDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add bp (Battery Percentage)
        Schema::connection('mysql_root')->table('nutrients_data', function (Blueprint $table) {
            $table->float('bp')->after('bv')->nullable()->default(0);
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
            $table->dropColumn('bp');
        });
    }
}
