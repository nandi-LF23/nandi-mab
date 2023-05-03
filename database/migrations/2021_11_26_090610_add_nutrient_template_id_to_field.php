<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNutrientTemplateIdToField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_root')->table('fields', function (Blueprint $table) {
            $table->unsignedBigInteger('nutrient_template_id')->nullable();
        });
        Schema::connection('mysql_root')->table('fields', function (Blueprint $table) {
            $table->foreign('nutrient_template_id')->references('id')->on('nutrient_templates')->onDelete('set null');
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
            $table->dropColumn('nutrient_template_id');
        });
    }
}
