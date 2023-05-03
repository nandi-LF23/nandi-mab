<?php

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
$stmt = $db->unprepared_query('SELECT * FROM node_data WHERE message_id_1=\'int\' ORDER BY id DESC LIMIT 1');

$row = $stmt->fetch(PDO::FETCH_ASSOC);

$nutr_stmt = $db->unprepared_query('SELECT * FROM nutrients_data WHERE message_id=\'int\' ORDER BY id DESC LIMIT 1');

$nutr_row = $nutr_stmt->fetch(PDO::FETCH_ASSOC);

// Get the latest device messages by message id (uuid)
$request = $client->request('GET', 'https://glds.ingenu.com/data/v1/receive/' . $row['message_id_2'], [
    'headers' => [
        'Authorization' => $body->token,
        'Content-Type' => 'application/json'
    ]
]);
$body = json_decode($request->getBody());

$request2 = $client->request('GET', 'https://glds.ingenu.com/data/v1/receive/' . $row['message_id'], [
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

//create function for request
function getRequest($client, $stmt, $row, $body) {
    $request = $client->request('GET', 'https://glds.ingenu.com/data/v1/receive/' . $row['message_id'], [
        'headers' => [
            'Authorization' => $body->token,
            'Content-Type' => 'application/json'
        ]
    ]);

    return $request;
}

//OR

function query($the_query) {
    $db = new db();
    $stmt = $db->unprepared_query($the_query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row;
}

//or

function get_stuff() {

    // Get the latest row of data
    $stmt = $db->unprepared_query('SELECT * FROM node_data WHERE message_id_1=\'int\' ORDER BY id DESC LIMIT 1');

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get the latest device messages by message id (uuid)
    $request = $client->request('GET', 'https://glds.ingenu.com/data/v1/receive/' . $row['message_id_2'], [
        'headers' => [
            'Authorization' => $body->token,
            'Content-Type' => 'application/json'
        ]
    ]);
    $body = json_decode($request->getBody());
}




// Run through received messages
for ($j = 0; $j < sizeof($body->uplinks); $j++) {
    // only interested in importing Datagram Uplink Events
    if (empty($body->uplinks[$j]->datagramUplinkEvent)) {
        continue;
    }

    $messageId = $body->uplinks[$j]->messageId;
    $nodeId = $body->uplinks[$j]->datagramUplinkEvent->nodeId;
    $b64 = $body->uplinks[$j]->datagramUplinkEvent->payload;
    $line = bin2hex(base64_decode($body->uplinks[$j]->datagramUplinkEvent->payload));
    $cid_sid = substr($line, 0, 4); // 4

    // if (str_contains($line, 'ff')) {
    //     str_replace($line, 'ff', 0); 
    // }

    // (1,2) GPS Position Message

    if ($cid_sid == '0102') {

        $tmp  = unpack("g", pack("H*", substr($line, 12, 8)));
        $latt = reset($tmp);
        $tmp  = unpack("g", pack("H*", substr($line, 20, 8)));
        $lng  = reset($tmp);

        if ($nodeId) {
            for ($i = 0; $i < 10; $i++) {
                $st = $db->unprepared_query('SELECT * FROM hardware_config WHERE node_address=\'' . ($nodeId . '-' . $i) . '\' LIMIT 1');
                $row = $st->fetch(PDO::FETCH_ASSOC);

                // ensure non-zero GPS coords
                // if (!empty($row) && $latt && $lng) {
                //     // GPD drift prevention
                //     if (abs($row['latt'] - $latt) > 0.001 || abs($row['lng'] - $lng) > 0.001) {
                //         $db->unprepared_query('UPDATE hardware_config SET latt=\'' . $latt . '\', lng=\'' . $lng . '\', updated_at=NOW() WHERE node_address=\'' . ($nodeId . '-' . $i) . '\'');
                //     }
                // }
            }
        }

        // (2,3) Probe Reading Message
        // (2,4) Probe with Pulse Reading Message (deprecated)
        // (2,5) Probe with Pulse Reading Message (dual probe)
        // (8,0) Probe with Pulse Reading Message (legacy MSF)

    } else if ($cid_sid == '0203' || $cid_sid == '0204' || $cid_sid == '0205' || $cid_sid == '0800') {

        // duplicate prevention check
        $sql_1 = 'SELECT COUNT(*) as total FROM node_data WHERE message_id_2 = \'' . $messageId . '\'';
        $stmt_1 = $db->unprepared_query($sql_1);
        $row_1 = $stmt_1->fetch(PDO::FETCH_ASSOC);

        if ($row_1['total'] == 0) {
            $sum = 0;
            $ave = 0;
            $null = null;
            $m_values = [];
            $t_values = [];

            // Time of Measurement
            $timestamp = substr($line, 4, 8); // 12
            $timestamp = hexdec(substr($timestamp, 6, 2) . substr($timestamp, 4, 2) . substr($timestamp, 2, 2) . substr($timestamp, 0, 2));
            $dataDate = gmdate("Y-m-d H:i:s", $timestamp);

            // Battery Voltage
            $bv = hexdec(substr($line, 14, 2) . substr($line, 12, 2)); // 16
            $bp = $calcBatt($bv, 3500, 4200);

            // 16-19 Unused (Internal Temp(2chars/1byte) + Flags(2chars/1byte))
            $ambient_temp = hexdec(substr($line, 16, 2));

            if ($cid_sid == '0205') { // (2,5) Multi-Probe

                $probe_address = hexdec(substr($line, 20, 2)); // 22
                $probe_address = $probe_address > 0 ? $probe_address - 48 : 0;

                $sensor_count = hexdec(substr($line, 22, 2));  // 24

                $rg = substr($line, 24 + 12 * $sensor_count, 8);
                $rg = hexdec(substr($rg, 6, 2) . substr($rg, 4, 2) . substr($rg, 2, 2) . substr($rg, 0, 2));

                $msensor = str_split(substr($line, 24, 8 * $sensor_count), 8); // 8 hex chars (4 bytes) * sensor count
                $tsensor = str_split(substr($line, 24 + 8 * $sensor_count, 4 * $sensor_count), 4); // 4 hex chars (2 bytes) * sensor count

                for ($i = 0; $i < $sensor_count; $i++) {
                    $m_values[] = hexdec(substr($msensor[$i], 6, 2) . substr($msensor[$i], 4, 2) . substr($msensor[$i], 2, 2) . substr($msensor[$i], 0, 2)) / 10000;
                    $t_values[] = hexdec(substr($tsensor[$i], 2, 2) . substr($tsensor[$i], 0, 2)) / 100;
                }
            } else { // (2,3) / (2,4) / (8,0) Single-Probe

                $probe_address = 0;
                $probe_address = 0;

                $sensor_count = hexdec(substr($line, 20, 2));  // 22

                if ($cid_sid == '0203' || $cid_sid == '0204') {

                    // Rain Gauge Value
                    $rg = substr($line, 22 + 12 * $sensor_count, 8); // skip over both moisture+temp values (4+8=12), read 8 hex chars (32-bit unsigned integer)
                    $rg = hexdec(substr($rg, 6, 2) . substr($rg, 4, 2) . substr($rg, 2, 2) . substr($rg, 0, 2));

                    $msensor = str_split(substr($line, 22, 8 * $sensor_count), 8); // 8 hex chars (4 bytes) * sensor count
                    $tsensor = str_split(substr($line, 22 + 8 * $sensor_count, 4 * $sensor_count), 4); // 4 hex chars (2 bytes) * sensor count

                    for ($i = 0; $i < $sensor_count; $i++) {
                        $m_values[] = hexdec(substr($msensor[$i], 6, 2) . substr($msensor[$i], 4, 2) . substr($msensor[$i], 2, 2) . substr($msensor[$i], 0, 2)) / 10000;
                        $t_values[] = hexdec(substr($tsensor[$i], 2, 2) . substr($tsensor[$i], 0, 2)) / 100;
                    }
                } else if ($cid_sid == '0800') {

                    // Rain Gauge Value
                    $rg = substr($line, 22 + 8 * $sensor_count, 8); // skip over both moisture+temp values (4+4=8), read 8 hex chars (32-bit unsigned integer)
                    $rg = hexdec(substr($rg, 6, 2) . substr($rg, 4, 2) . substr($rg, 2, 2) . substr($rg, 0, 2));

                    $msensor = str_split(substr($line, 22, 4 * $sensor_count), 4); // 4 hex chars (2 bytes) * sensor count
                    $tsensor = str_split(substr($line, 22 + 4 * $sensor_count, 4 * $sensor_count), 4); // 4 hex chars (2 bytes) * sensor count

                    for ($i = 0; $i < $sensor_count; $i++) {
                        $m_values[] = hexdec(substr($msensor[$i], 2, 2) . substr($msensor[$i], 0, 2)) / 100;
                        $t_values[] = hexdec(substr($tsensor[$i], 2, 2) . substr($tsensor[$i], 0, 2)) / 100;
                    }
                }
            }


            // calculate sum and average values

            // $nodeId = "{$nodeId}-{$probe_address}";
            $nodeAddress = "{$nodeId}-{$probe_address}";

            // for ($i = 0; $i < $sensor_count; $i++) {
            //     $sum += $m_values[$i];
            // }

            // if ($sensor_count) {
            //     $ave = $sum / $sensor_count;
            // }

            foreach (array_keys($m_values, 0) as $key) {
                unset($m_values[$key]);
            }

            $m_values = array_values($m_values);

            if (!empty($m_values)) {
                for ($i = 0; $i < count($m_values); $i++) {
                    $sum += (float)$m_values[$i];
                }
            }

            $ave = $sum / count($m_values);


            // WARNING: Here, the TARD known as JP chose to use his own custom column ordering to confuse future devs
            $sql = 'INSERT into node_data (date_time,probe_id,bv,bp,ambient_temp,message_id_1,message_id_2,average,accumulative,rg';

            for ($i = 1; $i < count($m_values) + 1; $i++) {
                $sql .= ', sm' . $i;
                $sql .= ', t' . $i;
            }

            $sql .= ') VALUES (\'' . $dataDate; // date_time
            $sql .= '\',\'' . $nodeAddress; // node_id
            $sql .= '\',\'' . $bv; // battry voltage
            $sql .= '\',\'' . $bp; // battry percentage
            $sql .= '\',\'' . $ambient_temp; // ambient temperature
            $sql .= '\',\'' . 'int'; // Intellect (Data Source)
            $sql .= '\',\'' . $messageId; // uuid
            $sql .= '\',\'' . $ave; // average
            $sql .= '\',\'' . $sum; // accumulative
            $sql .= '\',\'' . $rg; // rain gauge

            for ($i = 0; $i < count($m_values); $i++) {
                $sql .= '\',\'' . $m_values[$i];
                $sql .= '\',\'' . $t_values[$i];
            }

            $sql .= '\')';
            $stmt = $db->unprepared_query($sql);

            //this update query is for probe 0x000d0b27-0 and 0x000d129c-0 because it calculates it with 6 sensors instead of 2 
            //above script must be altered to work with soil value count not sensor count
            //$db->unprepared_query('UPDATE node_data SET average = accumulative / 2, sm3 = NULL, sm4 = NULL, sm5 = NULL, sm6 = NULL WHERE probe_id IN ("0x000d0b27-0", "0x000d129c-0")');

            //insert raw payload data into raw_data_b64 table
            $db->unprepared_query('ALTER TABLE raw_data_b64 ADD UNIQUE(b64_data)');
            $db->unprepared_query('INSERT INTO raw_data_b64 (device_id, b64_data, timestamp, message_id) VALUES ("' . $nodeId . '" , "' . $b64 . '" , "' . $dataDate . '", "' . $messageId . '") ON DUPLICATE KEY UPDATE message_id = VALUES(message_id)');

            //insert into sandbox (for backup purposes)
            $db->unprepared_query('REPLACE INTO `agri_sandbox`.`node_data` SELECT * FROM `agri`.`node_data` WHERE `date_time` >= DATE_SUB(CURDATE(), INTERVAL 30 minute) AND `message_id_1` LIKE "int"');

            $db->unprepared_query('UPDATE hardware_config SET date_time=\'' . $dataDate . '\' WHERE node_address=\'' . $nodeAddress . '\'');
        }
    }
    // nutrient probe
    else if ($cid_sid == '0206') {
        //insert into nutrient data
        // duplicate prevention check
        $sql_2 = 'SELECT COUNT(*) as total FROM nutrients_data WHERE message_id = \'' . $messageId . '\'';
        $stmt_2 = $db->unprepared_query($sql_1);
        $row_2 = $stmt_1->fetch(PDO::FETCH_ASSOC);

        if ($row_2['total'] == 0) {

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

        //remove these identifiers
        unset($output['Batt.Voltage'], $output['Internal Temp'], $output['Probe Address'], $output['M0_0'], $output['M1_0'], $output['M2_0'], $output['M3_0'], $output['M4_0'], $output['M5_0'], $output['M6_0'], $output['M7_0'], $output['M8_0'], $output['M9_0']);

        foreach ($output as $key => $val) {
            $sql = 'INSERT INTO nutrients_data (node_address, probe_serial, vendor_model_fw, version, identifier,value, date_reported, date_sampled, message_id, bv, bp, latt, lng, ambient_temp)';

            $sql .= ') VALUES ("' . $nodeAddress; // nodeId
            $sql .= ', (SELECT probe_serial FROM node_startup_info WHERE node_id="' . $nodeAddress . '" LIMIT 1)';
            $sql .= ', (SELECT probe_type FROM node_startup_info WHERE node_id="' . $nodeAddress . '" LIMIT 1)';
            $sql .= ', (SELECT probe_firmware FROM node_startup_info WHERE node_id="' . $nodeAddress . '" LIMIT 1)';
            $sql .= '\',\'' . $key; // identifier
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
        }
    }
}
