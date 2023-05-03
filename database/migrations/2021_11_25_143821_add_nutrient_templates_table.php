<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNutrientTemplatesTable extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // add nutrient_templates table
        Schema::connection('mysql_root')->create('nutrient_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128);
            $table->longtext('template');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        // Constraints
        Schema::connection('mysql_root')->table('nutrient_templates', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
            $table->unique(['name', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->dropIfExists('nutrient_templates');
    }
}
