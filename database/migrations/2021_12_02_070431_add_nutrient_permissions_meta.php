<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNutrientPermissionsMeta extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permissions = DB::connection('mysql_root')->table('permissions')->get()->keyBy('permission_name')->toArray();

        // 1.) Add new Subsystem: Nutrients
        $nutrient_subsystem_id = null;
        if(!DB::connection('mysql_root')->table('subsystems')->where('subsystem_name', 'Nutrients')->exists()){
            $nutrient_subsystem_id = DB::connection('mysql_root')->table('subsystems')->insertGetId([
                'subsystem_name' => 'Nutrients',
                'group_table'    => 'groups_nodes',
                'resource_table' => 'hardware_config',
                'route'          => 'nutrients'
            ]);
        } else {
            $nutrient_subsystem_id = DB::connection('mysql_root')->table('subsystems')->where('subsystem_name', 'Nutrients')->value('id');
        }

        // 2.) Add new Subsystem: Nutrients Templates
        $nutrient_templates_subsystem_id = null;
        if(!DB::connection('mysql_root')->table('subsystems')->where('subsystem_name', 'Nutrient Templates')->exists()){
            $nutrient_templates_subsystem_id = DB::connection('mysql_root')->table('subsystems')->insertGetId([
                'subsystem_name' => 'Nutrient Templates',
                'group_table'    => 'groups_nutrient_templates',
                'resource_table' => 'nutrient_templates',
                'route'          => 'login'
            ]);
        } else {
            $nutrient_templates_subsystem_id = DB::connection('mysql_root')->table('subsystems')->where('subsystem_name', 'Nutrient Templates')->value('id');
        }

        // 3.) Add Permissions for Subsystem: Nutrients
        if(DB::connection('mysql_root')->table('subsystem_permissions')->where('subsystem_id', $nutrient_subsystem_id)->count() == 0){
            DB::connection('mysql_root')->table('subsystem_permissions')->insert([
                ['subsystem_id' => $nutrient_subsystem_id, 'permission_id' => $permissions['View'  ]->id],
                ['subsystem_id' => $nutrient_subsystem_id, 'permission_id' => $permissions['Add'   ]->id],
                ['subsystem_id' => $nutrient_subsystem_id, 'permission_id' => $permissions['Edit'  ]->id],
                ['subsystem_id' => $nutrient_subsystem_id, 'permission_id' => $permissions['Delete']->id],
                ['subsystem_id' => $nutrient_subsystem_id, 'permission_id' => $permissions['Graph' ]->id],
                ['subsystem_id' => $nutrient_subsystem_id, 'permission_id' => $permissions['Export']->id]
            ]);
        }

        // 4.) Add Permissions for Subsystem: Nutrient Templates
        if(DB::connection('mysql_root')->table('subsystem_permissions')->where('subsystem_id', $nutrient_templates_subsystem_id)->count() == 0){
            DB::connection('mysql_root')->table('subsystem_permissions')->insert([
                ['subsystem_id' => $nutrient_templates_subsystem_id, 'permission_id' => $permissions['View'  ]->id],
                ['subsystem_id' => $nutrient_templates_subsystem_id, 'permission_id' => $permissions['Add'   ]->id],
                ['subsystem_id' => $nutrient_templates_subsystem_id, 'permission_id' => $permissions['Edit'  ]->id],
                ['subsystem_id' => $nutrient_templates_subsystem_id, 'permission_id' => $permissions['Delete']->id],
                ['subsystem_id' => $nutrient_templates_subsystem_id, 'permission_id' => $permissions['Import']->id],
                ['subsystem_id' => $nutrient_templates_subsystem_id, 'permission_id' => $permissions['Export']->id]
            ]);
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('mysql_root')->table('subsystems')->where('subsystem_name', 'Nutrients')->delete();
        DB::connection('mysql_root')->table('subsystems')->where('subsystem_name', 'Nutrient Templates')->delete();
    }
}
