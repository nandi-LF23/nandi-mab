<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class NodeStartupInfoAddFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_root')->table('node_startup_info', function (Blueprint $table) {
            $table->text('uniq_val')->nullable()->default(null);
            $table->text('node_firmware')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->table('node_startup_info', function (Blueprint $table) {
            $table->text('uniq_val')->nullable()->default(null);
            $table->text('node_firmware')->nullable()->default(null);
        });
    }
}
