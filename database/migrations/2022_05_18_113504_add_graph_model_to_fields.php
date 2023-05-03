<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGraphModelToFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_root')->table('fields', function (Blueprint $table) {
            $table->string('graph_model', 32)->nullable()->default('ave');
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
            $table->dropColumn('graph_model');
        });
    }
}
