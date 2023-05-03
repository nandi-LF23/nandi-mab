<?php


namespace App;

use PDO;
use Str;
use DB;
use App\Models\cultivars_management;
use Illuminate\Support\Facades\Log;
use App\Models\nutrient_templates;
use App\Models\nutri_data;
use Illuminate\Support\Facades\Http;


// A collection of various agronomy calculations

class Calculations
{

  public static function calcStatus(
    $moisture,    // accumulative / average (Depending on node's graph_type)
    $field_id,
    $full,        // field full value
    $refill,      // field refill value
    $todays_date, // DateTime object
    $timezone,
    $debug = false
  ) {

    $result = [
      'status'      => 0,
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
    $capacity    = $full - $refill;

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
    $status = (float)number_format((float)($status), 2, '.', '');

    $result = [
      'status'      => $status,
      'upper_value' => $upper_value,
      'lower_value' => $lower_value
    ];

    return $result;
  }

  public static function calcIrriRec(
    $moisture, /* accumulative / average (Depending on node's graph_type) */
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
        $irriRec = bcdiv($irriRec, 1, 2) . 'mm';
      }
      // Imperial was selected
      else if ($unitFormat == '2') {
        // Calculations are in inches by default
        $irriRec = bcdiv($irriRec / 25.44, 1, 2) . 'in';
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
    $ts24_string =  $ts24->format('Y-m-d') . ' 18:00:00';
    $te24_string = ($te24->modify('+1 day'))->format('Y-m-d') . ' 06:00:00';

    $ts48_string =  $ts48->format('Y-m-d') . ' 18:00:00';
    $te48_string = ($te48->modify('+1 day'))->format('Y-m-d') . ' 06:00:00';

    $ts72_string =  $ts72->format('Y-m-d') . ' 18:00:00';
    $te72_string = ($te72->modify('+1 day'))->format('Y-m-d') . ' 06:00:00';

    // ONE QUERY
    $range = DB::table('node_data')
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

    $return['one']   = self::calcGrossSMLoss($range_24, $column);
    $return['two']   = self::calcGrossSMLoss($range_48, $column);
    $return['three'] = self::calcGrossSMLoss($range_72, $column);

    return $return;
  }

  // calculates the loss of soil moisture for a range of values grouped within a 12 hour time period  
  public static function calcGrossSMLoss(
    $values, // 12 hour time period SM values (averages or accumulative values)
    $column,  // either average or accumulative
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

  // TODO: This function needs to be rewritten to accept either a dataset of values or a node address
  // and either a template JSON object or a template id (for max flexibilty and to centralize calculations)
  public static function calcNutrientAverageGaugeValues($node_address, $template_id)
  {
    $results = [
      'nutrient_lower' => 0,
      'nutrient_upper' => 0,
      'nutrient_gauge' => 0,
      'nutrient_avg'   => 0,
      'nutrient_pc'    => 0,
      'nutrient_label' => '',
      'NO3_avg' => 0,
      'NH4_avg' => 0
      /*  'moisture_avg' => 0,
      'temp_avg' => 0*/
    ];

    if (!$node_address) {
      return $results;
    }

    // fetch latest data values from nutri_data table
    $dataset = nutri_data::where('node_address', $node_address)
      // ->whereIn('identifier', ['M3_1', 'M4_1', 'M5_1', 'M6_1', 'M0 1', 'M1 1', 'M2 1', 'M3 1', 'M4 1', 'M5 1', 'M6 1', 'M7 1', 'M8 1', 'M9 1'])
      /// ->orderBy('identifier', 'asc') 
      ->orderBy('id', 'desc')
      ->limit(1)
      ->get();

    // get template values
    $tpl = nutrient_templates::where('id', $template_id)->first();
    if (!$tpl) {
      throw new \Exception("Missing Template: $template_id for node: $node_address");
    }

    $values = json_decode($tpl->template, true);
    // dd($dataset);

    if (is_array($values) && $dataset->count() == 4) {
      
      $poly1 = $values['poly1'] ?: 1;
      $poly2 = $values['poly2'] ?: 1;
      $lower = $values['lower_limit'] ?: 0;
      $upper = $values['upper_limit'] ?: 0;


      /*
        Brad: understand.....instead of it being an average of 4 sensors it would be
        the average of M3 & M4 for NH4 and 
        M5 & M6 for NO3 and the sum of the 2 averages 
        would be the resulting Nitrogen level
      */
      /*
      $nh4_1 = ($dataset->M3_1 * $poly1) + $poly2; // M3_1
      $nh4_2 = ($dataset->M4_1 * $poly1) + $poly2; // M4_1
      $nh4_avg = ($nh4_1 + $nh4_2) / 2;

      $no3_1 = ($dataset->M5_1 * $poly1) + $poly2; // M5_1
      $no3_2 = ($dataset->M6_1 * $poly1) + $poly2; // M6_1
      $no3_avg = ($no3_1 + $no3_2) / 2;

      $ppm_avg = $nh4_avg + $no3_avg;
*/
      // get template values
      /*$tpl = nutrient_templates::where('id', $template_id)->first();
if(!$tpl){ throw new \Exception("Missing Template: $template_id for node: $node_address"); }*/
      //$values = json_decode($tpl->template, true);


      $host = 'https://sandbox.myagbuddy.com';
      /*$dataset = Http::get($host.'/api/loadnodedata/'.$node_address);
print_r($dataset);die;*/
      $servername = "localhost";
      $username = "myagbuddy";
      $password = "N1ckn4ggp4dyw@gg!3212";


      $conn = new PDO("mysql:host=$servername;dbname=agri_sandbox", $username, $password);

      $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      $stmt = $conn->query("SELECT * FROM nutri_data WHERE node_address= '" . $node_address . "' ORDER BY id DESC LIMIT 1");

      $dataset = $stmt->fetch(PDO::FETCH_OBJ);

      $NO3_avg = 0;
      $NH4_avg = 0;



      $stmt = $conn->query("SELECT * FROM nutrient_template_data WHERE nutriprobe= '" . $node_address . "' ORDER BY id DESC LIMIT 1");
      $dataset = $stmt->fetch(PDO::FETCH_OBJ);
      $counter1 = 0;
      for ($i = 3; $i < 6; $i++) {
        for ($j = 1; $j < 4; $j + 2) {

          if ($dataset->{'M' . $i . '_' . $j}) {

            $counter1++;
            $stmt = $conn->query("SELECT * FROM nutrient_templates WHERE id=" . $dataset->{'M' . $i . '_' . $j});
            $polynomials = $stmt->fetch(PDO::FETCH_OBJ);
            $template = json_decode($polynomials->template);
            // dd($template);
            $NO3_avg += ($dataset->{'M' . $i . '_' . $j} * $template->poly1) + $template->poly2;
          }
        }
      }

      $counter2 = 0;
      for ($i = 3; $i < 6; $i++) {
        for ($j = 2; $j == 4; $j + 2) {
          if ($dataset->{'M' . $i . '_' . $j}) {
            $counter2++;
            $stmt = $conn->query("SELECT * FROM nutrient_templates WHERE id=" . $dataset->{'M' . $i . '_' . $j});
            $polynomials = $stmt->fetch(PDO::FETCH_OBJ);
            $template = json_decode($polynomials->template);
            $NH4_avg += ($dataset->{'M' . $i . '_' . $j} * $template->poly1) + $template->poly2;
          }
        }
      }


      $NH4_avg = $NH4_avg / $counter1;
      $NO3_avg = $NO3_avg / $counter2;

      $ppm_avg = ($NH4_avg + $NO3_avg) / 2;

      $diff    = abs($ppm_avg - $lower);
      $range   = abs($upper - $lower);
      $range   = $range ?: 1;

      $results['nutrient_lower'] = (float)$lower;
      $results['nutrient_upper'] = (float)$upper;
      $results['nutrient_gauge'] = (float)(($diff / $range) * 180) - 90; /* ANGLE SCALED VALUE (FOR TURNING DIAL) */
      $results['nutrient_pc']    = (float)(($diff / $range) * 100);      /* LINEAR PERCENTAGE VALUE (FOR COLORING FIELD) */
      $results['NH4_AVG']   = (float)$NH4_avg; /* ACTUAL VALUE */
      $results['NO3_AVG']   = (float)$NO3_avg; /* ACTUAL VALUE */
      $results['nutrient_avg']   = (float)$ppm_avg; /* ACTUAL VALUE */
      $results['moisture_avg']   = (float)$moisture_avg; /* ACTUAL VALUE */
      $results['temperature_avg']   = (float)$temperature_avg; /* ACTUAL VALUE */
      $results['nutrient_label'] = $tpl->name;
    }

    return $results;
  }
  /*

$M3_avg_val = 0;
$M3_val = 0;
$M3 = [];
$count = 0;
if(isset($item->M3_1))
{
    $count++;
    $M3_val = (($item->M3_1 * $poly1) + $poly2);
}
if(isset($item->M3_2))
{
    $count++;
    $M3_val += (($item->M3_2 * $poly1) + $poly2);
}
if(isset($item->M3_3))
{
    $count++;
    $M3_val += (($item->M3_3 * $poly1) + $poly2);
}
if(isset($item->M3_4))
{
    $count++;
    $M3_val += (($item->M3_4 * $poly1) + $poly2);
}

$M3_avg_val = $M3_val/$count; 


$M4_avg_val = 0;
$M4_val = 0;
$M4 = [];
$count = 0;
if(isset($item->M4_1))
{
    $count++;
    $M4_val = (($item->M4_1 * $poly1) + $poly2);
}
if(isset($item->M4_2))
{
    $count++;
    $M4_val += (($item->M4_2 * $poly1) + $poly2);
}
if(isset($item->M4_3))
{
    $count++;
    $M4_val += (($item->M4_3 * $poly1) + $poly2);
}
if(isset($item->M3_4))
{
    $count++;
    $M4_val += (($item->M4_4 * $poly1) + $poly2);
}

$M4_avg_val = $M4_val/$count; 

$M5_avg_val = 0;
$M5_val = 0;
$M5 = [];
$count = 0;
if(isset($item->M5_1))
{
    $count++;
    $M5_val = (($item->M5_1 * $poly1) + $poly2);
}
if(isset($item->M5_2))
{
    $count++;
    $M5_val += (($item->M5_2 * $poly1) + $poly2);
}
if(isset($item->M5_3))
{
    $count++;
    $M5_val += (($item->M5_3 * $poly1) + $poly2);
}
if(isset($item->M5_4))
{
    $count++;
    $M5_val += (($item->M5_4 * $poly1) + $poly2);
}

$M5_avg_val = $M5_val/$count; 

$M6_avg_val = 0;
$M6_val = 0;
$M6 = [];
$count = 0;
if(isset($item->M6_1))
{
    $count++;
    $M6_val = (($item->M6_1 * $poly1) + $poly2);
}
if(isset($item->M6_2))
{
    $count++;
    $M6_val += (($item->M6_2 * $poly1) + $poly2);
}
if(isset($item->M6_3))
{
    $count++;
    $M6_val += (($item->M6_3 * $poly1) + $poly2);
}
if(isset($item->M6_4))
{
    $count++;
    $M6_val += (($item->M6_4 * $poly1) + $poly2);
}

$M6_avg_val = $M6_val/$count; 


*/




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
        round(($color1[0] * $w1) + ($color2[0] * $w2)), // r
        round(($color1[1] * $w1) + ($color2[1] * $w2)), // g
        round(($color1[2] * $w1) + ($color2[2] * $w2))  // b
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

    $firstColorP  = $colors[$colorRange[0]][0];
    $secondColorP = $colors[$colorRange[1]][0];

    $firstColorA  = $colors[$colorRange[0]][1];
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
      $val = bcdiv(DB::table('node_data')
        ->select('average')
        ->where('probe_id', $node_address)
        ->orderBy('id', 'desc')
        ->limit(1)
        ->value('average'), 1, 2);
    } else if ($node_type == 'Nutrients') {
      $val = bcdiv(DB::table('nutri_data')->select('M0_1')
        ->where('node_address', $node_address)
        ->orderBy('id', 'desc')
        ->limit(1)
        ->value('M0_1'), 1, 2);
    }
    return $val;
  }

