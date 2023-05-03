<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSecurityRulesGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // add security_rules_groups junction table

        Schema::connection('mysql_root')->create('security_rules_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('security_rule_id');
            $table->unsignedBigInteger('group_id');

        });

        Schema::connection('mysql_root')->table('security_rules_groups', function (Blueprint $table) {
            $table->foreign('security_rule_id')->references('id')->on('security_rules')->onDelete('cascade');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->table('security_rules_groups', function (Blueprint $table) {
            Schema::connection('mysql_root')->dropIfExists('security_rules_groups');
        });
    }
}


