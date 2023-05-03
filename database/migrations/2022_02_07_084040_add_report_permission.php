<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReportPermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Add new Permission Verb
        $permission_id = DB::connection('mysql_root')->table('permissions')->insertGetId([
            'permission_name' => 'Report'
        ]);

        // 2. Add Permission Verb + Subsystem Record
        $perm_sub_id = DB::connection('mysql_root')->table('subsystem_permissions')->insertGetId([
            'subsystem_id' => 12 /* Entities */,
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
