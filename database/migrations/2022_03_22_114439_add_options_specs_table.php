<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOptionsSpecsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add Options Specification (Option Type Specification) (Like a Class)
        Schema::connection('mysql_root')->create('options_specs', function (Blueprint $table) {
            // PK
            $table->id();
            // Option Group
            $table->unsignedBigInteger('group_id');
            // Option Parent (For Repeaters/Groups/etc) (Optional)
            $table->unsignedBigInteger('parent_id')->nullable();
            // Option Slug (Key)
            $table->string('slug', 256);
            // Option Label
            $table->string('label', 256);
            // Option Description
            $table->string('desc', 512)->nullable();
            // Option Base Type (text/textarea/datepicker/datetimepicker/colorpicker/etc)
            $table->string('type', 128);
            // Option Default Value
            $table->longText('default');
            // Option Config (For adding extra configuration parameters, optional)
            $table->longText('config')->nullable();
        });

        // Add FK constraints/indexes
        Schema::connection('mysql_root')->table('options_specs', function (Blueprint $table) {
            $table->unique('slug');
            $table->foreign('parent_id')->references('id')->on('options_specs')->onDelete('cascade');
            $table->foreign('group_id')->references('id')->on('options_groups')->onDelete('cascade');
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
        Schema::connection('mysql_root')->table('options_specs', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['group_id']);
        });

        // Drop table
        Schema::connection('mysql_root')->dropIfExists('options_specs');
    }
}
