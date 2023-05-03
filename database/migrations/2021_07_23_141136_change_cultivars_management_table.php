<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCultivarsManagementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add company_id column to cultivars_management table
        Schema::connection('mysql_root')->table('cultivars_management', function (Blueprint $table) {

            //ADD ADDTIONAL company_id COLUMN
            $table->unsignedBigInteger('company_id');

            //RENAME PRIMARY KEY
            $table->renameColumn('idmanagement', 'id');

            //CHANGE FAUX fields_id TYPE
            $table->unsignedBigInteger('field_id')->change();

        });

        DB::connection('mysql_root')->table('cultivars_management')->update(['company_id' => 1]);

        // Constraints
        Schema::connection('mysql_root')->table('cultivars_management', function (Blueprint $table) {

            // THEN CHANGE PRIMARY KEY TYPE
            $table->bigIncrements('id')->nullable(false)->change();

            // ADD FOREIGN KEY CONSTRAINT TO fields (CANT - DIRTY DATA WILL CAUSE IT TO FAIL)
            // $table->foreign('field_id')->references('id')->on('fields');

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
        Schema::connection('mysql_root')->table('cultivars_management', function (Blueprint $table) {

            // REMOVE FOREIGN KEY CONSTRAINT
            $table->dropForeign(['company_id']);

            // THEN DROP COLUMN
            $table->dropColumn('company_id');

            // REMOVE FOREIGN KEY CONSTRAINT
            // $table->dropForeign(['field_id']);

            // CHANGE BACK PRIMAY KEY TYPE TO int(11)
            $table->integer('id', 11)->nullable(false)->default('NULL')->change();

        });

        Schema::connection('mysql_root')->table('cultivars_management', function (Blueprint $table) {

            // CHANGE BACK field_id KEY TYPE TO int(11)
            $table->integer('field_id', 11)->change();

            // THEN RENAME PRIMARY KEY COLUMN
            $table->renameColumn('id', 'idmanagement');
        });
    }
}
