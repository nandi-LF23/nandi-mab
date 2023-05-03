<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // add role_id column
        Schema::connection('mysql_root')->table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->after('role');
            $table->unsignedBigInteger('company_id')->after('role_id');
            $table->boolean('is_admin')->nullable(false)->default('0')->after('company_id');
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });

        // CONVERT ROLES

        // grab distinct role names
        $roles = DB::connection('mysql_root')->table('users')->distinct()->pluck('role');
        foreach($roles as $role){
            // insert them into roles table
            $role_id = DB::connection('mysql_root')->table('roles')->insertGetId([
                 'role_name' => $role,
                 'company_id' => 1, /* LiquidFibre */
            ]);
            // then update each user's role_id
            DB::connection('mysql_root')->table('users')->where('role', '=', $role)->update([ 'role_id' => $role_id ]);
        }

        // SET USERS AS ADMIN WHERE ROLE is 'Admin'
        $users = DB::connection('mysql_root')->table('users')->where('role', '=', 'Admin')->update(['is_admin' => 1]);

        // POPULATE USERS company_id column with first company
        $company = DB::connection('mysql_root')->table('companies')->first();
        DB::connection('mysql_root')->table('users')->update([ 'company_id' => $company->id ]);

        Schema::connection('mysql_root')->table('users', function(Blueprint $table) {
            // after values are in place, add foreign key constraints
            $table->foreign('role_id')->references('id')->on('roles');
            $table->foreign('company_id')->references('id')->on('companies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');

        });
    }
}
