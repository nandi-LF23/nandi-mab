<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GetMessageidForB64Data extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_root')->table('raw_data_b64', function (Blueprint $table) {
            $table->text('message_id')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     * 
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->table('raw_data_b64', function (Blueprint $table) {
            $table->text('message_id')->nullable()->default(null);
        });
    }
}
