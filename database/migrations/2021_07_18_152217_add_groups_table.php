<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add Groups table
        Schema::connection('mysql_root')->create('groups', function (Blueprint $table) {
            $table->id();
            $table->text('group_name');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('subsystem_id');
        });

        // Constraints
        Schema::connection('mysql_root')->table('groups', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('subsystem_id')->references('id')->on('subsystems');
        });

        // ensure unique group names per subsystem per company
        DB::connection('mysql_root')->statement('ALTER TABLE groups ADD CONSTRAINT unique_subsystem_cc_groups UNIQUE KEY (company_id, subsystem_id, group_name(50))');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->dropIfExists('groups');
    }
}
