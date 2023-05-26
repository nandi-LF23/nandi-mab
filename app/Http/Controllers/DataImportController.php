<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

use App\Models\node_data;
use App\Models\node_data_meter;
use App\Models\nutri_data;
use App\Models\hardware_config;
use App\Models\raw_data_dmt;
use App\Models\raw_data_catm;
use App\Models\raw_data_fieldwise;
use App\Models\raw_data_banner;
//use App\Mail\Message;
//use Mail;

class DataImportController extends Controller
{

    // Multi-Probe Digital Matter Telematics (DMT) - Eagle Datalogger Import oemserver.com endpoint function
    public function dmtImport(Request $request, $imei)
    {
        set_time_limit(0);

        Log::debug('dmtImport');
        // DISCARD INCOMPLETE REQUESTS
        if(empty($imei)) return response()->json([], 200);
        $data = $request->all();
        //if(empty($data['Records']) || empty($data['ProdId']) || $data['ProdId'] != 78) { return response()->json([], 200); }

        // SAVE RAW DATA
        if(empty($request->no_raw_save)){
            $json = json_encode($data); $raw = new raw_data_dmt(); $raw->device_id = $imei; $raw->device_data = $json; $raw->save();
            if(strpos($_SERVER['SERVER_NAME'], 'dev.myagbuddy.com') !== false){
                // CLONE TO SANDBOX
                Http::withBasicAuth('dmt@liquidfibre.com', '$jf843F$#ju23SE#2ju13D1ui!')
                ->withBody($json, 'application/json')
                ->post("https://sandbox.myagbuddy.com/api/dmtimport/{$imei}");
            }
        }

        $moisture_probe_models = [ 'ACHSDI', 'ACCSDI' ];
        $nutrient_probe_models = [ 'EC1VER', 'EC2VER' ];

        // "13AquaChckEC1VER001S2000009"
        $parse_aquacheck_string = function($str) use ($moisture_probe_models, $nutrient_probe_models) {
            $d = []; $d['ven'] = strpos( $str, '13AquaChck' ) !== false ? 'AquaChck' : 'DMT'; // probe vendor
            $str = str_replace( '13AquaChck', '', $str); $str = str_replace( array_merge($moisture_probe_models, $nutrient_probe_models), '', $str );
            $d['ver'] = substr( $str, 0, 3 ); // probe firmware version
            $d['ser'] = substr( $str, 3 ); // probe serial
            return $d;
        };

        $array_find = function($hs, array $nds){ foreach($nds as $n){ if(stripos($hs, $n) !== false){ return $n; } } return false; };
        $node_ver = "{$data['SerNo']}_{$data['ProdId']}_{$data['FW']}_{$data['ICCID']}";

        $m_items  = [];
        $n_items  = [];
        $hw_items = [];

        $date_reported = (new \DateTime())->format('Y-m-d H:i:s'); $date_sampled = '';
        $gps_epsilon = 0.001; // ~100 meters.

        foreach($data['Records'] as $row)
        {
            // skip records with empty fields (sanity check)
            if(empty($row['Fields'])){ continue; }

            $node_address = null; $probe_address = null;
            $date_sampled = $row['DateUTC'];

            $m_values = [];
            $t_values = [];
            $n_values = [];

            $latt = 0; $lng  = 0;
            $bv = 0; $bp = 0;
            $temp = 0;

            $model = ''; $vendor = ''; $probe_serial = ''; $firmware_ver = ''; $vendor_model_fw = '';

            $is_moisture_data = false;
            $is_nutrient_data = false;

            // skip incomplete nutrient readings
            if( !empty($row['Fields'][0]['FType']) && $row['Fields'][0]['FType'] == 4 &&
                !empty($row['Fields'][0]['DevId']) &&
                    (
                        strpos($row['Fields'][0]['DevId'], 'EC1VER') !== false ||
                        strpos($row['Fields'][0]['DevId'], 'EC2VER') !== false
                    )
                && count($row['Fields']) < 11 ){ continue; }

            foreach ($row['Fields'] as $field) {
                $type = (int) $field['FType'];

                if ($type == 0) { // gps
                    if (!empty($field['Lat']) && !empty($field['Long'])) {
                        $latt = (float) $field['Lat'];
                        $lng = (float) $field['Long'];
                        $hw = hardware_config::where('node_address', 'like', $imei . '%')->first();



                        $hw->latt = $latt;
                        $hw->lng = $lng;

                        $hw->date_time = $field['GpsUTC'];
                        $hw->save();

                        //    $hw = hardware_config::where('node_address', 'like', $imei . '%')->first();

                    }
                }

                if ($type == 4) { // sdi
                    $probe_address = (int) chr($field['DevAddr']);
                    $node_address = trim($imei) . '-' . $probe_address;

                    $ss = $parse_aquacheck_string($field['DevId']);
                    $vendor = $ss['ven'];
                    $firmware_ver = $ss['ver'];
                    $probe_serial = $ss['ser'];

                    if ($model = $array_find($field['DevId'], $moisture_probe_models)) {
                        $is_moisture_data = true; // Detect Soil Moisture Probes
                    }
                    if ($model = $array_find($field['DevId'], $nutrient_probe_models)) {
                        $is_nutrient_data = true; // Detect Nutrient Probes
                    }
                    if ($model) {
                        $vendor_model_fw = "{$vendor}.{$model}.{$firmware_ver}";
                    }
                }

                if ($type == 5) { // measurements
                    $count = count($field['Ms']);
                    if ($count > 0) {
                        $mt = (int) $field['MType'];
                        if ($is_moisture_data) { // SMs / Temps
                            if ($mt == 0) { // M0 == SM
                                $total = 0;
                                for ($s = 1; $s <= $count; $s++) {
                                    $val = $field['Ms'][$s - 1] / 1000;
                                    $total += $val;
                                    $m_values["sm{$s}"] = $val;
                                }
                                $m_values['accumulative'] = $total;
                                $m_values['average'] = $total / $count;
                            } else if ($mt == 1) { // M1 == Temp
                                for ($t = 1; $t <= $count; $t++) {
                                    $t_values["t{$t}"] = $field['Ms'][$t - 1] / 1000;
                                }
                            }
                        } else if ($is_nutrient_data) {
                            for ($n = 1; $n <= $count; $n++) {
                                $n_values["M" . $mt . "_" . $n] = $field['Ms'][$n - 1] / 1000;
                            }
                        }
                    }
                }

                if ($type == 6) { // analog
                    $bv = !empty($field["AnalogueData"]['1']) ? ((float) $field["AnalogueData"]['1']) : 0; // Batt.Volts.
                    $temp = !empty($field["AnalogueData"]['3']) ? (((float) $field["AnalogueData"]['3']) / 100) : 0; // Ambient Temperature (Deg C * 100)
                    //  $bp   = !empty($field["AnalogueData"]['1']) ? ( ( 3300 - (float) $field["AnalogueData"]['1'] ) / 4100  )*100 : 0; // Batt.Percentage.

                    // backfill



                    /*
                    * Eagles are 6.2v on the high side and 4.7
                    */


                    $range = 6200 - 4700;
                    $delta = $bv - 4700;
                    if ($delta <= 0)
                        $delta = 0;
                    $level = ($delta / $range) * 100;

                    $bp = $level < 0 ? 0 : ($level > 100 ? 100 : $level);


                    if (!empty($m_items) && is_array($m_items) && count($m_items) > 0) {
                        foreach ($m_items as &$m) {
                            if (is_object($m)) {
                                if ($bv) {
                                    $m->bv = $bv;
                                }
                                if ($temp) {
                                    $m->ambient_temp = $temp;
                                }
                                if ($bp) {
                                    $m->bp = $bp;
                                }
                            }
                        }
                    }
                    if (!empty($n_items) && is_array($n_items) && count($n_items) > 0) {
                        foreach ($n_items as &$n) {
                            if (is_object($n)) {
                                if ($bv) {
                                    $n->bv = $bv;
                                }
                                if ($temp) {
                                    $n->ambient_temp = $temp;
                                }
                                if ($bp) {
                                    $n->bp = $bp;
                                }
                            }
                        }
                    }
                }

            } // end for Fields

            if(!empty($m_values) && !empty($t_values)){
                $item = new node_data;
                $item->probe_id = $node_address;
                $item->date_time = $date_sampled;
                for($i = 1; $i <= 15; $i++){ $item->{"sm{$i}"} = 0; $item->{"t{$i}"} = 0; }
                foreach($m_values as $key => $val){ $item->{$key} = $val; }
                foreach($t_values as $key => $val){ $item->{$key} = $val; }
                $item->accumulative = $m_values['accumulative'];
                $item->average = $m_values['average'];
                $item->rg = 0;
                $item->bv = $item->bv ?: $bv;
                $item->bp = $item->bp ?: $bp;

                $item->latt = $item->latt ? $latt: $latt;
                $item->lng = $item->lng ? $lng : $lng;

                $item->ambient_temp = $item->ambient_temp ?: $temp;

                $item->message_id_1 = "dmt"; $item->message_id_2 = "dmt_{$node_address}_{$date_sampled}";
                $item->save();
                // Prevent storing entries with all zero values
                if($item->accumulative != 0){
                    $m_items[] = $item;
                }

                $hw_items[$node_address] = [ 'latitude'  => $latt, 'longitude' => $lng, 'date_time' => $date_sampled ];

            }

            if(!empty($n_values)){


                    $item = new nutri_data();

                    $item->node_address = $node_address; $item->probe_serial = $probe_serial;
                    $item->vendor_model_fw = $vendor_model_fw; $item->ver = $node_ver;
                    foreach($n_values as $key => $val){
                        $item->{$key} = $val;
                    }
                    $item->date_reported = $date_reported; $item->date_sampled = $date_sampled;

                    $item->bv = $item->bv ?: $bv;
                    $item->bp = $item->bp ?: $bp;

                    $item->latt = $item->latt ? $latt: $latt;
                    $item->lng = $item->lng ? $lng: $lng;

                    $item->ambient_temp = $item->ambient_temp ?: $temp;

                    $item->message_id = 'dmt_'.$node_address .'_' . $date_sampled;

                    $n_items[] = $item;

                    $hw_items[$node_address] = [ 'latitude'  => $latt, 'longitude' => $lng, 'date_time' => $date_sampled ];


                   // Log::info(print_r($item));
                   // $item->save();
            }

        } // end for Records

        if($m_items){
            foreach($m_items as &$m_item){
                if(!node_data::where('message_id_2', $m_item->message_id_2)->exists()){
                    $m_item->save();
                } else {
                    $existing = node_data::where('message_id_2', $m_item->message_id_2)->first();
                    if($existing){
                        $existing->update([
                            'ambient_temp' => $m_item->ambient_temp,
                            'bv' => $m_item->bv,
                            'bp' => $m_item->bp,
                            'latt' => $latt,
                            'lng' => $lng
                        ]);
                    }
                }
            }
        }
        if ($n_items) {
            foreach ($n_items as &$n_item) {
                if (is_object($n_item)) {
                    if (!nutri_data::where('message_id', $n_item->message_id)->exists()) {
                        $n_item->save();
                    }
                }
                if (is_object($n_item)) {
                    $existing = nutri_data::where('message_id', $n_item->message_id)->first();
                    if ($existing) {
                        $existing->update([
                            'ambient_temp' => $n_item->ambient_temp,
                            'bv' => $n_item->bv,
                            'bp' => $n_item->bp,
                            'latt' => $latt,
                            'lng' => $lng
                        ]);
                    }

                }
                if (is_array($n_item)) {
                    $existing = nutri_data::where('message_id', $n_item['message_id'])->first();
                    if ($existing) {
                        $existing->update([
                            'ambient_temp' => $n_item['ambient_temp'],
                            'bv' => $n_item['bv'],
                            'bp' => $n_item['bp'],
                            'latt' => $latt,
                            'lng' => $lng
                        ]);
                    }
                }
            }
        }
        if($hw_items){
            foreach($hw_items as $node_address => $item){
                $hw = hardware_config::where('node_address', $node_address)->first();
                if($hw){
                    if(!$hw->coords_locked){
                        $latt = $item['latitude']; $lng  = $item['longitude'];
                        // GPS DRIFT PREVENTION
                        if(($latt && (abs($latt - $hw->latt) > $gps_epsilon)) || ($lng && (abs($lng - $hw->lng) > $gps_epsilon))){
                            $hw->latt = $latt;
                            $hw->lng  = $lng;
                        }
                    }
                    $hw->date_time = $item['date_time'];
                    $hw->save();
                }
            }
        }

        return response()->json([], 200);
    }


