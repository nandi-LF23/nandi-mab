<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIntegrationsFieldToHardwareConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_root')->table('hardware_config', function (Blueprint $table) {
            // Integration Options (64K)
            $table->text('integrations')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->table('hardware_config', function (Blueprint $table) {
            $table->dropColumn('integrations');
        });
    }
}
