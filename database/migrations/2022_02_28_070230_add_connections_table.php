<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConnectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_root')->create('connections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 128);                           // General Connection Description
            $table->string('type', 64);                            // MQTT
            $table->string('status', 64)->default('Disconnected'); // Connected/Disconnected
            $table->integer('pid')->nullable();                    // Worker Process ID
            $table->datetime('started')->nullable();               // Uptime (Time Connected)
            $table->longtext('config');                            // Config Parameters
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->dropIfExists('connections');
    }
}
