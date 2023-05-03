<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsLockedFieldToCompanies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // add is_locked column (For locking Entity)
        Schema::connection('mysql_root')->table('companies', function (Blueprint $table) {
            $table->tinyInteger('is_locked')->nullable(false)->default('0');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->table('companies', function (Blueprint $table) {
            $table->dropColumn('is_locked');
        });
    }
}
