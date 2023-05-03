<?php
$output = null;
// Abort if system build is in progress
clearstatcache(true, 'mab_build_in_progress');
if (file_exists('mab_build_in_progress')) {
    return 1;
}
// Guzzle & others
require 'vendor/autoload.php';
// DB Library
require_once('db.php');
$db = new db();

// Authenticate with Ingenu
$client = new GuzzleHttp\Client(['verify' => false]);
$res = $client->request('POST', 'https://glds.ingenu.com/config/v1/session', [
    'headers' => ['username' => 'mab@liquidfibre.com', 'password' => 'M@b123456']
    //'headers' => ['username' => 'glds@liquidfibre.com', 'password'=>'P@ssword123']
]);
$body = json_decode($res->getBody());

// Get the latest row of data
$stmt = $db->unprepared_query('SELECT * FROM nutrients_data WHERE node_address LIKE \'%0x000%d%\' ORDER BY id desc LIMIT 1');

$row = $stmt->fetch(PDO::FETCH_ASSOC);


   //  echo $x['message_id'];
    // Get the latest device messages by message id (uuid)
    $request = $client->request('GET', 'https://glds.ingenu.com/data/v1/receive/' . $row['message_id'], [
        'headers' => [
            'Authorization' => $body->token,
            'Content-Type' => 'application/json'
        ]
    ]);
    
    

$body = json_decode($request->getBody());

// util function
$calcBatt = function ($reading, $lower, $upper) {
    $range = $upper - $lower;
    $delta = $reading - $lower;
    if ($delta <= 0) $delta = 0;
    $level = ($delta / $range) * 100;
    return $level < 0 ? 0 : ($level > 100 ? 100 : $level);
};

