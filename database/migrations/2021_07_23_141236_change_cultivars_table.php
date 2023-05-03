<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCultivarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add company_id column to cultivars table
        Schema::connection('mysql_root')->table('cultivars', function (Blueprint $table) {

            // ADD ADDTIONAL company_id COLUMN
            $table->unsignedBigInteger('company_id');

            // RENAME PRIMARY KEY
            $table->renameColumn('idcultivars', 'id');

            // RENAME FAUX FOREIGN KEY
            $table->renameColumn('idmanagement', 'cultivars_management_id');
        });

        // Constraints
        Schema::connection('mysql_root')->table('cultivars', function (Blueprint $table) {

            // THEN CHANGE PRIMARY KEY TYPE
            $table->bigIncrements('id')->nullable(false)->change();

            // CHANGE FAUX FK TYPE
            $table->unsignedBigInteger('cultivars_management_id')->change();

            // ADD FOREIGN KEY CONSTRAINT TO cultivars_management table
            $table->foreign('cultivars_management_id')->references('id')->on('cultivars_management');

            // ADD FOREIGN KEY CONSTRAINT TO company_id
            $table->foreign('company_id')->references('id')->on('companies');

            DB::connection('mysql_root')->table('cultivars')->update(['company_id' => 1]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->table('cultivars', function (Blueprint $table) {

            // REMOVE FOREIGN KEY CONSTRAINT
            $table->dropForeign(['company_id']);
            // THEN DROP COLUMN
            $table->dropColumn('company_id');


            // REMOVE FOREIGN KEY CONSTRAINT
            $table->dropForeign(['cultivars_management_id']);

            // THEN RENAME PRIMARY KEY COLUMN
            $table->renameColumn('id', 'idcultivars');

            // THEN RENAME FAUX COLUMN
            $table->renameColumn('cultivars_management_id', 'idmanagement');

        });

        Schema::connection('mysql_root')->table('cultivars', function (Blueprint $table) {

            // CHANGE BACK PRIMAY KEY TYPE TO int(11)
            $table->integer('idcultivars', 11)->nullable(false)->default('NULL')->change();

            // CHANGE BACK FAUX KEY TYPE TO int(11)
            $table->integer('idmanagement', 11)->change();

        });
    }
}