    public function catmImport(Request $request, $imei){

        // DATE REPORTED (USED BY NUTRIENTS)
        $date_reported = (new \DateTime())->format('Y-m-d H:i:s');

        // GET RAW DATA
        $raw_data = $request->getContent();

        // SAVE RAW DATA
        if(empty($request->no_raw_save)){
            $raw = new raw_data_catm();
            $raw->device_id = $imei ?: "NO_IMEI";
            $raw->device_data = $raw_data;
            $raw->save();
        }
        // SAVE RAW DATA

        // PROBESCHEDULE PUSH
        // if(empty($request->no_raw_save) && in_array($imei, ['354679096319994', '354679096314318', '354679096314458', '354679096319879', '354679096314151'])){
        //     Http::withBody($raw_data, 'application/json')->post('https://gateway.probeschedule.co.za/gateway/liquidfibre');
        // }

        // DAVE PROBE PUSH FROM .COM -> .CO.ZA
        // if(empty($request->no_raw_save) && stripos($_SERVER['HTTP_HOST'], '.com') !== false && in_array($imei, ['354679096314086'])){
        //     Http::withBody($raw_data, 'application/json')->post('https://myagbuddy.co.za/api/catmimport/354679096314086');
        //     Http::withBody($raw_data, 'application/json')->post('https://dev.myagbuddy.com/api/catmimport/354679096314086');
        // }

        // UTILITY FUNCTION: find any one of the multiple needles($nds) in a string haystack ($hs)
        $array_find = function($hs, array $nds){ foreach($nds as $n){ if(stripos($hs, $n) !== false){ return $n; } } return false; };

        // DEFAULT RETURN DATA + HEADERS
        $return_data   = [ 'id' => !empty($raw->id) ? $raw->id : 0, 'status' => 'EMPTY', 'length' => strlen($raw_data), 'sp' => 900, 'rp' => 900 ];
        $return_header = [ 'Content-Type' => 'LiquidFibre/json', 'Connection' => 'close' ];

        // ATTEMPT RAW DATA DECODE
        $data = json_decode($raw_data, true);

        // FAIL IF DATA FAULTY OR MISSING IMEI
        if(!$data || !$imei){
            if(!$data){ $return_data['status'] = 'ERROR: ' . json_last_error_msg(); }
            else if(!$imei){ $return_data['status'] = 'ERROR: NO IMEI'; }
            return response()->json($return_data, 422)->withHeaders($return_header);
        }

        // SM+TEMP (node_data) + NUTRIENT ITEMS (nutri_data) + HARDWARE_CONFIG updates (hardware_config)
        $m_items = []; $n_items = []; $hw_items = [];

        $nutrient_probe_models  = [ 'EC1VER' ];
        $nutrient_measure_types = [ 'MC', 'CC', 'RC', 'M', 'C', 'R' ];

        $moisture_probe_models  = [ 'ACHSDI', 'ACCSDI', '_GSSPS', 'SMP000'];
        $moisture_measure_types = [ 'MC', 'M' ];

        // CHECK IF THERE ARE MESSAGES
        if(!empty($data['msgs'])){

            // SM + TEMP STORAGE (ACROSS LOOP ITERATIONS)
            $m_values = [];
            $t_values = [];

            // PROCESS EACH MESSAGE (COLLECT DATA)
            foreach($data['msgs'] as $item){

                // DATA MESSAGE
                if(!empty($item['msgType']) && stripos($item['msgType'], 'DATA_') !== false){

                    $sensor_count   = !empty($item['val']) && is_array($item['val']) ? count(array_filter($item['val'])) : 0;
                    if(!$sensor_count){ continue; }

                    list($vendor, $model, $firmware_ver) = explode('.', $item['msgId']);
                    $message_type   = str_replace('DATA_', '', $item['msgType']); // M0, M1, etc.
                    $date_sampled   = (new \DateTime())->setTimestamp($item['timestamp'])->format('Y-m-d H:i:s');
                    $sensor_address = (int) $item['sensorAddress'];

                    // MOISTURE PROBE
                    if(in_array($model, $moisture_probe_models) && $array_find($message_type, $moisture_measure_types)){

                        // e.g 1 of M1 (number in message_type)
                        $device_address = (int) str_ireplace($moisture_measure_types, '', $message_type);

                        // GET SOIL MOISTURES (M0)
                        if($device_address == 0 && empty($m_values) && empty($t_values)){

                            $accumulative = 0;

                            for($i = 1; $i <= 15; $i++){ $m_values["sm$i"] = (float) 0; }
                            for($i = 1; $i <= $sensor_count; $i++){
                                if(!empty($item['val'][$i-1])){
                                    $val = (float) $item['val'][$i-1];
                                    $m_values["sm$i"] = $val;
                                    $accumulative += $val;
                                }
                            }

                            $m_values['probe_id']     = "{$imei}-{$sensor_address}";
                            $m_values['date_time']    = $date_sampled;
                            $m_values['accumulative'] = $accumulative;
                            $m_values['average']      = $accumulative / $sensor_count;
                            $m_values['bv']           = !empty($item['voltage']) ? $item['voltage'] : 0;
                            $m_values['bp']           = (float) !empty($item['voltage']) ? bcdiv((($item['voltage'] - 3500) / 1000) * 100, 1, 2) : 0;
                            $m_values['rg']           = 0;
                            $m_values['latt']         = (float) !empty($item['lat']) ? $item['lat'] : 0;
                            $m_values['lng']          = (float) !empty($item['lon']) ? $item['lon'] : 0;
                            $m_values['ambient_temp'] = (float) !empty($item['temperature']) ? $item['temperature'] : 0;
                            $m_values['message_id_1'] = 'catm';
                            $m_values['message_id_2'] = "catm_{$imei}-{$sensor_address}_{$date_sampled}";

                        }

                        // GET TEMPERATURES (M1)
                        if(($device_address == 1 || $device_address == 2) && !empty($m_values) && empty($t_values)){

                            for($i = 1; $i <= 15; $i++){ $t_values["t$i"] = (float) 0; }
                            for($i = 1; $i <= $sensor_count; $i++){
                                if(!empty($item['val'][$i-1])){
                                    $t_values["t$i"] = (float) $item['val'][$i-1];
                                }
                            }

                            $t_values['probe_id']  = "{$imei}-{$sensor_address}";
                            // this line is crucial - it causes the last packet's date_time to be used (DO NOT REMOVE)
                            $t_values['date_time'] = $date_sampled;

                        }

                        // GOT BOTH? STORE AS ITEM
                        if(!empty($m_values) && !empty($t_values) && $m_values['probe_id'] == $t_values['probe_id']){

                            $m_item = new node_data();
                            foreach($m_values as $k => $v){ $m_item->{$k} = $v; }
                            foreach($t_values as $k => $v){ $m_item->{$k} = $v; }

                            // STORE ITEM (PREVENT STORING ALL ZEROS)
                            if($m_item->accumulative != 0){
                                $m_items[] = $m_item;
                            }

                            // STORE HARDWARE_CONFIG UPDATES
                            $hw_items[$m_item->probe_id] = [
                                'latitude'  => $m_item->latt,
                                'longitude' => $m_item->lng,
                                'date_time' => $m_item->date_time
                            ];

                            // RESET STORAGES FOR NEXT MOISTURE ITEM
                            $m_values = [];
                            $t_values = [];

                        }

                    // NUTRIENT PROBE
                    } else if(in_array($model, $nutrient_probe_models) && $array_find($message_type, $nutrient_measure_types)){


                                $node_address = "{$imei}-{$sensor_address}";

                                $n_item = new nutri_data();

                                $n_item->node_address    = $node_address;
                                $n_item->probe_serial    = !empty($item['sensorSerial']) ? $item['sensorSerial'] : "";
                                $n_item->vendor_model_fw = !empty($item['msgId']) ? $item['msgId'] : "";
                                $n_item->version         = $firmware_ver;
                                for($i = 1; $i <= $sensor_count; $i++){

                                    if(!empty($item['val'][$i-1])){

                                        $n_item->{$message_type .'_'. $i} = (float) $item['val'][$i-1]; // [Measurement Type][Address]_[nr] (e.g M0_1, M0_2)

                                    }
                                }

                                $n_item->bv              = !empty($item['voltage']) ? $item['voltage'] : 0;
                                $n_item->bp              = (float) !empty($item['voltage']) ? bcdiv((($item['voltage'] - 3500) / 1000) * 100, 1, 2) : 0;
                                $n_item->latt            = (float) !empty($item['lat']) ? $item['lat'] : 0;
                                $n_item->lng             = (float) !empty($item['lon']) ? $item['lon'] : 0;
                                $n_item->ambient_temp    = (float) !empty($item['temperature']) ? $item['temperature'] : 0;

                                $n_item->date_reported   = $date_reported;
                                $n_item->date_sampled    = $date_sampled;
                                $n_item->message_id      = "catm_{$node_address}_{$n_item->identifier}_{$date_sampled}";

                                // STORE ITEM
                                $n_items[] = $n_item;

                                // STORE HARDWARE_CONFIG UPDATES
                                $hw_items[$node_address] = [
                                    'latitude'  => (float) !empty($item['lat']) ? $item['lat'] : 0,
                                    'longitude' => (float) !empty($item['lon']) ? $item['lon'] : 0,
                                    'date_time' => $n_item->date_sampled
                                ];

                    }
                }
            } // END FOREACH

        } // END IF

        // SAVE SM+TEMP ITEMS (node_data)
        if($m_items){
            foreach($m_items as $item){
                if(!node_data::where('message_id_2', $item->message_id_2)->exists()){
                    $item->save();
                    $return_data['status'] = "OK";
                } else if(!empty($request->update_existing)){
                    $existing = node_data::where('message_id_2', $item->message_id_2)->first();
                    if($existing){
                        $existing->update([
                            'ambient_temp' => $item->ambient_temp,
                            'bv' => $item->bv,
                            'bp' => $item->bp
                        ]);
                    }
                    $return_data['status'] = "OK";
                } else {
                    $return_data['status'] = "DUP";
                }
            }
        }

        // SAVE NUTRIENT ITEMS (nutri_data)
        if($n_items){
            foreach($n_items as $item){
                if(!nutri_data::where('message_id', $item->message_id)->exists()){
                    $item->save();
                    $return_data['status'] = "OK";
                } else {
                    $return_data['status'] = "DUP";
                }
            }
        }

        // UPDATE HARDWARE CONFIGS
        if($hw_items){
            foreach($hw_items as $node_address => $item){
                $hw = hardware_config::where('node_address', $node_address)->first();
                if($hw){
                    if(!$hw->coords_locked){
                        $gps_epsilon = 0.001;
                        $latt = $item['latitude'];
                        $lng  = $item['longitude'];
                        // GPS DRIFT PREVENTION
                        if(($latt && (abs($latt - $hw->latt) > $gps_epsilon)) || ($lng && (abs($lng - $hw->lng) > $gps_epsilon))){
                            $hw->latt = $latt;
                            $hw->lng  = $lng;
                        }
                    }
                    $hw->date_time = $item['date_time'];
                    $hw->save();
                }
            }
        }

        return response()->json($return_data, 200)->withHeaders($return_header);
    }

