<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Models\node_data;
use App\Models\nutri_data;
use App\Models\hardware_config;
use App\Models\cultivars_management;
use App\Models\fields;
//use App\Mail\Message;
//use Mail;

use Shapefile\Shapefile;
use Shapefile\ShapefileException;
use Shapefile\ShapefileReader;

use ZanySoft\Zip\Zip;

use TorMorten\Eventy\Facades\Events as Eventy;

class FileImportController extends Controller
{
    public function import(Request $request)
    {
        // expand on this (really check if a file is what it says it is, check Mimetype/check extension/etc)
        $types_formats = [
            'csv' => ['node_import', 'aquacheck', 'simple_t', 'partial_n'],
            'gis' => ['zip']
        ];

        $date_formats = [
            'df1' => 'Y-m-d H:i:s',
            'df2' => 'Y/m/d H:i:s',
            'df3' => 'Y-m-d H:i',
            'df4' => 'Y/m/d H:i'
        ];

        $acc = Auth::user();
        $path = '';

        if(!$acc->is_admin){
            // permission check
            $grants = $acc->requestAccess(['Node Config' => ['p' => ['Import'] ] ]);
            if(empty($grants['Node Config']['Import']['C'])){
                return response()->json(['status' => 'access_denied'], 403);
            }
        }

        // UPLOAD PHASE
        if($request->hasFile('filedata') && $request->has(['type', 'format'])){

            $file = $request->file('filedata');

            // UPLOAD ERROR
            if(!$file->isValid()){
                return response()->json(['status' => 'upload_error' ]);
            }

            // VALIDATION
            if(empty($types_formats[$request->type]) && empty($types_formats[$request->type][$request->format])){
                File::delete($file->getRealPath());
                return response()->json(['status' => 'unsupported_file' ]);
            }

            // CREATE HASH FILE
            $hash = hash('sha256', $file->getClientOriginalName());
            $path = public_path('uploads');
            $file->move($path, $hash);

            // CSV TYPE
            if($request->type == 'csv'){

                // COUNT ROWS
                $total = 0;
                $handle = fopen("$path/$hash", "r");
                while ($data = fgetcsv($handle)){ $total++; }
                fclose($handle);

                return response()->json([
                    'status' => 'file_uploaded',
                    'type'   => $request->type,
                    'format' => $request->format,
                    'ticket' => $hash,

                    'offset' => 0,
                    'total'  => $total
                ]);

            } else if($request->type == 'gis'){

                return response()->json([
                    'status' => 'file_uploaded',
                    'type'   => $request->type,
                    'format' => $request->format,
                    'ticket' => $hash
                ]);

            }

        // PROCESSING PHASE
        } else if($request->has(['type', 'format'])){ 

            // CSV BATCH DATA IMPORT
            if($request->type == 'csv') {

                if($request->has(['ticket', 'offset', 'total', 'delimiter'])){
                    
                    $filepath = public_path('uploads') . "/{$request->ticket}";
                    $dryrun = $request->has('dryrun') ? $request->dryrun : 'false';
                    $delimiter = $request->delimiter ?: ',';

                    // FILE FORMAT
                    $format = $request->format;

                    // TIMEZONE
                    $timezone = null;
                    if(!empty($request->timezone)){
                        $tz_idx = $request->timezone;
                        $timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
                        $timezone = $timezones[$tz_idx];
                    }

                    // DATE FORMAT (CURRENTLY ONLY FOR PARTIAL NUTRIENTS (PARTIAL_N))
                    $date_format = !empty($request->date_format) ? $request->date_format : null;

                    if($format == 'partial_n' && !empty($date_format)){
                        if(!in_array($date_format, $date_formats)){
                            Log::debug("Invalid date format: {$request->date_format}");
                            return response()->json(['status' => 'date_format_error']);
                        }
                        $date_format = $date_formats[$request->date_format];
                    }

                    // AQUACHECK CSV IMPORT CONTINUANCE
                    if($format == 'aquacheck'){
                        $result = $this->importAquacheckCSV($filepath, $request->offset, $request->total, $dryrun, $timezone, $delimiter);
                    // DISNEY/TUCOR
                    } else if($format == 'simple_t'){
                        $result = $this->importSimpleCSV($filepath, $request->offset, $request->total, $dryrun, $timezone, $delimiter);
                    // NUTRIENT CSV IMPORT (M3, M4, M5, M6)
                    } else if($format == 'partial_n'){
                        $result = $this->importPartialNutrientsCSV($filepath, $request->offset, $request->total, $dryrun, $timezone, $date_format, $delimiter);
                    // NODE BULK IMPORT CSV
                    } else if($format == 'node_import'){
                        $result = $this->importNodeBulkCSV($filepath, $request->offset, $request->total, $dryrun, $delimiter);
                    }

                    return response()->json([
                        'status' => $result['status'],
                        'type'   => $request->type,
                        'format' => $request->format,
                        'offset' => $result['offset'],
                        'total'  => $result['total'],
                        'stats'  => $result['stats']
                    ]);

                } else { return response()->json(['status' => 'general_error', 'extra' => 'control_var_missing']); }

            // GEO SHAPEFILE IMPORTS
            } else if($request->type == 'gis'){

                if($request->has(['ticket', 'field_id'])){

                    $filepath = public_path('uploads') . "/{$request->ticket}";
                    $field_id = $request->field_id;
                    $format = $request->format;

                    // .shp file (WKT/EWKT)
                    if($format == 'zip'){
                        $result = $this->importShapefile($filepath, $field_id);
                    }

                    return response()->json([
                        'status' => $result['status'],
                        'output' => $result['output'],
                        'type'   => $request->type,
                        'format' => $request->format
                    ]);

                } else { return response()->json(['status' => 'general_error', 'extra' => 'control_var_missing']); }

            } else { return response()->json(['status' => 'general_error', 'extra' => 'type_error']); }

        } else { return response()->json(['status' => 'general_error', 'extra' => 'format_missing']); }
    }

