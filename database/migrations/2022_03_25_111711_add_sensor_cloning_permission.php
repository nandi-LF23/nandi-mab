<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSensorCloningPermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Get Permission Verb
        $permission_id = DB::connection('mysql_root')->table('permissions')->where('permission_name', 'Clone')->value('id');
        // 2. Get Subsystem ID
        $subsystem_id = DB::connection('mysql_root')->table('subsystems')->where('subsystem_name', 'Sensor Types')->value('id');
        // 2. Add Permission Verb + Subsystem Record
        $perm_sub_id = DB::connection('mysql_root')->table('subsystem_permissions')->insertGetId([
            'subsystem_id' => $subsystem_id,
            'permission_id' => $permission_id
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
