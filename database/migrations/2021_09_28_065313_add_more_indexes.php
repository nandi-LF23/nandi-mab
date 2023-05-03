<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMoreIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        return;
        Schema::connection('mysql_root')->table('cultivars_management', function (Blueprint $table) {
            $table->index('field_id');
        });
        Schema::connection('mysql_root')->table('cultivars', function (Blueprint $table) {
            $table->index('stage_start_date');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->table('cultivars', function (Blueprint $table) {
            $table->dropIndex('stage_start_date');
        });        
        Schema::connection('mysql_root')->table('cultivars_management', function (Blueprint $table) {
            $table->dropIndex('field_id');
        });
    }
}