    // TODO: Finish import report mail
    // public function sendStatsMail(Request $request){

    //     $user = Auth::user();
   
    //     Mail::to($user->email)->send(new Message([
    //         'title' => 'CSV Import Stats',
    //         'content' => 'Test Message'
    //     ]));

    //     return response()->json(['ok'], 200);
    // }

    public function importAquacheckCSV($filepath, $offset, $total, $dryrun, $timezone, $delimiter)
    {
        $result = [
            'status' => 'continue',
            'offset' => $offset,
            'total'  => $total,
            'stats'  => [
                'cols' => [], // Column count errors
                'dt'   => [], // Erroneous Dates
                'sm'   => [], // Erroneous Soil Moistures
                'tmp'  => [], // Erroneous Temperatures
                'dup'  => [], // Duplicates
                'orp'  => [], // Orphans (Rows with no parent node)
                'node' => [], // Missing Node IDs
                'ins'  => []  // Successful inserts
            ]
        ];
        $line_number = 1;
        $skip = 0;
        $batch = 500;
        $cursor = 0;
        $probe_exists = false;
        $prev_probe_id = null;
        $prev_probe_exists = false;

        // OPEN AND CONFIRM FILE EXISTS
        $handle = fopen($filepath, "r");
        if(!$handle){ $result['status'] = 'general_error';  return $result; }

        // CSV "SEEK"
        while ($skip < $offset){ $data = fgetcsv($handle, 0, $delimiter); $skip++; $line_number++; }

        // PROCESS BATCH
        while(!feof($handle) && $cursor < $batch){

            $row = fgetcsv($handle, 0, $delimiter);

            // COLUMN COUNT CHECK: confirm at least 7 columns exist

            if($row === false || count($row) < 7){
                $result['stats']['cols'][] = $line_number++; $cursor++; continue;
            }

            // 1 @LOAD, ignored

            // 2 Device Serial
            $device_serial = trim($row[1]);

            // 3 Probe Serial
            $probe_serial = trim($row[2]);

            $probe_id = "{$device_serial}-{$probe_serial}";

            // CHECK IF PROBE EXISTS (BUT ALLOW ORPHANS)
            if($prev_probe_id != $probe_id){
                $probe_exists = hardware_config::where('node_address', $probe_id)->exists();
                if(!$probe_exists){
                    $result['stats']['node'][$probe_id] = true;
                    $result['stats']['orp'][] = $line_number++;
                }
            } else if(!$prev_probe_exists){
                $result['stats']['orp'][] = $line_number++;
            }

            // 4 Timestamp
            $row[3] = trim($row[3]);
            if(strlen($row[3]) !== 10 || empty($row[3])){
                $result['stats']['dt'][] = $line_number++;
                $cursor++;
                continue;
            }

            $yy = substr($row[3], 0, 2); // Year
            $mm = substr($row[3], 2, 2); // Month
            $dd = substr($row[3], 4, 2); // Day
            $hh = substr($row[3], 6, 2); // Hour
            $ii = substr($row[3], 8, 2); // Minute

            // MySQL DateTime format
            $date_time = "20{$yy}-{$mm}-{$dd} {$hh}:{$ii}:00"; // 19 chars
            try {
                $dt = \DateTime::createFromFormat(
                    'Y-m-d H:i:s',
                    $date_time,
                    new \DateTimeZone($timezone) /* from */
                );
                $dt->setTimeZone(new \DateTimeZone('UTC')); /* to */
                $date_time = $dt->format('Y-m-d H:i:s');
            } catch (\Exception $e){
                $result['stats']['dt'][] = $line_number++; $cursor++; continue;
            }

            // 5 Soil Moisture Readings
            $row[4] = trim($row[4]);
            $avg_count = 0;
            $sm1 = $sm2 = $sm3 = $sm4 = $sm5 = $sm6 = 0;
            if(!empty($row[4]) && ctype_xdigit($row[4]))
            {
                $v1 = hexdec(substr($row[4], 0, 4));
                if ($v1 == 0000 || $sm1 = 0) {
                    $sm1 = 0;
                }
                else if ($v1 > 0) {
                    $sm1 = number_format(100 - ($v1 / 327.67), 2, '.', '');
                    $avg_count++;
                }

                $v2 = hexdec(substr($row[4], 4, 4));
                if ($v2 == 0000 || $sm2 = 0) {
                    $sm2 = 0;
                }
                else if ($v2 > 0) {
                    $sm2= number_format(100 - ($v2 / 327.67), 2, '.', '');
                    $avg_count++;
                }

                $v3 = hexdec(substr($row[4], 8, 4));
                if ($v3 == 0000 || $sm3 = 0) {
                    $sm3 = 0;
                }
                else if ($v3 > 0) {
                    $sm3 = number_format(100 - ($v3 / 327.67), 2, '.', '');
                    $avg_count++;
                }

                $v4 = hexdec(substr($row[4], 12, 4));
                if ($v4 == 0000 || $sm4 = 0) {
                    $sm4 = 0;
                }
                else if ($v4 > 0) {
                    $sm4 = number_format(100 - ($v4 / 327.67), 2, '.', '');
                    $avg_count++;
                }

                $v5 = hexdec(substr($row[4], 16, 4));
                if ($v5 == 0000 || $sm5 = 0) {
                    $sm5 = 0;
                }
                else if ($v5 > 0) {
                    $sm5 = number_format(100 - ($v5 / 327.67), 2, '.', '');
                    $avg_count++;
                }

                $v6 = hexdec(substr($row[4], 20, 4));
                if ($v6 == 0000 || $sm6 = 0) {
                    $sm6 = 0;
                }
                else if ($v6 > 0) {
                    $sm6 = number_format(100 - ($v6 / 327.67), 2, '.', '');
                    $avg_count++;
                }

                // $sm1 = number_format(100 - (hexdec(substr($row[4], 0,  4)) / 327.67), 2, '.', ''); // 2 bytes
                // $sm2 = number_format(100 - (hexdec(substr($row[4], 4,  4)) / 327.67), 2, '.', ''); // 2 bytes
                // $sm3 = number_format(100 - (hexdec(substr($row[4], 8,  4)) / 327.67), 2, '.', ''); // 2 bytes
                // $sm4 = number_format(100 - (hexdec(substr($row[4], 12, 4)) / 327.67), 2, '.', ''); // 2 bytes
                // $sm5 = number_format(100 - (hexdec(substr($row[4], 16, 4)) / 327.67), 2, '.', ''); // 2 bytes
                // $sm6 = number_format(100 - (hexdec(substr($row[4], 20, 4)) / 327.67), 2, '.', ''); // 2 bytes

            } else { $result['stats']['sm'][] = $line_number++; $cursor++; continue; }

            // 6 Soil Temperature Readings (Celcius)
            $row[5] = trim($row[5]);
            $t1 = $t2 = $t3 = $t4 = $t5 = $t6 = 0;
            if(!empty($row[5]) && ctype_xdigit($row[5]))
            {
                $t1 = number_format(hexdec(substr($row[5], 0,  2)) / 5, 2, '.', ''); // 1 byte
                $t2 = number_format(hexdec(substr($row[5], 2,  2)) / 5, 2, '.', ''); // 1 byte
                $t3 = number_format(hexdec(substr($row[5], 4,  2)) / 5, 2, '.', ''); // 1 byte
                $t4 = number_format(hexdec(substr($row[5], 6,  2)) / 5, 2, '.', ''); // 1 byte
                $t5 = number_format(hexdec(substr($row[5], 8,  2)) / 5, 2, '.', ''); // 1 byte
                $t6 = number_format(hexdec(substr($row[5], 10, 2)) / 5, 2, '.', ''); // 1 byte

            } else { $result['stats']['tmp'][] = $line_number++; $cursor++; continue; }

            // 7 Aux Data
            $row[6] = trim($row[6]);
            $ps   = hexdec(substr($row[6], 0,  4)); // 2 bytes, Plant sense (NOT CURRENTLY USED BUT ADDED FOR COMPLETENESS)
            $rg   = hexdec(substr($row[6], 4,  4)); // 2 bytes, Rain Gauge
            $rssi = hexdec(substr($row[6], 8,  2)); // 1 byte,  Signal Strength (NOT CURRENTLY USED BUT ADDED FOR COMPLETENESS)
            $bv   = hexdec(substr($row[6], 10, 2)); // 1 byte,  Batt. Voltage
            $bp   = 50; // Batt Percentage (50% for now, will clarify with Brad on Percentage Calculation)
            $rs   = substr($row[6], 12, 2);         // 1 byte,  Reserved (#)

            // CALCULATED VALUES
            $average = number_format(($sm1 + $sm2 + $sm3 + $sm4 + $sm5 + $sm6) / $avg_count, 2, '.', '');
            $accumulative = number_format($sm1 + $sm2 + $sm3 + $sm4 + $sm5 + $sm6, 2, '.', '');

            // message_id_1 serves as a 'data source' field
            $message_id_1 = "csv"; 
            // probe id can't be longer than 21 chars
            $message_id_2 = "csv_{$probe_id}_{$date_time}";

            // DUPLICATE CHECK
            $row_exists = node_data::where('message_id_2', $message_id_2)->exists();
            if(!$row_exists){
                $record = [
                    'probe_id' => $probe_id,
                    'date_time' => $date_time,

                    'average' => $average,
                    'accumulative' => $accumulative,

                    'sm1' => $sm1, 'sm2' => $sm2, 'sm3' => $sm3, 'sm4' => $sm4, 'sm5'  => $sm5,
                    'sm6' => $sm6, 'sm7' => 0, 'sm8' => 0, 'sm9' => 0, 'sm10' => 0,
                    'sm11' => 0, 'sm12' => 0, 'sm13' => 0,'sm14' => 0,'sm15' => 0,

                    't1' => $t1, 't2' => $t2, 't3' => $t3, 't4' => $t4, 't5' => $t5,
                    't6' => $t6, 't7' => 0, 't8' => 0, 't9' => 0, 't10' => 0,
                    't11' => 0, 't12' => 0,'t13' => 0,'t14' => 0,'t15' => 0,

                    'rg' => $rg, 'bv' => $bv, 'bp' => $bp,
                    'latt' => 0,'lng' => 0,

                    'ambient_temp' => 0,

                    'message_id_1' => $message_id_1,
                    'message_id_2' => $message_id_2
                ];
                
                // INSERT INTO DATABASE
                if($dryrun == 'false'){
                    $saved = node_data::create($record);
                    if($saved){
                        $result['stats']['ins'][] = $line_number;
                    }
                }

            } else {
                $result['stats']['dup'][] = $line_number++; 
                $cursor++; 
                continue; 
            }

            // UAG probes
            $uag_probes = [
                '60528-21647',
                '60528-21505',
                '60528-21503',
                '60528-21502',
                '60528-21501',
                '60528-21487',
                '60528-21486',
                '60528-21485',
                '60528-21475',
                '60528-21473',
                '60528-21472',
                '60528-21471'
            ];

            if (str_contains($probe_id, '60528')) {
                foreach ($uag_probes as $uag_probe) {
                    DB::statement("UPDATE hardware_config HC
                    INNER JOIN node_data ND 
                    ON HC.node_address = ND.probe_id
                    SET
                    HC.date_time = (SELECT date_time FROM node_data 
                    WHERE probe_id = '$uag_probe' ORDER BY date_time DESC LIMIT 1)
                    WHERE HC.node_address = '$uag_probe'");
                }
            }

            $prev_probe_id = $probe_id;
            $prev_probe_exists = $probe_exists;
            $line_number++;
            $cursor++;
        }

        // UPDATE PROGRESS
        $result['status'] = 'import_continue';
        $result['offset'] = $offset + $cursor;
        $result['total'] = $total;
        $result['stats']['node'] = array_keys($result['stats']['node']);

        // COMPLETE
        if($offset >= $total){
            $result['status'] = 'import_complete';
            unlink($filepath);
        }

        return $result;
    }

    // <probe-id>, <date>, <sm1>, ... ,<sm15>, <t1>, ... <t15>
    public function importSimpleCSV($filepath, $offset, $total, $dryrun, $timezone, $delimiter)
    {
        $result = [
            'status' => 'continue',
            'offset' => $offset,
            'total'  => $total,
            'stats'  => [
                'cols' => [], // Column count errors
                'dt'   => [], // Erroneous Dates
                'sm'   => [], // Erroneous Soil Moistures
                'tmp'  => [], // Erroneous Temperatures
                'dup'  => [], // Duplicates
                'orp'  => [], // Orphans (Rows with no parent node)
                'node' => [], // Missing Node IDs
                'ins'  => []  // Successful inserts
            ]
        ];

        $line_number = 1;
        $skip = 0;
        $batch = 500;
        $cursor = 0;

        // OPEN AND CONFIRM FILE EXISTS
        $handle = fopen($filepath, "r");
        if(!$handle){ $result['status'] = 'general_error';  return $result; }

        // NO HEADER SKIPS NECESSARY 
        // if($offset == 0){
        //     $offset = x;
        // }

        // CSV "SEEK"
        while ($skip < $offset){ $data = fgetcsv($handle, 0, $delimiter); $skip++; $line_number++; }

        // PROCESS BATCH
        while(!feof($handle) && $cursor < $batch){

            $row = fgetcsv($handle, 0, $delimiter);

            // COLUMN COUNT CHECK: confirm at least 32 columns exist
            // <probe_id><date><sm1>...<sm15><t1><t15>
            if($row === false || count($row) < 32){
                $result['stats']['cols'][] = $line_number++; $cursor++; continue;
            }

            $probe_id = trim($row[0]);

            $date_time = trim($row[1]);
            try {
                $dt = \DateTime::createFromFormat(
                    'Y/n/j G:i',
                    $date_time,
                    new \DateTimeZone($timezone) /* from */
                );
                if(!$dt){
                    throw new \Exception("Invalid date format");
                }
                $dt->setTimeZone(new \DateTimeZone('UTC')); /* to */
                $date_time = $dt->format('Y-m-d H:i:s');
            } catch (\Exception $e){
                $result['stats']['dt'][] = $line_number++; $cursor++;
                continue;
            }

            $average = 0;
            $accumulative = 0;
            $sensor_count = 0;
            
            $sm1  = (float) trim($row[2]);
            if($sm1) $sensor_count++;
            $sm2  = (float) trim($row[3]);
            if($sm2) $sensor_count++;
            $sm3  = (float) trim($row[4]);
            if($sm3) $sensor_count++;
            $sm4  = (float) trim($row[5]);
            if($sm4) $sensor_count++;
            $sm5  = (float) trim($row[6]);
            if($sm5) $sensor_count++;

            $sm6  = (float) trim($row[7]);
            if($sm6) $sensor_count++;
            $sm7  = (float) trim($row[8]);
            if($sm7) $sensor_count++;
            $sm8  = (float) trim($row[9]);
            if($sm8) $sensor_count++;
            $sm9  = (float) trim($row[10]);
            if($sm9) $sensor_count++;
            $sm10 = (float) trim($row[11]);
            if($sm10) $sensor_count++;

            $sm11 = (float) trim($row[12]);
            if($sm11) $sensor_count++;
            $sm12 = (float) trim($row[13]);
            if($sm12) $sensor_count++;
            $sm13 = (float) trim($row[14]);
            if($sm13) $sensor_count++;
            $sm14 = (float) trim($row[15]);
            if($sm14) $sensor_count++;
            $sm15 = (float) trim($row[16]);
            if($sm15) $sensor_count++;

            $accumulative = $sm1 + $sm2 + $sm3 + $sm4 + $sm5 + $sm6 + $sm7 + $sm8 + $sm9 + $sm10 + $sm11 + $sm12 + $sm13 + $sm14 + $sm15;
            if($sensor_count){
                $average = $accumulative / $sensor_count;
            }

            $t1  = (float) trim($row[17]);
            $t2  = (float) trim($row[18]);
            $t3  = (float) trim($row[19]);
            $t4  = (float) trim($row[20]);
            $t5  = (float) trim($row[21]);

            $t6  = (float) trim($row[22]);
            $t7  = (float) trim($row[23]);
            $t8  = (float) trim($row[24]);
            $t9  = (float) trim($row[25]);
            $t10 = (float) trim($row[26]);

            $t11 = (float) trim($row[27]);
            $t12 = (float) trim($row[28]);
            $t13 = (float) trim($row[29]);
            $t14 = (float) trim($row[30]);
            $t15 = (float) trim($row[31]);

            $message_id_1 = "csv_tucor_simple";
            $message_id_2 = "csv_{$probe_id}_{$date_time}"; 

            $record = [
                'probe_id' => $probe_id,
                'date_time' => $date_time,

                'average' => $average,
                'accumulative' => $accumulative,

                'sm1'  => $sm1,  'sm2'  => $sm2,  'sm3'  => $sm3,  'sm4'  => $sm4,  'sm5'  => $sm5,
                'sm6'  => $sm6,  'sm7'  => $sm7,  'sm8'  => $sm8,  'sm9'  => $sm9,  'sm10' => $sm10,
                'sm11' => $sm11, 'sm12' => $sm12, 'sm13' => $sm13, 'sm14' => $sm14, 'sm15' => $sm15,

                't1'  => $t1,  't2'  => $t2,  't3'  => $t3,  't4'  => $t4,  't5'  => $t5,
                't6'  => $t6,  't7'  => $t7,  't8'  => $t8,  't9'  => $t9,  't10' => $t10,
                't11' => $t11, 't12' => $t12, 't13' => $t13, 't14' => $t14, 't15' => $t15,

                'rg' => 0, 'bv' => 4000, 'bp' => 50,
                'latt' => 0, 'lng' => 0,

                'ambient_temp' => 0,

                'message_id_1' => $message_id_1,
                'message_id_2' => $message_id_2
            ];

            // DUPLICATE CHECK
            $row_exists = node_data::where('message_id_2', $message_id_2)->exists();
            if(!$row_exists){
                // INSERT INTO DATABASE
                if($dryrun == 'false'){
                    $saved = node_data::create($record);
                    if($saved){
                        $result['stats']['ins'][] = $line_number;
                    }
                }

            } else { $result['stats']['dup'][] = $line_number; }

            $line_number++;
            $cursor++;
        }

        // UPDATE PROGRESS
        $result['status'] = 'import_continue';
        $result['offset'] = $offset + $cursor;
        $result['total'] = $total;
        $result['stats']['node'] = array_keys($result['stats']['node']);

        // COMPLETE
        if($offset >= $total){
            $result['status'] = 'import_complete';
            unlink($filepath);
        }

        return $result;
    }

    // Partial Nutrients Import: <node_address>,<date_time>,<M3_1>,<M4_1>,<M5_1>,<M6_1>
    public function importPartialNutrientsCSV($filepath, $offset, $total, $dryrun, $timezone, $date_format, $delimiter)
    {
        $result = [
            'status' => 'continue',
            'offset' => $offset,
            'total'  => $total,
            'stats'  => [
                'cols' => [], // Column count errors
                'dt'   => [], // Erroneous Dates
                'sm'   => [], // Erroneous Soil Moistures
                'tmp'  => [], // Erroneous Temperatures
                'dup'  => [], // Duplicates
                'orp'  => [], // Orphans (Rows with no parent node)
                'node' => [], // Missing Node IDs
                'ins'  => []  // Successful inserts
            ]
        ];

        $line_number = 1;
        $skip = 0;
        $batch = 500;
        $cursor = 0;

        // OPEN AND CONFIRM FILE EXISTS
        $handle = fopen($filepath, "r");
        if(!$handle){ $result['status'] = 'general_error';  return $result; }

        // NO HEADER SKIPS NECESSARY 
        // if($offset == 0){
        //     $offset = x;
        // }

        // CSV "SEEK"
        while ($skip < $offset){ $data = fgetcsv($handle, 0, $delimiter); $skip++; $line_number++; }

        // PROCESS BATCH
        while(!feof($handle) && $cursor < $batch){

            $row = fgetcsv($handle, 0, $delimiter);

            // COLUMN COUNT CHECK: confirm at least 6 columns exist

            if($row === false || count($row) < 6){
                $result['stats']['cols'][] = $line_number++; $cursor++; continue;
            }

            // 1: node_address
            $node_address = trim($row[0]);
            // 2: date_time (date_reported = date_sampled in this CSV import)
            $date_time = trim($row[1]);

            try {
                $dt = \DateTime::createFromFormat(
                    $date_format,
                    $date_time,
                    new \DateTimeZone($timezone) /* from */
                );
                $dt->setTimeZone(new \DateTimeZone('UTC')); /* to */
                $date_time = $dt->format($date_format);
            } catch (\Exception $e){
                $result['stats']['dt'][] = $line_number++; $cursor++;
                continue;
            }

            // 3,4,5,6 values

            for($i = 3; $i < 7; $i++){

                $message_id = "csv_{$node_address}_M{$i}_{$date_time}";

                $record = [
                    'node_address'    => $node_address,
                    'probe_serial'    => '00000000',
                    'vendor_model_fw' => 'CSV',
                    'version'         => 'CSV',
                    'date_reported'   => $date_time,
                    'date_sampled'    => $date_time,

                    'M3_1'      => trim($row[2]),
                    'M4_1'      => trim($row[3]),
                    'M5_1'      => trim($row[4]),
                    'M6_1'      => trim($row[5]),                                        

                    'message_id'      => $message_id
                ];

                // DUPLICATE CHECK
                if(!nutri_data::where('message_id', $message_id)->exists()){
                    // INSERT INTO DATABASE
                    if($dryrun == 'false'){
                        $saved = nutri_data::create($record);
                        if($saved){
                            $result['stats']['ins'][] = $line_number;
                        }
                    }

                } else { $result['stats']['dup'][] = $line_number; }

            }

            $line_number++;
            $cursor++;
        }

        // UPDATE PROGRESS
        $result['status'] = 'import_continue';
        $result['offset'] = $offset + $cursor;
        $result['total']  = $total;
        $result['stats']['node'] = array_keys($result['stats']['node']);

        // COMPLETE
        if($offset >= $total){
            $result['status'] = 'import_complete';
            unlink($filepath);
        }

        return $result;
    }

    public function importShapefile($filepath, $field_id)
    {
        $output = '';

        if(!Zip::check($filepath)){ return [ 'status' => 'invalid_or_missing_file' ]; }

        try {

            $zip_folder = "{$filepath}_dir";
            $extensions = [];
            $selectedFiles = [];

            $zip = Zip::open($filepath);
            $zip->extract($zip_folder);

            if( !file_exists($zip_folder) ){ return [ 'status' => 'file_extraction_error' ]; }

            $files = \File::allFiles($zip_folder);
            
            foreach ($files as $key => $file) {
                $extensions[] = $ext = $file->getExtension();
                $selectedFiles[$ext] = $file->getRealPath();
            }

            // ensure required files are present
            if( !in_array('shp', $extensions) ){ return [ 'status' => 'file_missing', 'file' => 'shp' ]; }
            if( !in_array('shx', $extensions) ){ return [ 'status' => 'file_missing', 'file' => 'shx' ]; }
            if( !in_array('dbf', $extensions) ){ return [ 'status' => 'file_missing', 'file' => 'dbf' ]; }

            $counts = array_count_values($extensions);

            // ensure no more than one type of each file is present
            if($counts['shp'] > 1){ return [ 'status' => 'file_multiple', 'file' => 'shp' ]; }
            if($counts['shx'] > 1){ return [ 'status' => 'file_multiple', 'file' => 'shx' ]; }
            if($counts['dbf'] > 1){ return [ 'status' => 'file_multiple', 'file' => 'dbf' ]; }

            $shapefile = new ShapefileReader($selectedFiles['shp'], [
                Shapefile::OPTION_SUPPRESS_M => true,
                Shapefile::OPTION_SUPPRESS_Z => true,
                Shapefile::OPTION_DBF_IGNORED_FIELDS => ['DATASET', 'OBJ__ID'],
            ]);

            $zone_data = [];
            $rows = [];

            while($geometry = $shapefile->fetchRecord()){
                if($geometry->isDeleted()){ continue; }
                $row = $geometry->getDataArray();
                $zone_data[] = [
                    'geom' => $geometry->getGeoJSON(false /* No BBox */, false /* No Feature Wrap */),
                    'data' => $row /* "properties */
                ];
                $rows[] = $row;
            }

            if($zone_data){

                $json_zone_data = json_encode($zone_data);

                $field = fields::where('id', $field_id)->first();
                $node  = hardware_config::where('node_address', $field->node_id)->first();

                $prev_zones_hash = md5($field->zones);
                $curr_zones_hash = md5($json_zone_data);

                $info = [
                    'zones_changed' => $prev_zones_hash != $curr_zones_hash,
                    'integrations'  => json_decode($node->integration_opts, true) // integration options
                ];

                $field->zones = $json_zone_data;
                $field->save();

                if(file_exists($filepath)){ unlink($filepath); }
                if(file_exists($zip_folder)){ File::deleteDirectory($zip_folder); }

                Eventy::action('node_config.zones.import', $node, $field, $info);
            }

            $output .= json_encode($rows);

        } catch (ShapefileException $e){
            $output = 
                "ERROR:\n" . 
                "Error Type: " . $e->getErrorType() . "\n" . 
                "Message: "    . $e->getMessage()   . "\n" . 
                "Details: "    . $e->getDetails()   . "\n";
        } catch (\Exception $e){
            $output = 
                "ERROR:\n".
                "Message: " . $e->getMessage();
        }

        $result = [
            'status' => 'processing_complete',
            'output' => $output
        ];

        return $result;
    }

    // Bulk Node Record Import (hardware_config)
    public function importNodeBulkCSV($filepath, $offset, $total, $dryrun, $delimiter)
    {
        /* Permissions required:

            Node Config - Import
            <Node Type> - Add
        */

        $acc = Auth::user();

        $result = [
            'status' => 'continue',
            'offset' => $offset,
            'total'  => $total,
            'stats'  => [
                'cols'   => [], // Column count errors
                'dup'    => [], // Duplicates
                'inv_pe' => [], // Permission Error
                'inv_na' => [], // Invalid Node Address
                'inv_nt' => [], // Invalid Node Type
                'inv_dt' => [], // Invalid Sensor/Device Type
                'inv_et' => [], // Invalid Entity
                'inv_ef' => [], // Empty (Required) Field
                'ins'    => []  // Successful inserts
            ]
        ];

        $line_number = 1;
        $skip   = 0;
        $batch  = 10;
        $cursor = 0;

        // OPEN AND CONFIRM FILE EXISTS
        $handle = fopen($filepath, "r");
        if(!$handle){ $result['status'] = 'general_error';  return $result; }

        // Batch Number
        $batch_id = "csv_".date('Y_m_d_H_i_s')."_user_{$acc->id}"; 
        // Permissions
        $cc_ids_by_type = [];
        // Entity Name -> companies.id
        $entities = [];
        // Device Make -> harware_management.id
        $device_makes  = [];
        // Node Addresses (Used for additional duplicate detection in file)
        $node_addresses = [];

        if(!$acc->is_admin){
            $grants = $this->acc->requestAccess([
                'Node Config'   => ['p' => ['All'] ],
                'Soil Moisture' => ['p' => ['All'] ],
                'Nutrients'     => ['p' => ['All'] ],
                'Well Controls' => ['p' => ['All'] ],
                'Meters'        => ['p' => ['All'] ]
            ]);

            if(empty($grants['Node Config']['Import']['C'])){
                $result['status'] = 'perm_error';  return $result;
            }

            $cc_ids_by_type['Soil Moisture'] = !empty($grants['Soil Moisture']['Add']['C']) ? $grants['Soil Moisture']['Add']['C'] : [];
            $cc_ids_by_type['Nutrients']     = !empty($grants['Nutrients']['Add']['C'])     ? $grants['Nutrients']['Add']['C']     : [];
            $cc_ids_by_type['Well Controls'] = !empty($grants['Well Controls']['Add']['C']) ? $grants['Well Controls']['Add']['C'] : [];
            $cc_ids_by_type['Meters']        = !empty($grants['Meters']['Add']['C'])        ? $grants['Meters']['Add']['C']        : [];
        }

        // CSV "SEEK"
        while ($skip < $offset){ $data = fgetcsv($handle, 0, $delimiter); $skip++; $line_number++; }

        // PROCESS BATCH
        while(!feof($handle) && $cursor < $batch){

            $row = fgetcsv($handle, 0, $delimiter);

            //Log::debug(var_export($row, true));

            // HANDLE THIS SITUATION UNIQUELY (EMPTY ROW)
            if($row === false){ continue; }

            // COLUMN COUNT CHECK: confirm at least 6 columns exist
            // <node_address><node_type><entity_name><node_make><node_serial><device_make>[<device_serial>]
            if(count($row) < 6){
                $result['stats']['cols'][] = $line_number++; $cursor++; continue;
            }

            // Detect Duplicates in Database
            if(empty(trim($row[0])) || hardware_config::where('node_address', trim($row[0]))->exists()){
                $result['stats']['dup'][] = $line_number++; $cursor++; continue;
            }

            // CSV FIELDS

            // 1. Node Address      (REQUIRED)
            $node_address     = trim($row[0]);
            // 2. Node Type         (REQUIRED)
            $node_type        = trim($row[1]);
            // 3. Entity Name       (REQUIRED)
            $entity_name      = trim($row[2]);
            // 4. Node Make         (REQUIRED)
            $node_make        = trim($row[3]);
            // 5. Node Serial       (REQUIRED)
            $node_serial      = trim($row[4]);
            // 6. Device Make       (REQUIRED)
            $device_make      = trim($row[5]);
            // 7. Device Serial     (OPTIONAL)
            $device_serial    = !empty(trim($row[6])) ? trim($row[6]) : '';

            // Log Empty Required Field
            if(
                empty($node_address) || empty($node_type)   ||
                empty($entity_name)  || empty($node_make)   ||
                empty($node_serial)  || empty($device_make)
            ){
                $result['stats']['inv_ef'][] = $line_number++; $cursor++; continue;
            }

            // Detect Duplicates in File
            if($node_address && empty($node_addresses[$node_address])){
                $node_addresses[$node_address] = true;
            } else {
                $result['stats']['dup'][] = $line_number++; $cursor++; continue;
            }

            // CREATE NEW NODE RECORD
            $node = new hardware_config();

            // AUTO-GENERATED FIELDS
            // ---------------------

            // Commissioning Date
            $node->commissioning_date = date('Y-m-d');

            // Field Name 
            $field_name = "Imported {$node_address} Field";

            // CSV FIELDS
            // ----------

            // 1. Node Address
            if(!preg_match("/[a-zA-Z0-9]+\-[0-9]+$/", $node_address)){
                // Invalid Node Address
                $result['stats']['inv_na'][] = $line_number++; $cursor++; continue;
            }
            $node->node_address = $node_address;

            // 1.1 Probe Address
            $tmp = explode('-', $node_address);
            $node->probe_address = $tmp[1];

            // 2. Node Type
            if(!in_array($node_type, ['Soil Moisture', 'Nutrients', 'Wells', 'Water Meter'])){
                $result['stats']['inv_nt'][] = $line_number++; $cursor++; continue;
            }
            $node->node_type = $node_type;

            // 3. Entity
            if(empty($entities[$entity_name])){
                $company_id = DB::table('companies')
                ->where('company_name', $entity_name)
                ->value('id');
                if(!$company_id){ 
                    // Invalid Entity Name
                    $result['stats']['inv_et'][] = $line_number++; $cursor++; continue;
                }
                $entities[$entity_name] = $company_id;
            } else {
                $company_id = $entities[$entity_name];
            }

            // Permission Check
            if(!$acc->is_admin && (empty($cc_ids_by_type[$node_type]) || !in_array($company_id, $cc_ids_by_type[$node_type]))){
                $result['stats']['inv_pe'][] = $line_number++; $cursor++; continue;
            }

            $node->company_id = $company_id;

            // 4. Node Make
            $node->node_make = $node_make;

            // 5. Node Serial;
            $node->node_serial_number = $node_serial;

            // 6. Device Make
            if(empty($device_makes[$company_id][$device_make])){
                $device_id = DB::table('hardware_management')
                ->where('company_id', $company_id)
                ->where('device_make', $device_make)
                ->value('id');

                // Existence Check: Ensure Device Type exists
                if(!$device_id){ $result['stats']['inv_dt'][] = $line_number++; $cursor++; continue; }

                // Logical check: Ensure Device Type matches Node Type
                $device_type = DB::table("hardware_management")->where('id', $device_id)->value('device_type');

                // Skip if types dont match
                if($device_type != $node_type){
                    $result['stats']['inv_tm'][] = $line_number++; $cursor++; continue;
                }

                $device_makes[$company_id][$device_make] = $device_id;
            } else {
                $device_id = $device_makes[$company_id][$device_make];
            }

            // FK to hardware_management
            $node->hardware_management_id = $device_id;

            // 7. Probe Serial
            $node->device_serial_number = $device_serial;

            // Field Sub-Record
            // ----------------

            if($dryrun == 'false'){

                // see if existing field exists, use that
                $field = fields::where('node_id', $node_address)->where('company_id', $company_id)->first();
                if(!$field){
                    // otherwise, create new field (per node)
                    $field = new fields();
                }
                $field->company_id = $company_id;
                $field->field_name = $field_name;
                $field->node_id = $node_address;
                $field->eto = 0;

                // SM has default graph_type of ave (set in DB)
                if($node_type == 'Nutrients'){

                    // Set default graph type to PPM Avg
                    $field->graph_type = 'nutrient_ppm_avg';

                    // Assign default nutrient template
                    $nutrient_template_id = DB::table('nutrient_templates')
                    ->where('company_id', $company_id)
                    ->where('name', 'Default Template')
                    ->value('id');
                    if($nutrient_template_id){
                        $field->nutrient_template_id = $nutrient_template_id;
                    }
                } else if(in_array($node_type, ['Wells', 'Water Meter'])){
                    $field->graph_type = 'pulse';
                }

                $field->save();

                // Cultivars Management Sub-Record
                // -------------------------------

                // see if existing cultivars_management record exists, use that
                $cm = cultivars_management::where('field_id', $field->id)->first();
                if(!$cm){
                    // otherwise, create new cultivars_management (per node)
                    $cm = new cultivars_management();
                }
                $cm->field_id = $field->id;
                $cm->company_id = $company_id;
                $cm->NI = 1;
                $cm->NR = 1;

                $cm->save();

            }

            $node->import_batch = $batch_id;

            $saved = $dryrun == 'false' ? $node->save() : true;

            if($saved){
                $result['stats']['ins'][] = $line_number;
            }

            $line_number++;
            $cursor++;
        }


        // UPDATE PROGRESS
        $result['status'] = 'import_continue';
        $result['offset'] = $offset + $cursor;
        $result['total']  = $total;

        // COMPLETE
        if($offset >= $total){
            $result['status'] = 'import_complete';
            unlink($filepath);
        }

        return $result;
    }
}
