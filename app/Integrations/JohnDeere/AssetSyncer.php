<?php

namespace App\Integrations\JohnDeere;

use Illuminate\Support\Facades\Log;
use App\Models\fields;
use App\Models\Setting;
use App\Calculations;
use App\Utils;

class AssetSyncer {

    protected $assets_api;

    public function __construct($assets_api)
    {
        $this->assets_api = $assets_api;
    }

    public function sync($node, $asset_id, $int_name, $field_obj = null)
    {
        $result = false;

        $timestamp_key = "{$int_name}.{$node->node_address}.ts";

        $field    = $field_obj ? $field_obj : fields::where('node_id', $node->node_address)->first();
        $datetime = $this->get_node_datetime($node);
        $geometry = $this->get_node_coordinates($node);
        $measdata = $this->get_node_measurement_data($node, $field);

        if($datetime && $geometry && $measdata){
            
            $timestamp = Setting::get($timestamp_key, NULL);
            if($datetime !== $timestamp){
                Setting::set($timestamp_key, $datetime);
                try {
                    $params = json_encode([[
                        '@type' => 'ContributedAssetLocation',
                        'timestamp' => $datetime,
                        'geometry'  => $geometry,
                        'measurementData' => $measdata
                    ]]);
                    $this->assets_api->create_location($asset_id, $params);
                    //Log::debug("Updated asset associated with " . $node->node_address . " ($asset_id)");
                    $result = true;

                } catch (\Illuminate\Http\Client\RequestException $ex){
                    Log::debug($ex->response);
                }
            } else {
                //Log::debug("AssetSyncer->sync: timestamps don't differ, skipping sync");
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
        return json_encode([
            "type" =>  "Feature",
            "geometry" => [
                "geometries" => [
                    [
                        "coordinates" => [ $node->lng, $node->latt ],
                        "type" => "Point"
                    ]
                ],
                "type" => "GeometryCollection"
            ]
        ]);
    }

    // DONE DURING SETUP

    public function get_node_measurement_data($node, $field)
    {
        $measurementData = [];
        if(!empty($node->node_address)){

            if($node->node_type == 'Nutrients' && !empty($field->nutrient_template_id)){

                // PPM Average (Linked)
                $results = Calculations::calcNutrientAverageGaugeValues($node->node_address, $field->nutrient_template_id);
                $graph_link = url('/') . "/nutrients/graph/{$node->node_address}?context=" . Utils::encryptEncode([
                    'restricted_to' => $node->company_id
                ]);

                $measurementData[] = [
                    "@type" => "BasicMeasurement",
                    "name"  => "[PPM NH4 (Avg)]($graph_link)",
                    "value" => "{$results['nutrient_NH4']}",
                    "unit"  => "PPM"
                ];
                $measurementData[] = [
                    "@type" => "BasicMeasurement",
                    "name"  => "[PPM NO3 (Avg)]($graph_link)",
                    "value" => "{$results['nutrient_NO3']}",
                    "unit"  => "PPM"
                ];

                $sm = Calculations::getLatestNodeAvgSM($node);
    
                // Soil Moisture
                $measurementData[] = [
                    "@type" => "BasicMeasurement",
                    "name"  => "Soil Moisture",
                    "value" => "{$sm}",
                    "unit"  => "%"
                ];

                $temp = Calculations::getLatestNodeAvgTemp($node);

                // Temperature
                $measurementData[] = [
                    "@type" => "BasicMeasurement",
                    "name"  => "Temperature",
                    "value" => "{$temp}",
                    "unit"  => "C"
                ];
    
            } else if($node->node_type == 'Soil Moisture'){

                $graph_link = url('/') . "/soil_moisture/graph/{$node->node_address}?context=" . Utils::encryptEncode([
                    'restricted_to' => $node->company_id
                ]);

                $sm = Calculations::getLatestNodeAvgSM($node);
    
                // Soil Moisture
                $measurementData[] = [
                    "@type" => "BasicMeasurement",
                    "name"  => "[Soil Moisture]($graph_link)",
                    "value" => "{$sm}",
                    "unit"  => "%"
                ];

                $temp = Calculations::getLatestNodeAvgTemp($node);

                // Temperature
                $measurementData[] = [
                    "@type" => "BasicMeasurement",
                    "name"  => "Temperature",
                    "value" => "{$temp}",
                    "unit"  => "C"
                ];

            }

            $zone = $node->zone;

            if($zone){

                // Zone
                $measurementData[] = [
                    "@type" => "BasicMeasurement",
                    "name"  => "Zone",
                    "value" => "{$zone}",
                    "unit" => " ",
                ];

            }

        }
        return $measurementData;
    }

}