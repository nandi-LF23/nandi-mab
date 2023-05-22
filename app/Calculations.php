<?php


namespace App;

use Str;
use DB;
use App\Models\cultivars_management;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\nutrient_templates;
use App\Models\nutrients_data;
use Illuminate\Support\Facades\Http;


// A collection of various agronomy calculations

class Calculations
{

  public static function calcStatus(
    $moisture,
    // accumulative / average (Depending on node's graph_type)
    $field_id,
    $full,
    // field full value
    $refill,
    // field refill value
    $todays_date,
    // DateTime object
    $timezone,
    $debug = false
  ) {

    $result = [
      'status' => 0,
      'upper_value' => 0,
      'lower_value' => 0
    ];

    if (!$field_id || !$moisture) {
      return $result;
    }

    if ($debug) {
      Log::debug(
        "$debug: moisture: " . $moisture .
          ', field_id: ' . $field_id .
          ', full: ' . $full . ', refill: ' . $refill .
          ', todays_date: ' . $todays_date->format('d/m/Y') .
          ', timezone: ' . $timezone->getName()
      );
    }

    $status = 0;
    $chosen_stage = NULL;

    $upper_value = $full;
    $lower_value = $refill;
    $capacity = $full - $refill;

    $growth_stages = cultivars_management::select(
      'stage_start_date',
      'upper',
      'lower',
      'duration'
    )
      ->join('cultivars', 'cultivars_management.id', 'cultivars.cultivars_management_id')
      ->where('field_id', $field_id)
      ->orderBy('stage_start_date', 'asc')
      ->get();

    if ($growth_stages->count()) {
      // TRY TO GET THE ACTIVE CULTIVAR STAGE
      foreach ($growth_stages as $stage) {

        $stage_start_date = new \DateTime($stage->stage_start_date);
        $stage_start_date->setTimezone($timezone);

        $stage_end_date = new \DateTime($stage->stage_start_date);
        $stage_end_date->setTimezone($timezone);
        $stage_end_date->add(new \DateInterval('P' . $stage->duration . 'D'));

        // TODAY'S DATE FALLS IN STAGE'S RANGE
        if ($todays_date >= $stage_start_date && $todays_date <= $stage_end_date) {
          $chosen_stage = $stage;

          $upper_value = $refill + ($capacity * ($chosen_stage->upper / 100));
          $lower_value = $refill + ($capacity * ($chosen_stage->lower / 100));

          $capacity = $upper_value - $lower_value;

          break;
        }
      }
    }

    // formula: (moisture - lower) / (capacity)
    // defaults:
    // full: 70
    // refill: 50
    // capacity = full - refill (70 - 50 = 20)
    // moisture - 50 / 20

    // lower value is either stage lower or fallback to refill

    // status calculation (preventing div zero for capacity)
    $status = $capacity ? (($moisture - $lower_value) / $capacity) * 100 : 0;

    // format value
    $status = (float) number_format((float) ($status), 2, '.', '');

    $result = [
      'status' => $status,
      'upper_value' => $upper_value,
      'lower_value' => $lower_value
    ];

    return $result;
  }

  public static function calcIrriRec(
    $moisture,
    /* accumulative / average (Depending on node's graph_type) */
    $upperValue,
    $unitFormat
  ) {
    // Calculate the Irrigation Recommendation only if all required values exist
    if ($moisture > 0) {

      if ($upperValue > $moisture) {
        // Calculate the irrigation recommendation
        $irriRec = ($upperValue - $moisture);
      } else {
        $irriRec = 0;
      }

      // Metric was selected
      if ($unitFormat == '1') {
        // x by 25.4 mm (Inch to mm)
        $irriRec = number_format($irriRec, 1, '.', '') . 'mm';
      }
      // Imperial was selected
      else if ($unitFormat == '2') {
        // Calculations are in inches by default
        $irriRec = number_format($irriRec / 25.44, 1, '.', '') . 'in';
      }
    } else {
      // Some of the required values are missing so we cannot make a recommendation
      // Validate the values so that we can help the user to generate a recommendation
      if ($moisture == 0) {
        $irriRec = 'N/A';
      }
    }

    // Return the recommendation
    return $irriRec;
  }

