<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsDistributorFieldToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // add is_distributor column (For Distributors)
        Schema::connection('mysql_root')->table('users', function (Blueprint $table) {
            $table->boolean('is_distributor')->nullable(false)->default('0')->after('is_admin');
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
            $table->dropColumn('is_distributor');
        });
    }
}
