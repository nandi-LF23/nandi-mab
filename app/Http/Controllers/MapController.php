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

class MapController extends Controller
{
  public function __construct()
  {
    $this->middleware('cors');
    $this->middleware(function ($request, $next) {
      $this->acc = Auth::user();
      return $next($request);
    });
    $this->timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
  }

  public function index(Request $request)
  {
    // TIMEZONE SETTINGS
    $this->tz = $this->timezones[$this->acc->timezone];
    if (!$this->tz) {
      $this->tz = 'UTC';
    } // 0 fails here
    $tzObj = new \DateTimeZone($this->tz);

    // TODAY's DATE
    $todays_date = new \DateTime('now');
    $todays_date->setTimezone($tzObj);

    // FETCH ALL NODES
    $nodes = hardware_config::select(
      'fields.id AS field_id',
      'fields.field_name',
      'fields.full',
      'fields.refill',
      'fields.graph_type',
      'fields.graph_model',
      'fields.graph_start_date',
      'fields.nutrient_template_id',
      'fields.perimeter',
      'hardware_config.id as node_id',
      'hardware_config.date_time',
      'hardware_config.node_address',
      'hardware_config.node_type',
      'hardware_config.latt',
      'hardware_config.lng',
      'hardware_management.measurement_type'
    )
      ->leftJoin('fields', 'hardware_config.node_address', 'fields.node_id')
      ->leftJoin('hardware_management', 'hardware_config.hardware_management_id', 'hardware_management.id');

    $items = [];
    $grants = [];

    // permission check
    if (!$this->acc->is_admin) {
      $grants = $this->acc->requestAccess([
        'Map'           => ['p' => ['All']],
        'Soil Moisture' => ['p' => ['All']],
        'Well Controls' => ['p' => ['Toggle']]
      ]);
      if (!empty($grants['Map']['View']['O'])) {
        $nodes->whereIn('hardware_config.id', $grants['Map']['View']['O']);
      } else if (!empty($grants['Map']['View']['C'])) {
        $nodes->whereIn('hardware_config.company_id', $grants['Map']['View']['C']);
      } else {
        return response()->json(['message' => 'access_denied', 'nodes' => [], 'grants' => $grants], 403);
      }
    }

    $nodes = $nodes->get()->toArray();

    // GET COMPANY
    $company = Company::where('id', $this->acc->company_id)->first();

    // GET SPECIFIC OPTIONS

    // marker outline color
    $opt_marker_outline_color = $company->get_option('map_marker_outline_color');

    foreach ($nodes as $index => &$node) {

      $data = [];
      $prev = [];

      // Manually adjust node's date_time field
      $dt = new \DateTime($node['date_time']);
      $dt->setTimezone($tzObj);
      $node['date_time'] = $dt->format('Y-m-d H:i:s');

      if ($node['field_name'] == null) {
        $node['field_name'] = 'Field';
      }

      // get each node's latest data row + previous row
      if ($node['node_type'] == 'Soil Moisture') {
        $rows = node_data::select('node_data.*', DB::raw('UNIX_TIMESTAMP(date_time) as sort_date'))
          ->where('probe_id', $node['node_address'])
          ->where('average', '>', 0)
          ->where('accumulative', '>', 0)
          ->orderBy('id', 'desc')
          ->limit(2)
          ->get()
          ->toArray();

        if (count($rows) == 2) {
          $data = $rows[0];
          $prev = $rows[1];
        }
      } else if ($node['node_type'] == 'Nutrients') {

        // $rows = nutri_data::where('node_address', $node['node_address'])
        // ->whereIn('identifier', [
        //   'M0_1', // sm
        //   'M1_1', // temp
        //   'M3_1', // nutrient
        //   'M4_1', // nutrient
        //   'M5_1', // nutrient
        //   'M6_1'  // nutrient
        // ])
        // ->orderBy('id', 'desc')
        // ->limit(4);

      } else if (in_array($node['node_type'], ['Wells', 'Water Meter'])) {

        $rows = node_data_meter::select('node_data_meters.*', DB::raw('UNIX_TIMESTAMP(date_time) as sort_date'))
          ->where('node_id', $node['node_address'])
          ->orderBy('idwm', 'desc')
          ->limit(2)
          ->get()
          ->toArray();

        if (count($rows) == 2) {
          $data = $rows[0];
          $prev = $rows[1];
        }
      }

      // TODO: Nutrients

      $defaults = [
        'base_node_address' => substr($node['node_address'], 0, strpos($node['node_address'], '-')), // chop off probe number
        'date_time' => "1970-01-01 00:00:00",
        'date_diff' => '',
        'sort_date' => 0,
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
        'status' => 0, 'full' => 70, 'refill' => 50, 'charging' => 0,
        'graph_start_date' => '',
        'nutrient_lower' => 0,
        'nutrient_upper' => 0,
        'nutrient_gauge' => 0,
        'perimeter' => null,
        'layer' => [
          'id' => $node['node_address'],
          'source' => $node['node_address'],
          'minzoom' => 0,
          'maxzoom' => 24,
          'type' => 'fill',
          'filter' => ['all', true, true],
          'paint' => [
            'fill-color' => '#27B4DC',
            'fill-opacity' => 0.5,
            'fill-outline-color' => '#27B4DC'
          ]
        ],
        'marker_outline_color' => $opt_marker_outline_color
      ];

      $sm_gradients = [
        [0,   [255, 0, 0]],    // 0-25%   red
        [25,  [255, 255, 0]],  // 25-50%  yellow
        [75,  [0, 255, 0]],    // 50-75%  green
        [100, [0, 0, 255]]     // 75-100% blue
      ];

      // get nutrient gauge value
      if (!empty($node['nutrient_template_id'])) {
        $results = Calculations::calcNutrientAverageGaugeValues($node['node_address'], $node['nutrient_template_id']);
        $node['nutrient_lower'] = !empty($results['nutrient_lower']) ? $results['nutrient_lower'] : 0;
        $node['nutrient_upper'] = !empty($results['nutrient_upper']) ? $results['nutrient_upper'] : 100;
        $node['nutrient_gauge'] = !empty($results['nutrient_gauge']) ? $results['nutrient_gauge'] : 0;
        $node['nutrient_avg']   = !empty($results['nutrient_avg'])   ? $results['nutrient_avg'] : 0;
      }

      if ($data) {

        // calc power state (uses UTC dates)
        $ps = Utils::calculatePowerState(NULL, $node['node_type'], (object)$data, (object)$prev);
        $node['charging'] = strpos($ps, ', charging') !== false ? 1 : 0;

        // localize datetime + calc last reading difference
        if ($data['date_time'] && $data['date_time'] != '1970-01-01 00:00:00') {
          $lr = new \DateTime($data['date_time']);
          $lr->setTimezone($tzObj);
          $data['date_time'] = $lr->format('Y-m-d H:i:s');
          $data['date_diff'] = $todays_date->diff($lr);
        }

        if ($node['node_type'] == 'Soil Moisture') {

          unset($data['id']);
          unset($data['probe_id']);
          unset($data['message_id_1']);
          unset($data['message_id_2']);
/*
          // format sm and temp readings
          for ($i = 1; $i < 15; $i++) {
            $data["sm".$i] = (float) bcdiv($data["sm".$i], 1, 2);
            $data["t".$i]  = (float) bcdiv($data["t".$i], 1, 2);
          }
*/
          if ($node['field_id']) {
            $moisture = $node['graph_model'] == 'ave' ? 'average' : 'accumulative';

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
            if (!empty($node['perimeter'])) {
              $color = Calculations::calcPercentageOfColorRange($data['status'], $sm_gradients);
              //$color = '#00FF00';
              $defaults['layer']['paint']['fill-color'] = $color;
              $defaults['layer']['paint']['fill-outline-color'] = $color;
            }
          }

          // array_merge:  a <- b <- c (where <- override direction)
          $items[$index] = array_merge($defaults, array_filter($node), array_filter($data));
        } else {

          unset($data['idwm']);
          unset($data['node_id']);
          unset($data['message_id']);

          $items[$index] = array_merge($defaults, array_filter($node), array_filter($data));
        }
      } else {

        $items[$index] = array_merge($defaults, array_filter($node));
      }

    }


    // sort by date
    if ($items) {
      usort($items, function ($a, $b) {
        return $b['sort_date'] - $a['sort_date'];
      });
    }

    // prevent activity logging for ajax map refreshes
    if (empty($request->is_refresh)) {
      $details = !empty($grants['Map']['View']['C']) ?
        'Company IDs: ' . implode(',',  $grants['Map']['View']['C']) : ($this->acc->is_admin ? 'All Objects' : 'Access Denied');
      $this->acc->logActivity('View', 'Map', $details);
    }

    return response()->json([
      'nodes'  => $items,
      'grants' => $grants
    ]);
  }
}
