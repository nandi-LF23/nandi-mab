<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Drop Spatie Tables

        // Disable foreign key checks!
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::connection('mysql_root')->dropIfExists('role_has_permissions');
        Schema::connection('mysql_root')->dropIfExists('model_has_permissions');
        Schema::connection('mysql_root')->dropIfExists('model_has_roles');
        Schema::connection('mysql_root')->dropIfExists('permissions');
        Schema::connection('mysql_root')->dropIfExists('roles');

        // Enable foreign key checks!
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Add Roles table
        Schema::connection('mysql_root')->create('roles', function (Blueprint $table) {
            $table->id();
            $table->text('role_name');
            $table->unsignedBigInteger('company_id');
        });

        // Constraints
        Schema::connection('mysql_root')->table('roles', function (Blueprint $table) {
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
        Schema::connection('mysql_root')->dropIfExists('roles');
    }
}
