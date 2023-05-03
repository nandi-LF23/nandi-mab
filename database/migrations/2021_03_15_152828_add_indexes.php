<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_root')->table('node_data', function (Blueprint $table) {
            $table->index('probe_id');
            $table->index('date_time');
        });

        Schema::connection('mysql_root')->table('node_data_meters', function (Blueprint $table) {
            $table->index('node_id');
            $table->index('date_time');
        });

        Schema::connection('mysql_root')->table('fields', function (Blueprint $table) {
            $table->index('node_id');
        });

        Schema::connection('mysql_root')->table('hardware_config', function (Blueprint $table) {
            $table->index('node_address');
        });

        Schema::connection('mysql_root')->table('cultivars', function (Blueprint $table) {
            $table->index('idmanagement');
        });

        Schema::connection('mysql_root')->table('cultivars_management', function (Blueprint $table) {
            $table->index('field_id');
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
            $table->dropIndex('probe_id');
            $table->dropIndex('date_time');
        });

        Schema::connection('mysql_root')->table('node_data_meters', function (Blueprint $table) {
            $table->dropIndex('node_id');
            $table->dropIndex('date_time');
        });

        Schema::connection('mysql_root')->table('fields', function (Blueprint $table) {
            $table->dropIndex('node_id');
        });

        Schema::connection('mysql_root')->table('hardware_config', function (Blueprint $table) {
            $table->dropIndex('node_address');
        });

        Schema::connection('mysql_root')->table('cultivars', function (Blueprint $table) {
            $table->dropIndex('idmanagement');
        });
        
        Schema::connection('mysql_root')->table('cultivars_management', function (Blueprint $table) {
            $table->dropIndex('field_id');
        });
    }
}
