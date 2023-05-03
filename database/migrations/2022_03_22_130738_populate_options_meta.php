<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PopulateOptionsMeta extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Insert Option Groups

        // Map Options
        $option_group_map_id = DB::connection('mysql_root')->table('options_groups')->insertGetId([
            'name' => 'Map',
            'desc' => 'Map Related Options'
        ]);

        // Field Management Options
        $option_group_fm_id = DB::connection('mysql_root')->table('options_groups')->insertGetId([
            'name' => 'Field Managment',
            'desc' => 'Field Management Related Options'
        ]);

        // Insert Option Specs

        // Map Marker Outline Color
        $map_marker_outline_spec_id = DB::connection('mysql_root')->table('options_specs')->insertGetId([
            'group_id' => $option_group_map_id,
            'slug' => 'map_marker_outline_color',
            'label' => 'Marker Outline Color',
            'desc' => 'Configure the Outline Color of all Map Markers',
            'type' => 'colorpicker',
            'default' => '#ffffff'
        ]);

        // Field Management Marker Outline Color
        $fm_marker_outline_spec_id = DB::connection('mysql_root')->table('options_specs')->insertGetId([
            'group_id' => $option_group_fm_id,
            'slug' => 'fm_marker_outline_color',
            'label' => 'Marker Outline Color',
            'desc' => 'Configure the Outline Color of all Field Management Markers',
            'type' => 'colorpicker',
            'default' => '#ffffff'
        ]);

        // Company Options

        // Insert Option Instances for all Companies
        $company_ids = DB::connection('mysql_root')->table('companies')->select('id')->get();

        foreach($company_ids as $row){
            DB::connection('mysql_root')->table('company_options')->insert([
                'company_id' => $row->id,
                'slug' => 'map_marker_outline_color',
                'value' => '#ffffff'
            ]);
        }

        foreach($company_ids as $row){
            DB::connection('mysql_root')->table('company_options')->insert([
                'company_id' => $row->id,
                'slug' => 'fm_marker_outline_color',
                'value' => '#ffffff'
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
        //
    }
}
