<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveTimestampsAlterColumnNameB64Data extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_root')->table('raw_data_b64', function (Blueprint $table) {
            $table->renameColumn('date_decoded', 'timestamp');
            $table->dropColumn('created_at');
            $table->dropColumn('updated_at');
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
            $table->renameColumn('date_decoded', 'timestamp');
            $table->timestamps();
        });
    }
}
