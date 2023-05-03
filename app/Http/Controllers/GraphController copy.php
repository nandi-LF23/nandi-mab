<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\node_data;
use App\Models\node_data_meter;
use App\Models\nutrient_templates;
use App\Models\nutri_data;
use App\Models\fields;
use App\Models\hardware_config;
use App\Models\hardware_management;
use App\Models\cultivars_management;
use App\Models\cultivars;
use App\User;
use App\Utils;
use App\Calculations;
use Illuminate\Support\Carbon;
use DB;

// NOTES:
// Three strategies for handling incomplete graph data with gaps:
// 1.) Store the zero value as is (Introduces Dips in the Graph)
// 2.) Use the Previous Value (Lessens Accuracy)
// 3.) Don't store zero values (Introduces Gaps) (used currently)

class GraphController extends Controller
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

    // Soil Moisture / Nutrient Graph Data
    public function sm_graph(Request $request)
    {
        $request->validate([
            'node_address'     => 'required|string',
            'graph_type'       => 'nullable|string',
            'graph_start_date' => 'nullable',
            'sub_days'         => 'nullable',
            'is_initial'       => 'nullable',
            'selection_start'  => 'nullable',
            'selection_end'    => 'nullable'
        ]);

        $graph_data = [];
        $populated  = [];
        $grants     = [];

        $N03Avg = 0;

        // CUSTOM START DATE
        $graph_start_date = null;

        $start_date = null;
        $end_date = null;

        $hwconfig = hardware_config::where('node_address', $request->node_address)->first();
        $field = fields::where('node_id', $request->node_address)->first();

        // ensure node and field exists
        if (!$hwconfig || !$field) {
            return response()->json(['message' => 'nonexistent']);
        }

        // subsystem specific permission check
        if (!$this->acc->is_admin) {
            $subsystem = Utils::convertNodeTypeToSubsystem($hwconfig->node_type);
            $grants = $this->acc->requestAccess([$subsystem => ['p' => ['All'], 'o' => $hwconfig->id, 't' => 'O']]);
            if (empty($grants[$subsystem]['Graph']['O'])) {
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        // Get Node's Last Reading Date (UTC)
        if ($hwconfig->node_type == 'Soil Moisture') {
            $end_date = DB::table('node_data')->where('probe_id', $hwconfig->node_address)->orderBy('date_time', 'desc')->value('date_time');
        } else if ($hwconfig->node_type == 'Nutrients') {
            $end_date = DB::table('nutri_data')->where('node_address', $hwconfig->node_address)->orderBy('date_sampled', 'desc')->value('date_sampled');
        } else {
            return response()->json(['message' => 'invalid_node_type']);
        }

        // Probe has no data whatsoever, return
        if ($end_date == null) {
            return response()->json(['message' => 'no_data']);
        }

        // set timezone from user's timezone preferences (TIMEZONES ARE ONLY FOR DISPLAYING DATA! ALL DATETIMES ARE STORED AS UTC IN THE DB!)
        $this->tz = $this->timezones[$this->acc->timezone];
        if (!$this->tz) {
            $this->tz = 'UTC';
        }
        $tzObj = new \DateTimeZone($this->tz);

        // get related nodes (nodes with same node type and same base node address)
        $related_nodes = hardware_config::where('node_address', 'like', '%' . (explode('-', $request->node_address)[0]) . '%')
            ->where('node_type', $hwconfig->node_type)
            ->select(['node_address'])
            ->get()
            ->toArray();

        // Set graph type from either get parameter (via frontend dropdown) or field configuration
        $graph_type = !empty($request->graph_type) ? $request->graph_type : $field->graph_type;
        // handle situation where graph type reflects an old node type
        $graph_type = !empty($graph_type) && $hwconfig->node_type == 'Nutrients' && !in_array($graph_type, ['nutrient', 'nutrient_ppm', 'nutrient_type_avg', 'nutrient_sm_sep_levels', 'nutrient_sm_avg', 'nutrient_temp_avg', 'nutrient_ec_avg']) ? 'nutrient_type_avg' : $graph_type;
        $graph_type = !empty($graph_type) && $hwconfig->node_type == 'Soil Moisture' && !in_array($graph_type, ['sm', 'sum', 'ave', 'temp', 'stack', 'tech']) ? 'ave' : $graph_type;
        $graph_type = empty($graph_type) && $hwconfig->node_type == 'Nutrients' ? 'nutrient_type_avg' : $graph_type;
        $graph_type = empty($graph_type) && $hwconfig->node_type == 'Soil Moisture' ? 'ave' : $graph_type;

        // DETERMINE IF DATA IS CURRENT OR NOT (WHEN THE LATEST DATA IS OLDER THAN A DAY)
        $not_current = false;
        $now = new \DateTime('now');
        $latest = new \DateTime($end_date);
        $diff = $now->diff($latest);
        $gap = $diff->format("%a");
        if ($gap > 1) {
            $not_current = true;
        }

        // manual range selection overrides graph_start_date
        if (!empty($request->selection_start) && !empty($request->selection_end)) {

            // JS timestamps are in milliseconds, convert to seconds
            $start = new \DateTime();
            $end   = new \DateTime();

            // Convert Javascript Timestamps to Unix Timestamps
            $start_ts = floor($request->selection_start / 1000);
            $end_ts = floor($request->selection_end / 1000);

            $start->setTimestamp($start_ts);
            $staged_start_date = $start->format('Y-m-d H:i:s');

            // Remove User's Timezone Offset from incoming Timestamps (Timezone Offset was added in previous request)
            $start_ts -= $tzObj->getOffset($start);
            $end_ts   -= $tzObj->getOffset($end);

            $start->setTimestamp($start_ts);
            $end->setTimestamp($end_ts);

            // to be used for determining the resolution
            $diff = $end->diff($start);
            $diff_days = $diff->format("%a");

            if (!$diff_days) {
                $diff_days = '1';
            } // fallback if difference is 0

            // UTC Dates for Querying DB
            $start_date = $start->format('Y-m-d H:i:s');

            // override end date with user's selection
            $end_date   = $end->format('Y-m-d H:i:s');

            // Range Selections override the Custom User Start Date
            $graph_start_date = $staged_start_date;
        } else {

            // INITIAL | CUSTOM | SUBDAYS

            if (!empty($request->is_initial)) {
                // if initial request, try get start date from field configuration
                $graph_start_date = $field->graph_start_date;
            } else if (!empty($request->graph_start_date)) {
                // else try get custom user chosen start date 
                $graph_start_date = $request->graph_start_date;
            }

            // Custom Start Date Set?
            if ($graph_start_date) {

                $start = new \DateTime($graph_start_date);
                $end   = new \DateTime($end_date);

                // if for some reason the user decides to choose a date newer than the end date,
                // create a custom 7 day interval
                if ($start >= $end) {
                    $start = new \DateTime($end_date);
                    $start->sub(new \DateInterval("P7D"));
                }

                $diff = $end->diff($start);       // works
                $diff_days = $diff->format("%a");

                if (!$diff_days) {
                    $diff_days = '1';
                } // fallback if difference is 0

                $staged_start_date = $start->format('Y-m-d H:i:s');

                // Remove Timezone to ensure start time is 00:00
                $ts = $start->getTimestamp();
                $ts -= $tzObj->getOffset($start);
                $start->setTimestamp($ts);

                // UTC Dates for Querying DB
                $start_date = $start->format('Y-m-d H:i:s');
                $graph_start_date = $staged_start_date;
            } else {

                $diff_days = !empty($request->sub_days) ? $request->sub_days : '7';

                $latest = new \DateTime($end_date);
                $latest->sub(new \DateInterval("P{$diff_days}D"));

                // Remove Timezone to ensure start time is 00:00
                $ts = $latest->getTimestamp();
                $ts -= $tzObj->getOffset($latest);
                $latest->setTimestamp($ts);

                // UTC Dates for Querying DB
                $start_date = $latest->format('Y-m-d H:i:s');

                // if(!in_array($gap, [1, 7, 14, 30, 365])){
                //     $graph_start_date = $start_date;
                // }
            }
        }

        $node_data = [];
        $nutr_data = [];
        $nutr_data_keys = [];

        // FETCH GRAPH DATA

        if (in_array($graph_type, ['sum', 'ave', 'temp', 'sm', 'stack', 'tech'])) {

            $query = node_data::where('probe_id', $request->node_address)
                ->where('date_time', '>=', $start_date);
            if ($end_date) {
                $query->where('date_time', '<=', $end_date);
            }

            $resolution = Utils::calc_graph_data_resolution($diff_days, 'date_time');
            if ($resolution) {
                $query->where(function ($query) use ($resolution, $start_date, $end_date) {
                    $query->where('date_time', $start_date);
                    $query->orWhere('date_time', $end_date);
                    $query->orWhereRaw($resolution);
                });
            }

            $query->orderBy('date_time');

            //Log::debug(Utils::getQuery($query));

            $node_data = $query->get();
        } else if (in_array($graph_type, ['nutrient', 'nutrient_ppm', 'nutrient_type_avg', 'nutrient_sm_sep_levels', 'nutrient_sm_avg', 'nutrient_temp_avg', 'nutrient_ec_avg'])) {

            /*$query = DB::table('nutri_data')
                ->where('node_address', $request->node_address)
                ->where('date_sampled', '>=', $start_date);
            if ($end_date) {
                $query->where('date_sampled', '<=', $end_date);
            }*/
            $query = nutri_data::where('node_address', $request->node_address)
                ->where('date_sampled', '>=', $start_date);
            if ($end_date) {
                $query->where('date_sampled', '<=', $end_date);
            }

            $resolution = Utils::calc_graph_data_resolution($diff_days, 'date_sampled');
            if ($resolution) {
                $query->where(function ($query) use ($resolution, $start_date, $end_date) {
                    $query->where('date_sampled', $start_date);
                    $query->orWhere('date_sampled', $end_date);
                    $query->orWhereRaw($resolution);
                });
            }

            $query->orderBy('date_sampled');

            Log::debug(Utils::getQuery($query));

            $nutr_data = $query->get();
        } /*else if ($graph_type == 'nutrient_ppm_avg') {

            $query = DB::table('nutri_data')
                ->select([
                    DB::raw("SUM(CASE WHEN identifier='M3_1' THEN value END) as M3_1"),
                    DB::raw("SUM(CASE WHEN identifier='M4_1' THEN value END) as M4_1"),
                    DB::raw("SUM(CASE WHEN identifier='M5_1' THEN value END) as M5_1"),
                    DB::raw("SUM(CASE WHEN identifier='M6_1' THEN value END) as M6_1"),
                    'date_reported',
                    'date_sampled'
                ])
                ->where('node_address', $request->node_address)
                ->where('date_sampled', '>=', $start_date);
            if ($end_date) {
                $query->where('date_sampled', '<=', $end_date);
            }

            $resolution = Utils::calc_graph_data_resolution($diff_days, 'date_sampled');
            if ($resolution) {
                $query->where(function ($query) use ($resolution, $start_date, $end_date) {
                    $query->where('date_sampled', $start_date);
                    $query->orWhere('date_sampled', $end_date);
                    $query->orWhereRaw($resolution);
                });
            }

            $query->groupBy('date_reported', 'date_sampled');
            $query->orderBy('date_sampled');

            //Log::debug(Utils::getQuery($query));

            $nutr_data = $query->get();
        }*/

        $uom = isset($this->acc->unit_of_measure) ? $this->acc->unit_of_measure : 1;
        $uom_suffix = $uom == 1 ? 'mm' : '"';
        $uom_factor = $uom == 1 ? 25 : 1;
        $uom_symbol = $uom == 1 ? 'C' : 'F';

        // POPULATION DETECTION
        switch ($graph_type) { // Graph Type
            case 'sum':
                $index = 0;
                foreach ($node_data as $item) {
                    if (!empty($item->date_time) && !empty($item->accumulative)) {
                        $populated[$index] = true;
                    }
                    $index++;
                }
                break;
            case 'ave':
                $index = 0;
                foreach ($node_data as $item) {
                    if (!empty($item->date_time) && !empty($item->average)) {
                        $populated[$index] = true;
                    }
                    $index++;
                }
                break;
            case 'temp':
                $index = 0;
                foreach ($node_data as $item) {
                    if (
                        !empty($item->date_time) &&
                        (!empty($item->t1)  || !empty($item->t2)  || !empty($item->t3)  || !empty($item->t4)  || !empty($item->t5)  ||
                            !empty($item->t6)  || !empty($item->t7)  || !empty($item->t8)  || !empty($item->t9)  || !empty($item->t10) ||
                            !empty($item->t11) || !empty($item->t12) || !empty($item->t13) || !empty($item->t14) || !empty($item->t15))
                    ) {
                        $populated[$index] = true;
                    }
                    $index++;
                }
                break;
            case 'sm':
                $index = 0;
                foreach ($node_data as $item) {
                    if (
                        !empty($item->date_time) &&
                        (!empty($item->sm1)  || !empty($item->sm2)  || !empty($item->sm3)  || !empty($item->sm4)  || !empty($item->sm5)  ||
                            !empty($item->sm6)  || !empty($item->sm7)  || !empty($item->sm8)  || !empty($item->sm9)  || !empty($item->sm10) ||
                            !empty($item->sm11) || !empty($item->sm12) || !empty($item->sm13) || !empty($item->sm14) || !empty($item->sm15) ||
                            !empty($item->average))
                    ) {
                        $populated[$index] = true;
                    }
                    $index++;
                }
                break;
            case 'stack':
                $index = 0;
                foreach ($node_data as $item) {
                    if (
                        !empty($item->date_time) &&
                        (!empty($item->sm1)  || !empty($item->sm2)  || !empty($item->sm3)  || !empty($item->sm4)  || !empty($item->sm5)  ||
                            !empty($item->sm6)  || !empty($item->sm7)  || !empty($item->sm8)  || !empty($item->sm9)  || !empty($item->sm10) ||
                            !empty($item->sm11) || !empty($item->sm12) || !empty($item->sm13) || !empty($item->sm14) || !empty($item->sm15))
                    ) {
                        $populated[$index] = true;
                    }
                    $index++;
                }
                break;
            case 'tech':
                $index = 0;
                foreach ($node_data as $item) {
                    if (!empty($item->date_time)) {
                        $populated[$index] = true;
                    }
                    $index++;
                }
                break;

                // nothing needed here for the nutrient graphs
            case 'nutrient':
            case 'nutrient_ppm':
                //case 'nutrient_ppm_avg':
            case 'nutrient_type_avg':
            case 'nutrient_sm_sep_levels':
            case 'nutrient_sm_avg';
            case 'nutrient_temp_avg';
            case 'nutrient_ec_avg';
                break;

            default:
                return response()->json(['error' => 'Invalid Graph Type Request']);
                break;
        }

        $x_min = 1000;
        $x_max = 0;

        $first_date = '';
        $last_date = '';

        if (in_array($graph_type, ['sum', 'ave', 'temp', 'sm', 'stack', 'tech'])) {

            // GET FIRST AND LAST READING DATES
            if ($node_data && $node_data->count()) {
                $first = $node_data->first();
                $node_first_date = new \DateTime($first->date_time);
                $node_first_date->setTimezone(new \DateTimeZone($this->tz));
                $first_date = $node_first_date->format('Y-m-d H:i:s');
                $x_min = ($node_first_date->getTimestamp() + $node_first_date->getOffset()) * 1000;

                $last = $node_data->last();
                $node_last_date = new \DateTime($last->date_time);
                // Localized Date
                $node_last_date->setTimezone(new \DateTimeZone($this->tz));
                $last_date = $node_last_date->format('Y-m-d H:i:s');
                $x_max = ($node_last_date->getTimestamp() + $node_last_date->getOffset()) * 1000;
            }
        } else if (in_array($graph_type, ['nutrient', 'nutrient_ppm', 'nutrient_type_avg', 'nutrient_sm_sep_levels', 'nutrient_sm_avg', 'nutrient_temp_avg', 'nutrient_ec_avg'])) {

            // GET FIRST AND LAST READING DATES
            $nutr_count = count($nutr_data);
            if ($nutr_data && $nutr_count) {
                $first = $nutr_data->first();
                $nutr_first_date = new \DateTime($first->date_sampled);
                $nutr_first_date->setTimezone(new \DateTimeZone($this->tz));
                $first_date = $nutr_first_date->format('Y-m-d H:i:s');
                $x_min = ($nutr_first_date->getTimestamp() + $nutr_first_date->getOffset()) * 1000;

                $last = $nutr_data->last();
                $nutr_last_date = new \DateTime($last->date_sampled);
                // Localized Date
                $nutr_last_date->setTimezone(new \DateTimeZone($this->tz));
                $last_date = $nutr_last_date->format('Y-m-d H:i:s');
                $x_max = ($nutr_last_date->getTimestamp() + $nutr_last_date->getOffset()) * 1000;
            }
        }

        // SM SPECIFIC
        $y_min = 1000;
        $y_max = 0;

        // CULTIVARS PROCESSING
        $stages = [];

        if (in_array($graph_type, ['sum', 'ave', 'temp', 'sm', 'stack'])) {

            // GET GROWTH STAGES FOR CORRESPONDING FIELD
            $growth_stages = cultivars_management::where('field_id', $field->id)
                ->join('cultivars', 'cultivars_management.id', '=', 'cultivars.cultivars_management_id')
                ->orderBy('stage_start_date', 'asc')
                ->get();

            $has_cultivars = $growth_stages->count() > 0;

            // PROCESS GROWTH STAGES SERIES
            if ($has_cultivars) {

                $capacity = $field->full - $field->refill;

                foreach ($growth_stages as $stage) {
                    $stage_start_date = new \DateTime($stage->stage_start_date);
                    $stage_start_date->setTimezone(new \DateTimeZone($this->tz));

                    $low  = (float)($field->refill + ($capacity * ($stage->lower / 100)));
                    $high = (float)($field->refill + ($capacity * ($stage->upper / 100)));

                    $y_min = $low < $y_min ? $low : $y_min;
                    $y_max = $high > $y_max ? $high : $y_max;

                    $stages[] = [
                        'x' => ($stage_start_date->getTimestamp() + $stage_start_date->getOffset()) * 1000, 'low' => $low, 'high' => $high
                    ];
                }

                // ADD CALCULATED LAST DATE (AND ENSURE IN RANGE OR CLIP WITH NODE DATA END DATE)
                $last = $growth_stages->last();
                $ld = new \DateTime($last->stage_start_date);
                $ld->setTimezone(new \DateTimeZone($this->tz));
                $ld->add(new \DateInterval('P' . $last->duration . 'D'));

                $ld = ($ld->getTimestamp() + $ld->getOffset()) * 1000;

                $low  = (float)($field->refill + ($capacity * ($last->lower / 100)));
                $high = (float)($field->refill + ($capacity * ($last->upper / 100)));

                $y_min = $low < $y_min ? $low : $y_min;
                $y_max = $high > $y_max ? $high : $y_max;

                $stages[] = ['x' => $ld, 'low' => $low, 'high' => $high];

                usort($stages, function ($a, $b) {
                    return $a['x'] - $b['x'];
                });
            }
        }

        // PLOT OPTIONS
        $plotOptions = [
            'line' =>  [
                'allowPointSelect' => false,
            ],
            'series' =>  [
                // 'stacking' => 'normal',
                'allowPointSelect' => false,
                'boostThreshold' => 0,
                'turboThreshold' => 0
            ]
        ];

        // CHART DATA PROCESSING
        switch ($graph_type) {
            case "sum":

                $sum = array();
                $index = 0;

                $y_min = 1000;
                $y_max = 0;

                foreach ($node_data as $series) {
                    if (!empty($series->accumulative) && !empty($populated[$index])) {
                        $dt = new \DateTime($series->date_time);
                        $dt->setTimezone(new \DateTimeZone($this->tz));
                        $y_min = $series->accumulative < $y_min ? $series->accumulative : $y_min;
                        $y_max = $series->accumulative > $y_max ? $series->accumulative : $y_max;
                        array_push($sum, [
                            'x' => ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            'y' => (float)$series->accumulative
                        ]);
                    }
                    $index++;
                }

                $graph_data = [
                    'graph' => array(
                        'series' => [
                            ['name' => 'Sum', 'color' => '#93a9d0', 'type' => 'spline', 'data' => $sum]
                        ],
                        'yAxis' => array(
                            'title' => array(
                                'text' => 'Percentage Sum'
                            )
                        ),
                        'title' => [
                            'text' => $field->field_name . ' - Percentage Sum - ' . $request->node_address,
                            'widthAdjust' => -200
                        ],
                        'plotOptions' => $plotOptions
                    ),
                    // 'x_max' => $x_max,
                    // 'x_min' => $x_min,
                    // 'y_max' => (float)($y_max) + 1,
                    // 'y_min' => (float)($y_min) - 1,
                    // 'full'  => $field->full,
                    // 'refill' => $field->refill,
                    'x_max' => (float)number_format($x_max, 2, '.', ''),
                    'x_min' => (float)number_format($x_min, 2, '.', ''),
                    'y_max' => (float)number_format($y_max, 2, '.', '') + 1,
                    'y_min' => (float)number_format($y_min, 2, '.', '') - 1,
                    'full'  => (float)number_format($field->full, 2, '.', ''),
                    'refill' => (float)number_format($field->refill, 2, '.', '')
                ];
                break;

            case "ave":
                $ave = array();
                $index = 0;

                $y_max = $field->full > $y_max ? $field->full : $y_max;
                $y_min = $field->refill < $y_min ? $field->refill : $y_min;

                $capacity = $field->full - $field->refill;
                $stageCount = count($stages);

                foreach ($node_data as $series) {
                    if (!empty($populated[$index])) {
                        $dt = new \DateTime($series->date_time);
                        $dt->setTimezone(new \DateTimeZone($this->tz));

                        $inAnyStage = false;

                        $y_max = $series->average > $y_max ? $series->average : $y_max;
                        $y_min = $series->average < $y_min ? $series->average : $y_min;

                        $xVal = ($dt->getTimestamp() + $dt->getOffset()) * 1000;

                        $rec = ['x' => $xVal, 'y' => (float)$series->average, 'status' => ''];

                        // Calculate Status per Data Point (if Growth Stages Exist)
                        if ($stageCount >= 2) {
                            for ($i = 0; $i < $stageCount - 1; $i++) {
                                // falls within growth stage
                                if ($xVal >= $stages[$i]['x'] && $xVal <= $stages[$i + 1]['x']) {
                                    $capacity = $stages[$i]['high'] - $stages[$i]['low'];
                                    $rec['status'] = (float)number_format($capacity ? ((($rec['y'] - $stages[$i]['low']) / $capacity) * 100) : 0, 2, '.', '');
                                    $inAnyStage = true;
                                    break;
                                }
                            }

                            // falls outside growth stage
                            if (!$inAnyStage) {
                                $capacity = $field->full - $field->refill;
                                $rec['status'] = (float)number_format($capacity ? ((($rec['y'] - $field->refill) / $capacity) * 100) : 0, 2, '.', '');
                            }
                        } else {
                            // (float)number_format($x_max, 2, '.', '')
                            $rec['status'] = (float)number_format($capacity ? ((($rec['y'] - $field->refill) / $capacity) * 100) : 0, 2, '.', '');
                        }

                        array_push($ave, $rec);
                    }
                    $index++;
                }

                $plotOptions['areasplinerange'] = [
                    'series' => [
                        'pointPlacement' => 'on'
                    ],
                    'fillColor' =>  [
                        'linearGradient' =>  [0, 0, 0, 300],
                        'stops' =>  [
                            [0, 'rgba(1, 164, 222, 0.5)'],
                            [1, 'rgba(1, 164, 222, 0.1)'],
                        ]
                    ]
                ];

                $series = [['name' => 'Average', 'color' => 'black', 'type' => 'spline', 'data' => $ave]];

                if ($has_cultivars) {
                    $series[] = ['name' => 'Stages', 'type' => 'areasplinerange', 'data' => $stages];
                }

                $graph_data = [
                    'graph' => array(
                        'series' => $series,
                        'yAxis' => array(
                            'title' => array(
                                'text' => 'Average Percentage'
                            )
                        ),
                        'title' => [
                            'text' => $field->field_name . ' - Percentage Average - ' . $request->node_address,
                            'widthAdjust' => -200
                        ],
                        'plotOptions' => $plotOptions
                    ),
                    'x_max' => (float)number_format($x_max, 2, '.', ''),
                    'x_min' => (float)number_format($x_min, 2, '.', ''),
                    'y_max' => (float)number_format($y_max, 2, '.', '') + 1,
                    'y_min' => (float)number_format($y_min, 2, '.', '') - 1,
                    'full'  => (float)number_format($field->full, 2, '.', ''),
                    'refill' => (float)number_format($field->refill, 2, '.', '')
                ];
                break;

            case 'sm':

                $y_min = 100;
                $y_max = 0;

                // separate levels
                for ($i = 1; $i <= 15; $i++) {
                    ${'sm' . $i} = array();
                    $index = 0;
                    foreach ($node_data as $series) {
                        if (!empty($series->{'sm' . $i}) && !empty($populated[$index])) {
                            $dt = new \DateTime($series->date_time);
                            $dt->setTimezone(new \DateTimeZone($this->tz));
                            $point = [
                                'x' => ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                                'y' => (float)number_format($series->{'sm' . $i}, 2, '.', ''), "grp$i" => "g$i"
                            ];
                            $y_min = $series->{'sm' . $i} < $y_min ? $series->{'sm' . $i} : $y_min;
                            $y_max = $series->{'sm' . $i} > $y_max ? $series->{'sm' . $i} : $y_max;
                            array_push(${'sm' . $i}, $point);
                        }
                        $index++;
                    }
                }

                // average (requested by Brad and included along with SMs for analysis)
                $ave = [];

                foreach ($node_data as $series) {
                    //if (!empty($series->average) && !empty($populated[$index])){
                    $dt = new \DateTime($series->date_time);
                    $dt->setTimezone(new \DateTimeZone($this->tz));
                    $xVal = ($dt->getTimestamp() + $dt->getOffset()) * 1000;
                    $point = ['x' => $xVal, 'y' => (float)$series->average, "grpavg" => 'avg'];
                    $ave[] = $point;
                    //}
                }


                $grpDefs = function ($grp) {
                    return [
                        'draggableX' => false,
                        'draggableY' => false,
                        'groupBy' => $grp
                    ];
                };

                $getDefaultSMSeries = function () use ($uom_factor, $uom_suffix, $sm1, $sm2, $sm3, $sm4, $sm5, $sm6, $sm7, $sm8, $sm9, $sm10, $sm11, $sm12, $sm13, $sm14, $sm15, $ave) {
                    return [
                        ['name' => "S.M " . $uom_factor * 4  . $uom_suffix, 'type' => 'spline', 'data' => (float)number_format($sm1, 2, '.', ''),  'dragDrop' => $grpDefs('grp1')],
                        ['name' => "S.M " . $uom_factor * 8  . $uom_suffix, 'type' => 'spline', 'data' => (float)number_format($sm2, 2, '.', ''),  'dragDrop' => $grpDefs('grp2')],
                        ['name' => "S.M " . $uom_factor * 12 . $uom_suffix, 'type' => 'spline', 'data' => (float)number_format($sm3, 2, '.', ''),  'dragDrop' => $grpDefs('grp3')],
                        ['name' => "S.M " . $uom_factor * 16 . $uom_suffix, 'type' => 'spline', 'data' => (float)number_format($sm4, 2, '.', ''),  'dragDrop' => $grpDefs('grp4')],
                        ['name' => "S.M " . $uom_factor * 20 . $uom_suffix, 'type' => 'spline', 'data' => (float)number_format($sm5, 2, '.', ''),  'dragDrop' => $grpDefs('grp5')],

                        ['name' => "S.M " . $uom_factor * 24 . $uom_suffix, 'type' => 'spline', 'data' => (float)number_format($sm6, 2, '.', ''),  'dragDrop' => $grpDefs('grp6')],
                        ['name' => "S.M " . $uom_factor * 28 . $uom_suffix, 'type' => 'spline', 'data' => (float)number_format($sm7, 2, '.', ''),  'dragDrop' => $grpDefs('grp7')],
                        ['name' => "S.M " . $uom_factor * 32 . $uom_suffix, 'type' => 'spline', 'data' => (float)number_format($sm8, 2, '.', ''),  'dragDrop' => $grpDefs('grp8')],
                        ['name' => "S.M " . $uom_factor * 36 . $uom_suffix, 'type' => 'spline', 'data' => (float)number_format($sm9, 2, '.', ''),  'dragDrop' => $grpDefs('grp9')],
                        ['name' => "S.M " . $uom_factor * 40 . $uom_suffix, 'type' => 'spline', 'data' => (float)number_format($sm10, 2, '.', ''), 'dragDrop' => $grpDefs('grp10')],

                        ['name' => "S.M " . $uom_factor * 44 . $uom_suffix, 'type' => 'spline', 'data' => (float)number_format($sm11, 2, '.', ''), 'dragDrop' => $grpDefs('grp11')],
                        ['name' => "S.M " . $uom_factor * 48 . $uom_suffix, 'type' => 'spline', 'data' => (float)number_format($sm12, 2, '.', ''), 'dragDrop' => $grpDefs('grp12')],
                        ['name' => "S.M " . $uom_factor * 52 . $uom_suffix, 'type' => 'spline', 'data' => (float)number_format($sm13, 2, '.', ''), 'dragDrop' => $grpDefs('grp13')],
                        ['name' => "S.M " . $uom_factor * 56 . $uom_suffix, 'type' => 'spline', 'data' => (float)number_format($sm14, 2, '.', ''), 'dragDrop' => $grpDefs('grp14')],
                        ['name' => "S.M " . $uom_factor * 60 . $uom_suffix, 'type' => 'spline', 'data' => (float)number_format($sm15, 2, '.', ''), 'dragDrop' => $grpDefs('grp15')],
                        ['name' => 'Average', 'type' => 'spline', 'data' => $ave, 'dragDrop' => $grpDefs('grpavg')]
                    ];
                };

                $hwm = hardware_management::where('id', $hwconfig->hardware_management_id)->first();
                if ($hwm) {
                    // Has Device defined
                    $series = [];
                    $spacings = [];
                    for ($i = 1; $i <= 15; $i++) {
                        if ($hwm->{"sensor_placing_$i"} == 'on') {
                            $spacings[] = $i;
                        }
                    }
                    $count = count($spacings);
                    $colors = ["#71588f", "#93a9d0", "#aa4644", "#89a54e", "#71588f", "#4298af", "#db843d", "#d09392", "#bacd96", "#a99bbe"];
                    // $color1 = '';
                    if ($count) {
                        for ($i = 1; $i <= $count; $i++) {
                            $series[] = [
                                'name' => "S.M " . ($uom_factor * ($spacings[$i - 1] * 4)) . $uom_suffix,
                                'color' => $colors[$i],
                                'type' => 'spline',
                                'data' => ${"sm" . $i},
                                'dragDrop' => $grpDefs("grp$i"),
                            ];
                        }

                        // if ($series[$i]['name'] == "S.M 100mm") {
                        //     $color1 = 'yellow';
                        // }

                        // Average
                        $series[] = [
                            'name' => 'Average',
                            'color' => '#000000',
                            'type' => 'spline',
                            'data' => $ave,
                            'dragDrop' => $grpDefs('grpavg'),
                        ];
                    } else {
                        // Fallback
                        $series = $getDefaultSMSeries();
                    }
                } else {
                    // Fallback
                    $series = $getDefaultSMSeries();
                }

                $graph_data = [
                    'graph' => [
                        'series' => $series,
                        'yAxis' => [
                            'title' => [
                                'text' => 'Percentage'
                            ]
                        ],
                        'title' => [
                            'text' => $field->field_name . ' - Separate Levels (%) - ' . $request->node_address,
                            'widthAdjust' => -200
                        ],
                        'plotOptions' => $plotOptions
                    ],
                    'x_max' => (float)number_format($x_max, 2, '.', ''),
                    'x_min' => (float)number_format($x_min, 2, '.', ''),
                    'y_max' => (float)number_format($y_max, 2, '.', '') + 1,
                    'y_min' => (float)number_format($y_min, 2, '.', '') - 1,
                    'full'  => (float)number_format($field->full, 2, '.', ''),
                    'refill' => (float)number_format($field->refill, 2, '.', '')
                ];

                break;

            case 'stack':

                $y_min = 100;
                $y_max = 0;

                // separate levels
                for ($i = 1; $i <= 15; $i++) {
                    ${'sm' . $i} = array();
                    $index = 0;
                    foreach ($node_data as $series) {
                        if (!empty($series->{'sm' . $i}) && !empty($populated[$index])) {
                            $dt = new \DateTime($series->date_time);
                            $dt->setTimezone(new \DateTimeZone($this->tz));
                            $point = [
                                'x' => ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                                'y' => (float)number_format($series->{'sm' . $i}, 2, '.', ''), "grp$i" => "g$i"
                            ];
                            $y_min = $series->{'sm' . $i} < $y_min ? $series->{'sm' . $i} : $y_min;
                            $y_max = $series->{'sm' . $i} > $y_max ? $series->{'sm' . $i} : $y_max;
                            array_push(${'sm' . $i}, $point);
                        }
                        $index++;
                    }
                }

                // average (requested by Brad and included along with SMs for analysis)
                // $ave = [];

                foreach ($node_data as $series) {
                    //if (!empty($series->average) && !empty($populated[$index])){
                    $dt = new \DateTime($series->date_time);
                    $dt->setTimezone(new \DateTimeZone($this->tz));
                    $xVal = ($dt->getTimestamp() + $dt->getOffset()) * 1000;
                    // $point = ['x' => $xVal, 'y' => (float)$series->average, "grpavg" => 'avg'];
                    // $ave[] = $point;
                    //}
                }


                $grpDefs = function ($grp) {
                    return [
                        'draggableX' => false,
                        'draggableY' => false,
                        'groupBy' => $grp
                    ];
                };

                $getDefaultSMSeries = function () use ($uom_factor, $uom_suffix, $sm1, $sm2, $sm3, $sm4, $sm5, $sm6, $sm7, $sm8, $sm9, $sm10, $sm11, $sm12, $sm13, $sm14, $sm15) {
                    return [
                        ['name' => "S.M " . $uom_factor * 4  . $uom_suffix, 'type' => 'spline', 'data' => $sm1,  'dragDrop' => $grpDefs('grp1'), 'stack' => 'grp'],
                        ['name' => "S.M " . $uom_factor * 8  . $uom_suffix, 'type' => 'spline', 'data' => $sm2,  'dragDrop' => $grpDefs('grp2'), 'stack' => 'grp'],
                        ['name' => "S.M " . $uom_factor * 12 . $uom_suffix, 'type' => 'spline', 'data' => $sm3,  'dragDrop' => $grpDefs('grp3'), 'stack' => 'grp'],
                        ['name' => "S.M " . $uom_factor * 16 . $uom_suffix, 'type' => 'spline', 'data' => $sm4,  'dragDrop' => $grpDefs('grp4'), 'stack' => 'grp'],
                        ['name' => "S.M " . $uom_factor * 20 . $uom_suffix, 'type' => 'spline', 'data' => $sm5,  'dragDrop' => $grpDefs('grp5'), 'stack' => 'grp'],

                        ['name' => "S.M " . $uom_factor * 24 . $uom_suffix, 'type' => 'spline', 'data' => $sm6,  'dragDrop' => $grpDefs('grp6'), 'stack' => 'grp'],
                        ['name' => "S.M " . $uom_factor * 28 . $uom_suffix, 'type' => 'spline', 'data' => $sm7,  'dragDrop' => $grpDefs('grp7'), 'stack' => 'grp'],
                        ['name' => "S.M " . $uom_factor * 32 . $uom_suffix, 'type' => 'spline', 'data' => $sm8,  'dragDrop' => $grpDefs('grp8'), 'stack' => 'grp'],
                        ['name' => "S.M " . $uom_factor * 36 . $uom_suffix, 'type' => 'spline', 'data' => $sm9,  'dragDrop' => $grpDefs('grp9'), 'stack' => 'grp'],
                        ['name' => "S.M " . $uom_factor * 40 . $uom_suffix, 'type' => 'spline', 'data' => $sm10, 'dragDrop' => $grpDefs('grp10'), 'stack' => 'grp'],

                        ['name' => "S.M " . $uom_factor * 44 . $uom_suffix, 'type' => 'spline', 'data' => $sm11, 'dragDrop' => $grpDefs('grp11'), 'stack' => 'grp'],
                        ['name' => "S.M " . $uom_factor * 48 . $uom_suffix, 'type' => 'spline', 'data' => $sm12, 'dragDrop' => $grpDefs('grp12'), 'stack' => 'grp'],
                        ['name' => "S.M " . $uom_factor * 52 . $uom_suffix, 'type' => 'spline', 'data' => $sm13, 'dragDrop' => $grpDefs('grp13'), 'stack' => 'grp'],
                        ['name' => "S.M " . $uom_factor * 56 . $uom_suffix, 'type' => 'spline', 'data' => $sm14, 'dragDrop' => $grpDefs('grp14'), 'stack' => 'grp'],
                        ['name' => "S.M " . $uom_factor * 60 . $uom_suffix, 'type' => 'spline', 'data' => $sm15, 'dragDrop' => $grpDefs('grp15'), 'stack' => 'grp']
                    ];
                };

                $hwm = hardware_management::where('id', $hwconfig->hardware_management_id)->first();
                if ($hwm) {
                    // Has Device defined
                    $series = [];
                    $spacings = [];
                    for ($i = 1; $i <= 15; $i++) {
                        if ($hwm->{"sensor_placing_$i"} == 'on') {
                            $spacings[] = $i;
                        }
                    }
                    $colors = ["#71588f", "#93a9d0", "#aa4644", "#89a54e", "#71588f", "#4298af", "#db843d", "#d09392", "#bacd96", "#a99bbe"];
                    $count = count($spacings);
                    if ($count) {
                        for ($i = 1; $i <= $count; $i++) {
                            $series[] = [
                                'name' => "S.M " . ($uom_factor * ($spacings[$i - 1] * 4)) . $uom_suffix,
                                'color' => $colors[$i],
                                'type' => 'spline',
                                'data' => ${"sm" . $i},
                                'dragDrop' => $grpDefs("grp$i"),
                            ];
                        }
                        // Average
                        // $series[] = [
                        //     'name' => 'Average',
                        //     'type' => 'spline',
                        //     'data' => $ave,
                        //     'dragDrop' => $grpDefs('grpavg'),
                        // ];
                    } else {
                        // Fallback
                        $series = $getDefaultSMSeries();
                    }
                } else {
                    // Fallback
                    $series = $getDefaultSMSeries();
                }

                $graph_data = [
                    'graph' => [
                        'series' => $series,
                        'yAxis' => [
                            'title' => [
                                'text' => 'Percentage'
                            ]
                        ],
                        'title' => [
                            'text' => $field->field_name . ' - Separate Levels (%) - ' . $request->node_address,
                            'widthAdjust' => -200
                        ],
                        'plotOptions' => $plotOptions
                    ],
                    'x_max' => $x_max,
                    'x_min' => $x_min,
                    'y_max' => (float)($y_max) + 1,
                    'y_min' => (float)($y_min) - 1,
                    'full'  => $field->full,
                    'refill' => $field->refill
                ];

                break;

            case 'temp':

                $y_min = 100;
                $y_max = 0;

                for ($i = 1; $i <= 15; $i++) {
                    ${'t' . $i} = array();

                    $index = 0;
                    foreach ($node_data as $series) {
                        $user = Auth::user();

                        if ($user->unit_of_measure  == 2) {
                            $seriesCalc = $series->{'t' . $i} * (9 / 5) + 32;
                        } else if ($user->unit_of_measure  == 1) {
                            $seriesCalc = $series->{'t' . $i};
                        }

                        if (!empty($series->{'t' . $i}) && !empty($populated[$index])) {
                            $dt = new \DateTime($series->date_time);
                            $dt->setTimezone(new \DateTimeZone($this->tz));
                            $y_min = $series->{'t' . $i} < $y_min ? $series->{'t' . $i} : $y_min;
                            $y_max = $series->{'t' . $i} > $y_max ? $series->{'t' . $i} : $y_max;
                            array_push(${'t' . $i}, [
                                'x' => ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                                'y' => (float)number_format($seriesCalc, 1, '.', ''),
                            ]);
                        }
                        $index++;
                    }
                }

                $getDefaultTempSeries = function () use ($uom_factor, $uom_suffix, $t1, $t2, $t3, $t4, $t5, $t6, $t7, $t8, $t9, $t10, $t11, $t12, $t13, $t14, $t15) {
                    return [
                        ['name' => "Temp " . $uom_factor * 4  . $uom_suffix, 'type' => 'spline', 'data'  =>  $t1],
                        ['name' => "Temp " . $uom_factor * 8  . $uom_suffix, 'type' => 'spline', 'data'  =>  $t2],
                        ['name' => "Temp " . $uom_factor * 12 . $uom_suffix, 'type' => 'spline', 'data'  =>  $t3],
                        ['name' => "Temp " . $uom_factor * 16 . $uom_suffix, 'type' => 'spline', 'data'  =>  $t4],
                        ['name' => "Temp " . $uom_factor * 20 . $uom_suffix, 'type' => 'spline', 'data'  =>  $t5],

                        ['name' => "Temp " . $uom_factor * 24 . $uom_suffix, 'type' => 'spline', 'data'  =>  $t6],
                        ['name' => "Temp " . $uom_factor * 28 . $uom_suffix, 'type' => 'spline', 'data'  =>  $t7],
                        ['name' => "Temp " . $uom_factor * 32 . $uom_suffix, 'type' => 'spline', 'data'  =>  $t8],
                        ['name' => "Temp " . $uom_factor * 36 . $uom_suffix, 'type' => 'spline', 'data'  =>  $t9],
                        ['name' => "Temp " . $uom_factor * 40 . $uom_suffix, 'type' => 'spline', 'data'  =>  $t10],

                        ['name' => "Temp " . $uom_factor * 44 . $uom_suffix, 'type' => 'spline', 'data'  =>  $t11],
                        ['name' => "Temp " . $uom_factor * 48 . $uom_suffix, 'type' => 'spline', 'data'  =>  $t12],
                        ['name' => "Temp " . $uom_factor * 52 . $uom_suffix, 'type' => 'spline', 'data'  =>  $t13],
                        ['name' => "Temp " . $uom_factor * 56 . $uom_suffix, 'type' => 'spline', 'data'  =>  $t14],
                        ['name' => "Temp " . $uom_factor * 60 . $uom_suffix, 'type' => 'spline', 'data'  =>  $t15],
                    ];
                };

                $hwm = hardware_management::where('id', $hwconfig->hardware_management_id)->first();
                if ($hwm) {
                    // Has Device defined
                    $series = [];
                    $spacings = [];
                    for ($i = 1; $i <= 15; $i++) {
                        if ($hwm->{"sensor_placing_$i"} == 'on') {
                            $spacings[] = $i;
                        }
                    }

                    $colors = ["#71588f", "#93a9d0", "#aa4644", "#89a54e", "#71588f", "#4298af", "#db843d", "#d09392", "#bacd96", "#a99bbe"];
                    $count = count($spacings);
                    if ($count) {
                        for ($i = 1; $i <= $count; $i++) {
                            $series[] = ['name' => "Temp " . ($uom_factor * ($spacings[$i - 1] * 4)) . $uom_suffix, 'color' => $colors[$i], 'type' => 'spline', 'data' => ${"t" . $i}];
                        }
                    } else {
                        // Fallback
                        $series = $getDefaultTempSeries();
                    }
                } else {
                    // Fallback
                    $series = $getDefaultTempSeries();
                }

                $graph_data = [
                    'graph' => array(
                        'series' => $series,
                        'yAxis' => array(
                            'title' => array(
                                'text' => 'Temperature (' . $uom_symbol . ')'
                            )
                        ),
                        'title' => [
                            'text' => $field->field_name . ' - Temperature (' . $uom_symbol . ') - ' . $request->node_address,
                            'widthAdjust' => -200
                        ],
                        'plotOptions' => $plotOptions
                    ),
                    'x_max' => $x_max,
                    'x_min' => $x_min,
                    'y_max' => (float)($y_max) + 1,
                    'y_min' => (float)($y_min) - 1,
                    'full'  => $field->full,
                    'refill' => $field->refill
                ];

                break;

            case 'tech':

                $y_min = 0;
                $y_max = 8000;

                $voltages = [];
                $ambient_temps = [];

                if ($node_data) {
                    $index = 0;
                    foreach ($node_data as $row) {
                        $user = Auth::user();

                        if ($user->unit_of_measure  == 2) {
                            $seriesCalc = $row->ambient_temp * (9 / 5) + 32;
                        } else if ($user->unit_of_measure  == 1) {
                            $seriesCalc = $row->ambient_temp;
                        }
                        // only get rows with non-empty date_time fields
                        if (!empty($populated[$index])) {
                            // convert UTC dates to localized user timezone dates
                            $dt = new \DateTime($row->date_time);
                            $dt->setTimezone(new \DateTimeZone($this->tz));
                            $timestamp = ($dt->getTimestamp() + $dt->getOffset()) * 1000;
                            if ($row->bv) {
                                array_push($voltages, [
                                    'x' => $timestamp,
                                    'y' => (float)($row->bv / 1000)
                                ]);
                            }
                            if ($row->ambient_temp) {
                                array_push($ambient_temps, [
                                    'x' => $timestamp,
                                    'y' => (float)($seriesCalc)
                                ]);
                            }
                        }
                        $index++;
                    }
                }

                $graph_data = [
                    'graph' => array(
                        'series' => [
                            ['name' => 'Battery Voltage', 'color' => '#89a54e', 'type' => 'spline', 'data' => $voltages],
                            ['name' => 'Ambient Temperatures', 'color' => '#aa4644', 'type' => 'spline', 'data' => $ambient_temps]
                        ],
                        'yAxis' => array(
                            'title' => array(
                                'text' => ''
                            )
                        ),
                        'title' => [
                            'text' => $field->field_name . ' - Technical - ' . $request->node_address,
                            'widthAdjust' => -200
                        ],
                        'plotOptions' => $plotOptions
                    ),
                    'x_max' => $x_max,
                    'x_min' => $x_min,
                    'y_max' => (float)($y_max) + 1,
                    'y_min' => (float)($y_min) - 1,
                ];

                break;

            case 'nutrient':

                $y_min = 0;
                $y_max = 0;

                $keys = [];

                $tpl_values = '';
                $ntpl = nutrient_templates::where('id', $field->nutrient_template_id)->first();
                if ($ntpl) {
                    $tpl_values = json_decode($ntpl->template, true);
                }

                /*   foreach ($nutr_data as $item) {
                    $keys[$item->identifier] = '';
                }
                $keys = array_keys($keys);
                foreach ($keys as $k) {
                    ${$k} = [];
                }*/
                $M0_1 = [];
                $M0_2 = [];
                $M0_3 = [];
                $M0_4 = [];

                $M1_1 = [];
                $M1_2 = [];
                $M1_3 = [];
                $M1_4 = [];

                $M2_1 = [];
                $M2_2 = [];
                $M2_3 = [];
                $M2_4 = [];

                $M3_1 = [];
                $M3_2 = [];
                $M3_3 = [];
                $M3_4 = [];

                $M4_1 = [];
                $M4_2 = [];
                $M4_3 = [];
                $M4_4 = [];

                $M5_1 = [];
                $M5_2 = [];
                $M5_3 = [];
                $M5_4 = [];

                $M6_1 = [];
                $M6_2 = [];
                $M6_3 = [];
                $M6_4 = [];


                // $M7_1 = [];
                // $M7_2 = [];
                // $M7_3 = [];
                // $M7_4 = [];

                // $M8_1 = [];
                // $M8_2 = [];
                // $M8_3 = [];
                // $M8_4 = [];

                // $M9_1 = [];
                // $M9_2 = [];
                // $M9_3 = [];
                // $M9_4 = [];

                $series = [];
                /*
                if ($keys) {*/
                //  foreach ($keys as $k) { // M0_1, M1_1, M2_1, etc
                foreach ($nutr_data as $item) {
                    //       if ($item->identifier == $k) {
                    // create new date stripping off seconds
                    $dt = new \DateTime(substr($item->date_sampled, 0, -3) . ':00');
                    $dt->setTimezone(new \DateTimeZone($this->tz));
                    //  $y_min = $item->value < $y_min ? $item->value : $y_min;
                    // $y_max = $item->value > $y_max ? $item->value : $y_max;




                    array_push($M0_1, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M0_1),
                    ]);
                    array_push($M0_2, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M0_2),
                    ]);
                    array_push($M0_3, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M0_3),
                    ]);
                    array_push($M0_4, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M0_1),
                    ]);

                    array_push($M1_1, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M1_1),
                    ]);
                    array_push($M1_2, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M1_2),
                    ]);
                    array_push($M1_3, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M1_3),
                    ]);
                    array_push($M1_4, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M1_4),
                    ]);

                    array_push($M2_1, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M2_1),
                    ]);
                    array_push($M2_2, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M2_2),
                    ]);
                    array_push($M2_3, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M2_3),
                    ]);
                    array_push($M2_4, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M2_4),
                    ]);

                    array_push($M3_1, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M3_1),
                    ]);
                    array_push($M3_2, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M3_2),
                    ]);
                    array_push($M3_3, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M3_3),
                    ]);
                    array_push($M3_4, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M3_4),
                    ]);


                    array_push($M4_1, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M4_1),
                    ]);
                    array_push($M4_2, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M4_2),
                    ]);
                    array_push($M4_3, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M3_3),
                    ]);
                    array_push($M4_4, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M3_4),
                    ]);




                    array_push($M5_1, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M5_1),
                    ]);
                    array_push($M5_2, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M5_2),
                    ]);
                    array_push($M5_3, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M5_3),
                    ]);
                    array_push($M5_4, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M5_4),
                    ]);

                    array_push($M6_1, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M6_1),
                    ]);
                    array_push($M6_2, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M6_2),
                    ]);
                    array_push($M6_3, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M6_3),
                    ]);
                    array_push($M6_4, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M6_4),
                    ]);


                    // array_push($M7_1, [
                    //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                    //     (float)($item->M7_1),
                    // ]);
                    // array_push($M7_2, [
                    //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                    //     (float)($item->M7_2),
                    // ]);
                    // array_push($M7_3, [
                    //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                    //     (float)($item->M7_3),
                    // ]);
                    // array_push($M7_4, [
                    //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                    //     (float)($item->M7_4),
                    // ]);


                    // array_push($M8_1, [
                    //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                    //     (float)($item->M8_1),
                    // ]);
                    // array_push($M8_2, [
                    //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                    //     (float)($item->M8_2),
                    // ]);
                    // array_push($M8_3, [
                    //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                    //     (float)($item->M8_3),
                    // ]);
                    // array_push($M8_4, [
                    //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                    //     (float)($item->M8_4),
                    // ]);

                    // array_push($M9_1, [
                    //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                    //     (float)($item->M9_1),
                    // ]);
                    // array_push($M9_2, [
                    //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                    //     (float)($item->M9_2),
                    // ]);
                    // array_push($M9_3, [
                    //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                    //     (float)($item->M9_3),
                    // ]);
                    // array_push($M9_4, [
                    //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                    //     (float)($item->M9_4),
                    // ]);
                    // }

                }
                // $series[] = ['name' => 'M0.1', 'type' => 'line', 'data' => $M0_1];
                // $series[] = ['name' => 'M0.2', 'type' => 'line', 'data' => $M0_2];
                // $series[] = ['name' => 'M0.3', 'type' => 'line', 'data' => $M0_3];
                // $series[] = ['name' => 'M0.4', 'type' => 'line', 'data' => $M0_4];

                // $series[] = ['name' => 'M1.1', 'type' => 'line', 'data' => $M1_1];
                // $series[] = ['name' => 'M1.2', 'type' => 'line', 'data' => $M1_2];
                // $series[] = ['name' => 'M1.3', 'type' => 'line', 'data' => $M1_3];
                // $series[] = ['name' => 'M1.4', 'type' => 'line', 'data' => $M1_4];

                // $series[] = ['name' => 'M2.1', 'type' => 'line', 'data' => $M2_1];
                // $series[] = ['name' => 'M2.2', 'type' => 'line', 'data' => $M2_2];
                // $series[] = ['name' => 'M2.3', 'type' => 'line', 'data' => $M2_3];
                // $series[] = ['name' => 'M2.4', 'type' => 'line', 'data' => $M2_4];

                $series[] = ['name' => 'M3.1', 'type' => 'line', 'data' => $M3_1];
                $series[] = ['name' => 'M3.2', 'type' => 'line', 'data' => $M3_2];
                $series[] = ['name' => 'M3.3', 'type' => 'line', 'data' => $M3_3];
                $series[] = ['name' => 'M3.4', 'type' => 'line', 'data' => $M3_4];

                $series[] = ['name' => 'M4.1', 'type' => 'line', 'data' => $M4_1];
                $series[] = ['name' => 'M4.2', 'type' => 'line', 'data' => $M4_2];
                $series[] = ['name' => 'M4.3', 'type' => 'line', 'data' => $M4_3];
                $series[] = ['name' => 'M4.4', 'type' => 'line', 'data' => $M4_4];

                $series[] = ['name' => 'M5.1', 'type' => 'line', 'data' => $M5_1];
                $series[] = ['name' => 'M5.2', 'type' => 'line', 'data' => $M5_2];
                $series[] = ['name' => 'M5.3', 'type' => 'line', 'data' => $M5_3];
                $series[] = ['name' => 'M5.4', 'type' => 'line', 'data' => $M5_4];

                $series[] = ['name' => 'M6.1', 'type' => 'line', 'data' => $M6_1];
                $series[] = ['name' => 'M6.2', 'type' => 'line', 'data' => $M6_2];
                $series[] = ['name' => 'M6.3', 'type' => 'line', 'data' => $M6_3];
                $series[] = ['name' => 'M6.4', 'type' => 'line', 'data' => $M6_4];

                //  $series[] = ['name' => 'M7.1', 'type' => 'line', 'data' => $M7_1];
                //  $series[] = ['name' => 'M7.2', 'type' => 'line', 'data' => $M7_2];
                //  $series[] = ['name' => 'M7.3', 'type' => 'line', 'data' => $M7_3];
                //  $series[] = ['name' => 'M7.4', 'type' => 'line', 'data' => $M7_4];

                //  $series[] = ['name' => 'M8.1', 'type' => 'line', 'data' => $M8_1];
                //  $series[] = ['name' => 'M8.2', 'type' => 'line', 'data' => $M8_2];
                //  $series[] = ['name' => 'M8.3', 'type' => 'line', 'data' => $M8_3];
                //  $series[] = ['name' => 'M8.4', 'type' => 'line', 'data' => $M8_4];

                //  $series[] = ['name' => 'M9.1', 'type' => 'line', 'data' => $M9_1];
                //  $series[] = ['name' => 'M9.2', 'type' => 'line', 'data' => $M9_2];
                //  $series[] = ['name' => 'M9.3', 'type' => 'line', 'data' => $M9_3];
                //  $series[] = ['name' => 'M9.4', 'type' => 'line', 'data' => $M9_4];


                // }
                // }

                $lower_limit = $tpl_values['lower_limit'] ? $tpl_values['lower_limit'] : 0;
                $upper_limit = $tpl_values['upper_limit'] ? $tpl_values['upper_limit'] : 0;

                // force boostmode
                $plotOptions['series']['boostThreshold'] = 1;
                $plotOptions['series']['turboThreshold'] = 1;

                $graph_data = [
                    'graph' => array(
                        'series' => $series,
                        'yAxis' => array(
                            'title' => array(
                                'text' => 'Nutrients'
                            )
                        ),
                        'title' => [
                            'text' => $field->field_name . ' - Nutrients - ' . $request->node_address,
                            'widthAdjust' => -200
                        ],
                        'plotOptions' => $plotOptions
                    ),
                    'x_max' => $x_max,
                    'x_min' => $x_min,
                    'y_max' => (float)($y_max) + 1,
                    'y_min' => (float)($y_min) - 1,
                    'keys'  => $keys,
                    'lower_limit' => $lower_limit,
                    'upper_limit' => $upper_limit
                ];

                // attempt to add in nutrient gauge values
                if (!empty($field->nutrient_template_id) && $field->nutrient_template_id) {

                    //Log::debug("nutrient: {$request->node_address} -> {$field->nutrient_template_id}");
                    $results = Calculations::calcNutrientAverageGaugeValues($request->node_address, $field->nutrient_template_id);

                    $graph_data['nutrient_lower'] = $results['nutrient_lower'];
                    $graph_data['nutrient_upper'] = $results['nutrient_upper'];
                    $graph_data['nutrient_gauge'] = $results['nutrient_gauge'];
                    $graph_data['nutrient_avg']   = $results['nutrient_avg'];
                    $graph_data['nutrient_label'] = $results['nutrient_label'];
                    //$graph_data['nutrient_NO3'] = $results['NO3_AVG'];
                    //$this->nutrient_type($nutr_data, $request, $field);
                }

                break;

            case 'nutrient_ppm':

                $y_min = 0;
                $y_max = 0;

                $M3_1 = [];
                $M3_2 = [];
                $M3_3 = [];
                $M3_4 = [];

                $M4_1 = [];
                $M4_2 = [];
                $M4_3 = [];
                $M4_4 = [];

                $M5_1 = [];
                $M5_2 = [];
                $M5_3 = [];
                $M5_4 = [];

                $M6_1 = [];
                $M6_2 = [];
                $M6_3 = [];
                $M6_4 = [];

                // $M7_1 = [];
                // $M7_2 = [];
                // $M7_3 = [];
                // $M7_4 = [];

                // $M8_1 = [];
                // $M8_2 = [];
                // $M8_3 = [];
                // $M8_4 = [];

                // $M9_1 = [];
                // $M9_2 = [];
                // $M9_3 = [];
                // $M9_4 = [];

                $series = [];

                $tpl_values = '';
                $ntpl = nutrient_templates::where('id', $field->nutrient_template_id)->first();
                if ($ntpl) {
                    $tpl_values = json_decode($ntpl->template, true);
                }

                if ($ntpl && $tpl_values) {

                    $poly1 = $tpl_values['poly1'] ?: 1;
                    $poly2 = $tpl_values['poly2'] ?: 0;


                    //    foreach ($keys as $k) { // M0_1, M1_1, M2_1, etc
                    foreach ($nutr_data as $item) {

                        // dd($item->M3_1);
                        //   if ($item->identifier == $k) {
                        // create new date stripping off seconds
                        $dt = new \DateTime(substr($item->date_sampled, 0, -3) . ':00');
                        $dt->setTimezone(new \DateTimeZone($this->tz));

                        //$ppm_val = ($item->value * $poly1) + $poly2;

                        /*   $y_min = $ppm_val < $y_min ? $ppm_val : $y_min;
                                $y_max = $ppm_val > $y_max ? $ppm_val : $y_max;
*/
                        /*  array_push(${$k}, [
                                    ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                                    (float)$ppm_val = ($item->value * $poly1) + $poly2;,
                                ]);*/


                        array_push($M3_1, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float)number_format((($item->M3_1 * $poly1) + $poly2), 1, '.', ''),
                            (float)$item->M3_1
                        ]);
                        array_push($M3_2, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float)number_format((($item->M3_2 * $poly1) + $poly2), 1, '.', ''),
                        ]);
                        array_push($M3_3, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float)number_format((($item->M3_3 * $poly1) + $poly2), 1, '.', ''),
                        ]);
                        array_push($M3_4, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float)number_format((($item->M3_4 * $poly1) + $poly2), 1, '.', ''),
                        ]);


                        array_push($M4_1, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float)number_format((($item->M4_1 * $poly1) + $poly2), 1, '.', ''),
                        ]);
                        array_push($M4_2, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float)number_format((($item->M4_2 * $poly1) + $poly2), 1, '.', ''),
                        ]);
                        array_push($M4_3, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float)number_format((($item->M4_3 * $poly1) + $poly2), 1, '.', ''),
                        ]);
                        array_push($M4_4, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float)number_format((($item->M4_4 * $poly1) + $poly2), 1, '.', ''),
                        ]);

                        array_push($M5_1, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float)number_format((($item->M5_1 * $poly1) + $poly2), 1, '.', ''),
                        ]);
                        array_push($M5_2, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float)number_format((($item->M5_2 * $poly1) + $poly2), 1, '.', ''),
                        ]);
                        array_push($M5_3, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float)number_format((($item->M5_3 * $poly1) + $poly2), 1, '.', ''),
                        ]);
                        array_push($M5_4, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float)number_format((($item->M5_4 * $poly1) + $poly2), 1, '.', ''),
                        ]);

                        array_push($M6_1, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float)number_format((($item->M6_1 * $poly1) + $poly2), 1, '.', ''),
                        ]);
                        array_push($M6_2, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float)number_format((($item->M6_2 * $poly1) + $poly2), 1, '.', ''),
                        ]);
                        array_push($M6_3, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float)number_format((($item->M6_3 * $poly1) + $poly2), 1, '.', ''),
                        ]);
                        array_push($M6_4, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float)number_format((($item->M6_4 * $poly1) + $poly2), 1, '.', ''),
                        ]);

                        // array_push($M7_1, [
                        //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        //     (float)number_format((($item->M7_1 * $poly1) + $poly2), 2, '.', ''),
                        // ]);
                        // array_push($M7_2, [
                        //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        //     (float)number_format((($item->M7_2 * $poly1) + $poly2), 2, '.', ''),
                        // ]);
                        // array_push($M7_3, [
                        //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        //     (float)number_format((($item->M7_3 * $poly1) + $poly2), 2, '.', ''),
                        // ]);
                        // array_push($M7_4, [
                        //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        //     (float)number_format((($item->M7_4 * $poly1) + $poly2), 2, '.', ''),
                        // ]);

                        // array_push($M8_1, [
                        //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        //     (float)number_format((($item->M8_1 * $poly1) + $poly2), 2, '.', ''),
                        // ]);
                        // array_push($M8_2, [
                        //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        //     (float)number_format((($item->M8_2 * $poly1) + $poly2), 2, '.', ''),
                        // ]);
                        // array_push($M8_3, [
                        //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        //     (float)number_format((($item->M8_3 * $poly1) + $poly2), 2, '.', ''),
                        // ]);
                        // array_push($M8_4, [
                        //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        //     (float)number_format((($item->M8_4 * $poly1) + $poly2), 2, '.', ''),
                        // ]);

                        // array_push($M9_1, [
                        //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        //     (float)number_format((($item->M9_1 * $poly1) + $poly2), 2, '.', ''),
                        // ]);
                        // array_push($M9_2, [
                        //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        //     (float)number_format((($item->M9_2 * $poly1) + $poly2), 2, '.', ''),
                        // ]);
                        // array_push($M9_3, [
                        //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        //     (float)number_format((($item->M9_3 * $poly1) + $poly2), 2, '.', ''),
                        // ]);
                        // array_push($M9_4, [
                        //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        //     (float)number_format((($item->M9_4 * $poly1) + $poly2), 2, '.', ''),
                        // ]);
                        //  }
                    }
                    $series[] = ['name' => "NO3(4''/100mm)", 'type' => 'spline', 'data' => $M3_1];
                    $series[] = ['name' => "NH4(8''/200mm)", 'type' => 'spline', 'data' => $M3_2];
                    $series[] = ['name' => "NO3(12''/300mm)", 'type' => 'spline', 'data' => $M3_3];
                    $series[] = ['name' => "NH4(16''/400mm)", 'type' => 'spline', 'data' => $M3_4];

                    $series[] = ['name' => "NO3(4''/100mm)", 'type' => 'spline', 'data' => $M4_1];
                    $series[] = ['name' => "NH4(8''/200mm)", 'type' => 'spline', 'data' => $M4_2];
                    $series[] = ['name' => "NO3(12''/300mm)", 'type' => 'spline', 'data' => $M4_3];
                    $series[] = ['name' => "NH4(16''/400mm)", 'type' => 'spline', 'data' => $M4_4];

                    $series[] = ['name' => "NO3(4''/100mm)", 'type' => 'spline', 'data' => $M5_1];
                    $series[] = ['name' => "NH4(8''/200mm)", 'type' => 'spline', 'data' => $M5_2];
                    $series[] = ['name' => "NO3(12''/300mm)", 'type' => 'spline', 'data' => $M5_3];
                    $series[] = ['name' => "NH4(16''/400mm)", 'type' => 'spline', 'data' => $M5_4];

                    $series[] = ['name' => "NO3(4''/100mm)", 'type' => 'spline', 'data' => $M6_1];
                    $series[] = ['name' => "NH4(8''/200mm)", 'type' => 'spline', 'data' => $M6_2];
                    $series[] = ['name' => "NO3(12''/300mm)", 'type' => 'spline', 'data' => $M6_3];
                    $series[] = ['name' => "NH4(16''/400mm)", 'type' => 'spline', 'data' => $M6_4];


                    // $series[] = ['name' => 'M7.1', 'type' => 'spline', 'data' => $M7_1];
                    // $series[] = ['name' => 'M7.2', 'type' => 'spline', 'data' => $M7_2];
                    // $series[] = ['name' => 'M7.3', 'type' => 'spline', 'data' => $M7_3];
                    // $series[] = ['name' => 'M7.4', 'type' => 'spline', 'data' => $M7_4];

                    // $series[] = ['name' => 'M8.1', 'type' => 'spline', 'data' => $M8_1];
                    // $series[] = ['name' => 'M8.2', 'type' => 'spline', 'data' => $M8_2];
                    // $series[] = ['name' => 'M8.3', 'type' => 'spline', 'data' => $M8_3];
                    // $series[] = ['name' => 'M8.4', 'type' => 'spline', 'data' => $M8_4];

                    // $series[] = ['name' => 'M9.1', 'type' => 'spline', 'data' => $M9_1];
                    // $series[] = ['name' => 'M9.2', 'type' => 'spline', 'data' => $M9_2];
                    // $series[] = ['name' => 'M9.3', 'type' => 'spline', 'data' => $M9_3];
                    // $series[] = ['name' => 'M9.4', 'type' => 'spline', 'data' => $M9_4];

                    // foreach () {

                    // }
                }
                //  }

                $lower_limit = $tpl_values['lower_limit'] ? $tpl_values['lower_limit'] : 0;
                $upper_limit = $tpl_values['upper_limit'] ? $tpl_values['upper_limit'] : 0;

                // force boostmode
                $plotOptions['series']['boostThreshold'] = 1;
                $plotOptions['series']['turboThreshold'] = 1;

                $graph_data = [
                    'graph' => array(
                        'series' => $series,
                        'yAxis' => array(
                            'title' => array(
                                'text' => 'Nutrient (depth in mm/inches)'
                            )
                        ),
                        'title' => [
                            'text' => $field->field_name . ' - Nutrient (depth in mm/inches) - ' . $request->node_address,
                            'widthAdjust' => -200
                        ],
                        'plotOptions' => $plotOptions
                    ),
                    'x_max' => $x_max,
                    'x_min' => $x_min,
                    'y_max' => (float)$y_max + 1,
                    'y_min' => (float)$y_min - 1,
                    //   'keys'  => $keys
                    'data' => $series[0]['data'],
                    'lower_limit' => $lower_limit,
                    'upper_limit' => $upper_limit

                ];

                // attempt to add in nutrient gauge values
                if (!empty($field->nutrient_template_id) && $field->nutrient_template_id) {


                    //Log::debug("nutrient_ppm: {$request->node_address} -> {$field->nutrient_template_id}");
                    $results = Calculations::calcNutrientAverageGaugeValues($request->node_address, $field->nutrient_template_id);

                    $graph_data['nutrient_lower'] = $results['nutrient_lower'];
                    $graph_data['nutrient_upper'] = $results['nutrient_upper'];
                    $graph_data['nutrient_gauge'] = $results['nutrient_gauge'];
                    $graph_data['nutrient_label'] = $results['nutrient_label'];
                    //$graph_data['nutrient_NO3'] = $results['NO3_AVG'];

                    // die;

                }

                break;

            case 'nutrient_ppm_avg':

                $y_min = 0;
                $y_max = 0;

                $data1 = [];
                $data2 = [];
                $data3 = [];
                $data4 = [];

                // get template values
                $tpl_values = '';
                $ntpl = nutrient_templates::where('id', $field->nutrient_template_id)->first();
                if ($ntpl) {
                    $tpl_values = json_decode($ntpl->template, true);
                }

                if (is_array($tpl_values)) {

                    $poly1 = $tpl_values['poly1'] ?: 1;
                    $poly2 = $tpl_values['poly2'] ?: 0;

                    foreach ($nutr_data as $item) {
                        // create new date stripping off seconds
                        $dt = new \DateTime(substr($item->date_sampled, 0, -3) . ':00');
                        $dt->setTimezone(new \DateTimeZone($this->tz));

                        $M3_avg_val = 0;
                        $M3_val = 0;
                        $M3 = [];
                        $count = 0;
                        if (isset($item->M3_1)) {
                            $count++;
                            $M3_val = ($item->M3_1 * $poly1) + $poly2;
                        }
                        if (isset($item->M3_2)) {
                            $count++;
                            $M3_val += ($item->M3_2 * $poly1) + $poly2;
                        }
                        if (isset($item->M3_3)) {
                            $count++;
                            $M3_val += (($item->M3_3 * $poly1) + $poly2);
                        }
                        if (isset($item->M3_4)) {
                            $count++;
                            $M3_val += (($item->M3_4 * $poly1) + $poly2);
                        }

                        // $M3_avg_val = $M3_val/$count;
                        $M3_avg_val = $M3_val / $count;


                        $M4_avg_val = 0;
                        $M4_val = 0;
                        $M4 = [];
                        $count = 0;
                        if (isset($item->M4_1)) {
                            $count++;
                            $M4_val = (($item->M4_1 * $poly1) + $poly2);
                        }
                        if (isset($item->M4_2)) {
                            $count++;
                            $M4_val += (($item->M4_2 * $poly1) + $poly2);
                        }
                        if (isset($item->M4_3)) {
                            $count++;
                            $M4_val += (($item->M4_3 * $poly1) + $poly2);
                        }
                        if (isset($item->M3_4)) {
                            $count++;
                            $M4_val += (($item->M4_4 * $poly1) + $poly2);
                        }

                        $M4_avg_val = $M4_val / $count;

                        $M5_avg_val = 0;
                        $M5_val = 0;
                        $M5 = [];
                        $count = 0;
                        if (isset($item->M5_1)) {
                            $count++;
                            $M5_val = (($item->M5_1 * $poly1) + $poly2);
                        }
                        if (isset($item->M5_2)) {
                            $count++;
                            $M5_val += (($item->M5_2 * $poly1) + $poly2);
                        }
                        if (isset($item->M5_3)) {
                            $count++;
                            $M5_val += (($item->M5_3 * $poly1) + $poly2);
                        }
                        if (isset($item->M5_4)) {
                            $count++;
                            $M5_val += (($item->M5_4 * $poly1) + $poly2);
                        }

                        $M5_avg_val = $M5_val / $count;

                        $M6_avg_val = 0;
                        $M6_val = 0;
                        $M6 = [];
                        $count = 0;
                        if (isset($item->M6_1)) {
                            $count++;
                            $M6_val = ($item->M6_1 * $poly1) + $poly2;
                        }
                        if (isset($item->M6_2)) {
                            $count++;
                            $M6_val += (($item->M6_2 * $poly1) + $poly2);
                        }
                        if (isset($item->M6_3)) {
                            $count++;
                            $M6_val += (($item->M6_3 * $poly1) + $poly2);
                        }
                        if (isset($item->M6_4)) {
                            $count++;
                            $M6_val += (($item->M6_4 * $poly1) + $poly2);
                        }

                        $M6_avg_val = $M6_val / $count;
                        /*
                        array_push($M3_2, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float)number_format((($item->M3_2 * $poly1) + $poly2), 2, '.', ''),
                        ]);
                        array_push($M3_3, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float)number_format((($item->M3_3 * $poly1) + $poly2), 2, '.', ''),
                        ]);
                        array_push($M3_4, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float)number_format((($item->M3_4 * $poly1) + $poly2), 2, '.', ''),
                        ]);
                       
*/
                        // dd($item);

                        // $ppm_avg = (
                        //     (($item->M3_1 * $poly1) + $poly2) +
                        //     (($item->M4_1 * $poly1) + $poly2) +
                        //     (($item->M5_1 * $poly1) + $poly2) +
                        //     (($item->M6_1 * $poly1) + $poly2)
                        // ) / 4;
                        /*
                        $nh4_1 = ($item->M3_1 * $poly1) + $poly2;
                        $nh4_2 = ($item->M4_1 * $poly1) + $poly2;
                        $nh4_avg = ($nh4_1 + $nh4_2) / 2;

                        $no3_1 = ($item->M5_1 * $poly1) + $poly2;
                        $no3_2 = ($item->M6_1 * $poly1) + $poly2;
                        $no3_avg = ($no3_1 + $no3_2) / 2;

                        $ppm_avg = $nh4_avg + $no3_avg;
*/
                        //                      $y_min = $ppm_avg < $y_min ? $ppm_avg : $y_min;
                        //                      $y_max = $ppm_avg > $y_max ? $ppm_avg : $y_max;

                        array_push($data1, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float) number_format($M3_avg_val, 1, '.', ''),
                        ]);
                        array_push($data2, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float) number_format($M4_avg_val, 1, '.', ''),
                        ]);
                        array_push($data3, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float) number_format($M5_avg_val, 1, '.', ''),
                        ]);
                        array_push($data4, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float) number_format($M6_avg_val, 1, '.', ''),
                        ]);
                    }
                }

                $series[] = ['name' => 'Average M.3', 'type' => 'spline', 'data' => $data1];
                $series[] = ['name' => 'Average M.4', 'type' => 'spline', 'data' => $data2];
                $series[] = ['name' => 'Average M.5', 'type' => 'spline', 'data' => $data3];
                $series[] = ['name' => 'Average M.6', 'type' => 'spline', 'data' => $data4];

                $lower_limit = $tpl_values['lower_limit'] ? $tpl_values['lower_limit'] : 0;
                $upper_limit = $tpl_values['upper_limit'] ? $tpl_values['upper_limit'] : 0;

                // force boostmode
                $plotOptions['series']['boostThreshold'] = 1;
                $plotOptions['series']['turboThreshold'] = 1;

                $graph_data = [
                    'graph' => array(
                        'series' => $series,
                        'yAxis' => array(
                            'title' => array(
                                'text' => 'Nutrients (PPM Average)'
                            )
                        ),
                        'title' => [
                            'text' => $field->field_name . ' - Nutrients (PPM Average) - ' . $request->node_address,
                            'widthAdjust' => -200
                        ],
                        'plotOptions' => $plotOptions
                    ),
                    'x_max' => $x_max,
                    'x_min' => $x_min,
                    'lower_limit' => $lower_limit,
                    'upper_limit' => $upper_limit

                    //              'y_max' => (float)($y_max) + 1,
                    //                'y_min' => (float)($y_min) - 1
                ];

                // attempt to add in nutrient gauge values
                if (!empty($field->nutrient_template_id) && $field->nutrient_template_id) {

                    //Log::debug("nutrient_ppm_avg: {$request->node_address} -> {$field->nutrient_template_id}");
                    $results = Calculations::calcNutrientAverageGaugeValues($request->node_address, $field->nutrient_template_id);

                    $graph_data['nutrient_lower'] = $results['nutrient_lower'];
                    $graph_data['nutrient_upper'] = $results['nutrient_upper'];
                    $graph_data['nutrient_gauge'] = $results['nutrient_gauge'];
                    $graph_data['nutrient_avg']   = $results['nutrient_avg'];
                    $graph_data['nutrient_label'] = $results['nutrient_label'];
                }

                break;


            case 'nutrient_type_avg':

                $y_min = 0;
                $y_max = 0;

                $data1 = [];
                $data2 = [];

                // get template values
                $tpl_values = '';
                $ntpl = nutrient_templates::where('id', $field->nutrient_template_id)->first();
                if ($ntpl) {
                    $tpl_values = json_decode($ntpl->template, true);
                }

                if (is_array($tpl_values)) {

                    $poly1 = $tpl_values['poly1'] ?: 1;
                    $poly2 = $tpl_values['poly2'] ?: 0;

                    foreach ($nutr_data as $item) {
                        // create new date stripping off seconds
                        $dt = new \DateTime(substr($item->date_sampled, 0, -3) . ':00');
                        $dt->setTimezone(new \DateTimeZone($this->tz));


                        $N03_avg = 0;
                        $count = 0;

                        if (isset($item->M3_1)) {
                            $count++;
                            $N03_avg += ($item->M3_1 * $poly1) + $poly2;
                        }
                        if (isset($item->M4_1)) {
                            $count++;
                            $N03_avg += (($item->M4_1 * $poly1) + $poly2);
                        }
                        if (isset($item->M5_1)) {
                            $count++;
                            $N03_avg += (($item->M5_1 * $poly1) + $poly2);
                        }
                        if (isset($item->M6_1)) {
                            $count++;
                            $N03_avg += ($item->M6_1 * $poly1) + $poly2;
                        }
                        if (isset($item->M3_3)) {
                            $count++;
                            $N03_avg += (($item->M3_3 * $poly1) + $poly2);
                        }
                        if (isset($item->M4_3)) {
                            $count++;
                            $N03_avg += (($item->M4_3 * $poly1) + $poly2);
                        }
                        if (isset($item->M5_3)) {
                            $count++;
                            $N03_avg += (($item->M5_3 * $poly1) + $poly2);
                        }
                        if (isset($item->M6_3)) {
                            $count++;
                            $N03_avg += (($item->M6_3 * $poly1) + $poly2);
                        }

                        $N03_avg = (($count > 0) ? ($N03_avg / $count) : 0);


                        $NH4_avg = 0;
                        $count2 = 0;
                        if (isset($item->M3_2)) {
                            $count2++;
                            $NH4_avg += ($item->M3_2 * $poly1) + $poly2;
                        }
                        if (isset($item->M4_2)) {
                            $count2++;
                            $NH4_avg += (($item->M4_2 * $poly1) + $poly2);
                        }
                        if (isset($item->M5_2)) {
                            $count2++;
                            $NH4_avg += (($item->M5_2 * $poly1) + $poly2);
                        }
                        if (isset($item->M6_2)) {
                            $count2++;
                            $NH4_avg += ($item->M6_2 * $poly1) + $poly2;
                        }
                        if (isset($item->M3_4)) {
                            $count2++;
                            $NH4_avg += (($item->M3_4 * $poly1) + $poly2);
                        }
                        if (isset($item->M4_4)) {
                            $count2++;
                            $NH4_avg += (($item->M4_4 * $poly1) + $poly2);
                        }
                        if (isset($item->M5_4)) {
                            $count2++;
                            $NH4_avg += (($item->M5_4 * $poly1) + $poly2);
                        }
                        if (isset($item->M6_3)) {
                            $count2++;
                            $NH4_avg += (($item->M6_4 * $poly1) + $poly2);
                        }

                        $NH4_avg = (($count2 > 0) ? ($NH4_avg / $count2) : 0);


                        array_push($data1, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float) number_format($N03_avg, 1, '.', ''),
                        ]);

                        array_push($data2, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float) number_format($NH4_avg, 1, '.', ''),
                        ]);
                    }
                }


                $series[] = ['name' => 'Avg N03 PPM', 'type' => 'spline', 'data' => $data1];
                $series[] = ['name' => 'Avg NH4 PPM', 'type' => 'spline', 'data' => $data2];

                // force boostmode
                $plotOptions['series']['boostThreshold'] = 1;
                $plotOptions['series']['turboThreshold'] = 1;


                $y_max = $field->full > $y_max ? $field->full : $y_max;
                $y_min = $field->refill < $y_min ? $field->refill : $y_min;

                $lower_limit = $tpl_values['lower_limit'] ? $tpl_values['lower_limit'] : 0;
                $upper_limit = $tpl_values['upper_limit'] ? $tpl_values['upper_limit'] : 0;


                // dd($field->full);

                // $y_max = $field->full > $y_max ? $field->full : $y_max;
                // $y_min = $field->refill < $y_min ? $field->refill : $y_min;

                $graph_data = [
                    'graph' => array(
                        'series' => $series,
                        'yAxis' => array(
                            'title' => array(
                                'text' => 'Nutrients Type Average'
                            )
                        ),
                        'title' => [
                            'text' => $field->field_name . ' - Nutrients Type Average - ' . $request->node_address,
                            'widthAdjust' => -200
                        ],
                        'plotOptions' => $plotOptions
                    ),
                    'x_max' => (float)number_format($x_max, 2, '.', ''),
                    'x_min' => (float)number_format($x_min, 2, '.', ''),
                    'y_max' => (float)number_format($y_max, 2, '.', '') + 1,
                    'y_min' => (float)number_format($y_min, 2, '.', '') - 1,
                    'lower_limit' => $lower_limit,
                    'upper_limit' => $upper_limit
                ];

                // attempt to add in nutrient gauge values
                if (!empty($field->nutrient_template_id) && $field->nutrient_template_id) {

                    //Log::debug("nutrient_ppm_avg: {$request->node_address} -> {$field->nutrient_template_id}");
                    $results = Calculations::calcNutrientAverageGaugeValues($request->node_address, $field->nutrient_template_id);


                    $graph_data['nutrient_lower'] = $results['nutrient_lower'];
                    $graph_data['nutrient_upper'] = $results['nutrient_upper'];
                    $graph_data['nutrient_gauge'] = $results['nutrient_gauge'];
                    $graph_data['nutrient_avg']   = $results['nutrient_avg'];
                    $graph_data['nutrient_label'] = $results['nutrient_label'];
                    $graph_data['nutrient_NO3']   = round($N03_avg, 1, PHP_ROUND_HALF_DOWN);
                    $graph_data['nutrient_NH4']   = round($NH4_avg, 1, PHP_ROUND_HALF_DOWN);
                }


                break;

            case 'nutrient_sm_sep_levels':

                $y_min = 0;
                $y_max = 0;

                $keys = [];

                $tpl_values = '';
                $ntpl = nutrient_templates::where('id', $field->nutrient_template_id)->first();
                if ($ntpl) {
                    $tpl_values = json_decode($ntpl->template, true);
                }

                /*   foreach ($nutr_data as $item) {
                    $keys[$item->identifier] = '';
                }
                $keys = array_keys($keys);
                foreach ($keys as $k) {
                    ${$k} = [];
                }*/
                $M0_1 = [];
                $M0_2 = [];
                $M0_3 = [];
                $M0_4 = [];

                $series = [];
                /*
                if ($keys) {*/
                //  foreach ($keys as $k) { // M0_1, M1_1, M2_1, etc
                foreach ($nutr_data as $item) {
                    //       if ($item->identifier == $k) {
                    // create new date stripping off seconds
                    $dt = new \DateTime(substr($item->date_sampled, 0, -3) . ':00');
                    $dt->setTimezone(new \DateTimeZone($this->tz));
                    //  $y_min = $item->value < $y_min ? $item->value : $y_min;
                    // $y_max = $item->value > $y_max ? $item->value : $y_max;




                    array_push($M0_1, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M0_1),
                    ]);
                    array_push($M0_2, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M0_2),
                    ]);
                    array_push($M0_3, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M0_3),
                    ]);
                    array_push($M0_4, [
                        ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        (float)($item->M0_1),
                    ]);
                }
                $series[] = ['name' => "S.M " . $uom_factor * 4  . $uom_suffix, 'type' => 'line', 'data' => $M0_1];
                $series[] = ['name' => "S.M " . $uom_factor * 8  . $uom_suffix, 'type' => 'line', 'data' => $M0_2];
                $series[] = ['name' => "S.M " . $uom_factor * 12  . $uom_suffix, 'type' => 'line', 'data' => $M0_3];
                $series[] = ['name' => "S.M " . $uom_factor * 16  . $uom_suffix, 'type' => 'line', 'data' => $M0_4];



                $lower_limit = $tpl_values['lower_limit'] ? $tpl_values['lower_limit'] : 0;
                $upper_limit = $tpl_values['upper_limit'] ? $tpl_values['upper_limit'] : 0;

                // force boostmode
                $plotOptions['series']['boostThreshold'] = 1;
                $plotOptions['series']['turboThreshold'] = 1;

                $graph_data = [
                    'graph' => array(
                        'series' => $series,
                        'yAxis' => array(
                            'title' => array(
                                'text' => 'Soil Moisture Levels'
                            )
                        ),
                        'title' => [
                            'text' => $field->field_name . ' - Soil Moisture Levels - ' . $request->node_address,
                            'widthAdjust' => -200
                        ],
                        'plotOptions' => $plotOptions
                    ),
                    'x_max' => $x_max,
                    'x_min' => $x_min,
                    'y_max' => (float)($y_max) + 1,
                    'y_min' => (float)($y_min) - 1,
                    'keys'  => $keys,
                    'lower_limit' => $lower_limit,
                    'upper_limit' => $upper_limit
                ];

                // attempt to add in nutrient gauge values
                if (!empty($field->nutrient_template_id) && $field->nutrient_template_id) {

                    //Log::debug("nutrient: {$request->node_address} -> {$field->nutrient_template_id}");
                    $results = Calculations::calcNutrientAverageGaugeValues($request->node_address, $field->nutrient_template_id);

                    $graph_data['nutrient_lower'] = $results['nutrient_lower'];
                    $graph_data['nutrient_upper'] = $results['nutrient_upper'];
                    $graph_data['nutrient_gauge'] = $results['nutrient_gauge'];
                    $graph_data['nutrient_avg']   = $results['nutrient_avg'];
                    $graph_data['nutrient_label'] = $results['nutrient_label'];
                    //$graph_data['nutrient_NO3'] = $results['NO3_AVG'];
                    //$this->nutrient_type($nutr_data, $request, $field);
                }

                break;

            case 'nutrient_sm_avg':
                $y_min = 0;
                $y_max = 0;

                // separate levels
                for ($i = 1; $i <= 15; $i++) {
                    ${'sm' . $i} = array();
                    $index = 0;
                    foreach ($node_data as $series) {
                        if (!empty($series->{'sm' . $i}) && !empty($populated[$index])) {
                            $dt = new \DateTime($series->date_time);
                            $dt->setTimezone(new \DateTimeZone($this->tz));
                            $point = [
                                'x' => ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                                'y' => (float)number_format($series->{'sm' . $i}, 2, '.', ''), "grp$i" => "g$i"
                            ];
                            $y_min = $series->{'sm' . $i} < $y_min ? $series->{'sm' . $i} : $y_min;
                            $y_max = $series->{'sm' . $i} > $y_max ? $series->{'sm' . $i} : $y_max;
                            array_push(${'sm' . $i}, $point);
                        }
                        $index++;
                    }
                }

                // average (requested by Brad and included along with SMs for analysis)
                $ave = [];

                foreach ($node_data as $series) {
                    //if (!empty($series->average) && !empty($populated[$index])){
                    $dt = new \DateTime($series->date_time);
                    $dt->setTimezone(new \DateTimeZone($this->tz));
                    $xVal = ($dt->getTimestamp() + $dt->getOffset()) * 1000;
                    $point = ['x' => $xVal, 'y' => (float)$series->average, "grpavg" => 'avg'];
                    $ave[] = $point;
                    //}
                }

                $data1 = [];
                // $data2 = [];

                // get template values
                $tpl_values = '';
                $ntpl = nutrient_templates::where('id', $field->nutrient_template_id)->first();
                if ($ntpl) {
                    $tpl_values = json_decode($ntpl->template, true);
                }

                if (is_array($tpl_values)) {

                    // $poly1 = $tpl_values['poly1'] ?: 1;
                    // $poly2 = $tpl_values['poly2'] ?: 0;

                    foreach ($nutr_data as $item) {
                        // create new date stripping off seconds
                        $dt = new \DateTime(substr($item->date_sampled, 0, -3) . ':00');
                        $dt->setTimezone(new \DateTimeZone($this->tz));


                        $nutr_sm_avg = 0;
                        $count = 0;

                        if (isset($item->M0_1)) {
                            $count++;
                            $nutr_sm_avg += $item->M0_1;
                        }
                        if (isset($item->M0_2)) {
                            $count++;
                            $nutr_sm_avg += $item->M0_2;
                        }
                        if (isset($item->M0_3)) {
                            $count++;
                            $nutr_sm_avg += $item->M0_3;
                        }
                        if (isset($item->M0_4)) {
                            $count++;
                            $nutr_sm_avg += $item->M0_4;
                        }
                        if (isset($item->M0_5)) {
                            $count++;
                            $nutr_sm_avg += $item->M0_5;
                        }
                        if (isset($item->M0_6)) {
                            $count++;
                            $nutr_sm_avg += $item->M0_6;
                        }

                        $nutr_sm_avg = (($count > 0) ? ($nutr_sm_avg / $count) / 100 : 0);
                        // $nutr_sm_avg = $item->ambient_temp;

                        array_push($data1, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float) number_format($nutr_sm_avg, 2, '.', ''),
                        ]);

                        // array_push($data2, [
                        //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        //     (float) number_format($NH4_avg, 1, '.', ''),
                        // ]);
                    }
                }

                $series[] = ['name' => 'Avg SM', 'type' => 'spline', 'data' => $data1];
                // $series[] = ['name' => 'Average NH4', 'type' => 'spline', 'data' => $data2];

                // force boostmode
                $plotOptions['series']['boostThreshold'] = 1;
                $plotOptions['series']['turboThreshold'] = 1;

                $graph_data = [
                    'graph' => array(
                        'series' => $series,
                        'yAxis' => array(
                            'title' => array(
                                'text' => 'Soil Moisture Average'
                            )
                        ),
                        'title' => [
                            'text' => $field->field_name . ' - Soil Moisture Average (%) - ' . $request->node_address,
                            'widthAdjust' => -200
                        ],
                        'plotOptions' => $plotOptions
                    ),
                    'x_max' => $x_max,
                    'x_min' => $x_min,
                ];

                // attempt to add in nutrient gauge values
                if (!empty($field->nutrient_template_id) && $field->nutrient_template_id) {

                    //Log::debug("nutrient_ppm_avg: {$request->node_address} -> {$field->nutrient_template_id}");
                    $results = Calculations::calcNutrientAverageGaugeValues($request->node_address, $field->nutrient_template_id);


                    $graph_data['nutrient_lower'] = $results['nutrient_lower'];
                    $graph_data['nutrient_upper'] = $results['nutrient_upper'];
                    $graph_data['nutrient_gauge'] = $results['nutrient_gauge'];
                    $graph_data['nutrient_avg']   = $results['nutrient_avg'];
                    $graph_data['nutrient_label'] = $results['nutrient_label'];
                    // $graph_data['nutrient_NO3']   = round($N03_avg, 1, PHP_ROUND_HALF_DOWN);
                    // $graph_data['nutrient_NH4']   = round($NH4_avg, 1, PHP_ROUND_HALF_DOWN);
                }

                break;

            case 'nutrient_temp_avg':
                $y_min = 0;
                $y_max = 0;

                $data1 = [];
                // $data2 = [];

                // get template values
                $tpl_values = '';
                $ntpl = nutrient_templates::where('id', $field->nutrient_template_id)->first();
                if ($ntpl) {
                    $tpl_values = json_decode($ntpl->template, true);
                }

                if (is_array($tpl_values)) {

                    // $poly1 = $tpl_values['poly1'] ?: 1;
                    // $poly2 = $tpl_values['poly2'] ?: 0;

                    foreach ($nutr_data as $item) {
                        // create new date stripping off seconds
                        $dt = new \DateTime(substr($item->date_sampled, 0, -3) . ':00');
                        $dt->setTimezone(new \DateTimeZone($this->tz));


                        $nutr_tmp_avg = 0;
                        $count = 0;

                        if (isset($item->M1_1)) {
                            $count++;
                            $nutr_tmp_avg += $item->M1_1;
                        }
                        if (isset($item->M1_2)) {
                            $count++;
                            $nutr_tmp_avg += $item->M1_2;
                        }
                        if (isset($item->M1_3)) {
                            $count++;
                            $nutr_tmp_avg += $item->M1_3;
                        }
                        if (isset($item->M1_4)) {
                            $count++;
                            $nutr_tmp_avg += $item->M1_4;
                        }
                        if (isset($item->M1_5)) {
                            $count++;
                            $nutr_tmp_avg += $item->M1_5;
                        }
                        if (isset($item->M1_6)) {
                            $count++;
                            $nutr_tmp_avg += $item->M1_6;
                        }

                        $nutr_tmp_avg = (($count > 0) ? ($nutr_tmp_avg / $count) : 0);
                        // $nutr_sm_avg = $item->ambient_temp;

                        array_push($data1, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float) number_format($nutr_tmp_avg, 1, '.', ''),
                        ]);

                        // array_push($data2, [
                        //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        //     (float) number_format($NH4_avg, 1, '.', ''),
                        // ]);
                    }
                }

                $series[] = ['name' => 'Avg Temp', 'type' => 'spline', 'data' => $data1];
                // $series[] = ['name' => 'Average NH4', 'type' => 'spline', 'data' => $data2];

                // force boostmode
                $plotOptions['series']['boostThreshold'] = 1;
                $plotOptions['series']['turboThreshold'] = 1;

                $graph_data = [
                    'graph' => array(
                        'series' => $series,
                        'yAxis' => array(
                            'title' => array(
                                'text' => 'Soil Temp Average'
                            )
                        ),
                        'title' => [
                            'text' => $field->field_name . ' - Soil Temp Average (' . $uom_symbol . ') - ' . $request->node_address,
                            'widthAdjust' => -200
                        ],
                        'plotOptions' => $plotOptions
                    ),
                    'x_max' => $x_max,
                    'x_min' => $x_min,
                ];

                // attempt to add in nutrient gauge values
                if (!empty($field->nutrient_template_id) && $field->nutrient_template_id) {

                    //Log::debug("nutrient_ppm_avg: {$request->node_address} -> {$field->nutrient_template_id}");
                    $results = Calculations::calcNutrientAverageGaugeValues($request->node_address, $field->nutrient_template_id);


                    $graph_data['nutrient_lower'] = $results['nutrient_lower'];
                    $graph_data['nutrient_upper'] = $results['nutrient_upper'];
                    $graph_data['nutrient_gauge'] = $results['nutrient_gauge'];
                    $graph_data['nutrient_avg']   = $results['nutrient_avg'];
                    $graph_data['nutrient_label'] = $results['nutrient_label'];
                    // $graph_data['nutrient_NO3']   = round($N03_avg, 1, PHP_ROUND_HALF_DOWN);
                    // $graph_data['nutrient_NH4']   = round($NH4_avg, 1, PHP_ROUND_HALF_DOWN);
                }

                break;

            case 'nutrient_ec_avg':
                $y_min = 0;
                $y_max = 0;

                $data1 = [];
                // $data2 = [];

                // get template values
                $tpl_values = '';
                $ntpl = nutrient_templates::where('id', $field->nutrient_template_id)->first();
                if ($ntpl) {
                    $tpl_values = json_decode($ntpl->template, true);
                }

                if (is_array($tpl_values)) {

                    // $poly1 = $tpl_values['poly1'] ?: 1;
                    // $poly2 = $tpl_values['poly2'] ?: 0;

                    foreach ($nutr_data as $item) {
                        // create new date stripping off seconds
                        $dt = new \DateTime(substr($item->date_sampled, 0, -3) . ':00');
                        $dt->setTimezone(new \DateTimeZone($this->tz));


                        $nutr_ec_avg = 0;
                        $count = 0;

                        if (isset($item->M2_1)) {
                            $count++;
                            $nutr_ec_avg += $item->M2_1;
                        }
                        if (isset($item->M2_2)) {
                            $count++;
                            $nutr_ec_avg += $item->M2_2;
                        }
                        if (isset($item->M2_3)) {
                            $count++;
                            $nutr_ec_avg += $item->M2_3;
                        }
                        if (isset($item->M2_4)) {
                            $count++;
                            $nutr_ec_avg += $item->M2_4;
                        }
                        if (isset($item->M2_5)) {
                            $count++;
                            $nutr_ec_avg += $item->M2_5;
                        }
                        if (isset($item->M2_6)) {
                            $count++;
                            $nutr_ec_avg += $item->M2_6;
                        }

                        $nutr_ec_avg = (($count > 0) ? ($nutr_ec_avg / $count) : 0);
                        // $nutr_sm_avg = $item->ambient_temp;

                        array_push($data1, [
                            ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            (float) number_format($nutr_ec_avg, 2, '.', ''),
                        ]);

                        // array_push($data2, [
                        //     ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                        //     (float) number_format($NH4_avg, 1, '.', ''),
                        // ]);
                    }
                }

                $series[] = ['name' => 'Average Nutrient EC', 'type' => 'spline', 'data' => $data1];
                // $series[] = ['name' => 'Average NH4', 'type' => 'spline', 'data' => $data2];

                // force boostmode
                $plotOptions['series']['boostThreshold'] = 1;
                $plotOptions['series']['turboThreshold'] = 1;

                $graph_data = [
                    'graph' => array(
                        'series' => $series,
                        'yAxis' => array(
                            'title' => array(
                                'text' => 'Nutrients EC Average'
                            )
                        ),
                        'title' => [
                            'text' => $field->field_name . ' - Nutrients EC Average - ' . $request->node_address,
                            'widthAdjust' => -200
                        ],
                        'plotOptions' => $plotOptions
                    ),
                    'x_max' => $x_max,
                    'x_min' => $x_min,
                ];

                // attempt to add in nutrient gauge values
                if (!empty($field->nutrient_template_id) && $field->nutrient_template_id) {

                    //Log::debug("nutrient_ppm_avg: {$request->node_address} -> {$field->nutrient_template_id}");
                    $results = Calculations::calcNutrientAverageGaugeValues($request->node_address, $field->nutrient_template_id);


                    $graph_data['nutrient_lower'] = $results['nutrient_lower'];
                    $graph_data['nutrient_upper'] = $results['nutrient_upper'];
                    $graph_data['nutrient_gauge'] = $results['nutrient_gauge'];
                    $graph_data['nutrient_avg']   = $results['nutrient_avg'];
                    $graph_data['nutrient_label'] = $results['nutrient_label'];
                    // $graph_data['nutrient_NO3']   = round($N03_avg, 1, PHP_ROUND_HALF_DOWN);
                    // $graph_data['nutrient_NH4']   = round($NH4_avg, 1, PHP_ROUND_HALF_DOWN);
                }

                break;
        }


        // TODO: detect first time, don't log subsequent ajax queries
        $this->acc->logActivity('Graph', 'Soil Moisture', $hwconfig->node_address);

        // push permissions to frontend
        if ($grants) {
            $graph_data['grants'] = $grants;
        }

        $related_nodes = array_map(function ($item) {
            return $item['node_address'];
        }, $related_nodes);
        sort($related_nodes);
        $graph_data['related_nodes'] = $related_nodes;

        // common route related fields
        $graph_data['last_date'] = $last_date;
        $graph_data['node_id'] = $hwconfig->id;
        $graph_data['field_name'] = $field->field_name;
        $graph_data['graph_type'] = $graph_type;
        $graph_data['graph_start_date'] = $graph_start_date;
        $graph_data['sub_days'] = empty($graph_start_date) ? $diff_days : 'custom';
        $graph_data['not_current'] = $not_current;

        $graph_data['restricted_to'] = $this->acc->restricted_to;

        return response()->json($graph_data);
    }

    // Wells Graph Data
    public function wells_graph(Request $request)
    {
        $request->validate([
            'node_address'     => 'required|string',
            'graph_type'       => 'nullable|string',
            'graph_start_date' => 'nullable',
            'sub_days'         => 'nullable',
            'is_initial'       => 'nullable',
            'selection_start'  => 'nullable',
            'selection_end'    => 'nullable'
        ]);

        $hwconfig = hardware_config::where('node_address', $request->node_address)->first();
        $field = fields::where('node_id', $request->node_address)->first();
        $hwm = hardware_management::where('id', $hwconfig->hardware_management_id)->first();
        $populated = [];
        $graph_data = [];
        $grants = [];

        $graph_start_date = null;

        // ensure node and field exists
        if (!$hwconfig || !$field) {
            return response()->json(['message' => 'nonexistent']);
        }

        // subsystem specific permission check
        if (!$this->acc->is_admin) {
            $subsystem = Utils::convertNodeTypeToSubsystem($hwconfig->node_type);
            $grants = $this->acc->requestAccess([$subsystem => ['p' => ['Graph'], 'o' => $hwconfig->id, 't' => 'O']]);
            if (empty($grants[$subsystem]['Graph']['O'])) {
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        // Get Node's Last Reading Date (UTC)
        if ($hwconfig->node_type == 'Wells') {
            $end_date = DB::table('node_data_meters')->where('node_id', $hwconfig->node_address)->orderBy('date_time', 'desc')->value('date_time');
        } else {
            return response()->json(['message' => 'invalid_node_type']);
        }

        // Probe has no data whatsoever
        if ($end_date == null) {
            return response()->json(['message' => 'no_data']);
        }

        // set timezone from user's timezone preferences
        $this->tz = $this->timezones[$this->acc->timezone];
        if (!$this->tz) {
            $this->tz = 'UTC';
        }
        $tzObj = new \DateTimeZone($this->tz);

        $graph_type = !empty($request->graph_type) ? $request->graph_type : $field->graph_type;
        // For when a Node type has changed (default to a sane graph type)
        $graph_type = !empty($graph_type) && $hwconfig->node_type == 'Wells' && !in_array($graph_type, ['pulse']) ? 'pulse' : $graph_type;

        // DETERMINE IF DATA IS CURRENT OR NOT
        // (WHEN THE LATEST DATA IS OLDER THAN A DAY)
        $not_current = false;
        $now = new \DateTime('now');
        $latest = new \DateTime($end_date);
        $diff = $now->diff($latest);
        $gap = $diff->format("%a");
        if ($gap > 1) {
            $not_current = true;
        }

        // manual range selection overrides graph_start_date
        if (!empty($request->selection_start) && !empty($request->selection_end)) {

            // JS timestamps are in milliseconds, convert to seconds
            $start = new \DateTime();
            $end   = new \DateTime();

            // Convert Javascript Timestamps to Unix Timestamps
            $start_ts = floor($request->selection_start / 1000);
            $end_ts = floor($request->selection_end / 1000);

            $start->setTimestamp($start_ts);
            $staged_start_date = $start->format('Y-m-d H:i:s');

            // Remove User's Timezone Offset from incoming Timestamps (Timezone Offset was added in previous request)
            $start_ts -= $tzObj->getOffset($start);
            $end_ts   -= $tzObj->getOffset($end);

            $start->setTimestamp($start_ts);
            $end->setTimestamp($end_ts);

            // to be used for determining the resolution
            $diff = $end->diff($start);
            $diff_days = $diff->format("%a");

            if (!$diff_days) {
                $diff_days = '1';
            } // fallback if difference is 0

            // UTC Dates for Querying DB
            $start_date = $start->format('Y-m-d H:i:s');

            // override end date with user's selection
            $end_date   = $end->format('Y-m-d H:i:s');

            // Range Selections override the Custom User Start Date
            $graph_start_date = $staged_start_date;
        } else {

            // INITIAL | CUSTOM | SUBDAYS

            if (!empty($request->is_initial)) {
                // if initial request, try get start date from field configuration
                $graph_start_date = $field->graph_start_date;
            } else if (!empty($request->graph_start_date)) {
                // else try get custom user chosen start date 
                $graph_start_date = $request->graph_start_date;
            }

            // Custom Start Date Set?
            if ($graph_start_date) {

                $start = new \DateTime($graph_start_date);
                $end   = new \DateTime($end_date);

                // if for some reason the user decides to choose a date newer than the end date,
                // create a custom 7 day interval
                if ($start >= $end) {
                    $start = new \DateTime($end_date);
                    $start->sub(new \DateInterval("P7D"));
                }

                $diff = $end->diff($start);       // works
                $diff_days = $diff->format("%a");

                if (!$diff_days) {
                    $diff_days = '1';
                } // fallback if difference is 0

                $staged_start_date = $start->format('Y-m-d H:i:s');

                // Remove Timezone to ensure start time is 00:00
                $ts = $start->getTimestamp();
                $ts -= $tzObj->getOffset($start);
                $start->setTimestamp($ts);

                // UTC Dates for Querying DB
                $start_date = $start->format('Y-m-d H:i:s');
                $graph_start_date = $staged_start_date;
            } else {

                $diff_days = !empty($request->sub_days) ? $request->sub_days : '7';

                $latest = new \DateTime($end_date);
                $latest->sub(new \DateInterval("P{$diff_days}D"));

                // Remove Timezone to ensure start time is 00:00
                $ts = $latest->getTimestamp();
                $ts -= $tzObj->getOffset($latest);
                $latest->setTimestamp($ts);

                // UTC Dates for Querying DB
                $start_date = $latest->format('Y-m-d H:i:s');

                // EXPERIMENTAL: ONLY SET CUSTOM DATE IF GAP IS NOT ONE OF SPECIFIC INTERVALS
                if (!in_array($gap, [1, 7, 14, 30, 365])) {
                    $graph_start_date = $start_date;
                }
            }
        }

        // FETCH DATA

        $query = node_data_meter::where('node_id', $request->node_address)
            ->where('date_time', '>=', $start_date)
            ->where('date_time', '<=', $end_date);

        $resolution = Utils::calc_graph_data_resolution($diff_days, 'date_time');
        if ($resolution) {
            $query->whereRaw($resolution);
        }

        $query->orWhereIn('date_time', [$start_date, $end_date]);
        $query->orderBy('date_time');

        $node_data = $query->get();

        // POPULATION DETECTION

        $index = 0;
        foreach ($node_data as $item) {
            if (!empty($item->date_time)) {
                $populated[$index] = true;
            }
            $index++;
        }

        $first_date = '';
        $last_date = '';

        $x_min = 1000;
        $x_max = 0;

        $y_min = 1000;
        $y_max = 0;

        // GET FIRST AND LAST READING DATES
        if ($node_data && $node_data->count()) {
            $first = $node_data->first();
            $node_first_date = new \DateTime($first->date_time);
            $node_first_date->setTimezone(new \DateTimeZone($this->tz));
            $first_date = $node_first_date->format('Y-m-d H:i:s');
            $x_min = ($node_first_date->getTimestamp() + $node_first_date->getOffset()) * 1000;

            $last = $node_data->last();
            $node_last_date = new \DateTime($last->date_time);
            $node_last_date->setTimezone(new \DateTimeZone($this->tz));
            $last_date = $node_last_date->format('Y-m-d H:i:s');
            $x_max = ($node_last_date->getTimestamp() + $node_last_date->getOffset()) * 1000;
        }

        $plotOptions = [
            'line' =>  [
                'allowPointSelect' => false,
            ],
            'series' =>  [
                'allowPointSelect' => true,
                'boostThreshold' => 0,
                'turboThreshold' => 0
            ]
        ];

        switch ($graph_type) {

            case 'pulse':

                $pulses = array();

                $meter_uom = $hwconfig->measurement_type;
                $user_uom = $this->acc->unit_of_measure == 1 ? 'Cubes' : 'Gallons';

                foreach ($node_data as $row) {
                    // only get rows with non-empty date_time fields
                    if (!empty($row->date_time)) {
                        $val = $row->pulse_1 * $hwconfig->pulse_weight;
                        // convert UTC dates to localized user timezone dates
                        $dt = new \DateTime($row->date_time);
                        $dt->setTimezone(new \DateTimeZone($this->tz));
                        $y_min = $val < $y_min ? $val : $y_min;
                        $y_max = $val > $y_max ? $val : $y_max;

                        array_push($pulses, [
                            'x' => ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            'y' => (float)($val)
                        ]);
                    }
                }

                $series = [
                    [
                        'name' => 'Pulses', 'data'  =>  $pulses
                    ]
                ];

                $graph_data = [
                    'graph' => [
                        'user_uom'  => $user_uom,
                        'meter_uom' => $meter_uom,
                        'series' => $series,
                        'title' => [
                            'text' => $field->field_name . ' - Wells - ' . $request->node_address,
                            'widthAdjust' => -200
                        ],
                        'plotOptions' => $plotOptions
                    ]
                ];

                break;

            case 'tech':

                $y_min = 0;
                $y_max = 8000;

                $voltages = [];
                $ambient_temps = [];

                if ($node_data) {

                    $index = 0;
                    $tzObj = new \DateTimeZone($this->tz);

                    foreach ($node_data as $row) {
                        // only get rows with non-empty date_time fields
                        if (!empty($populated[$index])) {

                            $val = $row->batt_volt;

                            // convert UTC dates to localized user timezone dates
                            $dt = new \DateTime($row->date_time);
                            $dt->setTimezone($tzObj);

                            $y_min = $val < $y_min ? $val : $y_min;
                            $y_max = $val > $y_max ? $val : $y_max;

                            array_push($voltages, [
                                'x' => ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                                'y' => (float)($val)
                            ]);
                        }
                        $index++;
                    }
                }

                $graph_data = [
                    'graph' => array(
                        'series' => [
                            ['name' => 'Battery Voltage', 'type' => 'spline', 'data' => $voltages],
                            ['name' => 'Ambient Temperatures', 'type' => 'spline', 'data' => $ambient_temps]
                        ],
                        'yAxis' => array(
                            'title' => array(
                                'text' => ''
                            )
                        ),
                        'title' => [
                            'text' => $field->field_name . ' - Technical - ' . $request->node_address,
                            'widthAdjust' => -200
                        ],
                        'plotOptions' => $plotOptions
                    ),
                    'x_min' => $x_min,
                    'x_max' => $x_max,
                    'y_max' => (float)($y_max) + 1,
                    'y_min' => (float)($y_min) - 1,
                ];

                break;
        }

        $this->acc->logActivity('Graph', 'Well Controls', $hwconfig->node_address);

        // push permissions to frontend
        if ($grants) {
            $graph_data['grants'] = $grants;
        }

        // common route related fields
        $graph_data['last_date'] = $last_date;
        $graph_data['node_id'] = $hwconfig->id;
        $graph_data['field_name'] = $field->field_name;
        $graph_data['graph_type'] = $graph_type;
        $graph_data['graph_start_date'] = $graph_start_date;
        $graph_data['sub_days'] = empty($graph_start_date) ? $diff_days : 'custom';
        $graph_data['not_current'] = $not_current;

        return response()->json($graph_data);
    }

    // Meter Graph Data
    public function meters_graph(Request $request)
    {
        $request->validate([
            'node_address'     => 'required|string',
            'graph_type'       => 'nullable|string',
            'graph_start_date' => 'nullable',
            'sub_days'         => 'nullable',
            'is_initial'       => 'nullable',
            'selection_start'  => 'nullable',
            'selection_end'    => 'nullable'
        ]);

        $hwconfig = hardware_config::where('node_address', $request->node_address)->first();
        $field = fields::where('node_id', $request->node_address)->first();
        $populated = [];
        $graph_data = [];
        $grants = [];

        $graph_start_date = null;

        // ensure node and field exists
        if (!$hwconfig || !$field) {
            return response()->json(['message' => 'nonexistent']);
        }

        // subsystem specific permission check
        if (!$this->acc->is_admin) {
            $subsystem = Utils::convertNodeTypeToSubsystem($hwconfig->node_type);
            $grants = $this->acc->requestAccess([$subsystem => ['p' => ['Graph'], 'o' => $hwconfig->id, 't' => 'O']]);
            if (empty($grants[$subsystem]['Graph']['O'])) {
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        // Get Node's Last Reading Date (UTC)
        if ($hwconfig->node_type == 'Water Meter') {
            // $end_date = DB::table('node_data_meters')->where('node_id', $hwconfig->node_address)->orderBy('date_time', 'desc')->value('date_time');
            $end_date = DB::table('node_data')->where('probe_id', $hwconfig->node_address)->orderBy('date_time', 'desc')->value('date_time');
        } else {
            return response()->json(['message' => 'invalid_node_type']);
        }

        // Probe has no data whatsoever
        if ($end_date == null) {
            return response()->json(['message' => 'no_data']);
        }

        // set timezone from user's timezone preferences
        $this->tz = $this->timezones[$this->acc->timezone];
        if (!$this->tz) {
            $this->tz = 'UTC';
        }
        $tzObj = new \DateTimeZone($this->tz);

        $graph_type = !empty($request->graph_type) ? $request->graph_type : $field->graph_type;
        $graph_type = !empty($graph_type) && $hwconfig->node_type == 'Water Meter' && !in_array($graph_type, ['pulse', 'tech']) ? 'pulse' : $graph_type;
        $graph_type = empty($graph_type) && $hwconfig->node_type == 'Water Meter' ? 'pulse' : $graph_type;

        // DETERMINE IF DATA IS CURRENT OR NOT
        // (WHEN THE LATEST DATA IS OLDER THAN A DAY)
        $not_current = false;
        $now = new \DateTime('now');
        $latest = new \DateTime($end_date);
        $diff = $now->diff($latest);
        $gap = $diff->format("%a");
        if ($gap > 1) {
            $not_current = true;
        }

        // manual range selection overrides graph_start_date
        if (!empty($request->selection_start) && !empty($request->selection_end)) {

            // JS timestamps are in milliseconds, convert to seconds
            $start = new \DateTime();
            $end   = new \DateTime();

            // Convert Javascript Timestamps to Unix Timestamps
            $start_ts = floor($request->selection_start / 1000);
            $end_ts = floor($request->selection_end / 1000);

            $start->setTimestamp($start_ts);
            $staged_start_date = $start->format('Y-m-d H:i:s');

            // Remove User's Timezone Offset from incoming Timestamps (Timezone Offset was added in previous request)
            $start_ts -= $tzObj->getOffset($start);
            $end_ts   -= $tzObj->getOffset($end);

            $start->setTimestamp($start_ts);
            $end->setTimestamp($end_ts);

            // to be used for determining the resolution
            $diff = $end->diff($start);
            $diff_days = $diff->format("%a");

            if (!$diff_days) {
                $diff_days = '1';
            } // fallback if difference is 0

            // UTC Dates for Querying DB
            $start_date = $start->format('Y-m-d H:i:s');

            // override end date with user's selection
            $end_date   = $end->format('Y-m-d H:i:s');

            // Range Selections override the Custom User Start Date
            $graph_start_date = $staged_start_date;
        } else {

            // INITIAL | CUSTOM | SUBDAYS

            if (!empty($request->is_initial)) {
                // if initial request, try get start date from field configuration
                $graph_start_date = $field->graph_start_date;
            } else if (!empty($request->graph_start_date)) {
                // else try get custom user chosen start date 
                $graph_start_date = $request->graph_start_date;
            }

            // Custom Start Date Set?
            if ($graph_start_date) {

                $start = new \DateTime($graph_start_date);
                $end   = new \DateTime($end_date);

                // if for some reason the user decides to choose a date newer than the end date,
                // create a custom 7 day interval
                if ($start >= $end) {
                    $start = new \DateTime($end_date);
                    $start->sub(new \DateInterval("P7D"));
                }

                $diff = $end->diff($start);       // works
                $diff_days = $diff->format("%a");

                if (!$diff_days) {
                    $diff_days = '1';
                } // fallback if difference is 0

                $staged_start_date = $start->format('Y-m-d H:i:s');

                // Remove Timezone to ensure start time is 00:00
                $ts = $start->getTimestamp();
                $ts -= $tzObj->getOffset($start);
                $start->setTimestamp($ts);

                // UTC Dates for Querying DB
                $start_date = $start->format('Y-m-d H:i:s');
                $graph_start_date = $staged_start_date;
            } else {

                $diff_days = !empty($request->sub_days) ? $request->sub_days : '7';

                $latest = new \DateTime($end_date);
                $latest->sub(new \DateInterval("P{$diff_days}D"));

                // Remove Timezone to ensure start time is 00:00
                $ts = $latest->getTimestamp();
                $ts -= $tzObj->getOffset($latest);
                $latest->setTimestamp($ts);

                // UTC Dates for Querying DB
                $start_date = $latest->format('Y-m-d H:i:s');

                // EXPERIMENTAL: ONLY SET CUSTOM DATE IF GAP IS NOT ONE OF SPECIFIC INTERVALS
                if (!in_array($gap, [1, 7, 14, 30, 365])) {
                    $graph_start_date = $start_date;
                }
            }
        }

        // FETCH DATA

        // $query = node_data_meter::where('node_id', $request->node_address)
        // ->where('date_time', '>=', $start_date)
        // ->where('date_time', '>=', $end_date);

        $query = node_data::where('probe_id', $request->node_address)
            ->where('date_time', '>=', $start_date);
        if ($end_date) {
            $query->where('date_time', '<=', $end_date);
        }

        $resolution = Utils::calc_graph_data_resolution($diff_days, 'date_time');
        if ($resolution) {
            $query->whereRaw($resolution);
        }

        $query->orWhereIn('date_time', [$start_date, $end_date]);
        $query->orderBy('date_time');

        $node_data = $query->get();

        // POPULATION DETECTION

        $index = 0;
        foreach ($node_data as $item) {
            if (!empty($item->date_time)) {
                $populated[$index] = true;
            }
            $index++;
        }

        $first_date = '';
        $last_date = '';

        $x_min = 1000;
        $x_max = 0;

        $y_min = 1000;
        $y_max = 0;

        // GET FIRST AND LAST READING DATES
        if ($node_data && $node_data->count()) {
            $first = $node_data->first();
            $node_first_date = new \DateTime($first->date_time);
            $node_first_date->setTimezone(new \DateTimeZone($this->tz));
            $first_date = $node_first_date->format('Y-m-d H:i:s');
            $x_min = ($node_first_date->getTimestamp() + $node_first_date->getOffset()) * 1000;

            $last = $node_data->last();
            $node_last_date = new \DateTime($last->date_time);
            $node_last_date->setTimezone(new \DateTimeZone($this->tz));
            $last_date = $node_last_date->format('Y-m-d H:i:s');
            $x_max = ($node_last_date->getTimestamp() + $node_last_date->getOffset()) * 1000;
        }

        $plotOptions = [
            'line' => [
                'allowPointSelect' => false,
            ],
            'series' => [
                'allowPointSelect' => true,
                'boostThreshold' => 0,
                'turboThreshold' => 0
            ]
        ];

        switch ($graph_type) {

            case 'pulse':

                $pulses = array();

                $meter_uom = $hwconfig->measurement_type;
                $user_uom = $this->acc->unit_of_measure == 1 ? 'Cubes' : 'Gallons';

                $tzObj = new \DateTimeZone($this->tz);

                foreach ($node_data as $row) {
                    // only get rows with non-empty date_time fields
                    if (!empty($row->date_time)) {
                        // $val = $row->rg * $hwconfig->pulse_weight;
                        $val = $row->rg;

                        // convert UTC dates to localized user timezone dates
                        $dt = new \DateTime($row->date_time);
                        $dt->setTimezone($tzObj);

                        $y_min = $val < $y_min ? $val : $y_min;
                        $y_max = $val > $y_max ? $val : $y_max;

                        array_push($pulses, [
                            'x' => ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                            'y' => (float)($val)
                        ]);
                    }
                }

                $series = [
                    [
                        'name' => 'Pulses', 'data'  =>  $pulses
                    ]
                ];

                $graph_data = [
                    'graph' => [
                        'user_uom'  => $user_uom,
                        'meter_uom' => $meter_uom,
                        'series' => $series,
                        'title' => [
                            'text' => $field->field_name . ' - Meters - ' . $request->node_address,
                            'widthAdjust' => -200
                        ],
                        'plotOptions' => $plotOptions
                    ],
                    'x_min' => $x_min,
                    'x_max' => $x_max,
                    'y_max' => (float)($y_max) + 1,
                    'y_min' => (float)($y_min) - 1,
                ];

                break;

            case 'tech':


                $meter_uom = $hwconfig->measurement_type;
                $user_uom = $this->acc->unit_of_measure == 1 ? 'Cubes' : 'Gallons';

                $y_min = 0;
                $y_max = 8000;

                $voltages = [];
                $ambient_temps = [];

                if ($node_data) {

                    $index = 0;
                    $tzObj = new \DateTimeZone($this->tz);

                    foreach ($node_data as $row) {
                        $user = Auth::user();

                        if ($user->unit_of_measure  == 2) {
                            $seriesCalc = $row->ambient_temp * (9 / 5) + 32;
                        } else if ($user->unit_of_measure  == 1) {
                            $seriesCalc = $row->ambient_temp;
                        }

                        // only get rows with non-empty date_time fields
                        if (!empty($populated[$index])) {

                            $val = $row->bv;

                            // convert UTC dates to localized user timezone dates
                            $dt = new \DateTime($row->date_time);
                            $dt->setTimezone($tzObj);
                            $timestamp = ($dt->getTimestamp() + $dt->getOffset()) * 1000;

                            $y_min = $val < $y_min ? $val : $y_min;
                            $y_max = $val > $y_max ? $val : $y_max;

                            if ($row->bv) {
                                array_push($voltages, [
                                    'x' => $timestamp,
                                    'y' => (float)($row->bv / 1000)
                                ]);
                            }
                            if ($row->ambient_temp) {
                                array_push($ambient_temps, [
                                    'x' => $timestamp,
                                    'y' => (float)($seriesCalc)
                                ]);
                            }
                        }
                        $index++;
                    }
                }

                $graph_data = [
                    'graph' => array(
                        'series' => [
                            ['name' => 'Battery Voltage', 'type' => 'spline', 'data' => $voltages],
                            ['name' => 'Ambient Temperatures', 'type' => 'spline', 'data' => $ambient_temps]
                        ],
                        'yAxis' => array(
                            'title' => array(
                                'text' => ''
                            )
                        ),
                        'user_uom'  => $user_uom,
                        'meter_uom' => $meter_uom,
                        'title' => [
                            'text' => $field->field_name . ' - Technical - ' . $request->node_address,
                            'widthAdjust' => -200
                        ],
                        'plotOptions' => $plotOptions
                    ),
                    'x_min' => $x_min,
                    'x_max' => $x_max,
                    'y_max' => (float)($y_max) + 1,
                    'y_min' => (float)($y_min) - 1,
                ];

                break;
        }

        $this->acc->logActivity('Graph', 'Meters', $hwconfig->node_address);

        // push permissions to frontend
        if ($grants) {
            $graph_data['grants'] = $grants;
        }

        // common route related fields
        $graph_data['last_date'] = $last_date;
        $graph_data['node_id'] = $hwconfig->id;
        $graph_data['field_name'] = $field->field_name;
        $graph_data['graph_type'] = $graph_type;
        $graph_data['graph_start_date'] = $graph_start_date;
        $graph_data['sub_days'] = empty($graph_start_date) ? $diff_days : 'custom';
        $graph_data['not_current'] = $not_current;

        return response()->json($graph_data);
    }

    public function nutrient_type($nutr_data, $request, $field)
    {

        $dataN03 = [];
        $data2NH4 = [];

        // get template values
        $tpl_values = '';
        $ntpl = nutrient_templates::where('id', $field->nutrient_template_id)->first();
        if ($ntpl) {
            $tpl_values = json_decode($ntpl->template, true);
        }

        if (is_array($tpl_values)) {

            $poly1 = $tpl_values['poly1'] ?: 1;
            $poly2 = $tpl_values['poly2'] ?: 0;

            foreach ($nutr_data as $item) {
                // create new date stripping off seconds
                $dt = new \DateTime(substr($item->date_sampled, 0, -3) . ':00');
                $dt->setTimezone(new \DateTimeZone($this->tz));


                $N03_avg = 0;
                $count = 0;

                if (isset($item->M3_1)) {
                    $count++;
                    $N03_avg += ($item->M3_1 * $poly1) + $poly2;
                }
                if (isset($item->M4_1)) {
                    $count++;
                    $N03_avg += (($item->M4_1 * $poly1) + $poly2);
                }
                if (isset($item->M5_1)) {
                    $count++;
                    $N03_avg += (($item->M5_1 * $poly1) + $poly2);
                }
                if (isset($item->M6_1)) {
                    $count++;
                    $N03_avg += ($item->M6_1 * $poly1) + $poly2;
                }
                if (isset($item->M3_3)) {
                    $count++;
                    $N03_avg += (($item->M3_3 * $poly1) + $poly2);
                }
                if (isset($item->M4_3)) {
                    $count++;
                    $N03_avg += (($item->M4_3 * $poly1) + $poly2);
                }
                if (isset($item->M5_3)) {
                    $count++;
                    $N03_avg += (($item->M5_3 * $poly1) + $poly2);
                }
                if (isset($item->M6_3)) {
                    $count++;
                    $N03_avg += (($item->M6_3 * $poly1) + $poly2);
                }

                $N03_avg = (($count > 0) ? ($N03_avg / $count) : 0);


                $NH4_avg = 0;
                $count2 = 0;
                if (isset($item->M3_2)) {
                    $count2++;
                    $NH4_avg += ($item->M3_2 * $poly1) + $poly2;
                }
                if (isset($item->M4_2)) {
                    $count2++;
                    $NH4_avg += (($item->M4_2 * $poly1) + $poly2);
                }
                if (isset($item->M5_2)) {
                    $count2++;
                    $NH4_avg += (($item->M5_2 * $poly1) + $poly2);
                }
                if (isset($item->M6_2)) {
                    $count2++;
                    $NH4_avg += ($item->M6_2 * $poly1) + $poly2;
                }
                if (isset($item->M3_4)) {
                    $count2++;
                    $NH4_avg += (($item->M3_4 * $poly1) + $poly2);
                }
                if (isset($item->M4_4)) {
                    $count2++;
                    $NH4_avg += (($item->M4_4 * $poly1) + $poly2);
                }
                if (isset($item->M5_4)) {
                    $count2++;
                    $NH4_avg += (($item->M5_4 * $poly1) + $poly2);
                }
                if (isset($item->M6_3)) {
                    $count2++;
                    $NH4_avg += (($item->M6_4 * $poly1) + $poly2);
                }

                $NH4_avg = (($count2 > 0) ? ($NH4_avg / $count2) : 0);


                array_push($dataN03, [
                    ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                    (float) number_format($N03_avg, 1, '.', ''),
                ]);

                array_push($data2NH4, [
                    ($dt->getTimestamp() + $dt->getOffset()) * 1000,
                    (float) number_format($NH4_avg, 1, '.', ''),
                ]);
            }
        }

        $series[] = ['name' => 'Average N03', 'type' => 'spline', 'data' => $dataN03];
        $series[] = ['name' => 'Average NH4', 'type' => 'spline', 'data' => $data2NH4];

        // force boostmode

        // attempt to add in nutrient gauge values
        if (!empty($field->nutrient_template_id) && $field->nutrient_template_id) {

            //Log::debug("nutrient_ppm_avg: {$request->node_address} -> {$field->nutrient_template_id}");
            $results = Calculations::calcNutrientAverageGaugeValues($request->node_address, $field->nutrient_template_id);
            // dd($N03_avg);

            $graph_data['nutrient_NO3']   = round($N03_avg, 1, PHP_ROUND_HALF_DOWN);
            $graph_data['nutrient_NH4']   = round($NH4_avg, 1, PHP_ROUND_HALF_DOWN);
        }
    }
}
