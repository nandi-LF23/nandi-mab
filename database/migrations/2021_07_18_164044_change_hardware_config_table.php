<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeHardwareConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add company_id column to hardware_config table

        Schema::connection('mysql_root')->table('hardware_config', function (Blueprint $table) {
            $table->dropPrimary('node_address');
            $table->bigIncrements('id')->change();
            $table->unsignedBigInteger('company_id');
        });

        DB::connection('mysql_root')->table('hardware_config')->update(['company_id' => 1]);

        // Constraints
        Schema::connection('mysql_root')->table('hardware_config', function (Blueprint $table) {
            $table->primary('id');
            $table->unique('node_address');
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
        Schema::connection('mysql_root')->table('hardware_config', function (Blueprint $table) {
            $table->dropPrimary('id');
            $table->primary('node_address');
            $table->dropUnique('hardware_config_node_address_unique');
            $table->integer('id')->change();
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
}
