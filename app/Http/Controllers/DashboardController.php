<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\hardware_config;
use App\Models\node_data;
use App\Models\node_data_meter;
use App\Models\nutri_data;
use App\Models\cultivars_management;
use App\Models\fields;
use App\Calculations;
use App\Utils;

class DashboardController extends Controller
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

    $request->validate([
      'cur_page'  => 'required|integer|min:1',
      'per_page'  => 'required|integer|min:5',
      'initial'   => 'required',
      'node_type' => 'required',
      'filter'    => 'nullable',
      'sort_by'   => 'required',
      'sort_dir'  => 'required'
    ]);

    // pagination
    $limit  = $request->per_page;
    $offset = ($request->cur_page - 1) * $limit;

    // sorting
    $sortBy  = $request->sort_by;
    $sortDir = $request->sort_dir;
log::debug($sortBy);
    // filtering
    $nodeType = $request->node_type;
    $filter   = !empty($request->filter) ? $request->filter : ''; // (optional filter param)

    $nodes  = [];
    $grants = [];
    $total  = 0;

    $this->tz = $this->timezones[$this->acc->timezone];
    if (!$this->tz) {
      $this->tz = 'UTC';
    }

    $tzObj = new \DateTimeZone($this->tz);
    $tzUTC = new \DateTimeZone('UTC');

    $todays_date = new \DateTime('now');
    $todays_date->setTimezone($tzObj);

    $t_minus_24 = (new \DateTime("now", $tzUTC))->sub(new \DateInterval('P1D'));
    $t_minus_48 = (new \DateTime("now", $tzUTC))->sub(new \DateInterval('P2D'));
    $t_minus_72 = (new \DateTime("now", $tzUTC))->sub(new \DateInterval('P3D'));

    $prefix = $this->acc->unit_of_measure == 1 ? 'mm' : 'in';

    $columns = [
      'companies.company_name',
      'fields.id AS field_id',
      'fields.field_name',
      'fields.full',
      'fields.refill',
      'fields.ni',
      'fields.nr',
      'fields.graph_type',
      'fields.graph_model',
      'fields.graph_start_date',
      'fields.wl_sensor_data',
      'hardware_config.date_time',
      'hardware_config.node_address',
      'hardware_config.node_type',
    ];

    if ($nodeType == 'All') {
      $columns[] = DB::raw('IFNULL(node_data.average, node_data_meters.pulse_1) as data1');
      $columns[] = DB::raw('IFNULL(node_data.accumulative, node_data_meters.pulse_2) as data2');
    } else if ($nodeType == 'Soil Moisture') {
      $columns[] = DB::raw('node_data.average as data1');
      $columns[] = DB::raw('node_data.accumulative as data2');
    } else {
      $columns[] = DB::raw('node_data_meters.pulse_1 as data1');
      $columns[] = DB::raw('node_data_meters.pulse_2 as data2');
    }

    // data sources viewable by admin only
    if ($this->acc->is_admin) {
      if ($nodeType == 'Soil Moisture') {
        $columns[] = DB::raw('IFNULL(node_data.message_id_1, "oth") as data_source');
      } else {
        $columns[] = DB::raw('"oth" as data_source');
      }
    }

    $sortBy = Utils::findColumnAlias(Utils::findFromPartial($columns, $sortBy, 'date_time'));


    $nodes = hardware_config::select($columns)
      ->leftJoin('fields', 'hardware_config.node_address', 'fields.node_id') // needed for the calculations
      ->leftJoin('companies', 'hardware_config.company_id', 'companies.id')  // needed for the entity column
      ->when($nodeType == 'All', function ($query) {
        $query
          ->leftJoin('node_data', function ($join) {
            $join
              ->on('hardware_config.node_address', '=', 'node_data.probe_id')
              ->on('hardware_config.date_time', '=', 'node_data.date_time');
          })
          ->leftJoin('node_data_meters', function ($join) {
            $join
              ->on('hardware_config.node_address', '=', 'node_data_meters.node_id')
              ->on('hardware_config.date_time', '=', 'node_data_meters.date_time');
          });
      })
     ->when($nodeType != 'All', function ($query) use ($nodeType) {
        if ($nodeType == 'Soil Moisture') {
          $query->leftJoin('node_data', function ($join) {
            $join
              ->on('hardware_config.node_address', '=', 'node_data.probe_id')
              ->on('hardware_config.date_time', '=', 'node_data.date_time');
          });
        } else {
          $query->leftJoin('node_data_meters', function ($join) {
            $join
              ->on('hardware_config.node_address', '=', 'node_data_meters.node_id')
              ->on('hardware_config.date_time', '=', 'node_data_meters.date_time');
          });
        }
        $query->where('hardware_config.node_type', $nodeType);
      })
      ->where(function ($query) use ($filter) {
        // filter by node_address, field_name or company_name
        $query->when($filter, function ($query, $filter) {
          $query->where('hardware_config.node_address', 'like', "%$filter%")
            ->orWhere('fields.field_name', 'like', "%$filter%")
            ->orWhere('companies.company_name', 'like', "%$filter%");
        });
      });

    if (!$this->acc->is_admin) {
      // permission check
      $grants = $this->acc->requestAccess([
        'Dashboard'     => ['p' => ['All']],
        'Soil Moisture' => ['p' => ['Graph']],
        'Well Controls' => ['p' => ['Graph']],
        'Meters'        => ['p' => ['Graph']]
      ]);

      if (!empty($grants['Dashboard']['View']['O'])) {
        $nodes->whereIn('hardware_config.id', $grants['Dashboard']['View']['O']);
      } else {
        $nodes = [];
      }
    }

    if ($nodes) {
      $total = $nodes->count();
      if ($total) {
        // sorting & pagination
        $nodes->orderBy($sortBy, $sortDir);
        $nodes = $nodes->skip($offset)->take($limit);
        //Log::debug($nodes->toSql());
        $nodes = $nodes->get();
      }
    }
    if ($nodes) {
      foreach ($nodes as $node) {
                Log::debug(node_data::where('probe_id', $node->node_address)->count());
          if (node_data::where('probe_id',$node->node_address)->count() > 0) {
            $dt = node_data::where('probe_id',$node->node_address)->select('date_time')->orderByDesc('id')->limit(1)->first();
                    if (isset($dt->date_time)) {
                        $node->date_time = $dt->date_time;
                      //  Log::debug($dt);
                      //  Log::debug($node->date_time);
                    }
            else
              $node->date_time = null;
          }

             //   Log::debug(node_data_meter::where('node_id', $node->node_address)->count());
          if (node_data_meter::where('node_id',$node->node_address)->count() > 0) {
            $dt = node_data_meter::where('node_id',$node->node_address)->select('date_time')->orderByDesc('idwm')->limit(1)->first();
            if (isset($dt->date_time))
              $node->date_time = $dt->date_time;
            else
              $node->date_time = null;
            }

               // Log::debug(nutri_data::where('node_address', $node->node_address)->count());
        if (nutri_data::where('node_address',$node->node_address)->count() > 0) {
          $dt = nutri_data::where('node_address',$node->node_address)->select('date_sampled')->orderByDesc('id')->limit(1)->first();
                    if (isset($dt->date_sampled)) {
                        $node->date_time = $dt->date_sampled;
                    //    Log::debug($dt);
                     //   Log::debug($node->date_time);
                    }
          else
            $node->date_time = null;
          }
        // Set default title if field row is missing
        if (empty($node->field_name)) {
          $node->field_name = 'Field ' . $node->node_address;
        }

        // Weather Station Data (ETo, etc)
        if (!empty($node->wl_sensor_data)) {
          $sensor_data = json_decode($node->wl_sensor_data, true);
          if ($sensor_data) {
            foreach ($sensor_data['sensors'] as $sensor) {
              foreach ($sensor['data'] as $sensor_data) {
                if (!empty($sensor_data['et_day'])) {
                  $node->eto = $sensor_data['et_day'];
                  break;
                } else {
                  $node->eto = "0.00";
                  break;
                }
              }
            }
          }
        } else {
          $node->eto = "0.00";
        }

        // localize datetime + calc last reading difference
        if ($node->date_time) {
          $lr = new \DateTime($node->date_time);
          $lr->setTimezone($tzObj);
          $node->date_time = $lr->format('Y-m-d H:i:s');
          $node->date_diff = $todays_date->diff($lr);
        } else {
          // quick way to set custom properties on laravel collection objects (array syntax)
          //$node->date_time = '1970-01-01 00:00:00';
          $node->date_diff = '';
        }

        // Calculations done only on SM Nodes with existing field rows
        if ($node->node_type == 'Soil Moisture' && $node->field_id) {

          // data1 == average, data2 == accumulative
          $moisture = $node->graph_model == 'ave' ? 'data1' : 'data2';

          // Calculate Status
          $result = Calculations::calcStatus(
            (float)$node->{$moisture},
            $node->field_id,
            (float)$node->full,    // joined field value
            (float)$node->refill,  // joined field value
            $todays_date,
            $tzObj,
            false /* debug */
          );

          if (is_array($result)) {
            $node->status = $result['status'];
          } else {
            $node->status = 0;
          }

          if ($node->status > 0 && $node->status < 100) {

            // Calculate Irri.Rec.
            $node->irri_rec = Calculations::calcIrriRec(
              (float)$node->{$moisture},
              (float)$result['upper_value'],
              $this->acc->unit_of_measure,
              $node->ni ?: 1,
              $node->nr ?: 1
            );
          } else {
            $node->irri_rec = "0.00{$prefix}";
          }

          // Calculate Three-day Deletion Rates
          $depletion = Calculations::calcDepletionRates(
            $node->node_address,
            $node->graph_type,
            $t_minus_24,
            $t_minus_48,
            $t_minus_72,
            $tzUTC
          );

          $aggregated_loss = ($depletion['one'] + $depletion['two'] + $depletion['three']) / 3;

          $node->one   = (float) bcdiv($node->status -  $aggregated_loss, 1, 2);
          $node->two   = (float) bcdiv($node->status - ($aggregated_loss * 2), 1, 2);
          $node->three = (float) bcdiv($node->status - ($aggregated_loss * 3), 1, 2);
        } else {
          $node->status = 0;
          $node->one = 0;
          $node->two = 0;
          $node->three = 0;
          $node->irri_rec = 0;
          $node->eto = 0;
        }

        unset($node->field_id);
        unset($node->full);
        unset($node->refill);
        unset($node->ni);
        unset($node->nr);
      } // end for

    } // end if

    // prevent activity logging for ajax map refreshes
    if (empty($request->initial)) {
      $details = !empty($grants['Dashboard']['View']['C']) ?
        'Company IDs: ' . implode(',',  $grants['Dashboard']['View']['C']) : ($this->acc->is_admin ? 'All Objects' : 'Access Denied');
      $this->acc->logActivity('View', 'Dashboard', $details);
    }

    $result = [
      'rows'   => $nodes,
      'total'  => $total,
    ];

    if (!empty($grants)) {
      $results['grants'] = $grants;
    }

    return response()->json($result);
  }
}
