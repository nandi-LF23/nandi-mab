<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add company_id column to fields table
        Schema::connection('mysql_root')->table('fields', function (Blueprint $table) {

            // ADD ADDTIONAL company_id COLUMN
            $table->unsignedBigInteger('company_id');

            // RENAME FAUX PRIMARY KEY
            $table->renameColumn('idfields', 'id');

            // REMOVE PRIMARY KEY CONSTRAINT FROM node_id COLUMN
            $table->dropPrimary('node_id');

        });

        DB::connection('mysql_root')->table('fields')->update(['company_id' => 1]);

        // Constraints
        Schema::connection('mysql_root')->table('fields', function (Blueprint $table) {

            // THEN CHANGE PRIMARY KEY TYPE
            $table->bigIncrements('id')->nullable(false)->default('NULL')->change();

            // CONVERT id TO ACTUAL PRIMARY KEY
            $table->primary('id');

            // ADD FOREIGN KEY CONSTRAINT TO company_id
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
        Schema::connection('mysql_root')->table('fields', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');

            // REMOVE PRIMARY KEY CONSTRAINT FROM id COLUMN
            $table->dropPrimary('id');

            // THEN RENAME PRIMARY KEY COLUMN
            $table->renameColumn('id', 'idfields');
        });

        Schema::connection('mysql_root')->table('fields', function (Blueprint $table) {

            // CHANGE BACK FAUX KEY TYPE TO int(2)
            $table->integer('idfields', 2)->nullable(false)->default('NULL')->change();

            // READD PRIMARY KEY CONSTRAINT TO node_id COLUMN
            $table->primary('node_id');

        });
    }
}