  public static function getLatestNodeAvgTemp($node)
  {
    $val = 0;
    $node_address = is_array($node) ? $node['node_address'] : $node->node_address;
    $node_type = is_array($node) ? $node['node_type'] : $node->node_type;

    if ($node_type == 'Soil Moisture') {
      $temps = DB::table('node_data')
        ->select(['t1', 't2', 't3', 't4', 't5', 't6', 't7', 't8', 't9', 't10', 't11', 't12', 't13', 't14', 't15'])
        ->where('probe_id', $node_address)
        ->orderBy('id', 'desc')
        ->limit(1)
        ->get()
        ->toArray();
      $tot = 0;
      $ctr = 0;
      if ($temps) {
        for ($i = 1; $i <= 15; $i++) {
          $k = "t$i";
          if (!empty($temps[$k]) && $temps[$k] > 0) {
            $tot += (float)$temps[$k];
            $ctr++;
          }
        }
        $val = bcdiv($ctr < 1 ? $tot : ($tot / $ctr), 1, 2);
      }
    } else if ($node_type == 'Nutrients') {
      $val = bcdiv(DB::table('nutri_data')->select('M1_1')
        ->where('node_address', $node_address)
        ->orderBy('id', 'desc')
        ->limit(1)
        ->value('M1_1'), 1, 2);
    }
    return $val;
  }
}
