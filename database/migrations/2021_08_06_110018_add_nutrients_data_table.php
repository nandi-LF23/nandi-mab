<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNutrientsDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Measurement Data Values (Sparse)
        Schema::connection('mysql_root')->create('nutrients_data', function (Blueprint $table) {
            $table->id();
            $table->string('node_address', 50);
            $table->string('probe_serial', 50);
            $table->string('vendor_model_fw', 100);
            $table->text('version'); // SDI version
            $table->string('identifier', 100); // M0-M9, MC0-MC9, etc.
            $table->decimal('value', 20, 10);
            $table->dateTime('date_reported')->nullable();
            $table->dateTime('date_sampled')->nullable();
            $table->string('message_id', 50);
        });

        // Constraints + Indexes
        Schema::connection('mysql_root')->table('nutrients_data', function (Blueprint $table) {
            $table->index('node_address');
            $table->index('message_id');
            $table->index('date_sampled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->drop('nutrients_data');
    }
}