    // Single Probe
    public function fieldwiseImport(Request $request)
    {
        //Log::debug('fieldwiseImport');

        $all_data = $request->all();

        if(!$request->has('OpCode')){ return response()->json(['status' => 'ERROR', 'msg' => 'Missing/empty key: OpCode.' ], 422); }
        if(!$request->has('DataJson')){ return response()->json(['status' => 'ERROR', 'msg' => 'Missing/Empty key: DataJson.' ], 422); }
        if($all_data['OpCode'] != 'probedata'){ return response()->json(['status' => 'ERROR', 'msg' => "Invalid OpCode key: {$all_data['OpCode']}. Supported: probedata." ], 422); }

        $data = json_decode($all_data['DataJson'], true);

        if(empty($data)){ return response()->json(['status' => 'ERROR', 'msg' => "Invalid JSON specified" ], 422); }
        if(empty($data['deviceid'])){ return response()->json(['status' => 'ERROR', 'msg' => "Missing/Empty deviceid" ], 422); }

        $node_address = trim($data['deviceid']) . '-0';

        // SAVE RAW DATA
        if(empty($request->no_raw_save)){
            $raw = new raw_data_fieldwise();
            $raw->device_id = $node_address;
            $raw->device_data = $all_data['DataJson'];
            $raw->save();
        }
        // SAVE RAW DATA

        $status = 'OK';
        $gps_epsilon = 0.001; // ~100 meters.
        $out_of_range = false;

        $dt = new \DateTime($data['dt']);
        $date_sampled = $dt->format('Y-m-d H:i:s');

        $item = new node_data();
        $item->probe_id = $node_address;
        $item->date_time = $date_sampled;
        $item->average = 0;
        $item->accumulative = 0;

        $sensor_count = 0;

        for($i = 1; $i <= 15; $i++){
            $sm = (float) $data["m$i"];
            if($sm > 99999999.00){ $out_of_range = true; /*Log::channel('fieldwise')->debug("O.O.R Measurement for $node_address, sm{$i}: $sm");*/ break; }
            $t = (float) $data["t$i"];
            if($t > 99999999.00){ $out_of_range = true; /*Log::channel('fieldwise')->debug("O.O.R Measurement for $node_address, t{$i}: $t");*/ break; }
            if($sm){ $sensor_count++; }
            $item->accumulative += $sm;
            $item->{"sm$i"} = $sm;
            $item->{"t$i"} = $t;
        }

        // Don't store all zero values
        if($item->accumulative == 0){ $out_of_range = true; }

        $item->average = $sensor_count ? $item->accumulative / $sensor_count : 0;
        if(!is_float($item->average)){ $item->average = 0; }

        $item->rg = 0;
        $item->bv = ((float) $data['batteryV']) * 1000;
        $item->bp = (float) bcdiv((((((float)$data['batteryV']) * 1000) - 5000) / 2200) * 100, 1, 2); // ~7.2 max
        $item->latt = (float) $data['lat'];
        $item->lng = (float) $data['lon'];
        $item->ambient_temp = (float) $data['temperature'];
        $item->message_id_1 = 'fw';
        $item->message_id_2 = "fw_{$node_address}_{$date_sampled}";

        if(!$out_of_range){

            if(!node_data::where('message_id_2', $item->message_id_2)->exists()){
                $item->save();
                $hw = hardware_config::where('node_address', $node_address)->first();
                if(!empty($hw) && is_object($hw)){
                    // GPS update
                    if(!$hw->coords_locked){
                        if(!empty($item->latt) && !empty($item->lng)){
                            // GPS drift prevention
                            if((abs($hw->latt - ((float)$item->latt)) > $gps_epsilon) || (abs($hw->lng - ((float)$item->lng)) > $gps_epsilon)){
                                $hw->latt = $item->latt;
                                $hw->lng = $item->lng;
                                $hw->save();
                            }
                        }
                    }
                    // update date_time on hardware_config (makes for easier joins)
                    if(!empty($item->date_time)){
                        $hw->date_time = $item->date_time;
                        $hw->save();
                    }
                }

            } else if(!empty($request->update_existing)){
                $existing = node_data::where('message_id_2', $item->message_id_2)->first();
                if($existing){
                    // only update selected fields
                    $existing->update([
                        'ambient_temp' => $item->ambient_temp,
                        'bv' => $item->bv,
                        'bp' => $item->bp
                    ]);
                }
            }

        } else {
            $status = 'Out of Range';
        }

        return response()->json(['status' => $status, 'id' => $node_address], 200);
    }

