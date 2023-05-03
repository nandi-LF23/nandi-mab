<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCompaniesAddIntegrationsField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // add integrations field to host integration options
        Schema::connection('mysql_root')->table('companies', function (Blueprint $table) {
            $table->longText('integrations')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->table('companies', function (Blueprint $table) {
            $table->dropColumn('integrations');
        });
    }
}
