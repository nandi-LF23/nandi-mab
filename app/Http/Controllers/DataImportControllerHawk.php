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
use App\Models\raw_data_dmt_hawk;
use App\Models\raw_data_catm;
use App\Models\raw_data_fieldwise;
use App\Models\raw_data_banner;
//use App\Mail\Message;
//use Mail;

class DataImportControllerHawk extends Controller
{
    // Multi-Probe Digital Matter Telematics (DMT) - Hawk Datalogger Import oemserver.com endpoint function
    public function dmtImportHawk(Request $request, $imei)
    {
        set_time_limit(0);

        Log::debug('dmtImportHawk imei:'.$imei);
        
        Log::debug('dmtImportHawk print_r'. serialize(print_r($request->all())));

       // return print_r($request->all());
        
        // DISCARD INCOMPLETE REQUESTS
        if(empty($imei)) return response()->json([], 200);
        $data = $request->all();
        Log::debug('dmtImportHawk data request success.');
        //Log::info(print_r($data, true));
        //if(empty($data['Records']) || empty($data['ProdId']) || $data['ProdId'] != 100) { return response()->json([], 200); }
        Log::debug('dmtImportHawk chcks success');
        Log::debug('dmtImportHawk');
        // SAVE RAW DATA
        if(empty($request->no_raw_save)){
            $json = json_encode($data); $raw = new raw_data_dmt_hawk(); $raw->device_id = $imei; $raw->device_data = $json; $raw->save();
            if(strpos($_SERVER['SERVER_NAME'], 'dev.myagbuddy.com') !== false){
                // CLONE TO SANDBOX
                /*Http::withBasicAuth('dmt@liquidfibre.com', '$jf843F$#ju23SE#2ju13D1ui!')
                ->withBody($json, 'application/json')
                ->post("https://sandbox.myagbuddy.com/api/dmtimport/{$imei}");*/
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
       // Log::debug('dmtImportHawk entering foreach');
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
            if( !empty($row['Fields'][0]['FType']) && $row['Fields'][0]['FType'] == 4
                && count($row['Fields']) < 11 ){ continue; }
                //Log::debug('dmtImportHawk past ftype check.'. $row['Fields'][0]['FType']);
            foreach($row['Fields'] as $field)
            {
                $type = (int) $field['FType'];

                if($type == 0){ // gps
                    if(!empty($field['Lat']) && !empty($field['Long'])){
                        $latt = (float) $field['Lat']; $lng = (float) $field['Long'];
                        if(!empty($m_items) && is_array($m_items) && count($m_items) > 0){
                            foreach($m_items as &$m){
                                if(is_object($m)) {
                                    $m->latt = $latt; $m->lng = $lng;
                                }
                            }
                        }
                        if(!empty($n_items) && is_array($n_items) && count($n_items) > 0){
                            foreach($n_items as &$n){
                                if(is_object($n)) {
                                    $n->latt = $latt; $n->lng = $lng;
                                }
                            }
                        }
                    }
                }

                if($type == 4){ // sdi
                    $probe_address = (int) chr($field['DevAddr']);
                    $node_address  = trim($imei) . '-' . $probe_address;

                    $ss = $parse_aquacheck_string($field['DevId']);
                    $vendor = $ss['ven']; $firmware_ver = $ss['ver']; $probe_serial = $ss['ser'];

                    if($model = $array_find($field['DevId'], $moisture_probe_models)){
                        $is_moisture_data = true; // Detect Soil Moisture Probes
                    } else if($model = $array_find($field['DevId'], $nutrient_probe_models)){
                        $is_nutrient_data = true; // Detect Nutrient Probes
                    }
                    if($model){ $vendor_model_fw = "{$vendor}.{$model}.{$firmware_ver}"; }
                }

                if($type == 5){ // measurements
                    $count = count($field['Ms']);
                    if($count > 0){
                        $mt = (int) $field['MType'];
                        if($is_moisture_data){ // SMs / Temps
                            if($mt == 0){ // M0 == SM
                                $total = 0;
                                for($s = 1; $s <= $count; $s++){ $val = $field['Ms'][$s-1] / 1000; $total += $val; $m_values["sm{$s}"] = $val; }
                                $m_values['accumulative'] = $total; $m_values['average'] = $total / $count;
                            } else if($mt == 1){ // M1 == Temp
                                for($t = 1; $t <= $count; $t++){ $t_values["t{$t}"] = $field['Ms'][$t-1] / 1000; }
                            }
                        } else if($is_nutrient_data){
                            for($n = 1; $n <= $count; $n++){ $n_values["M" . $mt . "_" . $n] = $field['Ms'][$n-1] / 1000; }
                        }
                    }
                }

                if($type == 6){ // analog
                    $bv   = !empty($field["AnalogueData"]['1']) ? (   (float) $field["AnalogueData"]['1'] )          : 0; // Batt.Volts.
                    $temp = !empty($field["AnalogueData"]['3']) ? ( ( (float) $field["AnalogueData"]['3'] ) / 100  ) : 0; // Ambient Temperature (Deg C * 100)
                 //   $bp   = !empty($field["AnalogueData"]['1']) ? ( ( 3300 - (float) $field["AnalogueData"]['1'] ) / 4100  )*100 : 0; // Batt.Percentage.
                    // backfill

                    

                    /*
                    Dead level= 3.3v
Full Level=4.1v
*/
$range = 4100 - 3300;
        $delta = $bv - 3300;
        if ($delta <= 0) $delta = 0;
        $level = ($delta / $range) * 100;
        $bp = $level < 0 ? 0 : ($level > 100 ? 100 : $level);

                    if(!empty($m_items) && is_array($m_items) && count($m_items) > 0){
                        foreach($m_items as &$m){
                            if(is_object($m)){
                                if($bv){ $m->bv = $bv; }
                                if($temp){ $m->ambient_temp = $temp; }
                                if($bp){ $m->bp = $bp; }
                            }
                        }
                    }
                    if(!empty($n_items) && is_array($n_items) && count($n_items) > 0){
                        foreach($n_items as &$n){
                            if(is_object($n)){
                                if($bv){ $n->bv = $bv; }
                                if($temp){ $n->ambient_temp = $temp; }
                                if($bp){ $n->bp = $bp; }
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

                $item->latt = $item->latt ?: $latt; $item->lng = $item->lng ?: $lng;

                $item->ambient_temp = $item->ambient_temp ?: $temp;

                $item->message_id_1 = "dmt"; $item->message_id_2 = "dmt_{$node_address}_{$date_sampled}";

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

                $item->latt = $item->latt ?: $latt;
                $item->lng = $item->lng ?: $lng;
                
                $item->ambient_temp = $item->ambient_temp ?: $temp;

                $item->message_id = 'dmt_'.$node_address . '_' . $date_sampled;

                $n_items[] = $item;

                $hw_items[$node_address] = [ 'latitude'  => $latt, 'longitude' => $lng, 'date_time' => $date_sampled ];
            

                Log::info(print_r($item));
                $item->save();

            }

        } // end for Records

        if($m_items){
            foreach($m_items as &$m_item){
                if(!node_data::where('message_id_2', $m_item->message_id_2)->exists()){
                    $m_item->save();
                } else if(!empty($request->update_existing)){
                    $existing = node_data::where('message_id_2', $m_item->message_id_2)->first();
                    if($existing){
                        $existing->update([
                            'ambient_temp' => $m_item->ambient_temp,
                            'bv' => $m_item->bv,
                            'bp' => $m_item->bp
                        ]);
                    }
                }
            }
        }
        if($n_items){
            foreach($n_items as &$n_item){
                
                        $n_item->save();
               
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


}
