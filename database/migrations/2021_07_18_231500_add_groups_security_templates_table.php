<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGroupsSecurityTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add GroupsRoles table
        Schema::connection('mysql_root')->create('groups_security_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('object_id');
        });

        // Constraints
        Schema::connection('mysql_root')->table('groups_security_templates', function (Blueprint $table) {
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
            $table->foreign('object_id')->references('id')->on('security_templates')->onDelete('cascade');
            // an object may only appear once in a group
            $table->unique(['group_id', 'object_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->dropIfExists('groups_security_templates');
    }
}
