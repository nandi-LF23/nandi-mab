<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDataformatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Remove Node Type Column (Moved into Spec)
        Schema::connection('mysql_root')->table('dataformats', function (Blueprint $table) {
            $table->dropColumn('node_type');
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
            $table->string('node_type', 64);  // Node Type
        });
    }
}