  public static function calcDepletionRates(
    $node_address,
    $graph_type,
    $ts24,
    $ts48,
    $ts72,
    $tz
  ) {

    $return = ['one' => 0, 'two' => 0, 'three' => 0];
    $column = $graph_type == 'sum' ? 'accumulative' : 'average';

    $te24 = clone $ts24;
    $te48 = clone $ts48;
    $te72 = clone $ts72;

    // 24 Hour
    $ts24_string = $ts24->format('Y-m-d') . ' 18:00:00';
    $te24_string = ($te24->modify('+1 day'))->format('Y-m-d') . ' 06:00:00';

    $ts48_string = $ts48->format('Y-m-d') . ' 18:00:00';
    $te48_string = ($te48->modify('+1 day'))->format('Y-m-d') . ' 06:00:00';

    $ts72_string = $ts72->format('Y-m-d') . ' 18:00:00';
    $te72_string = ($te72->modify('+1 day'))->format('Y-m-d') . ' 06:00:00';

    // ONE QUERY
    $range = DB::connection('mysql')->table('node_data')
      ->select($column, 'date_time')
      ->where($column, '>', 0)
      ->where('probe_id', '=', $node_address)
      ->where(function ($query) use ($ts24_string, $te24_string, $ts48_string, $te48_string, $ts72_string, $te72_string) {
        $query
          ->whereBetween('date_time', [$ts24_string, $te24_string])
          ->orWhereBetween('date_time', [$ts48_string, $te48_string])
          ->orWhereBetween('date_time', [$ts72_string, $te72_string]);
      })
      ->orderBy('date_time', 'asc');

    $range = $range->get();

    $range_24 = [];
    $range_48 = [];
    $range_72 = [];

    foreach ($range as $obj) {
      $dt = new \DateTime($obj->date_time, $tz);
      //if($obj->{$column} > 0){
      $dts = $dt->format('Y-m-d H:i:s');
      if ($dt > $ts24 && $dt < $te24) {
        $range_24[$dts] = (float) $obj->{$column};
      } else
                if ($dt > $ts48 && $dt < $te48) {
        $range_48[$dts] = (float) $obj->{$column};
      } else
                    if ($dt > $ts72 && $dt < $te72) {
        $range_72[$dts] = (float) $obj->{$column};
      }
      //}
    }

    $return['one'] = self::calcGrossSMLoss($range_24, $column);
    $return['two'] = self::calcGrossSMLoss($range_48, $column);
    $return['three'] = self::calcGrossSMLoss($range_72, $column);

    return $return;
  }

  // calculates the loss of soil moisture for a range of values grouped within a 12 hour time period
  public static function calcGrossSMLoss(
    $values,
    // 12 hour time period SM values (averages or accumulative values)
    $column,
    // either average or accumulative
    $variance = 0 // adjustable variance (also forces positive differences) (SM loss, not gain)
  ) {
    $gross_loss = 0;
    if ($values) {
      $prev_val = (float) $values[array_key_first($values)];
      foreach ($values as $k => $val) {
        $diff = $prev_val - (float) $val;
        if ($diff > $variance) {
          $gross_loss += $diff;
        }
        $prev_val = $val;
      }
    }
    return $gross_loss;
  }

