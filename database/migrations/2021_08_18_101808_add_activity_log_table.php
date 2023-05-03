<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActivityLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Define Activity Log Table + Columns
        Schema::connection('mysql_root')->create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->text('user_name');
            $table->unsignedBigInteger('operation_id');
            $table->unsignedBigInteger('subsystem_id');
            $table->text('details');
            $table->text('company_name');
            $table->dateTime('occurred');
        });

        // Constraints + Indexes
        Schema::connection('mysql_root')->table('activity_log', function (Blueprint $table) {
            $table->foreign('operation_id')->references('id')->on('permissions');
            $table->foreign('subsystem_id')->references('id')->on('subsystems');
            $table->index('user_name');
            $table->index('company_name');
            $table->index('occurred');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->drop('activity_log');
    }
}
