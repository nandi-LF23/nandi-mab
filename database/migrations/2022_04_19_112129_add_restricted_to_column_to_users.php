<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRestrictedToColumnToUsers extends Migration
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
            $table->text('restricted_to')->nullable()->after('is_active');
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
            $table->dropColumn('restricted_to');
        });
    }
}
