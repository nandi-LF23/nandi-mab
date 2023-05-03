<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSecurityTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // add security_templates table
        Schema::connection('mysql_root')->create('security_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_name', 75)->unique();
            $table->longtext('template_data');
            $table->unsignedBigInteger('company_id');
            $table->timestamps();
        });

        // Constraints
        Schema::connection('mysql_root')->table('security_templates', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->dropIfExists('security_templates');
    }
}


