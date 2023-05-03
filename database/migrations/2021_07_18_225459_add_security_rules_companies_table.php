<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSecurityRulesCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // add security_rules_companies junction table

        Schema::connection('mysql_root')->create('security_rules_companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('security_rule_id');
            $table->unsignedBigInteger('company_id');

        });

        Schema::connection('mysql_root')->table('security_rules_companies', function (Blueprint $table) {
            $table->foreign('security_rule_id')->references('id')->on('security_rules')->onDelete('cascade');
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
        Schema::connection('mysql_root')->table('security_rules_companies', function (Blueprint $table) {
            Schema::connection('mysql_root')->dropIfExists('security_rules_companies');
        });
    }
}


