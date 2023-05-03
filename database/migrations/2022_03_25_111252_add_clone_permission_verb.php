<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClonePermissionVerb extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Check if 'Clone' exists, if not, add it
        $permission_id = DB::connection('mysql_root')->table('permissions')->where('permission_name', 'Clone')->value('id');
        if(!$permission_id){
            DB::connection('mysql_root')->table('permissions')->insertGetId([
                'permission_name' => 'Clone'
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
        // Remove Permission Verb
        DB::connection('mysql_root')->table('permissions')->where('permission_name', 'Clone')->delete();
    }
}