  public static function calcNutrientAverageGaugeValuesJDOC($node_address)
  {
    $results = [
      'nutrient_lower' => 0,
      'nutrient_upper' => 0,
      'nutrient_gauge' => 0,
      'nutrient_avg' => 0,
      'moisture_avg' => 0,
      'nutrient_pc' => 0,
      'nutrient_label' => '',
      'NO3_AVG' => 0,
      'NH4_AVG' => 0
    ];
    log::debug('THE NODE ADDRESS ABOUT TO BE CALCD:' . $node_address);
    // if($node_address == '355523768818892-0')
    if (true) {
      // log::debug('inside if');

      $dataset = DB::connection('mysql')->table('nutri_data')->where('node_address', $node_address)->orderBy('id', 'DESC')->Limit(1)->get();
      if ($dataset->count() > 0) {

        log::debug('get node adddr:' . $dataset[0]->node_address);

        $NO3_avg = 0;
        $NH4_avg = 0;
        $counter1 = 0;
        $counter2 = 0;

        /*
                for($i = 3; $i <= 6; $i++)
                {
                // log::debug('inside first for loop (i)');
                for($j = 1; $j <= 4; $j++)
                {
                // log::debug('inside second for loop (j)');
                $dataset_nutrient_template_data = DB::connection('mysql')->table('nutrient_template_data')->where('nutriprobe',$dataset[0]->node_address)->Limit(1)->get();
                if ($dataset_nutrient_template_data->count() > 0)
                {
                $groupstring = 'M'.$i.'_'.$j.'_GROUP';
                if($dataset_nutrient_template_data[0]->{$groupstring}==1){
                $datasetstring = 'M'.$i.'_'.$j;
                if(isset($dataset[0]->{$datasetstring})) {
                $dataset_nutrient_templates = DB::connection('mysql')->table('nutrient_templates')->where('id',$dataset_nutrient_template_data[0]->$datasetstring)->Limit(1)->get();
                if($dataset_nutrient_templates->count() > 0){
                $counter1++;
                $template = json_decode($dataset_nutrient_templates[0]->template);
                $NO3_avg += ($dataset[0]->$datasetstring * $template->poly1) + $template->poly2;
                }
                }
                }
                }
                }
                }
                */
        for ($i = 3; $i <= 6; $i++) {
          log::debug('inside first for loop (i)');

          for ($j = 1; $j <= 4; $j++) {
            $dataset_nutrient_template_data = DB::connection('mysql')->table('nutrient_template_data')->where('nutriprobe', $dataset[0]->node_address)->Limit(1)->get();
            if ($dataset_nutrient_template_data->count() > 0) {
              $groupstring = 'M' . $i . '_' . $j . '_GROUP';
              if ($dataset_nutrient_template_data[0]->{$groupstring} == 1) {
                $datasetstring = 'M' . $i . '_' . $j;
                // if(isset($dataset[0]->{$datasetstring})) {


                $dataset_nutrient_templates = DB::connection('mysql')->table('nutrient_templates')->where('id', $dataset_nutrient_template_data[0]->$datasetstring)->Limit(1)->get();
                if ($dataset_nutrient_templates->count() > 0) {
                  $counter1++;
                  $template = json_decode($dataset_nutrient_templates[0]->template);
                  $NO3_avg += ($dataset[0]->$datasetstring * $template->poly1) + $template->poly2;
                }
                //}
              }
            }
            // log::debug('inside second for loop (j)');
            $dataset_nutrient_template_data = DB::connection('mysql')->table('nutrient_template_data')->where('nutriprobe', $dataset[0]->node_address)->Limit(1)->get();
            if ($dataset_nutrient_template_data->count() > 0) {
              $groupstring = 'M' . $i . '_' . $j . '_GROUP';
              if ($dataset_nutrient_template_data[0]->{$groupstring} == 2) {
                $datasetstring = 'M' . $i . '_' . $j;
                // if(isset($dataset[0]->{$datasetstring})) {


                $dataset_nutrient_templates = DB::connection('mysql')->table('nutrient_templates')->where('id', $dataset_nutrient_template_data[0]->$datasetstring)->Limit(1)->get();
                if ($dataset_nutrient_templates->count() > 0) {
                  $counter2++;
                  $template = json_decode($dataset_nutrient_templates[0]->template);
                  $NH4_avg += ($dataset[0]->$datasetstring * $template->poly1) + $template->poly2;
                }
                //}
              }
            }
          }
        }

        //log::debug('NO3 avg: '.$NO3_avg/$counter1);

        $results = [
          'nutrient_lower' => 0,
          'nutrient_upper' => 0,
          'nutrient_gauge' => 0,
          'nutrient_avg' => 0,
          'moisture_avg' => 0,
          'nutrient_pc' => 0,
          'nutrient_label' => '',
          'NO3_AVG' => ($counter1 ? number_format((float) $NO3_avg / $counter1, 1, '.', '') : 0),
          'NH4_AVG' => ($counter2 ? number_format((float) $NH4_avg / $counter2, 1, '.', '') : 0)
        ];
      }
      return $results;
    } else {
      $results = [
        'nutrient_lower' => 0,
        'nutrient_upper' => 0,
        'nutrient_gauge' => 0,
        'nutrient_avg' => 0,
        'moisture_avg' => 0,
        'nutrient_pc' => 0,
        'nutrient_label' => '',
        'NO3_AVG' => 0,
        'NH4_AVG' => 0,
        'PPM_AVG' => 0
        /*  'moisture_avg' => 0,
                'temp_avg' => 0*/
      ];
      return $results;
    }
  }


  // TODO: This function needs to be rewritten to accept either a dataset of values or a node address
  // and either a template JSON object or a template id (for max flexibilty and to centralize calculations)
  public static function calcNutrientAverageGaugeValues($node_address, $template_id)
  {
    $results = [
      'nutrient_lower' => 0,
      'nutrient_upper' => 0,
      'nutrient_gauge' => 0,
      'nutrient_avg' => 0,
      'moisture_avg' => 0,
      'nutrient_pc' => 0,
      'nutrient_label' => '',
      'NO3_AVG' => 0,
      'NH4_AVG' => 0,
      'PPM_AVG' => 0
      /*  'moisture_avg' => 0,
            'temp_avg' => 0*/
    ];
    return $results;
  }