    public function bannerImport(Request $request)
    {
        //Log::debug('bannerImport');
        /*
            {
            "id":"bb9ac190-0e7c-469f-b3b4-461e8d27e611",
            "pkt":"20210819065201",
            "ser":"830956",
            "reg1001":"1113871429", <- probe 1
            "reg1003":"1111329104",
            "reg1005":"1111573658",
            "reg1007":"1113723554",
            "reg1009":"1112647007",
            "reg1011":"1112415246",

            "reg1013":"1109096031", <- probe 2
            "reg1015":"1116311544",
            "reg1017":"1116929628",
            "reg1019":"1118001967",
            "reg1021":"1116040396",
            "reg1023":"1113695845",

            "reg1025":"1106028106", <- probe 3
            "reg1027":"1108849458",
            "reg1029":"1111325880",
            "reg1031":"1111248076",
            "reg1033":"1115005726",
            "reg1035":"1116156303",

            "reg1037":"0",          <- probe 4
            "reg1039":"0",
            "reg1041":"0",
            "reg1043":"0",
            "reg1045":"0",
            "reg1047":"0",

            "reg1049":"1117564920"  <- ??? extra data
            }
        */

        // util: filter input array by partial keys
        $filter   = function($array, $search){ return array_filter($array, function($key) use($search){ return strpos($key, $search) !== false; }, ARRAY_FILTER_USE_KEY ); };
        // util: return html response
        $html     = function($response){ return response($response, 200)->withHeaders([ 'Content-Type' => 'text/html'/*, 'Connection' => 'close' */]); };
        // util: safe get value from array (with default)
        $get      = function($array, $key, $default = ''){ return !empty($array[$key]) ? $array[$key] : $default; };
        // util: convert integer representation of float to binary float
        $conv     = function($num){ return @end(unpack('f', pack('i', (string) $num))); };

        // util: parse the input, creating node_data objects, returning array of node_data objects
        $parse    = function($input, $site_id) use($filter, $get, $conv){

            $timestamp =    $get($input, 'pkt');
            $registers = $filter($input, 'reg');
            $events    = $filter($input, 'event');
            $flags     = $filter($input, 'flags');
            $items     = [];

            ksort($registers, SORT_NATURAL);

            $registers_keys = array_keys($registers);
            $sensor_count   = 6;
            $probe_count    = floor(count($registers_keys) / $sensor_count);
            $reg_vals       = array_values($registers);

            // index = 0; index < 4; index++ , 0, 1, 2, 3
            for($index = 0; $index < $probe_count; $index++)
            {
                $item            = new node_data();
                $item->probe_id  = $site_id . '-' . str_ireplace('reg', '', $registers_keys[ $index * $sensor_count ]);
                $dt              = \DateTime::createFromFormat("YmdHis", $timestamp);
                $item->date_time = $dt->format('Y-m-d H:i:s');

                $item->sm1 = !empty($reg_vals[ ($index * $sensor_count) + 0 ]) ? ($conv($reg_vals[ ($index * $sensor_count) + 0 ])) : 0;
                $item->sm2 = !empty($reg_vals[ ($index * $sensor_count) + 1 ]) ? ($conv($reg_vals[ ($index * $sensor_count) + 1 ])) : 0;
                $item->sm3 = !empty($reg_vals[ ($index * $sensor_count) + 2 ]) ? ($conv($reg_vals[ ($index * $sensor_count) + 2 ])) : 0;
                $item->sm4 = !empty($reg_vals[ ($index * $sensor_count) + 3 ]) ? ($conv($reg_vals[ ($index * $sensor_count) + 3 ])) : 0;
                $item->sm5 = !empty($reg_vals[ ($index * $sensor_count) + 4 ]) ? ($conv($reg_vals[ ($index * $sensor_count) + 4 ])) : 0;
                $item->sm6 = !empty($reg_vals[ ($index * $sensor_count) + 5 ]) ? ($conv($reg_vals[ ($index * $sensor_count) + 5 ])) : 0;

                // all probes throwing errors, discard packet and continue
                if($item->sm1 == 66 && $item->sm2 == 66 && $item->sm3 == 66 && $item->sm4 == 66 && $item->sm5 == 66 && $item->sm6 == 66){
                    //Log::debug($item->probe_id . ': Discarding packet due to all 6 probes yielding error (66) values');
                    continue;
                }

                // skip imports containing NaNs after conversion
                if(is_nan($item->sm1) && is_nan($item->sm2) && is_nan($item->sm3) && is_nan($item->sm4) && is_nan($item->sm5) && is_nan($item->sm6)){
                    continue;
                }

                $item->sm7  = 0; $item->sm8  = 0; $item->sm9  = 0; $item->sm10 = 0;
                $item->sm11 = 0; $item->sm12 = 0; $item->sm13 = 0; $item->sm14 = 0;
                $item->sm15 = 0;

                $item->t1   = 0; $item->t2   = 0; $item->t3   = 0; $item->t4   = 0; $item->t5  = 0;
                $item->t6   = 0; $item->t7   = 0; $item->t8   = 0; $item->t9   = 0; $item->t10 = 0;
                $item->t11  = 0; $item->t12  = 0; $item->t13  = 0; $item->t14  = 0; $item->t15 = 0;

                $item->accumulative = $item->sm1 + $item->sm2 + $item->sm3 + $item->sm4 + $item->sm5 + $item->sm6;
                $item->average = $item->accumulative / $sensor_count;

                $item->rg = 0;
                $item->bv = 2250;
                $item->bp = 50;

                $item->latt = 0;
                $item->lng = 0;

                $item->ambient_temp = 0;

                $item->message_id_1 = 'bf';
                $item->message_id_2 = "bf_{$item->probe_id}_{$item->date_time}";

                // ensure entry doesn't have all zero values
                if($item->accumulative != 0){
                    $items[] = $item;
                }
            }

            return $items;
        };

        $data_items = [];
        $site_id = 'NO_SITE_ID';
        $site_id_full = 'NO_SITE_ID';

        if($request->isMethod('get')){

            $input = $request->all();

            // node is is last part of site id (e.g: bb9ac190-0e7c-469f-b3b4-461e8d27e611, 461e8d27e611 -- node id)
            $site_id_full  = $get($input, 'id', 'NO_SITE_ID');
            $site_id_parts = explode('-', $site_id_full);
            $site_id       = (!empty($site_id_parts) && is_array($site_id_parts) && count($site_id_parts) > 1) ? end($site_id_parts) : 'NO_SITE_ID';

            // SAVE RAW DATA
            if(empty($request->no_raw_save)){
                $raw = new raw_data_banner();
                $raw->device_id = $site_id;
                $raw->device_data = json_encode($input);
                $raw->save();
            }
            // SAVE RAW DATA

            // PARSE
            $data_items = $parse($input, $site_id);

        } else if($request->isMethod('post')){

            $rawBody = $request->getContent();

            // ensure simplexml_load_string can parse the XML by escaping the invalid contents of the <log> tags
            if($rawBody && strpos($rawBody, '![CDATA') === false){
                $rawBody = str_replace('&', '&amp;', $rawBody);
            }

            //Log::debug('rawBody: ' . $rawBody);
            $xml = simplexml_load_string($rawBody, 'SimpleXMLElement', LIBXML_NOCDATA );
            if ($xml !== false) {

                // node is is last part of site id (e.g: bb9ac190-0e7c-469f-b3b4-461e8d27e611, 461e8d27e611 -- node id)
                $site_id_full  = $xml && !empty($xml->id) ? $xml->id : 'NO_SITE_ID';
                $site_id_parts = explode('-', $site_id_full);
                $site_id       = (!empty($site_id_parts) && is_array($site_id_parts) && count($site_id_parts) > 1) ? end($site_id_parts) : 'NO_SITE_ID';

                foreach($xml->httplog as $log){
                    $input = [];
                    if(!empty($log->miss)){
                        parse_str($log->miss, $input);
                    } else if(!empty($log->log)){
                        parse_str($log->log, $input);
                    }
                    // PARSE
                    $data_items = array_merge($data_items, $parse($input, $site_id));
                }

                // SAVE RAW DATA
                if(empty($request->no_raw_save)){

                    $raw = new raw_data_banner();
                    $raw->device_id = $site_id;
                    if(!empty($input)){
                        $input['id'] = $site_id_full;
                        $input['st'] = $xml->st;
                        $raw->device_data = json_encode($input);
                    } else {
                        $rawBody = str_replace('&amp;', '&', $rawBody);
                        $raw->device_data = $rawBody;
                    }
                    $raw->save();
                }
                // SAVE RAW DATA



            } else {
                Log::debug(__FUNCTION__ . ': Failed to parse XML');
            }

        }

        if($data_items){
            foreach($data_items as &$itm){
                if(!empty($itm->message_id_2) && !node_data::where('message_id_2', $itm->message_id_2)->exists()){
                    $itm->save();

                    // update date_time on hardware_config (makes for easier joins)
                    if(hardware_config::where('node_address', $itm->probe_id)->exists()){
                        hardware_config::where('node_address', $itm->probe_id)->update(['date_time' => $itm->date_time]);
                    }

                } else if(!empty($request->update_existing)){

                    // for reruns
                    $existing = node_data::where('message_id_2', $itm->message_id_2)->first();
                    if($existing){
                        // only update selected fields
                        $existing->update([
                            'ambient_temp' => $itm->ambient_temp,
                            'bv' => $itm->bv,
                            'bp' => $itm->bp
                        ]);
                    }

                }
            }
        }

        $ack_body = "<html><head><title>HTTP Push Ack</title></head><body>id={$site_id_full}</body></html>";

        return $html($ack_body);
    }

    public function campbellImport(Request $request)
    {
        Log::debug($request->all());
        return response('', 200);
    }
}
