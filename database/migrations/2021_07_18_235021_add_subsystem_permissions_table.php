<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubsystemPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // add subsystem_permissions junction table
        Schema::connection('mysql_root')->create('subsystem_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subsystem_id');
            $table->unsignedBigInteger('permission_id');
        });

        // initial subsystem permissions
        $subsystem_permissions = [
            'Map'                => [ 'View' ],
            'Dashboard'          => [ 'View' ],
            'Field Management'   => [ 'View', 'Add', 'Edit', 'Delete' ],
            'Soil Moisture'      => [ 'View', 'Add', 'Edit', 'Delete', 'Graph', 'Export' ],
            'Cultivars'          => [ 'View', 'Add', 'Edit', 'Delete' ],

            'Cultivar Stages'    => [ 'View', 'Add', 'Edit', 'Delete'],
            'Cultivar Templates' => [ 'View', 'Add', 'Edit', 'Delete', 'Import', 'Export' ],
            'Well Controls'      => [ 'View', 'Add', 'Edit', 'Delete', 'Graph', 'Export', 'Toggle' ],
            'Meters'             => [ 'View', 'Add', 'Edit', 'Delete', 'Graph', 'Export' ],
            'Node Config'        => [ 'View', 'Add', 'Edit', 'Delete', 'Import', 'Export', 'Reboot', 'Flash' ],

            'Sensor Types'       => [ 'View', 'Add', 'Edit', 'Delete' ],
            'Entities'           => [ 'View', 'Add', 'Edit', 'Delete' ],
            'Users'              => [ 'View', 'Add', 'Edit', 'Delete', 'Reset Password' ],
            'Roles'              => [ 'View', 'Add', 'Edit', 'Delete', 'Import', 'Export' ],
            'Groups'             => [ 'View', 'Add', 'Edit', 'Delete' ],

            'Security Rules'     => [ 'View', 'Add', 'Edit', 'Delete' ],
            'Security Templates' => [ 'View', 'Add', 'Edit', 'Delete' ]
        ];

        // assign permission verbs to subsystems
        $subsystems = DB::connection('mysql_root')->table('subsystems')->get();
        foreach($subsystems as $s){
            foreach($subsystem_permissions[$s->subsystem_name] as $sp){
                $p = DB::connection('mysql_root')->table('permissions')->where('permission_name', $sp)->first();
                DB::connection('mysql_root')->table('subsystem_permissions')->insert([
                    'subsystem_id'  => $s->id,
                    'permission_id' => $p->id
                ]);
            }
        }

        // constraints
        Schema::connection('mysql_root')->table('subsystem_permissions', function (Blueprint $table) {
            $table->foreign('subsystem_id')->references('id')->on('subsystems')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->table('subsystem_permissions', function (Blueprint $table) {
            Schema::connection('mysql_root')->dropIfExists('subsystem_permissions');
        });
    }
}
