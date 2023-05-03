<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeSerialNumberFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql_root')->table('hardware_config', function (Blueprint $table) {
            // Rename
            $table->renameColumn('serial_number', 'node_serial_number');
        });
        Schema::connection('mysql_root')->table('hardware_config', function (Blueprint $table) {
            // Add
            $table->string('device_serial_number', 255)->after('node_serial_number')->nullable();
        });

        // Move over Probe (Device) Serial numbers (probe_id) from Field to Hardware Config
        $nodes = DB::table('hardware_config')->get()->toArray();
        foreach($nodes as $node){
            if(DB::table('fields')->where('node_id', $node->node_address)->exists()){
                $probe_serial = DB::table('fields')->where('node_id', $node->node_address)->value('probe_id');
                if($probe_serial){
                    DB::table('hardware_config')->where('node_address', $node->node_address)->update([
                        'device_serial_number' => $probe_serial
                    ]);
                }
            }
        }

        Schema::connection('mysql_root')->table('fields', function (Blueprint $table) {
            // Remove
            $table->dropColumn('probe_id');
        });
        
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
