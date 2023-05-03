<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCompanyIdForeignKeyConstraints extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1 cultivars - Recreate FK Constraint
        Schema::connection('mysql_root')->table('cultivars', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 2 cultivars_management - Recreate FK Constraint
        Schema::connection('mysql_root')->table('cultivars_management', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 3 cultivars_templates - Recreate FK Constraint
        Schema::connection('mysql_root')->table('cultivars_templates', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 4 distributors_companies - Recreate FK Constraint
        Schema::connection('mysql_root')->table('distributors_companies', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 5 fields - Recreate FK Constraint
        Schema::connection('mysql_root')->table('fields', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 6 groups - Recreate FK Constraint
        Schema::connection('mysql_root')->table('groups', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 7 hardware_config - Recreate FK Constraint
        Schema::connection('mysql_root')->table('hardware_config', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 8 hardware_management - Recreate FK Constraint
        Schema::connection('mysql_root')->table('hardware_management', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 9 nutrient_templates - Recreate FK Constraint
        Schema::connection('mysql_root')->table('nutrient_templates', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 10 roles - Recreate FK Constraint
        Schema::connection('mysql_root')->table('roles', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 11 security_rules - Recreate FK Constraint
        Schema::connection('mysql_root')->table('security_rules', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 12 security_rules_companies - Recreate FK Constraint
        Schema::connection('mysql_root')->table('security_rules_companies', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 13 security_templates - Recreate FK Constraint
        Schema::connection('mysql_root')->table('security_templates', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // 14 users - Recreate FK Constraint
        Schema::connection('mysql_root')->table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
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
        //
    }
}
