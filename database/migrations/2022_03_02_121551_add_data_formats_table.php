<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDataFormatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add dataformats table (Used with Connections to Parse Incoming Data via Dynamically Created Spec)
        Schema::connection('mysql_root')->create('dataformats', function (Blueprint $table) {
            $table->id();
            $table->string('name', 128); // General Format Description
            $table->string('format', 64);  // Binary/JSON
            $table->string('node_type', 64);  // Node Type
            $table->longtext('spec');    // Specification Metadata
        });

        // Add FK reference column to connections
        Schema::connection('mysql_root')->table('connections', function (Blueprint $table) {
            $table->unsignedBigInteger('dataformat_id')->nullable();
        });

        // Add FK constraint from connections to dataformats
        Schema::connection('mysql_root')->table('connections', function (Blueprint $table) {
            $table->foreign('dataformat_id')->nullable()->references('id')->on('dataformats');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->table('connections', function (Blueprint $table) {
            $table->dropForeign(['dataformat_id']);
        });

        Schema::connection('mysql_root')->dropIfExists('dataformats');
    }
}
