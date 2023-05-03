<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeHardwareManagementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // change primary key int size
        Schema::connection('mysql_root')->table('hardware_management', function (Blueprint $table) {
            $table->bigIncrements('id')->change(); // already primary
            $table->unsignedBigInteger('company_id');
            $table->text('device_make');
        });

        // Copy over device_make_sm / device_make_wm to device_make
        $hm = DB::connection('mysql_root')->table('hardware_management')->get();
        $probes = ['Soil Moisture','Tank Level'];
        $meters = ['Wells','Water Meter'];

        foreach($hm as $m){
            if(!empty($m->device_make_sm) && in_array($m->device_type, $probes)){
                DB::connection('mysql_root')->table('hardware_management')->where('id', $m->id)->update(['device_make' => $m->device_make_sm ]);
            } else if(!empty($m->device_make_wm) && in_array($m->device_type, $meters)){
                DB::connection('mysql_root')->table('hardware_management')->where('id', $m->id)->update(['device_make' => $m->device_make_wm ]);
            }
        }

        // Constraints
        Schema::connection('mysql_root')->table('hardware_management', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies');
            DB::connection('mysql_root')->table('hardware_management')->update(['company_id' => 1]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('mysql_root')->table('hardware_management', function (Blueprint $table) {
            $table->integer('id')->change();
            $table->dropColumn('device_make');
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
}
