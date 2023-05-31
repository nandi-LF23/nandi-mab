<?php
// Battery Voltage
$bv = hexdec(substr($line, 14, 2) . substr($line, 12, 2)); // 16
$bp = $calcBatt($bv, 3200, 4200);

// 16-19 Unused (Internal Temp(2chars/1byte) + Flags(2chars/1byte))
$ambient_temp = hexdec(substr($line, 16, 2));

$probe_address = hexdec(substr($line, 20, 2)); // 22
$probe_address = $probe_address > 0 ? $probe_address - 48 : 0;

$sql = '';

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
    '0203' => [
        'Reading Time|8|TIMESTAMP',
        'Batt.Voltage|4|UINT16',
        'Internal Temp|2|INT8',
        'Flags|2|ENUM_PWR',
        'Probe Address|2|PROBE_ADDR',
        //'Moisture Reading|8*Sensor Count|MOIST_READ',
        //'Temp. Reading|4*Sensor Count|TEMP_READ'
        'Moisture Reading|?|NUTR_VALUE_READ',
        'Temp. Reading|?|NUTR_VALUE_READ',
    ],
];

$reverse_bytes = function ($hex) {
    return implode(array_reverse(explode(' ', chunk_split($hex, 2, ' '))));
};

$formatters = [
    'TIMESTAMP'  => function ($data) use ($reverse_bytes) {
        return gmdate('d-m-Y H:i:s', hexdec($reverse_bytes($data)));
    },
    'FLOAT32'    => function ($data) use ($reverse_bytes) {
        $v = unpack("g", pack("H*", $data));
        return reset($v);
    }, // number_format($number, 2, '.', '');
    'UINT8' => function ($data) {
        return hexdec($data);
    },
    'UINT16'     => function ($data) use ($reverse_bytes) {
        return hexdec($reverse_bytes($data));
    },
    'UINT32' => function ($data) use ($reverse_bytes) {
        return hexdec($reverse_bytes($data));
    },
    'INT8' => function ($data) {
        $v = unpack("l", pack("l", hexdec($data)));
        return reset($v);
    },
    'INT16'      => function ($data) use ($reverse_bytes) {
        $v = unpack("l", pack("l", hexdec($reverse_bytes($data))));
        return reset($v);
    },
    'INT32' => function ($data) use ($reverse_bytes) {
        $v = unpack("l", pack("l", hexdec($reverse_bytes($data))));
        return reset($v);
    },
    'HEX'        => function ($data) {
        return chunk_split($data, 2, ' ');
    },
    'STRING'     => function ($data) {
        return pack("H*", $data);
    },
    'CHAR'       => function ($data) {
        return chr(hexdec($data));
    },
    'ENUM_BOOT'  => function ($data) {
        $m = ['', 'Power On Switch', 'Brown Out', 'External', 'Watch Dog', 'Software'];
        $i = hexdec($data);
        return ($i >= 1 && $i < count($m)) ? $m[$i] : 'Invalid';
    },
    'ENUM_PWR'   => function ($data) {
        $m = ['No Power, Not Charging', 'No Power, Charging', 'Power, Not Charging', 'Power, Charging'];
        $i = hexdec($data);
        return ($i >= 0 && $i < count($m)) ? $m[$i] : 'Invalid';
    },
    'MOIST_READ' => function ($data) use ($reverse_bytes) {
        $v = unpack("l", pack("l", hexdec($reverse_bytes($data))));
        $v = reset($v);
        return number_format($v / 10000, 2, '.', '') . '%';
    },
    'TEMP_READ'  => function ($data) use ($reverse_bytes) {
        $v = unpack("l", pack("l", hexdec($reverse_bytes($data))));
        $v = reset($v);
        return ($v / 100) . '°C';
    },
    'ENUM_PWR_L' => function ($data) {
        $v = '';
        $x = hexdec($data);
        $x & 0x1 ? 'Power, ' : 'No Power, ';
        $x .= $x & 0x2 ? 'Charging' : 'Not Charging';
        return $v;
    },
    'MOIST_READ_L' => function ($data) use ($reverse_bytes) {
        $v = unpack("s", pack("s", hexdec($reverse_bytes($data))));
        $v = reset($v);
        return number_format($v / 100, 2, '.', '') . '%';
    },
    'TEMP_READ_L'  => function ($data) use ($reverse_bytes) {
        $v = unpack("s", pack("s", hexdec($reverse_bytes($data))));
        $v = reset($v);
        return ($v / 100) . '°C';
    },
    'PROBE_ADDR' => function ($data) {
        $v = ((int)hexdec($data)) - 48;
        return $v;
    },
    'BATT_VOLT' => function ($data) use ($reverse_bytes) {
        return hexdec($reverse_bytes($data)) / 100;
    },
    'NUTR_VALUE_READ' => function ($data, $decimation, $bit7) use ($reverse_bytes, $payload) {
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
//for($j = 1; $j < 10; $j++)

$sql = 'SELECT probe_type FROM node_startup_info WHERE node_id = "' . $nodeId .  '" AND sdi_address = "' . $probe_address . '" LIMIT 1';
$stmt = $db->unprepared_query($sql);
$paddr = $stmt->fetch(PDO::FETCH_BOTH);
//strpos($paddr[0],'ACH',-3) ? $probe_type = 'moisture' : $probe_type = 'nutrient';
switch($paddr[0])
{
    case '13AquaChckACCSDI' : {$probe_type = 'moisture'; break;}
    case '13AquaChckACHSDI'  : {$probe_type = 'moisture'; break;}
    case '13AquaChckACC' : {$probe_type = 'moisture'; break;}
    case '13AquaChckACH'  : {$probe_type = 'moisture'; break;}
    case '13AquaChckEC2'  : {$probe_type = 'nutrient';break;}
    default: {$probe_type = 'nutrient';break;}
}
echo $probe_type .PHP_EOL. $paddr[0] . PHP_EOL;
file_put_contents('/var/www/live_rpma_import_log.log', $probe_type . PHP_EOL . $paddr[0] . PHP_EOL);
//die;
//echo $probe_type . PHP_EOL . $j . PHP_EOL;

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

$nodeAddress = "{$nodeId}-{$probe_address}";
if($probe_type == 'nutrient')
{
$sql = 'INSERT INTO nutri_data (node_address, probe_serial, vendor_model_fw, ver, date_reported, date_sampled, message_id, bv, bp, latt, lng, ambient_temp,';

$output['M0_1'] ? $sql .= 'M0_1' : null ;$output['M0_2'] ? $sql .= ',' : null ;
$output['M0_2'] ? $sql .= 'M0_2' : null ;$output['M0_3'] ? $sql .= ',' : null ;
$output['M0_3'] ? $sql .= 'M0_3' : null ;$output['M0_4'] ? $sql .= ',' : null ;
$output['M0_4'] ? $sql .= 'M0_4' : null ;$output['M1_1'] ? $sql .= ',' : null ;

$output['M1_1'] ? $sql .= 'M1_1' : null ;$output['M1_2'] ? $sql .= ',' : null ;
$output['M1_2'] ? $sql .= 'M1_2' : null ;$output['M1_3'] ? $sql .= ',' : null ;
$output['M1_3'] ? $sql .= 'M1_3' : null ;$output['M1_4'] ? $sql .= ',' : null ;
$output['M1_4'] ? $sql .= 'M1_4' : null ;$output['M2_1'] ? $sql .= ',' : null ;

$output['M2_1'] ? $sql .= 'M2_1' : null ;$output['M2_2'] ? $sql .= ',' : null ;
$output['M2_2'] ? $sql .= 'M2_2' : null ;$output['M2_3'] ? $sql .= ',' : null ;
$output['M2_3'] ? $sql .= 'M2_3' : null ;$output['M2_4'] ? $sql .= ',' : null ;
$output['M2_4'] ? $sql .= 'M2_4' : null ;$output['M3_1'] ? $sql .= ',' : null ;

$output['M3_1'] ? $sql .= 'M3_1' : null ;$output['M3_2'] ? $sql .= ',' : null ;
$output['M3_2'] ? $sql .= 'M3_2' : null ;$output['M3_3'] ? $sql .= ',' : null ;
$output['M3_3'] ? $sql .= 'M3_3' : null ;$output['M3_4'] ? $sql .= ',' : null ;
$output['M3_4'] ? $sql .= 'M3_4' : null ;$output['M4_1'] ? $sql .= ',' : null ;

$output['M4_1'] ? $sql .= 'M4_1' : null ;$output['M4_2'] ? $sql .= ',' : null ;
$output['M4_2'] ? $sql .= 'M4_2' : null ;$output['M4_3'] ? $sql .= ',' : null ;
$output['M4_3'] ? $sql .= 'M4_3' : null ;$output['M4_4'] ? $sql .= ',' : null ;
$output['M4_4'] ? $sql .= 'M4_4' : null ;$output['M5_1'] ? $sql .= ',' : null ;

$output['M5_1'] ? $sql .= 'M5_1' : null ;$output['M5_2'] ? $sql .= ',' : null ;
$output['M5_2'] ? $sql .= 'M5_2' : null ;$output['M5_3'] ? $sql .= ',' : null ;
$output['M5_3'] ? $sql .= 'M5_3' : null ;$output['M5_4'] ? $sql .= ',' : null ;
$output['M5_4'] ? $sql .= 'M5_4' : null ;$output['M6_1'] ? $sql .= ',' : null ;

$output['M6_1'] ? $sql .= 'M6_1' : null ;$output['M6_2'] ? $sql .= ',' : null ;
$output['M6_2'] ? $sql .= 'M6_2' : null ;$output['M6_3'] ? $sql .= ',' : null ;
$output['M6_3'] ? $sql .= 'M6_3' : null ;$output['M6_4'] ? $sql .= ',' : null ;
$output['M6_4'] ? $sql .= 'M6_4' : null ;

$sql .= ')';

$sql .= ' VALUES ("' . $nodeAddress ; // nodeId
$sql .= '", (SELECT probe_serial FROM node_startup_info WHERE node_id = "' . $nodeAddress . '" LIMIT 1)';
$sql .= ', (SELECT probe_type FROM node_startup_info WHERE node_id = "' . $nodeAddress . '" LIMIT 1)';
$sql .= ', (SELECT probe_firmware FROM node_startup_info WHERE node_id = "' . $nodeAddress . '" LIMIT 1)';
$sql .= ',\'' . $timestamp; // date reported
$sql .= '\',\'' . $DateTime; // date sampled
$sql .= '\',\'' . $messageId; // message id
$sql .= '\',\'' . $bv; // bv
$sql .= '\',\'' . $bp; // bp
$sql .= '\',\'' . 0.00; // lat
$sql .= '\',\'' . 0.00; // long
$sql .= '\',\'' .  $ambient_temp; // ambient_temp

$output['M0_1'] ? $sql .= '\',\'' .  number_format($output['M0_1'], 2, '.', '') :  null;
$output['M0_2'] ? $sql .= '\',\'' .  number_format($output['M0_2'], 2, '.', '') :  null;
$output['M0_3'] ? $sql .= '\',\'' .  number_format($output['M0_3'], 2, '.', '') :  null;
$output['M0_4'] ? $sql .= '\',\'' .  number_format($output['M0_4'], 2, '.', '') :  null;

$output['M1_1'] ? $sql .= '\',\'' .  number_format($output['M1_1'], 2, '.', '') :  null;
$output['M1_2'] ? $sql .= '\',\'' .  number_format($output['M1_2'], 2, '.', '') :  null;
$output['M1_3'] ? $sql .= '\',\'' .  number_format($output['M1_3'], 2, '.', '') :  null;
$output['M1_4'] ? $sql .= '\',\'' .  number_format($output['M1_4'], 2, '.', '') :  null;

$output['M2_1'] ? $sql .= '\',\'' .  number_format($output['M2_1'], 2, '.', '') :  null;
$output['M2_2'] ? $sql .= '\',\'' .  number_format($output['M2_2'], 2, '.', '') :  null;
$output['M2_3'] ? $sql .= '\',\'' .  number_format($output['M2_3'], 2, '.', '') :  null;
$output['M2_4'] ? $sql .= '\',\'' .  number_format($output['M2_4'], 2, '.', '') :  null;

$output['M3_1'] ? $sql .= '\',\'' .  number_format($output['M3_1'], 2, '.', '') :  null;
$output['M3_2'] ? $sql .= '\',\'' .  number_format($output['M3_2'], 2, '.', '') :  null;
$output['M3_3'] ? $sql .= '\',\'' .  number_format($output['M3_3'], 2, '.', '') :  null;
$output['M3_4'] ? $sql .= '\',\'' .  number_format($output['M3_4'], 2, '.', '') :  null;

$output['M4_1'] ? $sql .= '\',\'' .  number_format($output['M4_1'], 2, '.', '') :  null;
$output['M4_2'] ? $sql .= '\',\'' .  number_format($output['M4_2'], 2, '.', '') :  null;
$output['M4_3'] ? $sql .= '\',\'' .  number_format($output['M4_3'], 2, '.', '') :  null;
$output['M4_4'] ? $sql .= '\',\'' .  number_format($output['M4_4'], 2, '.', '') :  null;

$output['M5_1'] ? $sql .= '\',\'' .  number_format($output['M5_1'], 2, '.', '') :  null;
$output['M5_2'] ? $sql .= '\',\'' .  number_format($output['M5_2'], 2, '.', '') :  null;
$output['M5_3'] ? $sql .= '\',\'' .  number_format($output['M5_3'], 2, '.', '') :  null;
$output['M5_4'] ? $sql .= '\',\'' .  number_format($output['M5_4'], 2, '.', '') :  null;

$output['M6_1'] ? $sql .= '\',\'' .  number_format($output['M6_1'], 2, '.', '') :  null;
$output['M6_2'] ? $sql .= '\',\'' .  number_format($output['M6_2'], 2, '.', '') :  null;
$output['M6_3'] ? $sql .= '\',\'' .  number_format($output['M6_3'], 2, '.', '') :  null;
$output['M6_4'] ? $sql .= '\',\'' .  number_format($output['M6_4'], 2, '.', '') :  null;


$sql .= '\')';

//print_r($sql);

$nutr_stmt = $db->unprepared_query($sql);
file_put_contents('/var/www/live_rpma_import_log.log',print_r($nutr_stmt));

//die;
}
if ($probe_type == 'moisture')
{
    $sum = 0;
    $ave = 0;

    $counter = 0;

    if($output['M0_1']) { $sum += $output['M0_1'];$counter++; };
    if($output['M0_2']) { $sum += $output['M0_2'];$counter++; };
    if($output['M0_3']) { $sum += $output['M0_3'];$counter++; };
    if($output['M0_4']) { $sum += $output['M0_4'];$counter++; };

    $ave = $sum / $counter;

    // Battery Voltage
    $bv = hexdec(substr($line, 14, 2) . substr($line, 12, 2)); // 16
    $bp = $calcBatt($bv, 3200, 4200);

    $sql = 'INSERT into node_data (date_time,probe_id,bv,bp,ambient_temp,message_id_1,message_id_2,average,accumulative,';

/*    for ($i = 1; $i < count($m_values) + 1; $i++) {
        $sql .= ', sm' . $i;
        $sql .= ', t' . $i;
    }
*/

    $output['M0_1'] ? $sql .= 'sm1' : null ;$output['M0_2'] ? $sql .= ',' : null ;
    $output['M0_2'] ? $sql .= 'sm2' : null ;$output['M0_3'] ? $sql .= ',' : null ;
    $output['M0_3'] ? $sql .= 'sm3' : null ;$output['M0_4'] ? $sql .= ',' : null ;
    $output['M0_4'] ? $sql .= 'sm4' : null ;$output['M1_1'] ? $sql .= ',' : null ;

    $output['M1_1'] ? $sql .= 't1' : null ;$output['M1_2'] ? $sql .= ',' : null ;
    $output['M1_2'] ? $sql .= 't2' : null ;$output['M1_3'] ? $sql .= ',' : null ;
    $output['M1_3'] ? $sql .= 't3' : null ;$output['M1_4'] ? $sql .= ',' : null ;
    $output['M1_4'] ? $sql .= 't4' : null ;

    $sql .= ') VALUES (\'' . $DateTime; // date_time
    $sql .= '\',\'' . $nodeAddress; // node_id
    $sql .= '\',\'' . $bv; // battry voltage
    $sql .= '\',\'' . $bp; // battry percentage
    $sql .= '\',\'' . $ambient_temp; // ambient temperature
    $sql .= '\',\'' . 'int'; // Intellect (Data Source)
    $sql .= '\',\'' . $messageId; // uuid
    $sql .= '\',\'' . $ave; // average
    $sql .= '\',\'' . $sum; // accumulative
    //$sql .= '\',\'' . $rg; // rain gauge

 /*   for ($i = 0; $i < count($m_values); $i++) {
        $sql .= '\',\'' . $m_values[$i];
        $sql .= '\',\'' . $t_values[$i];
    }*/

    $output['M0_1'] ? $sql .= '\',\'' .  number_format($output['M0_1'], 2, '.', '') :  null;
    $output['M0_2'] ? $sql .= '\',\'' .  number_format($output['M0_2'], 2, '.', '') :  null;
    $output['M0_3'] ? $sql .= '\',\'' .  number_format($output['M0_3'], 2, '.', '') :  null;
    $output['M0_4'] ? $sql .= '\',\'' .  number_format($output['M0_4'], 2, '.', '') :  null;

    $output['M1_1'] ? $sql .= '\',\'' .  number_format($output['M1_1'], 2, '.', '') :  null;
    $output['M1_2'] ? $sql .= '\',\'' .  number_format($output['M1_2'], 2, '.', '') :  null;
    $output['M1_3'] ? $sql .= '\',\'' .  number_format($output['M1_3'], 2, '.', '') :  null;
    $output['M1_4'] ? $sql .= '\',\'' .  number_format($output['M1_4'], 2, '.', '') :  null;

    $sql .= '\')';
    $db->unprepared_query($sql);


  /*  echo $sql . PHP_EOL;

    print_r($stmt);*/

    echo 'data ends here for the payload'.PHP_EOL;

 //   die;
}
$sql1 = 'SELECT COUNT(*) FROM raw_data_b64 WHERE message_id=\''.$messageId.'\'';
$stmt = $db->unprepared_query( $sql1 );
$row = $stmt->fetch(PDO::FETCH_NUM);
if (!$row[0]) {
    $db->unprepared_query('INSERT INTO raw_data_b64 (device_id, b64_data, timestamp, message_id) VALUES ("' . $nodeId . '" , "' . $b64 . '" , "' . $timestamp . '", "' . $messageId . '") ON DUPLICATE KEY UPDATE message_id = VALUES(message_id)');
    // print_r($stmt);
    $db->unprepared_query('UPDATE hardware_config SET date_time=\'' . $DateTime . '\' WHERE node_address=\'' . $nodeAddress . '\'');
    //insert with b64
    $db->unprepared_query($sql);
}
