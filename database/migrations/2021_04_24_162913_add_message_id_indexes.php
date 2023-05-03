<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMessageIdIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_root')->table('node_data', function (Blueprint $table) {
            $table->index('message_id_1');
        });
        Schema::connection('mysql_root')->table('node_data_meters', function (Blueprint $table) {
            $table->index('message_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->table('node_data', function (Blueprint $table) {
            // <table>_<indexname>_index
            $table->dropIndex('node_data_message_id_1_index');
        });
        Schema::connection('mysql_root')->table('node_data_meters', function (Blueprint $table) {
            $table->dropIndex('node_data_meters_message_id_index');
        });
    }
}
