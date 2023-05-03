<?php

namespace App;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use App\Models\node_data;
use App\Models\nutri_data;
use App\Models\node_data_meter;

class Utils {
    // credits: Wordpress
    public static function sanitize_filename($filename)
    {
        $filename = preg_replace(
            '~
            [<>:"/\\|?*]|            # file system reserved https://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
            [\x00-\x1F]|             # control characters http://msdn.microsoft.com/en-us/library/windows/desktop/aa365247%28v=vs.85%29.aspx
            [\x7F\xA0\xAD]|          # non-printing characters DEL, NO-BREAK SPACE, SOFT HYPHEN
            [#\[\]@!$&\'()+,;=]|     # URI reserved https://tools.ietf.org/html/rfc3986#section-2.2
            [{}^\~`]                 # URL unsafe characters https://www.ietf.org/rfc/rfc1738.txt
            ~x',
            '-', $filename);
        $filename = strip_tags($filename); 
        $filename = preg_replace('/[\r\n\t ]+/', ' ', $filename);
        $filename = preg_replace('/[\"\*\/\:\<\>\?\'\|]+/', ' ', $filename);
        $filename = strtolower($filename);
        $filename = html_entity_decode( $filename, ENT_QUOTES, "utf-8" );
        $filename = htmlentities($filename, ENT_QUOTES, "utf-8");
        $filename = preg_replace("/(&)([a-z])([a-z]+;)/i", '$2', $filename);
        $filename = str_replace(' ', '-', $filename);
        $filename = rawurlencode($filename);
        $filename = str_replace('%', '-', $filename);
        return $filename;
    }

    // credits: https://stackoverflow.com/questions/2955251/php-function-to-make-slug-url-string (Maerlyn)
    public static function slugify($text, string $divider = '_')
    {
        $text = preg_replace('~[^\pL\d]+~u', $divider, $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, $divider);
        $text = preg_replace('~-+~', $divider, $text);
        $text = strtolower($text);
        if (empty($text)) { return 'n-a'; }
        return $text;
    }

    public static function get_first_number($text)
    {
        if(empty($text)){ return ''; }
        
        $var = array_filter(preg_split("/\D+/", $text));
        return reset($var);
    }

    // credits: https://stackoverflow.com/questions/51296733/laravel-file-storage-how-to-store-decoded-base64-image
    public static function upload_base64_file($filename, $file_data)
    {
        $base64_image = $file_data;
        @list($type, $file_data) = explode(';', $base64_image);
        @list($b64slug, $file_data) = explode(',', $file_data); 
        @list(,$extension) = explode('/', $type);
        $url = '';
        if($filename && $extension){
            $imageName = $filename . '.' . $extension;
            Storage::disk('public')->put($imageName, base64_decode($file_data), 'public');
            $url = Storage::url($imageName);
        }
        return $url;
    }

    public static function convertSubsystemToNodeType($subsystem)
    {
        $subsystemToTypeMap = [
            'Well Controls' => 'Wells',
            'Meters'        => 'Water Meter',
            'Soil Moisture' => 'Soil Moisture',
            'Nutrients'     => 'Nutrients'
        ];
        return array_key_exists($subsystem, $subsystemToTypeMap) ? $subsystemToTypeMap[$subsystem] : '';
    }

    public static function convertNodeTypeToSubsystem($type)
    {
        $typeToSubsystemMap = [
            'Wells'         => 'Well Controls',
            'Water Meter'   => 'Meters',
            'Soil Moisture' => 'Soil Moisture',
            'Nutrients'     => 'Nutrients'
        ];
        return array_key_exists($type, $typeToSubsystemMap) ? $typeToSubsystemMap[$type] : '';
    }

    public static function encryptEncode($data)
    {
        return base64_encode(Crypt::encryptString(json_encode($data)));
    }

    public static function decryptDecode($data)
    {
        return json_decode(Crypt::decryptString(base64_decode($data)), true);
    }

    public static function findFromPartial($array, $needle, $default){
        if(!$array || !$default) return '';
        foreach($array as $haystack){
          if(stripos($haystack, $needle) !== false){
            return $haystack;
          }
        }
        return $default;
    }

    public static function findColumnAlias($column)
    {
        if(strpos($column, ' as ') !== false){
            $tmp = explode(' as ', $column);
            return trim($tmp[1]);
        } else if(strpos($column, ' AS ') !== false){
            $tmp = explode(' AS ', $column);
            return trim($tmp[1]);
        }
        return $column;
    }

    public static function getTimeZoneOffsets($identifiers = [])
    {
        if(!$identifiers){
            $identifiers = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
        }
        $offsets = [];

        // This is the proper way to get timezone offsets (as opposed to using DateTimeZone::listAbbreviations())
        foreach($identifiers as $ident){
            $now = new \DateTime(null, new \DateTimeZone($ident));
            $offset = $now->getOffset();
            if($offset){
                $offset /= 3600; // convert seconds to hours
            }
            $offsets[$ident] = 'UTC' . ($offset >= 0 ? ('+' . $offset) : $offset);
        }
        return $offsets;
    }

    // Charging/Discharging (uses UTC dates)
    public static function calculatePowerState(
        $node_address,
        $node_type = 'Soil Moisture',
        $curr_pop = NULL,
        $prev_pop = NULL
    ){
        $powerState = 'Indeterminate';
        $curr_bv = 0;
        $prev_bv = 0;
        $curr_dt = null;
        $prev_dt = null;

      /*  if($curr_pop && $prev_pop){
            if($node_type == 'Soil Moisture'){
                $curr_bv = $curr_pop->bv;
                $curr_dt = $curr_pop->date_time;
                $prev_bv = $prev_pop->bv;
                $prev_dt = $prev_pop->date_time;
            } else if($node_type == 'Nutrients'){
                $curr_bv = $curr_pop->bv;
                $curr_dt = $curr_pop->date_time;
                $prev_bv = $prev_pop->bv;
                $prev_dt = $prev_pop->date_time;
            } else {
                $curr_bv = $curr_pop->batt_volt;
                $curr_dt = $curr_pop->date_time;
                $prev_bv = $prev_pop->batt_volt;
                $prev_dt = $prev_pop->date_time;
            }
        } else*/ if($node_address) {
            if($node_type == 'Soil Moisture'){
                $readings = node_data::select(['date_time', 'bv'])
                    ->where('probe_id', $node_address)
                    ->orderBy('id', 'desc')
                    ->where('average', '>', 0)
                    ->where('accumulative', '>', 0)
                    ->limit(2)
                    ->get();
                if($readings->count() == 2){
                    $curr_bv = $readings[0]->bv;
                    $curr_dt = $readings[0]->date_time;
                    $prev_bv = $readings[1]->bv;
                    $prev_dt = $readings[1]->date_time;
                }
            } else if($node_type == 'Nutrients'){
                $readings = nutri_data::select(['date_sampled AS date_time', 'bv'])
                ->distinct('date_sampled')
                ->orderBy('date_sampled', 'desc')
                ->limit(2)
                ->get()
                ->toArray();
                if($readings->count() == 2){
                    $curr_bv = $readings[0]->bv;
                    $curr_dt = $readings[0]->date_time;
                    $prev_bv = $readings[1]->bv;
                    $prev_dt = $readings[1]->date_time;
                }
            } else {
                $readings = node_data_meter::select(['date_time', 'batt_volt'])
                    ->where('node_id', $node_address)
                    ->orderBy('idwm', 'desc')
                    ->limit(2)
                    ->get();
                if($readings->count() == 2){
                    $curr_bv = $readings[0]->batt_volt;
                    $curr_dt = $readings[0]->date_time;
                    $prev_bv = $readings[1]->batt_volt;
                    $prev_dt = $readings[1]->date_time;
                }
            }
        }

        if($curr_bv && $prev_bv && $curr_dt && $prev_dt){

            $curr_time = strtotime($curr_dt) / 60;
            $prev_time = strtotime($prev_dt) / 60;
            $now = strtotime("now") / 60;

            // ensure reading is less than 60 minutes old (and readings differ no more than 60 minutes)
            if((($now - $curr_time) < 60) && (($curr_time - $prev_time) < 60)){
                $powerState = 'Power, ';

                $delta = $curr_bv - $prev_bv;

                if($delta > 0){
                    $powerState .= 'charging';
                } else if($delta == 0){
                    $powerState .= 'idle';
                } else {
                    $powerState .= 'discharging';
                }
            }
        }

        return $powerState;
    }

    // https://stackoverflow.com/questions/27314506/laravel-how-to-get-query-with-bindings
    public static function getQuery($builder)
    {
        $addSlashes = str_replace('?', "'?'", $builder->toSql());
        return vsprintf(str_replace('?', '%s', $addSlashes), $builder->getBindings());
    }

    // recursive array_merge
    public static function array_merge_rec(array $array1, array $array2)
    {
        $merged = $array1;
    
        foreach ($array2 as $key => & $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::array_merge_rec($merged[$key], $value);
            } else if (is_numeric($key)) {
                 if (!in_array($value, $merged)) {
                    $merged[] = $value;
                 }
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /*
    public static function calc_graph_data_resolution($diff_days, $date_column){
        if($diff_days <= 7){
            // One Week or Less - Full Resolution
            return "";
        } else if($diff_days > 7 && $diff_days <= 14){
            // Two Weeks - Ten Minute Intervals
            return "MINUTE($date_column) IN (0,1,2,3, 9,10,11, 19,20,21, 29,30,31, 39,40,41, 49,50,51, 57,58,59) "; // 22/60
        } else if($diff_days > 14 && $diff_days <= 31){
            // One Month - Fifteen Minute Intervals
            return "MINUTE($date_column) IN (0,1,2,3, 14,15,16, 29,30,31, 44,45,46, 57,58,59) "; // 15/60
        } else if($diff_days > 31){
            // Bigger than one Month - Daily Intervals
            return "HOUR($date_column) IN (0,1,2, 21,22,23) AND MINUTE($date_column) IN (0,1,2,3,4,5,6, 54,55,56,57,58,59)"; // 12/60
        }
    }
    */

    // public static function calc_graph_data_resolution($diff_days, $date_column){
    //     if($diff_days <= 7){
    //         // One Week or Less - Full Resolution
    //         return "";
    //     } else if($diff_days > 7 && $diff_days <= 14){
    //         // Two Weeks - Ten Minute Intervals
    //         $resolution = "IN (0,1,2,3, 9,10,11, 19,20,21, 29,30,31, 39,40,41, 49,50,51, 57,58,59)"; // 22/60
    //     } else if($diff_days > 14 && $diff_days <= 31){
    //         // One Month - Fifteen Minute Intervals
    //         $resolution = "IN (0,1,2,3, 14,15,16, 29,30,31, 44,45,46, 57,58,59)"; // 15/60
    //     } else if($diff_days > 31 && $diff_days <= 90){
    //         // Three Months - Half-Hour Intervals
    //         $resolution = "IN (0,1,2,3,4,5, 28,29,30,31,32, 55,56,57,58,59)"; // 15/60
    //     } else if($diff_days > 90 ){
    //         // Bigger than 3 Months - Hourly Intervals
    //         $resolution = "IN (0,1,2,3,4,5,6, 54,55,56,57,58,59)"; // 12/60
    //     }
    //     return "MINUTE($date_column) {$resolution} ";
    // }

    public static function calc_graph_data_resolution($diff_days, $date_column)
    {
        if ($diff_days <= 7) {
            // One Week or Less - Full Resolution
            return "";
        } else if ($diff_days > 7 && $diff_days <= 14) {
            // Two Weeks - Ten Minute Intervals (NOW: 15 minute intervals)
            $resolution = "IN (0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27, 28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54, 55,56,57,58,59)"; // 22/60
        } else if ($diff_days > 14 && $diff_days <= 31) {
            // One Month - Fifteen Minute Intervals (new: 15)
            $resolution = "IN (0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27, 28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54, 55,56,57,58,59)"; // 15/60
        } else if ($diff_days > 31 && $diff_days <= 90) {
            // Three Months - Half-Hour Intervals
            $resolution = "IN (0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27, 28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54, 55,56,57,58,59)"; // 15/60
        } else if ($diff_days > 90) {
            // Bigger than 3 Months - Hourly Intervals
            $resolution = "IN (0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27, 28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54, 55,56,57,58,59)"; // 12/60
        }
        return "MINUTE($date_column) {$resolution} ";
    }

    public static function time_duration_display($date){

        $start = new \DateTime($date);
        $now   = new \DateTime('now');
        
        $durationInSeconds = $now->getTimestamp() - $start->getTimestamp();

        $duration = '';
        $days = floor($durationInSeconds / 86400);
        $durationInSeconds -= $days * 86400;
        $hours = floor($durationInSeconds / 3600);
        $durationInSeconds -= $hours * 3600;
        $minutes = floor($durationInSeconds / 60);
        $seconds = $durationInSeconds - $minutes * 60;
        
        if($days > 0) {
            $duration .= $days . ' day' . ($days > 1 ? 's' : '');
        }
        if($hours > 0) {
            $duration .= ' ' . $hours . ' hour' . ($hours > 1 ? 's' : '');
        }
        if($minutes > 0) {
            $duration .= ' ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        }
        if($seconds > 0) {
            $duration .= ' ' . $seconds . ' second' . ($seconds > 1 ? 's' : '');
        }
        return trim($duration);
    }
}