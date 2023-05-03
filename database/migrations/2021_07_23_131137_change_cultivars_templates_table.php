<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCultivarsTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add company_id column to hardware_management table

        Schema::connection('mysql_root')->table('cultivars_templates', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id');
        });

        DB::connection('mysql_root')->table('cultivars_templates')->update(['company_id' => 1]);

        // Constraints
        Schema::connection('mysql_root')->table('cultivars_templates', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->table('cultivars_templates', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
}
