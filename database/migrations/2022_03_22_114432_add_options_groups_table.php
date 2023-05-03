<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOptionsGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add Options Table (Option Groups)
        Schema::connection('mysql_root')->create('options_groups', function (Blueprint $table) {
            // PK
            $table->id();
            // Group Name
            $table->string('name', 256);
            // Group Description
            $table->string('desc', 512)->nullable();
            // Group Config (For adding extra configuration parameters, optional)
            $table->longText('config')->nullable();
        });

        // Add FK constraints/indexes
        Schema::connection('mysql_root')->table('options_groups', function (Blueprint $table) {
            $table->index('name', 256);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop table
        Schema::connection('mysql_root')->dropIfExists('options_groups');
    }
}
