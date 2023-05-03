<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_root')->table('users', function (Blueprint $table) {
            // is_active field
            $table->boolean('is_active')->nullable(false)->default('1')->after('is_distributor');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
}
