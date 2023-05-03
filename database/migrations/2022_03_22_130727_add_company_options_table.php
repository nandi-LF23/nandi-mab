<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCompanyOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add Per-Company Options Table
        Schema::connection('mysql_root')->create('company_options', function (Blueprint $table) {
            $table->id();
            // Owning Company
            $table->unsignedBigInteger('company_id');
            // Option Slug (Key, Indexed)
            $table->string('slug', 256);
            // Option Value
            $table->longText('value');
        });

        // Add FK constraints/indexes
        Schema::connection('mysql_root')->table('company_options', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('slug')->references('slug')->on('options_specs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove contraints/indexes
        Schema::connection('mysql_root')->table('company_options', function (Blueprint $table) {
            $table->dropForeign(['slug']);
            $table->dropForeign(['company_id']);
        });

        // Drop table
        Schema::connection('mysql_root')->dropIfExists('company_options');
    }
}
