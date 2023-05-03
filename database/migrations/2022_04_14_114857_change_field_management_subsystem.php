<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeFieldManagementSubsystem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection('mysql_root')->table('subsystems')->where('subsystem_name', 'Field Management')->update([
            'group_table' => 'groups_nodes',
            'resource_table' => 'hardware_config',
            'route' => 'field_management'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('mysql_root')->table('subsystems')->where('subsystem_name', 'Field Management')->update([
            'group_table' => 'groups_fields',
            'resource_table' => 'fields',
            'route' => 'fields_manage'
        ]);
    }
}