  public static function calcPercentageOfColorRange($percentage, $colors)
  {
    /*
        // Example Colors
        $colors = [
        [0,   [255, 0, 0] ],
        [25,  [0, 128, 0] ],
        [75,  [0, 0, 255] ],
        [100, [255, 0, 0] ]
        ];
        */
    $pickRGB = function ($color1, $color2, $weight) {
      $p = $weight;
      $w = ($p * 2) - 1;
      $w1 = (($w / 1) + 1) / 2;
      $w2 = 1 - $w1;
      return [
        round(($color1[0] * $w1) + ($color2[0] * $w2)),
        // r
        round(($color1[1] * $w1) + ($color2[1] * $w2)),
        // g
        round(($color1[2] * $w1) + ($color2[2] * $w2)) // b
      ];
    };

    $percentage = $percentage <= 0 ? 1 : $percentage;
    $percentage = $percentage >= 100 ? 99 : $percentage;

    $colorRange = [];

    for ($i = 0; $i < count($colors); $i++) {
      if ($percentage <= $colors[$i][0]) {
        $colorRange = [$i - 1, $i];
        break;
      }
    }

    $firstColorP = $colors[$colorRange[0]][0];
    $secondColorP = $colors[$colorRange[1]][0];

    $firstColorA = $colors[$colorRange[0]][1];
    $secondColorA = $colors[$colorRange[1]][1];

    // Calculate ratio between the two closest colors
    $range = $secondColorP - $firstColorP;
    $lower = $percentage - $firstColorP;
    $ratio = $lower / $range;

    $rgb = $pickRGB($secondColorA, $firstColorA, $ratio);

    // convert to hex
    return '#' . sprintf("%02X", $rgb[0]) . sprintf("%02X", $rgb[1]) . sprintf("%02X", $rgb[2]);
  }

  public static function getLatestNodeAvgSM($node)
  {
    $val = 0;
    $node_address = is_array($node) ? $node['node_address'] : $node->node_address;
    $node_type = is_array($node) ? $node['node_type'] : $node->node_type;

    if ($node_type == 'Soil Moisture') {
      $val = number_format(DB::connection('mysql')->table('node_data')
        ->select('average')
        ->where('probe_id', $node_address)
        ->orderBy('id', 'desc')
        ->limit(1)
        ->value('average'), 1, '.', '');;
    } else if ($node_type == 'Nutrients') {
      $dataset = DB::connection('mysql')->table('nutri_data')->where('node_address', $node_address)->orderBy('id', 'DESC')->Limit(1)->get();
      $val = 0;
      $counter = 0;

      for ($j = 1; $j <= 4; $j++) {
        log::debug('inside for loop');
        $datasetstring = 'M0_' . $j;
        if (isset($dataset[0]->{$datasetstring})) {

          $counter++;

          $val += $dataset[0]->{$datasetstring};
        }
      }
      if ($val) {
        $val = $val / $counter;
      }
    }



    return number_format((float) ($val), 2, '.', '');
  }

  public static function getLatestNodeAvgTemp($node)
  {
    $val = 0;
    $node_address = is_array($node) ? $node['node_address'] : $node->node_address;
    $node_type = is_array($node) ? $node['node_type'] : $node->node_type;

    $user = Auth::user();


    //test
    if ($node_type == 'Soil Moisture') {
      $temps = DB::connection('mysql')->table('node_data')
      ->select(['t1', 't2', 't3', 't4', 't5', 't6', 't7', 't8', 't9', 't10', 't11', 't12', 't13', 't14', 't15'])
      ->where('probe_id', $node_address)
        ->orderBy('id', 'desc')
        ->limit(1)
        ->first();
      $tot = 0;
      $ctr = 0;
      if ($temps) {
        for ($i = 1; $i <= 15; $i++) {
          $k = "t" . $i;

          //skips the next code 
          if (!(empty($temps->{$k})) && ($temps->{$k} > 0)) {
            $tot += $temps->{$k};
            $ctr++;
          }
        }
        $val = number_format($ctr < 1 ? $tot : ($tot / $ctr), 1, '.', '');
      }
    } 


    //works
    else if ($node_type == 'Nutrients') {
      $dataset = DB::connection('mysql')->table('nutri_data')->where('node_address', $node_address)->orderBy('id', 'DESC')->Limit(1)->get();
      $val = 0;
      $counter = 0;

      for ($j = 1; $j <= 4; $j++) {
        log::debug('inside for loop');
        $datasetstring = 'M1_' . $j;
        if (isset($dataset[0]->{$datasetstring})) {

          $counter++;

          $val += $dataset[0]->{$datasetstring};
        }
      }
      if ($val) {
        $val = $val / $counter;
      }
    }

    // if ($user->unit_of_measure == 2) {
    //   return $val = number_format((float)($val * (9 / 5) + 32), 1, '.', '');
    // } else if ($user->unit_of_measure  == 1) {
    //   return number_format((float)($val), 1, '.', '');
    // }
    return number_format((float)($val), 1, '.', '');
  }
}
