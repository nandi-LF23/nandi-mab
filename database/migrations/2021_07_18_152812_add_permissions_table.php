<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add Permissions 'verb' table
        Schema::connection('mysql_root')->create('permissions', function (Blueprint $table) {
            $table->id();
            $table->text('permission_name');
        });

        DB::connection('mysql_root')->table('permissions')->insert([
            [ 'permission_name' => 'View'],
            [ 'permission_name' => 'Add'],
            [ 'permission_name' => 'Edit'],
            [ 'permission_name' => 'Delete'],
            [ 'permission_name' => 'Graph'],
            [ 'permission_name' => 'Import'],
            [ 'permission_name' => 'Export'],
            [ 'permission_name' => 'Reset Password'],
            [ 'permission_name' => 'Toggle'],
            [ 'permission_name' => 'Reboot'],
            [ 'permission_name' => 'Flash'],
            [ 'permission_name' => 'Auth']
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->dropIfExists('permissions');
    }
}
