<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Models\Company;
use App\Models\hardware_config;
use App\Models\node_data;
use App\Models\node_data_meter;
use App\Models\nutri_data;
use App\Models\cultivars_management;
use App\Models\fields;

use App\Calculations;
use App\Utils;

class FieldMapController extends Controller
{
  public function __construct()
  {
    $this->middleware('cors');
    $this->middleware(function ($request, $next) { $this->acc = Auth::user(); return $next($request); });
    $this->timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
  }

  public function index(Request $request) {
   

    // TIMEZONE SETTINGS
    $this->tz = $this->timezones[$this->acc->timezone];
    if(!$this->tz){ $this->tz = 'UTC'; }
    $tzObj = new \DateTimeZone($this->tz);

    // TODAY's DATE
    $todays_date = new \DateTime('now');
    $todays_date->setTimezone($tzObj);

    // FETCH ALL NODES
    $nodes = hardware_config::select([
      'fields.id AS field_id',
      'fields.field_name',
      'fields.full',
      'fields.refill',
      'fields.graph_type',
      'fields.graph_model',
      'fields.graph_start_date',
      'fields.nutrient_template_id',
      'fields.perimeter',
      'fields.zones',
      'hardware_config.id as node_id',
      'hardware_config.date_time',
      DB::raw('UNIX_TIMESTAMP(hardware_config.date_time) as sort_date'),
      'hardware_config.node_address',
      'hardware_config.node_type',
      'hardware_config.latt',
      'hardware_config.lng',
      'hardware_management.measurement_type' // wells/meters
    ])
    ->leftJoin('fields', 'hardware_config.node_address', 'fields.node_id')
    ->leftJoin('hardware_management', 'hardware_config.hardware_management_id', 'hardware_management.id');

    $items = [];
    $grants = [];

    // permission check
    if(!$this->acc->is_admin){
      $grants = $this->acc->requestAccess([
        'Field Management' => ['p' => ['All'] ],
        'Soil Moisture'    => ['p' => ['All'] ],
        'Nutrients'        => ['p' => ['All'] ],
        'Well Controls'    => ['p' => ['Toggle'] ]
      ]);
      if(!empty($grants['Field Management']['View']['O'])){
        $nodes->whereIn('hardware_config.id', $grants['Field Management']['View']['O']);
      } else {
        return response()->json([ 'message' => 'access_denied', 'nodes' => [], 'grants' => $grants ], 403);
      }
    }

    $nodes = $nodes->get()->toArray();

    // GET COMPANY
    $company = Company::where('id', $this->acc->company_id)->first();

    // GET SPECIFIC OPTIONS

    // marker outline color
    $opt_marker_outline_color = $company->get_option('fm_marker_outline_color');

    foreach($nodes as $index => &$node){

      $node_address = $node['node_address'];

      // Manually adjust node's date_time field
      $dt = new \DateTime($node['date_time']);
      $dt->setTimezone($tzObj);
      $node['date_time'] = $dt->format('Y-m-d H:i:s');

      // ------------------------
      // FETCH SOIL MOISTURE DATA
      // ------------------------

      if($node['node_type'] == 'Soil Moisture'){
        
        // get each node's latest data row + previous row
        $data = (new node_data())->toArray();
        $prev = (new node_data())->toArray();

        $rows = node_data::select([
          'node_data.*',
          DB::raw('UNIX_TIMESTAMP(date_time) as sort_date')
        ])
        ->where('probe_id', $node_address)
        ->where('average', '>', 0)
        ->where('accumulative', '>', 0)
        ->orderBy('id', 'desc')
        ->limit(2)
        ->get();

        if($rows->count() == 2){
          // node_data table
          $data = $rows[0]->toArray(); // newest data row
          $prev = $rows[1]->toArray(); // previous data row
        }

      // -------------------
      // FETCH NUTRIENT DATA
      // -------------------

      } else if($node['node_type'] == 'Nutrients'){

        $data = (new nutri_data())->toArray();
        $prev = (new nutri_data())->toArray();

        $ids  = [];
        $cols = [ 'id', 'M0_1', 'M1_1', 'bv', 'bp', 'date_sampled', DB::raw('UNIX_TIMESTAMP(date_sampled) as sort_date') ];
      // old crap  $identifiers = [ 'M0_1', 'M1_1', 'M3_1', 'M4_1', 'M5_1', 'M6_1' ];

        // Raw Records
        //echo $node_address . PHP_EOL;
        $rows = nutri_data::where('node_address',  $node_address)
        ->select('*')
      //  ->whereIn('identifier', $identifiers)
        ->orderBy('id', 'desc')
        ->limit(1)
        ->get()
        ->toArray();
        //print_r($rows);
        //$data['date_time'] = $rows[0]['date_sampled'];
        $prev_rows = null;
if(empty($rows)) continue;
        // Collate into data object
       // if(count($rows) == 6){
          foreach($rows as $row){
            $ids[] = $row['id'];
            $data['M0_1'] = (float) ($row['M0_1']);
            $data['bv'] = $row['bv'];
            $data['bp'] = $row['bp'];
            $data['date_time'] = $row['date_sampled'];
            $prev_rows = nutri_data::select($cols)
            ->where('node_address', $node_address)
            /*->whereNotIn('id', $ids) // This was causing a nasty Performance Drop */
            ->where('date_sampled', '<', $row['date_sampled'])
           // ->whereIn('identifier', $identifiers)
            ->orderBy('id', 'desc')
            ->limit(1)
            ->get()
            ->toArray();
            $_date_time = $row['date_sampled'];
            if($row['M0_1']){
              $data['average'] = (float) ($row);
              $data['accumulative'] = (float) ($row);
              $data['sm1'] = (float) ($row);
            } else if($row['M1_1']){
              $data['t1'] = (float) ($row);
            }
          }
          // temporary fix
          if(empty($data['average'])){ $data['average'] = 0; }
        //}

        // Raw Prev Records
       

        // Collate into prev object
        if($prev_rows){
          foreach($prev_rows as $row){
            $prev[$row['M0_1']] = (float) ($row['M0_1']);
            $prev['bv'] = $row['bv'];
            $prev['bp'] = $row['bp'];
            $prev['date_time'] = $row['date_sampled'];
            if($row['M0_1']){
              $prev['average'] = (float) ($row['M0_1']);
              $prev['accumulative'] = (float) ($row['M0_1']);
              $prev['sm1'] = (float) ($row['M0_1']);
            } else if($row['M1_1']){
              $prev['t1'] = (float) ($row['M1_1']);
            }
          }
          // temporary fix
          if(empty($prev['average'])){ $prev['average'] = 0; }
        }

      // ---------------------------
      // FETCH WELLS AND METERS DATA
      // ---------------------------

      } else if(in_array($node['node_type'], ['Wells', 'Water Meter'])){

        $data = (new node_data_meter())->toArray();
        $prev = (new node_data_meter())->toArray();

        $rows = node_data_meter::select([
          'node_data_meters.*',
          DB::raw('UNIX_TIMESTAMP(date_time) as sort_date')
        ])
        ->where('node_id', $node_address)
        ->orderBy('idwm', 'desc')
        ->limit(2)
        ->get()
        ->toArray();

        if(count($rows) == 2){
          // node_data_meters table
          $data = $rows[0]; // newest data row
          $prev = $rows[1]; // previous data row
        }
      }

      // ---------------------
      // PER NODE DEFAULT DATA
      // ---------------------

      $defaults = [

        'base_node_address' => substr($node_address, 0, strpos($node_address, '-')), // chop off probe number
        'date_time' => "1970-01-01 00:00:00",
        'date_diff' => '',
        'sort_date' => 0,
        'field_name' => 'Field',
        'average'   => 0, 'accumulative' => 0,
        'measurement_type'  => 'Cubes',
        
        'sm1'  => 0, 'sm2'  => 0, 'sm3'  => 0, 'sm4'  => 0, 'sm5'  => 0,
        'sm6'  => 0, 'sm7'  => 0, 'sm8'  => 0, 'sm9'  => 0, 'sm10' => 0,
        'sm11' => 0, 'sm12' => 0, 'sm13' => 0, 'sm14' => 0, 'sm15' => 0,
        't1'   => 0, 't2'   => 0, 't3'   => 0, 't4'   => 0, 't5'   => 0,
        't6'   => 0, 't7'   => 0, 't8'   => 0, 't9'   => 0, 't10'  => 0,
        't11'  => 0, 't12'  => 0, 't13'  => 0, 't14'  => 0, 't15'  => 0,

        'rg'   => 0, 'bv'   => 0, 'bp'   => 0,
        'latt' => $node['latt'], 'lng'  => $node['lng'],
        'status_gauge' => 0, 'status' => 0, 'full' => 70, 'refill' => 50, 'charging' => 0,
        'graph_start_date' => '',

        'nutrient_lower' => 0,
        'nutrient_upper' => 0,
        'nutrient_gauge' => 0,
        'nutrient_pc' => 0,
        'nutrient_label' => '',
        'color_ppm_avg' => '',
        'color_sm_status' => '',

        // Perimeter "Source"
        'perimeter' => null, 

        // Perimeter "Layer"
        'layer' => [
          'id' => $node_address,
          'source' => $node_address,
          'minzoom' => 5,
          'maxzoom' => 24,
          'filter' => ['any', true, true ], // Soil Moisture is default visible layer

          'type' => 'fill',
          //'type' => 'line',

          'paint' => [
            'fill-color' => '',
            'fill-opacity' => 0.5,
            'fill-outline-color' => ''
            // 'line-color' => '',
            // 'line-width' => 3
          ]
        ],
        'zones' => null,
        'marker_outline_color' => $opt_marker_outline_color
      ];

      // IF THERE IS DATA
      if($data){

        // --------------------------------------
        // CALCULATE POWER STATE (USES UTC DATES)
        // --------------------------------------

        $ps = Utils::calculatePowerState(NULL, $node['node_type'], (object)$data, (object)$prev);
        $node['charging'] = strpos($ps, ', charging') !== false ? 1 : 0;

        // localize datetime + calc last reading difference
        if($data['date_time'] && $data['date_time'] != '1970-01-01 00:00:00'){
          $lr = new \DateTime($data['date_time']);
          $lr->setTimezone($tzObj);
          $data['date_time'] = $lr->format('Y-m-d H:i:s');
          $data['date_diff'] = $todays_date->diff($lr);
        }

        // --------------------------
        // PROCESS SOIL MOISTURE DATA
        // --------------------------

        if($node['node_type'] == 'Soil Moisture'){

          // FORMAT SM + TEMP READINGS
/*          for($i = 1; $i < 15; $i++){
            $data["sm$i"] = (float) bcdiv($data["sm$i"],1, 2);
            $data["t$i"]  = (float) bcdiv($data["t$i"], 1, 2);
          }
*/
          if($node['field_id']){

            $moisture = $node['graph_model'] == 'ave' ? 'average' : 'accumulative';

            // CALCULATE SM STATUS
            $result = Calculations::calcStatus(
              (float)$data[$moisture],
              $node['field_id'],
              (float)$node['full'],
              (float)$node['refill'],
              $todays_date,
              $tzObj,
              false /* debug */
            );

            $data['status'] = $result['status'];
            $data['sm_avg'] = Calculations::getLatestNodeAvgSM($node);
            $data['sm_gauge'] = ($data['status'] * 1.8) - 90;
            $data['temp_avg'] = Calculations::getLatestNodeAvgTemp($node);
            $data['temp_gauge'] = ($data['temp_avg'] * 1.8) - 90;

            if($data['sm_gauge'] < -90){ $data['sm_gauge'] = -90; }
            if($data['sm_gauge'] > 90){ $data['sm_gauge'] = 90; }

            // CHECK IF FIELD HAS PERIMETER DEFINED (WE DONT TOUCH PERIMETER, RETURNED AS IS (JSON-ENCODED FeatureCollection))
            if(!empty($node['perimeter'])){

              // CALCULATE SM FIELD STATUS COLOR
              $statusColor = Calculations::calcPercentageOfColorRange(
                $data['status'],
                [
                  [ 0,   [ 255, 0, 0 ]   ], // 0-25%   red
                  [ 25,  [ 255, 255, 0 ] ], // 25-50%  yellow
                  [ 75,  [ 0, 255, 0 ]   ], // 50-75%  green
                  [ 100, [ 0, 0, 255 ]   ]  // 75-100% blue
                ]
              );

              $node['color_sm_status'] = $statusColor;
              
              // Soil Moisture is default

              $defaults['layer']['paint']['fill-color'] = $statusColor;
              $defaults['layer']['paint']['fill-outline-color'] = $statusColor;

            }
          }

          // array_merge:  a <- b <- c (where <- override direction)

          unset($data['id']);
          unset($data['probe_id']);
          unset($data['message_id_1']);
          unset($data['message_id_2']);

          $items[$index] = array_merge( $defaults, array_filter($node), array_filter($data) );

        // ---------------------
        // PROCESS NUTRIENT DATA
        // ---------------------

        } else if($node['node_type'] == 'Nutrients'){

          // ENSURE NODE HAS ASSOCIATED FIELD DEFINED
          if($node['field_id']){

            $moisture = 'average'; // always

            // calculate status
            $result = Calculations::calcStatus(
              (float)$data[$moisture],
              $node['field_id'],
              (float)$node['full'],
              (float)$node['refill'],
              $todays_date,
              $tzObj,
              false /* debug */
            );

            $data['status'] = $result['status'];
            $data['sm_avg'] = Calculations::getLatestNodeAvgSM($node);
            $data['sm_gauge'] = ($data['status'] * 1.8) - 90;
            $data['temp_avg'] = Calculations::getLatestNodeAvgTemp($node);
            $data['temp_gauge'] = ($data['temp_avg'] * 1.8) - 90;

            if($data['sm_gauge'] < -90){ $data['sm_gauge'] = -90; }
            if($data['sm_gauge'] > 90){ $data['sm_gauge'] = 90; }

            // Attempt to calculate NUTRIENT GAUGE VALUES
            if(!empty($node['nutrient_template_id'])){

              $results = Calculations::calcNutrientAverageGaugeValues($node_address, $node['nutrient_template_id']);

              $data['nutrient_lower'] = $results['nutrient_lower'];
              $data['nutrient_upper'] = $results['nutrient_upper'];
              $data['nutrient_gauge'] = $results['nutrient_gauge'];
              $data['nutrient_avg'] = $results['nutrient_avg'];
              $data['nutrient_pc'] = $results['nutrient_pc']; // percentage
              $data['nutrient_label'] = $results['nutrient_label'];

              // CALCULATE SM FIELD STATUS COLOR
              $ppmAverageColor = Calculations::calcPercentageOfColorRange(
                $data['nutrient_pc'],
                [
                  [ 0,   [ 255, 0, 0 ]   ], // 0%   red
                  [ 20,  [ 255, 255, 0 ] ], // 20%  yellow 
                  [ 40,  [ 0, 240, 0 ]   ], // 40%  lightgreen
                  [ 50,  [ 0, 255, 0 ]   ], // 50%  green
                  [ 60,  [ 0, 240, 0 ]   ], // 60%  lightgreen
                  [ 80,  [ 255, 255, 0 ] ], // 80%  yellow
                  [ 100, [ 255, 0, 0 ]   ]  // 100% red
                ]
              );
              $data['color_ppm_avg'] = $ppmAverageColor;
            }

            // CALCULATE SM FIELD STATUS COLOR
            $statusColor = Calculations::calcPercentageOfColorRange(
              $data['status'],
              [
                [ 0,   [ 255, 0, 0 ]   ], // 0-25%   red
                [ 25,  [ 255, 255, 0 ] ], // 25-50%  yellow
                [ 75,  [ 0, 255, 0 ]   ], // 50-75%  green
                [ 100, [ 0, 0, 255 ]   ]  // 75-100% blue
              ]
            );

            $data['color_sm_status'] = $statusColor;

            // Soil Moisture is default

            $defaults['layer']['paint']['fill-color'] = $statusColor;
            $defaults['layer']['paint']['fill-outline-color'] = $statusColor;
            // $defaults['layer']['paint']['line-color'] = $statusColor;

            // PRE-PROCESS ZONES (STREAMLINE FOR MAP LAYER RENDERING)
            if(!empty($node['zones'])){
              $zones = json_decode($node['zones'], true);

              $zone_colors = [
                "1" => "#00FF00", // green,
                "2" => "#DAFF00", // lightgreen,
                "3" => "#FFDA00", // yellow
                "4" => "#FE0000"  // red
              ];

              if($zones){
                foreach($zones as $idx => &$info){

                  $zone_id = (int) Utils::get_first_number($info['data']['ZONE_ID']); 
                  $base_id = "{$node['node_address']}_{$node['field_id']}_{$zone_id}";

                  $zones[$idx]['source'] = [
                    'type' => 'geojson',
                    'data' => [
                      'type' => 'Feature',
                      'geometry' => json_decode($info['geom'], true),
                      'properties' => [
                          'Zone' => true
                      ]
                    ]
                  ];

                  $zones[$idx]['layer'] = [
                    'id' => $base_id,
                    'source' => $base_id,
                    'minzoom' => 10,
                    'maxzoom' => 24,
                    'type' => 'fill',
                    'filter' => [ '==', [ 'get', 'Zone' ], true ],
                    'paint' => [
                      'fill-color' => array_key_exists($zone_id, $zone_colors) ? $zone_colors[$zone_id] : "#555555",
                      'fill-opacity' => 1,
                      'fill-outline-color' => 'rgba(0,0,0,1.0)',
                      'fill-antialias' => true
                    ]
                  ];

                  unset($info['geom']);

                }
                $data['zones'] = $zones;
              }
            }
          }

          // array_merge:  a <- b <- c (where <- override direction)

          unset($data['id']);
          unset($data['identifier']);
          unset($data['message_id']);

          $items[$index] = array_merge($defaults, array_filter($node), array_filter($data));

        // ---------------------------
        // PROCESS WELLS + METERS DATA
        // ---------------------------

        } else {

          unset($data['idwm']);
          unset($data['node_id']);
          unset($data['message_id']);

          $items[$index] = array_merge($defaults, array_filter($node), array_filter($data));
        }

      // FALLBACK (WHEN NO DATA)
      } else {
        $items[$index] = array_merge($defaults, array_filter($node));
      }
    }

    // SORT BY DATE
    if($items){
      usort($items, function($a, $b){
        return $b['sort_date'] - $a['sort_date'];
      });
    }

    // LOG ACTIVITIES
    if(empty($request->is_refresh)){
      $details = !empty($grants['Field Management']['View']['C']) ? 
          'Company IDs: ' . implode(',',  $grants['Field Management']['View']['C']) :
          ($this->acc->is_admin ? 'All Objects' : 'Access Denied');
      $this->acc->logActivity('View', 'Map', $details);
    }

    return response()->json([
      'nodes'  => $items,
      'grants' => $grants
    ]);
  }
}
