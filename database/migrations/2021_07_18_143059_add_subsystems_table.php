<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubsystemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_root')->create('subsystems', function (Blueprint $table) {
            $table->id();
            $table->text('subsystem_name');
            $table->text('group_table');
            $table->text('resource_table');
            $table->text('route');
        });

        DB::connection('mysql_root')->table('subsystems')->insert([
            [ 'subsystem_name' => 'Map',                'group_table' => 'groups_nodes',               'resource_table' => 'hardware_config',      'route' => 'map' ],
            [ 'subsystem_name' => 'Dashboard',          'group_table' => 'groups_nodes',               'resource_table' => 'hardware_config',      'route' => 'dashboard' ],
            [ 'subsystem_name' => 'Field Management',   'group_table' => 'groups_fields',              'resource_table' => 'fields',               'route' => 'fields_manage' ],
            [ 'subsystem_name' => 'Soil Moisture',      'group_table' => 'groups_nodes',               'resource_table' => 'hardware_config',      'route' => 'soil_moisture' ],
            [ 'subsystem_name' => 'Cultivars',          'group_table' => 'groups_cultivars',           'resource_table' => 'cultivars_management', 'route' => 'login' ],
            
            [ 'subsystem_name' => 'Cultivar Stages',    'group_table' => 'groups_cultivars_stages',    'resource_table' => 'cultivars',            'route' => 'login' ],
            [ 'subsystem_name' => 'Cultivar Templates', 'group_table' => 'groups_cultivars_templates', 'resource_table' => 'cultivars_templates',  'route' => 'login' ],
            [ 'subsystem_name' => 'Well Controls',      'group_table' => 'groups_nodes',               'resource_table' => 'hardware_config',      'route' => 'well_controls' ],
            [ 'subsystem_name' => 'Meters',             'group_table' => 'groups_nodes',               'resource_table' => 'hardware_config',      'route' => 'meters' ],
            [ 'subsystem_name' => 'Node Config',        'group_table' => 'groups_nodes',               'resource_table' => 'hardware_config',      'route' => 'node_config' ],

            [ 'subsystem_name' => 'Sensor Types',       'group_table' => 'groups_sensors',             'resource_table' => 'hardware_management',  'route' => 'sensor_types' ],
            [ 'subsystem_name' => 'Entities',           'group_table' => 'groups_companies',           'resource_table' => 'companies',            'route' => 'entities_manage' ],
            [ 'subsystem_name' => 'Users',              'group_table' => 'groups_users',               'resource_table' => 'users',                'route' => 'users_manage' ],
            [ 'subsystem_name' => 'Roles',              'group_table' => 'groups_roles',               'resource_table' => 'roles',                'route' => 'roles_manage' ],
            [ 'subsystem_name' => 'Groups',             'group_table' => 'groups_groups',              'resource_table' => 'groups',               'route' => 'groups_manage' ],

            [ 'subsystem_name' => 'Security Rules',     'group_table' => 'groups_security_rules',      'resource_table' => 'security_rules',       'route' => 'login' ],
            [ 'subsystem_name' => 'Security Templates', 'group_table' => 'groups_security_templates',  'resource_table' => 'security_templates',   'route' => 'login' ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->dropIfExists('subsystems');
    }
}