// Run through received messages
for ($j = 0; $j < sizeof($body->uplinks); $j++) {
    // only interested in importing Datagram Uplink Events
    if (empty($body->uplinks[$j]->datagramUplinkEvent)) {
        continue;
    }

//print_r($body->uplinks[$j]);

    $messageId = $body->uplinks[$j]->messageId;
    $nodeId = $body->uplinks[$j]->datagramUplinkEvent->nodeId;
    $b64 = $body->uplinks[$j]->datagramUplinkEvent->payload;
    $line = bin2hex(base64_decode($body->uplinks[$j]->datagramUplinkEvent->payload));
    $cid_sid = substr($line, 0, 4); // 4
var_dump($cid_sid);
    // nutrient probe
    if ($cid_sid == '0206') {
        //insert into nutrient data
        // duplicate prevention check
        $sql = 'SELECT COUNT(*) as total FROM nutrients_data WHERE message_id = \'' . $messageId . '\'';
        $stmt = $db->unprepared_query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (true) {

            // Time of Measurement
            $timestamp = substr($line, 4, 8); // 12
            $timestamp = hexdec(substr($timestamp, 6, 2) . substr($timestamp, 4, 2) . substr($timestamp, 2, 2) . substr($timestamp, 0, 2));
            $dataDate = gmdate("Y-m-d H:i:s", $timestamp);

            // Battery Voltage
            $bv = hexdec(substr($line, 14, 2) . substr($line, 12, 2)); // 16
            $bp = $calcBatt($bv, 3500, 4200);

            // 16-19 Unused (Internal Temp(2chars/1byte) + Flags(2chars/1byte))
            $ambient_temp = hexdec(substr($line, 16, 2));

            $probe_address = hexdec(substr($line, 20, 2)); // 22
            $probe_address = $probe_address > 0 ? $probe_address - 48 : 0;

            static $message_types = [
                '0206' => [
                    'Reading Time|8|TIMESTAMP',
                    'Batt.Voltage|4|UINT16',
                    'Internal Temp|2|INT8',
                    'Flags|2|ENUM_PWR',
                    'Probe Address|2|PROBE_ADDR',
                    'M0|?|NUTR_VALUE_READ',
                    'M1|?|NUTR_VALUE_READ',
                    'M2|?|NUTR_VALUE_READ',
                    'M3|?|NUTR_VALUE_READ',
                    'M4|?|NUTR_VALUE_READ',
                    'M5|?|NUTR_VALUE_READ',
                    'M6|?|NUTR_VALUE_READ',
                    'M7|?|NUTR_VALUE_READ',
                    'M8|?|NUTR_VALUE_READ',
                    'M9|?|NUTR_VALUE_READ',
                ],
            ];

            $reverse_bytes = function ($hex) {
                return implode(array_reverse(explode(' ', chunk_split($hex, 2, ' '))));
            };

            $formatters = [
                'HEX'        => function ($data) {
                    return chunk_split($data, 2, ' ');
                },
                'NUTR_VALUE_READ' => function ($data, $decimation, $bit7) use ($reverse_bytes, $line) {
                    if ($bit7 > 0) {
                        // 32bit value
                        $v = unpack("l", pack("l", hexdec($reverse_bytes($data))));
                    } else {
                        // 16 bit value
                        $v = unpack("s", pack("s", hexdec($reverse_bytes($data))));
                    }

                    $v = reset($v);
                    return number_format($v * pow(0.1, $decimation), 5, '.', '');
                },
            ];

            $format_values = true;

            $cid_sid = substr($line, 0, 4);
            $payload_length = strlen($line);
            $offset = 4;


            if (empty($message_types[$cid_sid]) || !is_array($message_types[$cid_sid])) {
                return ['Unknown Message' => $formatters['HEX']($line)];
            }
            $output = ['CID-SID' => $formatters['HEX']($cid_sid)];
            print_r($output);
            foreach ($message_types[$cid_sid] as $part) {
                $tmp = explode('|', $part);
                $label = $tmp[0];
                $bcount = $tmp[1];
                $formatter = ($format_values && !empty($tmp[2]) && !empty($formatters[$tmp[2]])) ? $formatters[$tmp[2]] : $formatters['HEX'];

                if ($bcount == '?') {
                    // Chunk decoding
                    $format_byte = hexdec(substr($line, $offset, 2));
                    $bit7 = $format_byte & 0x80;
                    $decimation = (int)(($format_byte >> 4) & 0x7);
                    $levels = (int)($format_byte & 0x0F);
                    $offset += 2;

                    $output[$label . '_SENSORS'] = $levels;

                    for ($i = 0; $i < $levels; $i++) {
                        if ($bit7 > 0) {
                            $output[$label . '_' . ($i + 1)] = $formatter(substr($line, $offset, 8), $decimation, $bit7);
                            $offset += 8;
                        } else {
                            $output[$label . '_' . ($i + 1)] = $formatter(substr($line, $offset, 4), $decimation, $bit7);
                            $offset += 4;
                        }
                    }
                } else if (strstr($bcount, '*') !== false) {
                    // composite length
                    $factors = explode('*', $bcount);
                    $count = (int) $factors[0];
                    $lookup = $factors[1];

                    $total = hexdec($output[$lookup]);
                    for ($i = 0; $i < $total; $i++) {
                        $output[$label . ' ' . ($i + 1)] = $formatter(substr($line, $offset + ($i * $count), $count));
                    }
                    $offset += $count * $total;
                    // simple length
                } else {
                    $output[$label] = $formatter(substr($line, $offset, $bcount));
                    $offset += (int) $bcount;
                }
                
            }

            if (
                $offset < $payload_length
            ) {
                $output['Leftover Bytes'] = $formatters['HEX'](substr($line, $offset, $payload_length - $offset));
            }
        }

        $nodeAddress = "{$nodeId}-{$probe_address}";
        echo 'print r $output';
        

        //remove these identifiers
        unset($output['Batt.Voltage'], $output['Internal Temp'], $output['Probe Address'], $output['M0_0'], $output['M1_0'], $output['M2_0'], $output['M3_0'], $output['M4_0'], $output['M5_0'], $output['M6_0'], $output['M7_0'], $output['M8_0'], $output['M9_0']);

        unset($output['CID-SID'], $output['Reading Time'], $output['Flags'], $output['M0_SENSORS'], $output['M1_SENSORS'], $output['M2_SENSORS'], $output['M3_SENSORS'], $output['M4_SENSORS'], $output['M5_SENSORS'], $output['M6_SENSORS'], $output['M7_SENSORS'], $output['M8_SENSORS'], $output['M9_SENSORS']);


        print_r($output);
        foreach ($output as $key => $val) {
          
            $sql = 'INSERT INTO nutrients_data (node_address, probe_serial, vendor_model_fw, version, identifier,value, date_reported, date_sampled, message_id, bv, bp, latt, lng, ambient_temp)';

            $sql .= 'VALUES ("' . $nodeAddress ; // nodeId
            $sql .= '", (SELECT probe_serial FROM node_startup_info WHERE node_id="' . $nodeId . '-0" LIMIT 1)';
            $sql .= ', (SELECT probe_type FROM node_startup_info WHERE node_id="' . $nodeId . '" LIMIT 1)';
            $sql .= ', (SELECT probe_firmware FROM node_startup_info WHERE node_id="' . $nodeId . '" LIMIT 1)';
            $sql .= ',\'' . $key; // identifier
            $sql .= '\',\'' . $val; // value
            $sql .= '\',\'' . $dataDate; // date reported
            $sql .= '\',\'' . $dataDate; // date sampled
            $sql .= '\',\'' . $messageId; // message id
            $sql .= '\',\'' . $bv; // bv
            $sql .= '\',\'' . $bp; // bp
            $sql .= '\',\'' . 0.00; // lat
            $sql .= '\',\'' . 0.00; // long
            $sql .= '\',\'' .  $ambient_temp; // ambient_temp
            $sql .= '\')';
            $nutr_stmt = $db->unprepared_query($sql);
            var_dump($sql);
           print_r($nutr_stmt);
        }
    }
}
