<?php

namespace App\Integrations\JohnDeere;

use Illuminate\Support\Facades\Log;
use App\Models\fields;
use App\Models\Setting;
use App\Calculations;

class FlagSyncer {

    protected $flags_api;
    protected $flags_cat_api;

    public function __construct($flags_api, $flags_cat_api)
    {
        $this->flags_api = $flags_api;
        $this->flags_cat_api = $flags_cat_api;
    }

    public function sync($node, $flag_id, $int_name, $org_id, $field_obj = null)
    {
        $result = false;

        $field         = $field_obj ? $field_obj : fields::where('node_id', $node->node_address)->first();
        $timestamp_key = "{$int_name}.{$node->node_address}.flag_ts";
        $n_cat_key     = "{$int_name}.{$node->node_address}.flag_cat_id";
        $node_cat_name = "Probe - {$node->node_type}";
        $jdo_field_key = "{$int_name}.field_{$field->id}";

        // Get Node Flag Category ID (Or Create)
        $n_cat_id = Setting::get($n_cat_key, NULL); // TODO: SET TO NULL WHEN CHANGING NODE'S TYPE TO FORCE REFETCH
        if(!$n_cat_id){
            $n_cat_id = $this->flags_cat_api->get_field_by_kv($org_id, 'id', 'categoryTitle', $node_cat_name);
            if(!$n_cat_id){
                $n_cat_id = $this->flags_cat_api->create_flag_category($org_id, $node_cat_name);
                if(!$n_cat_id){ Log::debug("SyncFlagsTask: Could not create flag category: {$node_cat_name}"); return false; }
                //Log::debug("SyncFlagsTask: Created flag category: {$node_cat_name}");
            }
            Setting::set($n_cat_key, $n_cat_id);
        }

        // Get Field ID
        $field_id = Setting::get($jdo_field_key);
        if(!$field_id){ Log::debug('Missing field id for field ' . $field->field_name ); return false; }

        // Collect Needed Data
        $datetime = $this->get_node_datetime($node);
        $geometry = $this->get_node_coordinates($node);
        $metadata = $this->get_node_flag_metadata($node, $field);

        if($datetime && $geometry && $metadata){
            $timestamp = Setting::get($timestamp_key, NULL);
            if($datetime !== $timestamp){
                Setting::set($timestamp_key, $datetime);
                try {
                    $this->flags_api->update_flag($org_id, $n_cat_id, $field_id, $flag_id, [
                        '@type' => 'Flag',
                        'id' => $flag_id,
                        'geometry' => $geometry,
                        'notes' => $node->node_address,
                        'archived' => 'false',
                        'proximityAlertEnabled' => 'false',
                        'metadata' => $metadata
                    ]);

                    // Log::debug("Synced Flag {$node->node_address} ($flag_id)");

                    $result = true;

                } catch (\Illuminate\Http\Client\RequestException $ex){
                    Log::debug('Exception');
                    Log::debug($ex->response);
                }
            }  else {
                //Log::debug("FlagSyncer->sync: timestamps don't differ, skipping sync");
            }
        }
        return $result;
    }

    public function get_node_datetime($node)
    {
        return $node->date_time ? str_replace("+00:00", "Z", (new \DateTime($node->date_time))->format('c')) : null;
    }

    public function get_node_coordinates($node)
    {
        return [ 'type' => 'Point', 'coordinates' => [ $node->lng, $node->latt ] ];
    }

    // DONE DURING SETUP

    public function get_node_flag_metadata($node, $field)
    {
        $metadata = [];
        if(!empty($node->node_address)){

            if($node->node_type == 'Nutrients' && !empty($field->nutrient_template_id)){
                // PPM Average
                $results = Calculations::calcNutrientAverageGaugeValues($node->node_address, $field->nutrient_template_id);

                $metadata[] = [
                    "name"  => "PPM_AVG",
                    "value" => "{$results['nutrient_avg']}",
                ];
            }

            $temp = Calculations::getLatestNodeAvgTemp($node);

            // Temperature
            $metadata[] = [
                "name"  => "TEMPERATURE",
                "value" => "{$temp}",
            ];

            $sm = Calculations::getLatestNodeAvgSM($node);

            // Soil Moisture
            $metadata[] = [
                "name"  => "SOIL_MOISTURE",
                "value" => "{$sm}",
            ];

            $zone = $node->zone;

            if($zone){

                // Zone ID
                $metadata[] = [
                    "name"  => "ZONE",
                    "value" => "{$zone}",
                ];

            }

        }
        return $metadata;
    }

}