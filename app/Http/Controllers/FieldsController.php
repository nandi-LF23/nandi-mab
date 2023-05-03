<?php

namespace App\Http\Controllers;

// use Auth;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\fields;
use App\Models\node_data;
use App\Models\nutri_data;
use App\Models\nutrient_templates;
use App\Models\nutrient_template_data;
use App\Models\node_data_metaer;
use App\Models\hardware_config;
use App\Models\hardware_management;
use App\Models\cultivars_management;
use App\Models\cultivars;
use App\Calculations;
use App\Utils;

use TorMorten\Eventy\Facades\Events as Eventy;

class FieldsController extends Controller
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

    // Manage Soil Moisture Form Populate
    public function manage_sm(Request $request)
    {
        if (empty($request->node_address)) {
            return response()->json(['message' => 'missing_addr']);
        }

        $sm_data = [];
        $node_data_meters = [];
        $grants = [];

        $this->tz = $this->timezones[$this->acc->timezone];
        if (!$this->tz) {
            $this->tz = 'UTC';
        }

        $tzObj = new \DateTimeZone($this->tz);
        $todays_date = new \DateTime('now');
        $todays_date->setTimezone($tzObj);

        // Get Node Config record
        $hwconfig = hardware_config::where("node_address", $request->node_address)->first();
        if (!$hwconfig) {
            return response()->json(['message' => 'missing_node']);
        }

        // Get Field associated with Node Config
        $field = fields::where('node_id', $request->node_address)->first();
        if (!$field) {
            return response()->json(['message' => 'missing_field']);
        }

        // transitional code: previous code delay-created cm records, but since we now do permission checking,
        // we need the cm row to exist beforehand -- so we create it (only if it doesnt exist yet).
        $cm = cultivars_management::where('field_id', $field->id)->first();
        if (!$cm) {
            $cm = cultivars_management::create([
                'NI' => 1, 'NR' => 1,
                'field_id' => $field->id,
                'company_id' => $field->company_id
            ]);
        }

        if (!$this->acc->is_admin) {
            // permission check
            $grants = $this->acc->requestAccess([
                'Soil Moisture' => ['p' => ['All'], 'o' => $hwconfig->id, 't' => 'O'],
                'Cultivars'     => ['p' => ['All']],
            ]);
            if (empty($grants['Soil Moisture']['Edit']['O'])) {
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        // Get Latest SM Record
        $node_data = node_data::where("probe_id", $request->node_address)->orderBy('id', 'desc')->first();

        // Ensure there is data
        if ($node_data) {

            // Set timezone on Date
            $dt = new \DateTime($node_data->date_time);
            $dt->setTimezone(new \DateTimeZone($this->tz));
            $field->date_time = $dt->format('Y-m-d H:i:s');

            // data1 == average, data2 == accumulative
            $moisture = $field->graph_model == 'ave' ? 'average' : 'accumulative';

            // Calculate Status
            $result = Calculations::calcStatus(
                (float)$node_data->{$moisture},
                $field->id,
                (float)$field->full,
                (float)$field->refill,
                $todays_date,
                $tzObj,
                false /* Debug */
            );

            if (is_array($result)) {
                $field->status = $result['status'];
            } else {
                $field->status = 0;
            }
        } else {
            $field->date_time = '1970-01-01 00:00:00';
            $field->status = 0;
        }

        // Ensure Graph Type is sane
        if (!in_array($field->graph_type, ['sm', 'sum', 'ave', 'temp', 'tech'])) {
            $field->graph_type = 'ave';
        }

        $power_state = Utils::calculatePowerState($request->node_address);

        // Get Todays Date
        $dt = new \DateTime('now');
        $tzObj = new \DateTimeZone($this->tz);
        $dt->setTimezone($tzObj);
        $date_now = $dt->format('Y-m-d H:i');

        $sm_data = [
            'node_id' => $hwconfig->id,
            'fields' => $field,
            'grants' => $grants,
            'power_state' => $power_state,
            'date_now' => $date_now,
            'cm_id' => $cm->id
        ];

        $this->acc->logActivity('View', 'Soil Moisture', "Node: {$hwconfig->node_address} ({$hwconfig->id})");

        return response()->json($sm_data);
    }

    // Manage Nutrients Form Populate
    public function manage_n(Request $request)
    {
        if (empty($request->node_address)) {
            return response()->json(['message' => 'missing_addr']);
        }

        $this->tz = $this->timezones[$this->acc->timezone];
        if (!$this->tz) {
            $this->tz = 'UTC';
        }

        $node_address = $request->node_address;
        $grants = [];

        // Get Node Config record
        $hwconfig = hardware_config::where("node_address", $node_address)->first();
        if (!$hwconfig) {
            return response()->json(['message' => 'missing_node']);
        }

        // Get Field associated with Node Config
        $field = fields::where('node_id', $node_address)->first();
        if (!$field) {
            return response()->json(['message' => 'missing_field']);
        }

        if (!$this->acc->is_admin) {
            // permission check
            $grants = $this->acc->requestAccess([
                'Nutrients' => ['p' => ['All'], 'o' => $hwconfig->id, 't' => 'O'],
                'Nutrient Templates'  => ['p' => ['All']],
            ]);
            if (empty($grants['Nutrients']['Edit']['O'])) {
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        // defaults
        $data = [
            'raw'    => ['M3_1' => 0, 'M3_2' => 0, 'M3_3' => 0, 'M3_4' => 0, 'avg_M3' => 0, 'M4_1' => 0, 'M4_2' => 0, 'M4_3' => 0, 'M4_4' => 0, 'avg_M4' => 0, 'M5_1' => 0, 'M5_2' => 0, 'M5_3' => 0, 'M5_4' => 0, 'avg_M5' => 0, 'M6_1' => 0, 'M6_2' => 0, 'M6_3' => 0, 'M6_4' => 0, 'avg_M3' => 0],
            'ppm'    => ['M3_1' => 0, 'M3_2' => 0, 'M3_3' => 0, 'M3_4' => 0, 'avg_M3' => 0, 'M4_1' => 0, 'M4_2' => 0, 'M4_3' => 0, 'M4_4' => 0, 'avg_M4' => 0, 'M5_1' => 0, 'M5_2' => 0, 'M5_3' => 0, 'M5_4' => 0, 'avg_M5' => 0, 'M6_1' => 0, 'M6_2' => 0, 'M6_3' => 0, 'M6_4' => 0, 'avg_M3' => 0],
            'pounds' => ['M3_1' => 0, 'M3_2' => 0, 'M3_3' => 0, 'M3_4' => 0, 'avg_M3' => 0, 'M4_1' => 0, 'M4_2' => 0, 'M4_3' => 0, 'M4_4' => 0, 'avg_M4' => 0, 'M5_1' => 0, 'M5_2' => 0, 'M5_3' => 0, 'M5_4' => 0, 'avg_M5' => 0, 'M6_1' => 0, 'M6_2' => 0, 'M6_3' => 0, 'M6_4' => 0, 'avg_M3' => 0],
            'soil_moist' => ['M0_1' => 0, 'M0_2' => 0, 'M0_3' => 0, 'M0_4' => 0, 'avg_M0' => 0],
            'temp' => ['M1_1' => 0, 'M1_2' => 0, 'M1_3' => 0, 'M1_4' => 0, 'avg_M1' => 0],
            'ec' => ['M2_1' => 0, 'M2_2' => 0, 'M2_3' => 0, 'M2_4' => 0, 'avg_M2' => 0],
            'lower_limit' => 0,
            'upper_limit' => 0,
            'nutrient_label' => '',
            'metric' => 0,
            'latest_date_reported' => '1970-01-01 00:00:00',
            'active_template_id' => null,
            'active_template_name' => 'No template applied yet',
            'install_depth' => 2.4
        ];

        if ($field) {

            // Currently evaluating this query...time will tell
            $row = nutri_data::where('node_address', $node_address)
                //  ->whereIn('identifier', ['M3_1', 'M4_1', 'M5_1', 'M6_1'])
                ->orderBy('id', 'desc')
                ->limit(1)->get();
            $row = collect($row);
            //       print_r($data) . PHP_EOL;

            /*print_r($row) . PHP_EOL;
die;*/
            $values = null;

            $raw_total    = 0;
            $ppm_total    = 0;
            $pounds_total = 0;
            $count        = 4;
            $gauge_value  = 0;

            // 0. prefetch template
            if (!empty($field->nutrient_template_id)) {
                $ntpl = nutrient_templates::where('id', $field->nutrient_template_id)->first();
                if ($ntpl) {
                    $data['active_template_id']   = $ntpl->id;
                    $data['active_template_name'] = $ntpl->name;
                    $data['current_template'] = $ntpl->template;
                    $values = json_decode($ntpl->template, true);
                }
            }

            // 1. we have data and template values to apply
            /*  if($count == 4 && $values)*/ {

                $dt = new \DateTime($row[0]->date_reported);
                $tzObj = new \DateTimeZone($this->tz);
                $dt->setTimezone($tzObj);
                $data['latest_date_reported'] = $dt->format('Y-m-d H:i:s');

                // Current template fields

                $poly1          = $values['poly1'] ?: 1;
                $poly2          = $values['poly2'] ?: 1;
                $lower_limit    = $values['lower_limit'] ?: 0;
                $upper_limit    = $values['upper_limit'] ?: 0;
                $nutrient_label = $ntpl->name ?: '';
                $metric         = $values['metric'] ?: '';
                $pound_conv     = $field->install_depth ?: 2.4;



                /* deprecated
                    $ppm_val   = ($row->value * $poly1) + $poly2;
                    $pound_val = $ppm_val * $pound_conv;
*/

                //number_format($, 2, '.', '')


                //nandi added for the nutrient management template (please dont call me pritt lol)

                $data['soil_moist']['M0_1']    = number_format($row[0]->M0_1, 2, '.', '');
                $data['soil_moist']['M0_2']    = number_format($row[0]->M0_2, 2, '.', '');
                $data['soil_moist']['M0_3']    = number_format($row[0]->M0_3, 2, '.', '');
                $data['soil_moist']['M0_4']    = number_format($row[0]->M0_4, 2, '.', '');

                $data['soil_moist']['avg_M0'] = number_format(($data['soil_moist']['M0_1'] + $data['soil_moist']['M0_2'] + $data['soil_moist']['M0_3'] + $data['soil_moist']['M0_4']) / 4, 2);

                $data['temp']['M1_1']    = number_format($row[0]->M1_1, 1, '.', '');
                $data['temp']['M1_2']    = number_format($row[0]->M1_2, 1, '.', '');
                $data['temp']['M1_3']    = number_format($row[0]->M1_3, 1, '.', '');
                $data['temp']['M1_4']    = number_format($row[0]->M1_4, 1, '.', '');

                $data['temp']['avg_M1'] = number_format(($data['temp']['M1_1'] + $data['temp']['M1_2'] + $data['temp']['M1_3'] + $data['temp']['M1_4']) / 4, 1);

                //$temp_avg = $data['temp']['avg_M1'];

                $user = Auth::user();

                    if ($user->unit_of_measure  == 2) {
                        $data['temp']['avg_M1'] = ($data['temp']['avg_M1'] * (9 / 5) + 32);
                        $data['temp_uom'] = ' °F';
                    } else if ($user->unit_of_measure  == 1) {
                        $data['temp']['avg_M1'] = $data['temp']['avg_M1'];
                        $data['temp_uom'] = ' °C';
                    }
                    //$data['temp']['avg_M1'] = $temp_avg;


                // $user = Auth::user();

                // if (isset($data['temp']['avg_M1'])) {
                //     if ($user->unit_of_measure  == 2) {
                //         $data['temp']['avg_M1'] = ($data['temp']['avg_M1'] * (9 / 5) + 32) . ' °F';
                //     } else if ($user->unit_of_measure  == 1) {
                //         $data['temp']['avg_M1'] = $data['temp']['avg_M1'] . ' °C';
                //     }
                // }


                // $data['ec']['M2_1']    = number_format($row[0]->M2_1, 2, '.', '');
                // $data['ec']['M2_2']    = number_format($row[0]->M2_2, 2, '.', '');
                // $data['ec']['M2_3']    = number_format($row[0]->M2_3, 2, '.', '');
                // $data['ec']['M2_4']    = number_format($row[0]->M2_4, 2, '.', '');

                $data['raw']['M3_1']    = number_format($row[0]->M3_1, 1, '.', '');
                $data['raw']['M3_2']    = number_format($row[0]->M3_2, 1, '.', '');
                $data['raw']['M3_3']    = number_format($row[0]->M3_3, 1, '.', '');
                $data['raw']['M3_4']    = number_format($row[0]->M3_4, 1, '.', '');

                $data['raw']['avg_M3'] = number_format(($data['raw']['M3_1'] + $data['raw']['M3_2'] + $data['raw']['M3_3'] + $data['raw']['M3_4']) / 4, 1);

                $data['raw']['M4_1']    = number_format($row[0]->M4_1, 1, '.', '');
                $data['raw']['M4_2']    = number_format($row[0]->M4_2, 1, '.', '');
                $data['raw']['M4_3']    = number_format($row[0]->M4_3, 1, '.', '');
                $data['raw']['M4_4']    = number_format($row[0]->M4_4, 1, '.', '');

                $data['raw']['avg_M4'] = number_format(($data['raw']['M4_1'] + $data['raw']['M4_2'] + $data['raw']['M4_3'] + $data['raw']['M4_4']) / 4, 1);

                $data['raw']['M5_1']    = number_format($row[0]->M5_1, 1, '.', '');
                $data['raw']['M5_2']    = number_format($row[0]->M5_2, 1, '.', '');
                $data['raw']['M5_3']    = number_format($row[0]->M5_3, 1, '.', '');
                $data['raw']['M5_4']    = number_format($row[0]->M5_4, 1, '.', '');

                $data['raw']['avg_M5'] = number_format(($data['raw']['M5_1'] + $data['raw']['M5_2'] + $data['raw']['M5_3'] + $data['raw']['M5_4']) / 4, 1);

                $data['raw']['M6_1']    = number_format($row[0]->M6_1, 1, '.', '');
                $data['raw']['M6_2']    = number_format($row[0]->M6_2, 1, '.', '');
                $data['raw']['M6_3']    = number_format($row[0]->M6_3, 1, '.', '');
                $data['raw']['M6_4']    = number_format($row[0]->M6_4, 1, '.', '');

                $data['raw']['avg_M6'] = number_format(($data['raw']['M6_1'] + $data['raw']['M6_2'] + $data['raw']['M6_3'] + $data['raw']['M6_4']) / 4, 1);

                //just to apply each template's data (jp for loop)
                for ($i = 3; $i <= 6; $i++) {
                    // log::debug('inside first for loop (i)');
                    for ($j = 1; $j <= 4; $j++) {
                        // log::debug('inside second for loop (j)');
                        $dataset_nutrient_template_data = nutrient_template_data::where('nutriprobe', $request->node_address)->limit(1)->get();
                        if ($dataset_nutrient_template_data->count() > 0) {

                            $datasetstring = 'M' . $i . '_' . $j;
                            if (isset($row[0]->{$datasetstring})) {


                                $dataset_nutrient_templates = nutrient_templates::where('id', $dataset_nutrient_template_data[0]->$datasetstring)->Limit(1)->get();
                                if ($dataset_nutrient_templates->count() > 0) {

                                    $template = json_decode($dataset_nutrient_templates[0]->template);
                                    $data['ppm'][$datasetstring] = number_format(($row[0]->$datasetstring * $template->poly1) + $template->poly2, 1, '.', '');
                                }
                            }
                        }
                    }
                }

                $data['pounds']['M3_1'] = number_format((($row[0]->M3_1 * $poly1) + $poly2) * $pound_conv, 1, '.', '');
                $data['pounds']['M3_2'] = number_format((($row[0]->M3_2 * $poly1) + $poly2) * $pound_conv, 1, '.', '');
                $data['pounds']['M3_3'] = number_format((($row[0]->M3_3 * $poly1) + $poly2) * $pound_conv, 1, '.', '');
                $data['pounds']['M3_4'] = number_format((($row[0]->M3_4 * $poly1) + $poly2) * $pound_conv, 1, '.', '');

                $data['pounds']['avg_M3'] = number_format(($data['pounds']['M3_1'] + $data['pounds']['M3_2'] + $data['pounds']['M3_3'] + $data['pounds']['M3_4']) / 4, 1);

                $data['pounds']['M4_1'] = number_format((($row[0]->M4_1 * $poly1) + $poly2) * $pound_conv, 1, '.', '');
                $data['pounds']['M4_2'] = number_format((($row[0]->M4_2 * $poly1) + $poly2) * $pound_conv, 1, '.', '');
                $data['pounds']['M4_3'] = number_format((($row[0]->M4_3 * $poly1) + $poly2) * $pound_conv, 1, '.', '');
                $data['pounds']['M4_4'] = number_format((($row[0]->M4_4 * $poly1) + $poly2) * $pound_conv, 1, '.', '');

                $data['pounds']['avg_M4'] = number_format(($data['pounds']['M4_1'] + $data['pounds']['M4_2'] + $data['pounds']['M4_3'] + $data['pounds']['M4_4']) / 4, 1);

                $data['pounds']['M5_1'] = number_format((($row[0]->M5_1 * $poly1) + $poly2) * $pound_conv, 1, '.', '');
                $data['pounds']['M5_2'] = number_format((($row[0]->M5_2 * $poly1) + $poly2) * $pound_conv, 1, '.', '');
                $data['pounds']['M5_3'] = number_format((($row[0]->M5_3 * $poly1) + $poly2) * $pound_conv, 1, '.', '');
                $data['pounds']['M5_4'] = number_format((($row[0]->M5_4 * $poly1) + $poly2) * $pound_conv, 1, '.', '');

                $data['pounds']['avg_M5'] = number_format(($data['pounds']['M5_1'] + $data['pounds']['M5_2'] + $data['pounds']['M5_3'] + $data['pounds']['M5_4']) / 4, 1);

                $data['pounds']['M6_1'] = number_format((($row[0]->M6_1 * $poly1) + $poly2) * $pound_conv, 1, '.', '');
                $data['pounds']['M6_2'] = number_format((($row[0]->M6_2 * $poly1) + $poly2) * $pound_conv, 1, '.', '');
                $data['pounds']['M6_3'] = number_format((($row[0]->M6_3 * $poly1) + $poly2) * $pound_conv, 1, '.', '');
                $data['pounds']['M6_4'] = number_format((($row[0]->M6_4 * $poly1) + $poly2) * $pound_conv, 1, '.', '');

                $data['pounds']['avg_M6'] = number_format(($data['pounds']['M6_1'] + $data['pounds']['M6_2'] + $data['pounds']['M6_3'] + $data['pounds']['M6_4']) / 4, 1);


                ////////////////////////////////end/////////


                $data['raw']['M4_1']    = number_format($row[0]->M4_1, 1, '.', '');
             //   $data['ppm']['M4_1']    = number_format((($row[0]->M4_1 * $poly1) + $poly2), 1, '.', '');
                $data['pounds']['M4_1'] = number_format((($row[0]->M4_1 * $poly1) + $poly2) * $pound_conv, 1, '.', '');

                $data['raw']['M5_1']    = number_format($row[0]->M5_1, 1, '.', '');
             //   $data['ppm']['M5_1']    = number_format((($row[0]->M5_1 * $poly1) + $poly2), 1, '.', '');
                $data['pounds']['M5_1'] = number_format((($row[0]->M5_1 * $poly1) + $poly2) * $pound_conv, 1, '.', '');

                $data['raw']['M6_1']    = number_format($row[0]->M6_1, 1, '.', '');
              //  $data['ppm']['M6_1']    = number_format((($row[0]->M6_1 * $poly1) + $poly2), 1, '.', '');
                $data['pounds']['M6_1'] = number_format((($row[0]->M6_1 * $poly1) + $poly2) * $pound_conv, 1, '.', '');

                /*print_r($data) . PHP_EOL;

print_r($row) . PHP_EOL;
die;*/

                $raw_total    = number_format($row[0]->M3_1 + $row[0]->M4_1 + $row[0]->M5_1 + $row[0]->M6_1, 1, '.', '');
                $ppm_total    = number_format((($row[0]->M3_1 * $poly1) + $poly2) + (($row[0]->M4_1 * $poly1) + $poly2) + (($row[0]->M5_1 * $poly1) + $poly2) + (($row[0]->M6_1 * $poly1) + $poly2), 1, '.', '');
                $pounds_total = number_format((($row[0]->M3_1 * $poly1) + $poly2) * $pound_conv + (($row[0]->M4_1 * $poly1) + $poly2) * $pound_conv + (($row[0]->M5_1 * $poly1) + $poly2) * $pound_conv + (($row[0]->M6_1 * $poly1) + $poly2) * $pound_conv, 1, '.', '');
            }

            if ($raw_total) {

                $nh4_1        = ($row[0]->M3_1 * $poly1) + $poly2; // M3_1
                $nh4_1_pounds = $nh4_1 * $pound_conv;
                $nh4_2        = ($row[0]->M4_1 * $poly1) + $poly2; // M4_1
                $nh4_2_pounds = $nh4_2 * $pound_conv;

                $nh4_avg = ($nh4_1 + $nh4_2) / 2;
                $nh4_avg_pounds = ($nh4_1_pounds + $nh4_2_pounds) / 2;

                $no3_1        = ($row[0]->M5_1 * $poly1) + $poly2; // M5_1
                $no3_1_pounds = $no3_1 * $pound_conv;
                $no3_2        = ($row[0]->M6_1 * $poly1) + $poly2; // M6_1
                $no3_2_pounds = $no3_2 * $pound_conv;

                $no3_avg = ($no3_1 + $no3_2) / 2;
                $no3_avg_pounds = ($no3_1_pounds + $no3_2_pounds) / 2;

                $ppm_avg = $nh4_avg + $no3_avg;
                $ppm_avg_pounds = $nh4_avg_pounds + $no3_avg_pounds;

                $data['raw']['avg']    = number_format($raw_total    / $count, 2, '.', '');
                //$data['ppm']['avg']    = $ppm_total    / $count;
                $data['ppm']['avg']    = number_format($ppm_avg, 2, '.', '');
                //$data['pounds']['avg'] = $pounds_total / $count;
                $data['pounds']['avg'] = number_format($ppm_avg_pounds, 2, '.', '');

                $data['lower_limit']    = $lower_limit;
                $data['upper_limit']    = $upper_limit;
                $data['nutrient_label'] = $nutrient_label;
                $data['metric']         = $metric;

                // $user = Auth::user();

                // if (isset($data['temp']['avg_M1'])) {
                //     if ($user->unit_of_measure  == 2) {
                //         $data['temp']['avg_M1'] = ($data['temp']['avg_M1'] * (9 / 5) + 32) . ' °F';
                //     } else if ($user->unit_of_measure  == 1) {
                //         $data['temp']['avg_M1'] = $data['temp']['avg_M1'] . ' °C';
                //     }
                // }

                // can if/else this later (based on chosen metric)
                $average = $ppm_total / $count;

                $diff  = abs($average - $lower_limit);
                $range = abs($upper_limit - $lower_limit);
                $range = $range ?: 1;
                $gauge_scale = 180;
                $shift = 90;
                $gauge_value = (($diff / $range) * $gauge_scale) - $shift;
            }

            $data['gauge_value'] = $gauge_value;
        }

        // Ensure Graph Type is sane
        if (!in_array($field->graph_type, ['nutrient', 'nutrient_ppm', 'nutrient_ppm_avg'])) {
            $field->graph_type = 'nutrient_ppm_avg';
        }

        $dt = new \DateTime('now');
        $tzObj = new \DateTimeZone($this->tz);
        $dt->setTimezone($tzObj);
        $data['date_now'] = $dt->format('Y-m-d H:i');

        $data['install_depth']    = $field->install_depth;
        $data['graph_type']       = $field->graph_type;
        $data['graph_start_date'] = $field->graph_start_date;

        $data['node_id'] = $hwconfig->id;
        $data['company_id'] = $hwconfig->company_id;
        $data['node_address'] = $node_address;
        $data['field_id'] = $field->id;
        $data['field_name'] = $field->field_name;

        if ($grants) {
            $data['grants'] = $grants;
        }


        return response()->json($data);
    }

    // Manage Wells Form Populate
    public function manage_wells(Request $request)
    {
        if (empty($request->node_address)) {
            return response()->json(['message' => 'missing_addr']);
        }

        $hwconfig = hardware_config::where("node_address", $request->node_address)->first();
        if (!$hwconfig) {
            return response()->json(['message' => 'nonexistent', 'obj' => 'node']);
        }

        $field = fields::where('node_id', $request->node_address)->first();
        if (!$field) {
            return response()->json(['message' => 'nonexistent', 'obj' => 'field']);
        }

        $hwm = hardware_management::where('id', $hwconfig->hardware_management_id)->first();
        if (!$hwm) {
            return response()->json(['message' => 'nonexistent', 'obj' => 'hwm']);
        }

        $grants = [];

        $this->tz = $this->timezones[$this->acc->timezone];
        if (!$this->tz) {
            $this->tz = 'UTC';
        }

        if (!$this->acc->is_admin) {
            // permission check
            $grants = $this->acc->requestAccess(['Well Controls' => ['p' => ['Edit'], 'o' => $hwconfig->id, 't' => 'O']]);
            if (empty($grants['Well Controls']['Edit']['O'])) {
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        $node_data_meters    = node_data_meter::where("node_id", $request->node_address)->orderBy('idwm', 'desc')->first();
        $hwconfig->date_time = $node_data_meters ? $node_data_meters->date_time->tz($this->tz)->format('Y-m-d H:i:s') : '1970-01-01 00:00:00';

        // Ensure Graph Type is sane
        if (!in_array($field->graph_type, ['pulse', 'tech'])) {
            $field->graph_type = 'pulse';
        }

        // Get Todays Date
        $dt = new \DateTime('now');
        $tzObj = new \DateTimeZone($this->tz);
        $dt->setTimezone($tzObj);
        $date_now = $dt->format('Y-m-d H:i');

        $wells_data = [
            'hw'       => $hwconfig,
            'field'    => $field,
            'hwm'      => $hwm,
            'ndm'      => $node_data_meters,
            'date_now' => $date_now
        ];

        if ($grants) {
            $wells_data['grants'] = $grants;
        }

        $this->acc->logActivity('View', 'Well Controls', "Node: {$hwconfig->node_address} ({$hwconfig->id})");

        return response()->json($wells_data);
    }

    // Manage Meters Form Populate
    public function manage_meters(Request $request)
    {
        if (empty($request->node_address)) {
            return response()->json(['message' => 'missing_addr']);
        }

        $hwconfig = hardware_config::where("node_address", $request->node_address)->first();
        if (!$hwconfig) {
            return response()->json(['message' => 'nonexistent', 'obj' => 'node']);
        }

        $field = fields::where('node_id', $request->node_address)->first();
        if (!$field) {
            return response()->json(['message' => 'nonexistent', 'obj' => 'field']);
        }

        $hwm = hardware_management::where('id', $hwconfig->hardware_management_id)->first();
        if (!$hwm) {
            return response()->json(['message' => 'nonexistent', 'obj' => 'hwm']);
        }

        $grants = [];

        $this->tz = $this->timezones[$this->acc->timezone];
        if (!$this->tz) {
            $this->tz = 'UTC';
        }

        if (!$this->acc->is_admin) {
            // permission check
            $grants = $this->acc->requestAccess(['Meters' => ['p' => ['Edit'], 'o' => $hwconfig->id, 't' => 'O']]);
            if (empty($grants['Meters']['Edit']['O'])) {
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        $node_data_meters    = node_data_meter::where("node_id", $request->node_address)->orderBy('idwm', 'desc')->first();
        $hwconfig->date_time = $node_data_meters ? $node_data_meters->date_time->tz($this->tz)->format('Y-m-d H:i:s') : '1970-01-01 00:00:00';

        // Ensure Graph Type is sane
        if (!in_array($field->graph_type, ['pulse', 'tech'])) {
            $field->graph_type = 'pulse';
        }

        // Get Todays Date
        $dt = new \DateTime('now');
        $tzObj = new \DateTimeZone($this->tz);
        $dt->setTimezone($tzObj);
        $date_now = $dt->format('Y-m-d H:i');

        $meter_data = [
            'hw'       => $hwconfig,
            'field'    => $field,
            'hwm'      => $hwm,
            'ndm'      => $node_data_meters,
            'date_now' => $date_now
        ];

        if ($grants) {
            $meter_data['grants'] = $grants;
        }

        $this->acc->logActivity('View', 'Meters', "Node: {$hwconfig->node_address} ({$hwconfig->id})");

        return response()->json($meter_data);
    }

    // Field record update (On Soil Moisture Form page)
    public function update_sm(Request $request)
    {
        $request->validate([
            /* EXCEPTION: We keep this 'node_id' (instead of node_address), because it's needed in a model update for the fields table (who still uses node_id) */
            'model.node_id' => 'required',
            'model.full' => 'required',
            'model.refill' => 'required',
            'model.ni' => 'required',
            'model.nr' => 'required',
            'model.graph_type' => 'required',
            'model.graph_start_date' => 'nullable'
        ]);

        $model = $request->model;

        $hwconfig = hardware_config::where('node_address', $model['node_id'])->first();
        if (!$hwconfig) {
            return response()->json(['message' => 'nonexistent']);
        }

        if (!$this->acc->is_admin) {
            // permission check
            $grants = $this->acc->requestAccess(['Soil Moisture' => ['p' => ['Edit'], 'o' => $hwconfig->id, 't' => 'O']]);
            if (empty($grants['Soil Moisture']['Edit']['O'])) {
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        $field = fields::where('node_id', $model['node_id'])->update($model);
        $field = fields::where('node_id', $model['node_id'])->first();

        $this->acc->logActivity('Edit', 'Soil Moisture', "Field: {$field->field_name} ({$field->id})");

        return response()->json([
            'message' => 'field_updated'
        ]);
    }

    // Field record update (On Nutrients Form page)
    public function update_n(Request $request)
    {
        $request->validate([
            'model.node_address' => 'required',
            'model.install_depth' => 'required'
        ]);

        $model = $request->model;
        $node_address = $model['node_address'];
        $node_id = $model['node_id'];
        $model['node_id'] = $node_address;
        unset($model['node_address']);

        fields::where('node_id', $node_address)->update($model);

        return response()->json([
            'message' => 'field_updated'
        ]);
    }

    // UPDATE WELLS AND METERS (SHOULD BE SPLIT)
    public function update_wm(Request $request)
    {
        $request->validate([
            'field_name' => 'required',
            'device_serial_number' => 'required',
            'graph_type' => 'required',
            'graph_start_date' => 'nullable'
        ]);

        $hwconfig = hardware_config::where('node_address', $request->node_address)->first();
        if (!$hwconfig) {
            return response()->json(['message' => 'nonexistent']);
        }

        $subsystem = Utils::convertNodeTypeToSubsystem($hwconfig->node_type);

        // subsystem specific permission check
        if (!$this->acc->is_admin) {
            $grants = $this->acc->requestAccess([
                $subsystem => ['p' => ['Edit'], 'o' => $request->id, 't' => 'O']
            ]);
            if (empty($grants[$subsystem]['Edit']['O'])) {
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        $record = [
            'field_name' => $request->field_name,
            'graph_type' => $request->graph_type
        ];
        if (!empty($request->graph_start_date)) {
            $record['graph_start_date'] = $request->graph_start_date;
        }

        $fields = fields::where('node_id', $request->node_address)->update($record);

        $this->acc->logActivity('Edit', $subsystem, "Node:{$hwconfig->node_type}:{$hwconfig->node_address}");

        return response()->json(['message' => 'node_updated']);
    }

    public function clear_field_zones(Request $request)
    {

        $request->validate([
            'field_id' => 'required'
        ]);

        $field = fields::where('id', $request->field_id)->first();

        if ($field) {

            $node = hardware_config::where('node_address', $field->node_id)->first();
            $integrations = json_decode($node->integration_opts, true);

            Eventy::action('fields.zones.before_clear', $node, $field, [
                'integrations' => $integrations
            ]);

            $field->zones = null;
            $field->save();

            Eventy::action('fields.zones.cleared', $node, $field, [
                'integrations' => $integrations
            ]);
        }

        return response()->json(['message' => 'success']);
    }

    public function clear_field_perimeter(Request $request)
    {

        $request->validate([
            'field_id' => 'required'
        ]);

        $field = fields::where('id', $request->field_id)->first();

        if ($field) {

            $node = hardware_config::where('node_address', $field->node_id)->first();
            $integrations = json_decode($node->integration_opts, true);

            Eventy::action('fields.perimeter.before_clear', $node, $field, [
                'integrations' => $integrations
            ]);

            $field->perimeter = null;
            $field->save();

            Eventy::action('fields.perimeter.cleared', $node, $field, [
                'integrations' => $integrations
            ]);
        }

        return response()->json(['message' => 'success']);
    }
}
