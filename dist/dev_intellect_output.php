<?php

use phpDocumentor\Reflection\DocBlock\Tags\Var_;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// DB
function mab_db_connect()
{

    $engine = 'mysql';
    $host = '127.0.0.1';
    $database = 'agri';
    $user = 'myagbuddy';
    $pass = 'N1ckn4ggp4dyw@gg!3212';

    $connection_string = $engine . ':dbname=' . $database . ";host=" . $host;
    try {
        $conn = new PDO($connection_string, $user, $pass);
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        $conn = null;
    }
    return $conn;
}

function mab_db_query($conn, $query, &$last_id = null)
{
    try {
        $stmt = $conn->query($query);
        if ($last_id) {
            $last_id = $conn->lastInsertId();
        }
        return $stmt;
    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
    }
    return null;
}

/* AUTHENTICATE: returns token on success, false on failure */
function intellect_authenticate($user, $pass)
{
    $url = 'https://glds.ingenu.com/config/v1/session';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["username: {$user}", "password: {$pass}"]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    if ($result !== false) {
        $data = json_decode($result);
        if ($data && isset($data->token)) {
            return $data->token;
        } else {
            return false;
        }
    }
    return false;
}

/* RECEIVE messages from buffer */
function intellect_receive($token, $message_id = '')
{
    $url = 'https://glds.ingenu.com/data/v1/receive' . ($message_id ? "/{$message_id}" : '') . '?count=500';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: {$token}"]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    if ($result !== false) {
        $data = json_decode($result);
        return $data ? $data : false;
    }
    return false;
}

/* GET message type as array or string (formatted) */
function intellect_payload_get_message_type($payload, $formatted = false)
{
    $data = str_split($payload, 2);
    $msg = [hexdec($data[0]), hexdec($data[1])];
    if ($formatted) {
        if ($msg[0] == 1 && $msg[1] == 1) {
            return '(1,1) Boot Message';
        } // has date? n
        if ($msg[0] == 1 && $msg[1] == 2) {
            return '(1,2) GPS Position Message';
        } // has date? Y [0,3]
        if ($msg[0] == 1 && $msg[1] == 3) {
            return '(1,3) Probe Type Message';
        } // has date? n
        if ($msg[0] == 1 && $msg[1] == 4) {
            return '(1,4) Boot Message V2';
        } // has date? n
        if ($msg[0] == 2 && $msg[1] == 1) {
            return '(2,1) Sensor Reading Message';
        } // has date? Y [0,3]
        if ($msg[0] == 2 && $msg[1] == 2) {
            return '(2,2) Relay Response Ack/Nack';
        } // has date? Y [0,3] [4,7]
        if ($msg[0] == 2 && $msg[1] == 3) {
            return '(2,3) Probe Reading Message';
        } // has date? Y [0,3]
        if ($msg[0] == 2 && $msg[1] == 4) {
            return '(2,4) Probe with Pulse Reading Message';
        } // has date? Y [0,3]
        if ($msg[0] == 2 && $msg[1] == 5) {
            return '(2,5) Probe with Pulse Reading Message (multi probe)';
        } // has date? Y [0,3]
        if ($msg[0] == 3 && $msg[1] == 1) {
            return '(3,1) Config Parameter Report Message';
        } // has date? n
        if ($msg[0] == 8 && $msg[1] == 0) {
            return '(8,0) Legacy Probe Data Message';
        } // has date? y
        return 'Unknown Message';
    }
    return $msg;
}

/* SPECIFIC MESSAGE HANDLING */

/* GET message timestamp */
function intellect_payload_get_date($payload, $dateFormat = 'Y-m-d H:i:s')
{
    $msg = intellect_payload_get_message_type($payload);

    // (1,1) Boot Message
    if ($msg[0] == 1 && $msg[1] == 1) {
        return 'No Date';
    } // has date? n
    // (1,2) GPS Position Message
    if ($msg[0] == 1 && $msg[1] == 2) { // has date? Y [0,3]
        $ts = substr($payload, 4, 8);
        $ts = hexdec(substr($ts, 6, 2) . substr($ts, 4, 2) . substr($ts, 2, 2) . substr($ts, 0, 2));
        return gmdate($dateFormat, $ts);
        //return gmdate($dateFormat, round($ts / 1000));
    }
    // (1,3) Probe Type Message
    if ($msg[0] == 1 && $msg[1] == 3) {
        return 'No Date';
    } // has date? n
    // (1,4) Boot Message V2
    if ($msg[0] == 1 && $msg[1] == 4) {
        return 'No Date';
    } // has date? n
    // (2,1) Sensor Reading Message
    if ($msg[0] == 2 && $msg[1] == 1) { // has date? Y [0,3]
        $ts = substr($payload, 4, 8);
        $ts = hexdec(substr($ts, 6, 2) . substr($ts, 4, 2) . substr($ts, 2, 2) . substr($ts, 0, 2));
        return gmdate($dateFormat, $ts);
    }
    // (2,2) Relay Response Ack/Nack
    if ($msg[0] == 2 && $msg[1] == 2) { // has date? Y [0,3] [4,7]
        $ts = substr($payload, 4, 8);
        $ts = hexdec(substr($ts, 6, 2) . substr($ts, 4, 2) . substr($ts, 2, 2) . substr($ts, 0, 2));
        return gmdate($dateFormat, $ts);
    }
    // (2,3) Probe Reading Message
    if ($msg[0] == 2 && $msg[1] == 3) { // has date? Y [0,3]
        $ts = substr($payload, 4, 8);
        $ts = hexdec(substr($ts, 6, 2) . substr($ts, 4, 2) . substr($ts, 2, 2) . substr($ts, 0, 2));
        return gmdate($dateFormat, $ts);
    }
    // (2,4) Probe with Pulse Reading Message
    if ($msg[0] == 2 && $msg[1] == 4) { // has date? Y [0,3]
        $ts = substr($payload, 4, 8);
        $ts = hexdec(substr($ts, 6, 2) . substr($ts, 4, 2) . substr($ts, 2, 2) . substr($ts, 0, 2));
        return gmdate($dateFormat, $ts);
    }
    // (2,5) Probe with Pulse Reading Message (multi probe)
    if ($msg[0] == 2 && $msg[1] == 5) { // has date? Y [0,3]
        $ts = substr($payload, 4, 8);
        $ts = hexdec(substr($ts, 6, 2) . substr($ts, 4, 2) . substr($ts, 2, 2) . substr($ts, 0, 2));
        return gmdate($dateFormat, $ts);
    }
    // (3,1) Config Parameter Report Message
    if ($msg[0] == 3 && $msg[1] == 1) {
        return 'No Date';
    } // has date? n
    // (5) Relay Instruction
    if ($msg[0] == 5) { // has date? Y [0,3]
        $ts = substr($payload, 4, 8);
        $ts = hexdec(substr($ts, 6, 2) . substr($ts, 4, 2) . substr($ts, 2, 2) . substr($ts, 0, 2));
        return gmdate($dateFormat, $ts);
    }
    // (6) Request Config Values
    if ($msg[0] == 6) {
        return 'No Date';
    } // has date? n
    // (7) Config Values to Update
    if ($msg[0] == 7) {
        return 'No Date';
    } // has date? n

    // (8,0) Legacy Probe Data Message
    if ($msg[0] == 8 && $msg[1] == 0) { // has date? Y [0,3]
        $ts = substr($payload, 4, 8);
        $ts = hexdec(substr($ts, 6, 2) . substr($ts, 4, 2) . substr($ts, 2, 2) . substr($ts, 0, 2));
        return gmdate($dateFormat, $ts);
    }

    return 'No Date';
}

/* FORMAT hex by grouping bytes */

function intellect_payload_unserialize($payload, $format_values = false, $nodeAddress = NULL)
{
    static $message_types = [
        // (1,1) Boot Message
        '0101' => [
            'Firmware Ver|2|UINT8',
            'Protocol Ver|2|UINT8',
            'Hardware Ver|2|UINT8',
            'Manuf.Date|8|TIMESTAMP',
            'Batt.Voltage|4|UINT16',
            'Internal Temp|2|INT8',
            'Bus Voltage|4|UINT16'
        ],
        // (1,2) GPS Position Message
        '0102' => [
            'GPS Lock Time|8|TIMESTAMP',
            'Latitude|8|FLOAT32',
            'Longitude|8|FLOAT32'
        ],
        // (1,3) Probe Type Message
        '0103' => [
            'SDI Address|2|CHAR',
            'Enum|4|UINT16',
            'ID String|64|STRING'
        ],
        // (1,4) Boot Message V2
        '0104' => [
            'Firmware Major|2|UINT8',
            'Firmware Minor|2|UINT8',
            'Firmware Point|2|UINT8',
            'Protocol Ver|2|UINT8',
            'Hardware Ver|2|UINT8',
            'Manuf.Date|8|TIMESTAMP',
            'Batt.Voltage|4|UINT16',
            'Internal Temp|2|INT8',
            'Bus Voltage|4|UINT16',
            'Boot Cause|2|ENUM_BOOT'
        ],
        // (2,1) Sensor Reading Message
        '0201' => [
            'Reading Time|8|TIMESTAMP',
            'Batt.Voltage|4|UINT16',
            'Internal Temp|2|INT8',
            'Flags|2|ENUM_PWR',
            'Pulse 1 Counter|8|UINT32',
            'Pulse 2 Counter|8|UINT32',
            'Pulse 1 State|2|UINT8',
            'Pulse 2 State|2|UINT8',
            'Pulse 1 mA|4|UINT16',
            'Pulse 2 mA|4|UINT16',
            'Ultrasonic|4|UINT16'
        ],
        // (2,2) Relay Response Ack/Nack
        '0202' => [
            'Received Time|8|TIMESTAMP',
            'Valid Time|8|TIMESTAMP',
            'Success|2|UINT8'
        ],
        // (2,3) Probe Reading Message
        '0203' => [
            'Reading Time|8|TIMESTAMP',
            'Batt.Voltage|4|UINT16',
            'Internal Temp|2|INT8',
            'Flags|2|ENUM_PWR',
            'Sensor Count|2|UINT8',
            'Moisture Reading|8*Sensor Count|MOIST_READ',
            'Temp. Reading|4*Sensor Count|TEMP_READ'
        ],
        // (2,4) Probe with Pulse Reading Message
        '0204' => [
            'Reading Time|8|TIMESTAMP',
            'Batt.Voltage|4|UINT16',
            'Internal Temp|2|INT8',
            'Flags|2|ENUM_PWR',
            'Sensor Count|2|UINT8',
            'Moisture Reading|8*Sensor Count|MOIST_READ',
            'Temp. Reading|4*Sensor Count|TEMP_READ',
            'Pulse 1 Counter|8|UINT32',
            'Pulse 2 Counter|8|UINT32',
            'Pulse 1 State|2|UINT8',
            'Pulse 2 State|2|UINT8',
            'Pulse 1 mA|4|UINT16',
            'Pulse 2 mA|4|UINT16'
        ],
        // (2,5) Probe with Pulse Reading Message (multi probe)
        '0205' => [
            'Reading Time|8|TIMESTAMP',
            'Batt.Voltage|4|UINT16',
            'Internal Temp|2|INT8',
            'Flags|2|ENUM_PWR',
            'Probe Address|2|PROBE_ADDR',
            'Sensor Count|2|UINT8',
            'Moisture Reading|8*Sensor Count|MOIST_READ',
            'Temp. Reading|4*Sensor Count|TEMP_READ',
            'Pulse 1 Counter|8|UINT32',
            'Pulse 2 Counter|8|UINT32',
            'Pulse 1 State|2|UINT8',
            'Pulse 2 State|2|UINT8',
            'Pulse 1 mA|4|UINT16',
            'Pulse 2 mA|4|UINT16'
        ],
        // (3,1) Config Parameter Report Message
        '0301' => [
            'Key Count|2|UINT8',
            'Key&Value|12*Key Count|HEX'
        ],
        // LEGACY NODE: Probe Data Message
        '0800' => [
            'Reading Time|8|TIMESTAMP',
            'Batt.Voltage|4|UINT16',
            'Internal Temp|2|INT8',
            'Flags|2|ENUM_PWR_L',
            'Sensor Count|2|UINT8',
            'Moisture Reading|4*Sensor Count|MOIST_READ_L',
            'Temp. Reading|4*Sensor Count|TEMP_READ_L',
            'Rain Gauge|8|UINT32'
        ]
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
        'UINT8'      => function ($data) {
            return hexdec($data);
        },
        'UINT16'     => function ($data) use ($reverse_bytes) {
            return hexdec($reverse_bytes($data));
        },
        'UINT32'     => function ($data) use ($reverse_bytes) {
            return hexdec($reverse_bytes($data));
        },
        'INT8'       => function ($data) {
            $v = unpack("l", pack("l", hexdec($data)));
            return reset($v);
        },
        'INT16'      => function ($data) use ($reverse_bytes) {
            $v = unpack("l", pack("l", hexdec($reverse_bytes($data))));
            return reset($v);
        },
        'INT32'      => function ($data) use ($reverse_bytes) {
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
        }
    ];

    $cid_sid = substr($payload, 0, 4);
    $payload_length = strlen($payload);
    $offset = 4;

    if (empty($message_types[$cid_sid]) || !is_array($message_types[$cid_sid])) {
        return ['Unknown Message' => $formatters['HEX']($payload)];
    }

    $output = ['CID-SID' => $formatters['HEX']($cid_sid)];

    foreach ($message_types[$cid_sid] as $part) {
        $tmp = explode('|', $part);
        $label = $tmp[0];
        $bcount = $tmp[1];
        $formatter = ($format_values && !empty($tmp[2]) && !empty($formatters[$tmp[2]])) ? $formatters[$tmp[2]] : $formatters['HEX'];
        // composite length
        if (strstr($bcount, '*') !== false) {
            $factors = explode('*', $bcount);
            $count = (int) $factors[0];
            $lookup = $factors[1];

            $total = hexdec($output[$lookup]);
            for ($i = 0; $i < $total; $i++) {
                $output[$label . ' ' . ($i + 1)] = $formatter(substr($payload, $offset + ($i * $count), $count));
            }
            $offset += $count * $total;
            // simple length
        } else {
            $output[$label] = $formatter(substr($payload, $offset, $bcount));
            $offset += (int) $bcount;
        }
    }

    if ($offset < $payload_length) {
        $output['Leftover Bytes'] = $formatters['HEX'](substr($payload, $offset, $payload_length - $offset));
    }

    return $output;
}

function node_store_startup_info($input, $date_time)
{

    $conn = mab_db_connect();

    //NB: this error switch off is just temporary while I work on average calc
    error_reporting(E_ALL & ~E_NOTICE);
    ini_set('display_startup_errors', 0);
    ini_set('display_errors', 0);

    // foreach($input as $key => $val) {

    //     $boot_msg = $key == "CID-SID" ? $val : null;
    //     $firmware_major = $key == "Firmware Major" ? $val : null;
    //     $firmware_minor = $key == "Firmware Minor" ? $val : null;
    //     $firmware_point = $key == "Firmware Point" ? $val : null;
    //     // $firmware_version1 = $firmware_major.$firmware_minor.$firmware_point;

    // }
    // print_r($firmware_point);

    // node setup info 
    // if (isset($input['SDI Address']) || isset ($input['Batt.Voltage'])) {


    // if (!empty($input["Firmware Major"]) || !empty($input["Firmware Minor"]) || !empty($input["Firmware Point"]) || !empty($input["ID String"]) || !empty($input["SDI Address"])) {


    $firmware_version = $input["Firmware Major"] . '.' . $input["Firmware Minor"] . '.' . $input["Firmware Point"];


    // if (!empty($input["ID String"])) {
    $re = '~[^0]+~';
    $probe_id = $input["ID String"];
    preg_match_all($re, $probe_id, $probe_type, PREG_SET_ORDER, 0);
    // if (!empty($probe_type)) {
    $probe_type = implode("", $probe_type[0]);
    // } else {
    //     echo 'probe type is empty';
    // }

    $re2 = '~[^I-S]+~m';
    preg_match_all($re2, $probe_id, $probe_firmware, PREG_SET_ORDER, 0);
    // if (!empty($probe_firmware)) {
    $probe_firmware = implode("", $probe_firmware[2]);
    // } else {
    //     echo 'probe firmware is empty';
    // }

    $re3 = '~[^S,}]+~m';
    preg_match_all($re3, $probe_id, $probe_serial, PREG_SET_ORDER, 0);
    // if (!empty($probe_serial)) {
    $probe_serial = implode("", $probe_serial[2]);
    // } else {
    //     echo 'probe serial is empty';
    // }
    //attempt to create unique key for the table. Is not perfected 
    $uniq = $date_time;

    mab_db_query($conn, 'INSERT INTO `agri`.`node_startup_info` (`boot_msg`, `node_id`, `date_time`, `batt_voltage`, `sdi_address`, `probe_type`, `probe_firmware`, `probe_serial`, `uniq_val`, `node_firmware`) VALUES ("' . $input["CID-SID"] . '", "0x000d129c", "' . $date_time . '", "' . $input["Batt.Voltage"] . '", "' . $input["SDI Address"] . '", "' . $probe_type . '", "' . $probe_firmware . '", "' . $probe_serial . '", "' . $uniq . '", "' . $firmware_version . '") ON DUPLICATE KEY UPDATE id = VALUES(id)');

    mab_db_query($conn, 'UPDATE node_startup_info AS t1 SET t1.batt_voltage = ( SELECT MAX( t2.batt_voltage ) FROM (SELECT * FROM node_startup_info AS N2) AS t2 WHERE t2.batt_voltage != "" AND t2.date_time BETWEEN DATE_SUB( t1.date_time, INTERVAL 8 MINUTE ) AND DATE_ADD( t1.date_time, INTERVAL 8 MINUTE ) ) WHERE t1.batt_voltage = ""');

    mab_db_query($conn, 'DELETE FROM `node_startup_info` WHERE boot_msg IN ("02 05 ", "02 04 ", "03 01 ", "01 02 ")');
    // }

}

//calculate ave based on reading values not sensor count
function calc_average_on_reading_values($input, $date_time, $nodeAddress, $messsage_id_2)
{
    $conn = mab_db_connect();

    $accumulative = (float)$input["Moisture Reading 1"] + (float)$input["Moisture Reading 2"] + (float)$input["Moisture Reading 3"] + (float) $input["Moisture Reading 4"] + (float)$input["Moisture Reading 5"] + (float)$input["Moisture Reading 6"];

    $sum = 0;

    $m_values = [
        $input["Moisture Reading 1"],
        $input["Moisture Reading 2"],
        $input["Moisture Reading 3"],
        $input["Moisture Reading 4"],
        $input["Moisture Reading 5"],
        $input["Moisture Reading 6"]
    ];

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

    // print_r($ave);

    // if ($input["Moisture Reading 1"] != NULL) {
    //     $count = 1;
    // }

    // print_r($nodeAddress);

    // util function
    $calcBatt = function ($reading, $lower, $upper) {
        $range = $upper - $lower;
        $delta = $reading - $lower;
        if ($delta <= 0) $delta = 0;
        $level = ($delta / $range) * 100;
        return $level < 0 ? 0 : ($level > 100 ? 100 : $level);
    };

    $bp = $calcBatt((float)$input["Batt.Voltage"], 3500, 4200);


    mab_db_query($conn, 'ALTER TABLE `agri_sandbox`.`node_data` ADD UNIQUE(message_id_2)');
    mab_db_query($conn, 'INSERT INTO `agri_sandbox`.`node_data`(`probe_id`, `date_time`, `average`, `accumulative`, `sm1`, `sm2`, `sm3`, `sm4`, `sm5`, `sm6`, `t1`, `t2`, `t3`, `t4`, `t5`, `t6`, `rg`, `bv`, `bp`, `ambient_temp`, `message_id_1`, `message_id_2`) 

        VALUES (
            "' . $nodeAddress . '",
            "' . $date_time . '",
            "' . (float)$ave . '", 
            "' . (float)$sum . '", 
            "' . (float)$input["Moisture Reading 1"] . '", 
            "' . (float)$input["Moisture Reading 2"] . '", 
            "' . (float)$input["Moisture Reading 3"] . '", 
            "' . (float)$input["Moisture Reading 4"] . '", 
            "' . (float)$input["Moisture Reading 5"] . '", 
            "' . (float)$input["Moisture Reading 6"] . '", 
            "' . (float)$input["Temp. Reading 1"] . '", 
            "' . (float)$input["Temp. Reading 2"] . '", 
            "' . (float)$input["Temp. Reading 3"] . '", 
            "' . (float)$input["Temp. Reading 4"] . '", 
            "' . (float)$input["Temp. Reading 5"] . '", 
            "' . (float)$input["Temp. Reading 6"] . '", 
            "0", 
            "' . (float)$input["Batt.Voltage"] . '", 
            "' . (float)$bp . '",
            "' . (float)$input["Internal Temp"] . '", 
            "int", 
            "' . $messsage_id_2 . '") 
            ON DUPLICATE KEY UPDATE id = VALUES(id)');
}

function intellect_payload_format_array($input, $prefix = '', $date_time, $nodeAddress = '', $messsage_id_2 = '')
{

    $output = "<div class='flex-row beg_values'>";
    $i = 1;

    $node_address = $nodeAddress . '-' . $input['Probe Address'];

    calc_average_on_reading_values($input, $date_time, $node_address, $messsage_id_2);

    node_store_startup_info($input, $date_time);

    foreach ($input as $label => $element) {
        $element = trim($element, ' ');
        $output .= "<div class='col-md-4'>$label: <strong class='hov' id='{$prefix}{$i}'>$element</strong></div>";
        $i++;
    }
    $output .= "</div>";

    return $output;
}

function intellect_payload_format_hex_pipes_with_ids($input, $prefix = 'end')
{
    $counter = 0;
    return
        "<strong class='end_values'>" .
        implode(
            ' | ',
            array_map(function ($i) use ($prefix, &$counter) {
                $counter++;
                return "<span class='hov' id='{$prefix}{$counter}'>{$i}</span>";
            }, $input)
        ) .
        "</strong>";
}

//insert raw_data_b64 into db
function insert_b64($nodeId, $payload, $date, $message_id)
{
    $conn = mab_db_connect();
    mab_db_query($conn, 'ALTER TABLE raw_data_b64 ADD UNIQUE(b64_data)');
    mab_db_query($conn, 'INSERT INTO raw_data_b64 (device_id, b64_data, timestamp, message_id) VALUES ("' . $nodeId . '" , "' . $payload . '" , "' . $date . '", "' . $message_id . '") ON DUPLICATE KEY UPDATE message_id = VALUES(message_id)');

    //update query for if there is a need to manually update the date
    // mab_db_query($conn, 'UPDATE raw_data_b64 SET timestamp = "' . $date . '" WHERE b64_data =  "' . $payload . '"'); 
}

/* MAIN */

$token = intellect_authenticate('mab@liquidfibre.com', 'M@b123456');
if (!$token) {
    echo "<p>failed to authenticate</p>";
    exit;
}

$message_id = isset($_GET['message_id']) ? $_GET['message_id'] : '';
$node_id_filter = isset($_GET['node_id']) ? $_GET['node_id'] : '';
$data = intellect_receive($token, $message_id);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intellect Output</title>
    <style>
        body {
            background: darkseagreen;
        }

        .row {
            position: relative;
            display: block;
            padding: 0.25rem;
            outline: 1px solid #ddd;
            margin: 0;
            margin-bottom: 0.5rem;
            background: white;
        }

        .row ul {
            list-style: none;
        }

        .row ul li {
            list-style-type: none;
        }

        .row ul li strong {
            color: #777;
        }

        .row .num {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 4rem;
            color: #999;
            font-weight: bold;
        }

        .flex-row {
            display: flex;
            flex-flow: row wrap;
        }

        .flex-row .col-md-4 {
            display: block;
            width: 25%;
        }

        @media(max-width:767px) {
            .flex-row .col-md-4 {
                width: 100%;
            }
        }
    </style>
    <script>
        /*! LeaderLine v1.0.5 (c) anseki https://anseki.github.io/leader-line/ */
        var LeaderLine = function() {
            "use strict";
            var te, g, y, S, _, o, t, h, f, p, a, i, l, v = "leader-line",
                M = 1,
                I = 2,
                C = 3,
                L = 4,
                n = {
                    top: M,
                    right: I,
                    bottom: C,
                    left: L
                },
                A = 1,
                V = 2,
                P = 3,
                N = 4,
                T = 5,
                m = {
                    straight: A,
                    arc: V,
                    fluid: P,
                    magnet: N,
                    grid: T
                },
                ne = "behind",
                r = v + "-defs",
                s = '<svg xmlns="http://www.w3.org/2000/svg" version="1.1" id="leader-line-defs"><style><![CDATA[.leader-line{position:absolute;overflow:visible!important;pointer-events:none!important;font-size:16px}#leader-line-defs{width:0;height:0;position:absolute;left:0;top:0}.leader-line-line-path{fill:none}.leader-line-mask-bg-rect{fill:#fff}.leader-line-caps-mask-anchor,.leader-line-caps-mask-marker-shape{fill:#000}.leader-line-caps-mask-anchor{stroke:#000}.leader-line-caps-mask-line,.leader-line-plugs-face{stroke:transparent}.leader-line-line-mask-shape{stroke:#fff}.leader-line-line-outline-mask-shape{stroke:#000}.leader-line-plug-mask-shape{fill:#fff;stroke:#000}.leader-line-plug-outline-mask-shape{fill:#000;stroke:#fff}.leader-line-areaAnchor{position:absolute;overflow:visible!important}]]></style><defs><circle id="leader-line-disc" cx="0" cy="0" r="5"/><rect id="leader-line-square" x="-5" y="-5" width="10" height="10"/><polygon id="leader-line-arrow1" points="-8,-8 8,0 -8,8 -5,0"/><polygon id="leader-line-arrow2" points="-4,-8 4,0 -4,8 -7,5 -2,0 -7,-5"/><polygon id="leader-line-arrow3" points="-4,-5 8,0 -4,5"/><g id="leader-line-hand"><path style="fill: #fcfcfc" d="M9.19 11.14h4.75c1.38 0 2.49-1.11 2.49-2.49 0-.51-.15-.98-.41-1.37h1.3c1.38 0 2.49-1.11 2.49-2.49s-1.11-2.53-2.49-2.53h1.02c1.38 0 2.49-1.11 2.49-2.49s-1.11-2.49-2.49-2.49h14.96c1.37 0 2.49-1.11 2.49-2.49s-1.11-2.49-2.49-2.49H16.58C16-9.86 14.28-11.14 9.7-11.14c-4.79 0-6.55 3.42-7.87 4.73H-2.14v13.23h3.68C3.29 9.97 5.47 11.14 9.19 11.14L9.19 11.14Z"/><path style="fill: black" d="M13.95 12c1.85 0 3.35-1.5 3.35-3.35 0-.17-.02-.34-.04-.51h.07c1.85 0 3.35-1.5 3.35-3.35 0-.79-.27-1.51-.72-2.08 1.03-.57 1.74-1.67 1.74-2.93 0-.59-.16-1.15-.43-1.63h12.04c1.85 0 3.35-1.5 3.35-3.35 0-1.85-1.5-3.35-3.35-3.35H17.2C16.26-10.93 13.91-12 9.7-12 5.36-12 3.22-9.4 1.94-7.84c0 0-.29.33-.5.57-.63 0-3.58 0-3.58 0C-2.61-7.27-3-6.88-3-6.41v13.23c0 .47.39.86.86.86 0 0 2.48 0 3.2 0C2.9 10.73 5.29 12 9.19 12L13.95 12ZM9.19 10.28c-3.46 0-5.33-1.05-6.9-3.87-.15-.27-.44-.44-.75-.44 0 0-1.81 0-2.82 0V-5.55c1.06 0 3.11 0 3.11 0 .25 0 .44-.06.61-.25l.83-.95c1.23-1.49 2.91-3.53 6.43-3.53 3.45 0 4.9.74 5.57 1.72h-4.3c-.48 0-.86.38-.86.86s.39.86.86.86h22.34c.9 0 1.63.73 1.63 1.63 0 .9-.73 1.63-1.63 1.63H15.83c-.48 0-.86.38-.86.86 0 .47.39.86.86.86h2.52c.9 0 1.63.73 1.63 1.63s-.73 1.63-1.63 1.63h-3.12c-.48 0-.86.38-.86.86 0 .47.39.86.86.86h2.11c.88 0 1.63.76 1.63 1.67 0 .9-.73 1.63-1.63 1.63h-3.2c-.48 0-.86.39-.86.86 0 .47.39.86.86.86h1.36c.05.16.09.34.09.51 0 .9-.73 1.63-1.63 1.63C13.95 10.28 9.19 10.28 9.19 10.28Z"/></g><g id="leader-line-crosshair"><path d="M0-78.97c-43.54 0-78.97 35.43-78.97 78.97 0 43.54 35.43 78.97 78.97 78.97s78.97-35.43 78.97-78.97C78.97-43.54 43.55-78.97 0-78.97ZM76.51-1.21h-9.91v-9.11h-2.43v9.11h-11.45c-.64-28.12-23.38-50.86-51.5-51.5V-64.17h9.11V-66.6h-9.11v-9.91C42.46-75.86 75.86-42.45 76.51-1.21ZM-1.21-30.76h-9.11v2.43h9.11V-4.2c-1.44.42-2.57 1.54-2.98 2.98H-28.33v-9.11h-2.43v9.11H-50.29C-49.65-28-27.99-49.65-1.21-50.29V-30.76ZM-30.76 1.21v9.11h2.43v-9.11H-4.2c.42 1.44 1.54 2.57 2.98 2.98v24.13h-9.11v2.43h9.11v19.53C-27.99 49.65-49.65 28-50.29 1.21H-30.76ZM1.22 30.75h9.11v-2.43h-9.11V4.2c1.44-.42 2.56-1.54 2.98-2.98h24.13v9.11h2.43v-9.11h19.53C49.65 28 28 49.65 1.22 50.29V30.75ZM30.76-1.21v-9.11h-2.43v9.11H4.2c-.42-1.44-1.54-2.56-2.98-2.98V-28.33h9.11v-2.43h-9.11V-50.29C28-49.65 49.65-28 50.29-1.21H30.76ZM-1.21-76.51v9.91h-9.11v2.43h9.11v11.45c-28.12.64-50.86 23.38-51.5 51.5H-64.17v-9.11H-66.6v9.11h-9.91C-75.86-42.45-42.45-75.86-1.21-76.51ZM-76.51 1.21h9.91v9.11h2.43v-9.11h11.45c.64 28.12 23.38 50.86 51.5 51.5v11.45h-9.11v2.43h9.11v9.91C-42.45 75.86-75.86 42.45-76.51 1.21ZM1.22 76.51v-9.91h9.11v-2.43h-9.11v-11.45c28.12-.64 50.86-23.38 51.5-51.5h11.45v9.11h2.43v-9.11h9.91C75.86 42.45 42.45 75.86 1.22 76.51Z"/><path d="M0 83.58-7.1 96 7.1 96Z"/><path d="M0-83.58 7.1-96-7.1-96"/><path d="M83.58 0 96 7.1 96-7.1Z"/><path d="M-83.58 0-96-7.1-96 7.1Z"/></g></defs></svg>',
                ae = {
                    disc: {
                        elmId: "leader-line-disc",
                        noRotate: !0,
                        bBox: {
                            left: -5,
                            top: -5,
                            width: 10,
                            height: 10,
                            right: 5,
                            bottom: 5
                        },
                        widthR: 2.5,
                        heightR: 2.5,
                        bCircle: 5,
                        sideLen: 5,
                        backLen: 5,
                        overhead: 0,
                        outlineBase: 1,
                        outlineMax: 4
                    },
                    square: {
                        elmId: "leader-line-square",
                        noRotate: !0,
                        bBox: {
                            left: -5,
                            top: -5,
                            width: 10,
                            height: 10,
                            right: 5,
                            bottom: 5
                        },
                        widthR: 2.5,
                        heightR: 2.5,
                        bCircle: 5,
                        sideLen: 5,
                        backLen: 5,
                        overhead: 0,
                        outlineBase: 1,
                        outlineMax: 4
                    },
                    arrow1: {
                        elmId: "leader-line-arrow1",
                        bBox: {
                            left: -8,
                            top: -8,
                            width: 16,
                            height: 16,
                            right: 8,
                            bottom: 8
                        },
                        widthR: 4,
                        heightR: 4,
                        bCircle: 8,
                        sideLen: 8,
                        backLen: 8,
                        overhead: 8,
                        outlineBase: 2,
                        outlineMax: 1.5
                    },
                    arrow2: {
                        elmId: "leader-line-arrow2",
                        bBox: {
                            left: -7,
                            top: -8,
                            width: 11,
                            height: 16,
                            right: 4,
                            bottom: 8
                        },
                        widthR: 2.75,
                        heightR: 4,
                        bCircle: 8,
                        sideLen: 8,
                        backLen: 7,
                        overhead: 4,
                        outlineBase: 1,
                        outlineMax: 1.75
                    },
                    arrow3: {
                        elmId: "leader-line-arrow3",
                        bBox: {
                            left: -4,
                            top: -5,
                            width: 12,
                            height: 10,
                            right: 8,
                            bottom: 5
                        },
                        widthR: 3,
                        heightR: 2.5,
                        bCircle: 8,
                        sideLen: 5,
                        backLen: 4,
                        overhead: 8,
                        outlineBase: 1,
                        outlineMax: 2.5
                    },
                    hand: {
                        elmId: "leader-line-hand",
                        bBox: {
                            left: -3,
                            top: -12,
                            width: 40,
                            height: 24,
                            right: 37,
                            bottom: 12
                        },
                        widthR: 10,
                        heightR: 6,
                        bCircle: 37,
                        sideLen: 12,
                        backLen: 3,
                        overhead: 37
                    },
                    crosshair: {
                        elmId: "leader-line-crosshair",
                        noRotate: !0,
                        bBox: {
                            left: -96,
                            top: -96,
                            width: 192,
                            height: 192,
                            right: 96,
                            bottom: 96
                        },
                        widthR: 48,
                        heightR: 48,
                        bCircle: 96,
                        sideLen: 96,
                        backLen: 96,
                        overhead: 0
                    }
                },
                E = {
                    behind: ne,
                    disc: "disc",
                    square: "square",
                    arrow1: "arrow1",
                    arrow2: "arrow2",
                    arrow3: "arrow3",
                    hand: "hand",
                    crosshair: "crosshair"
                },
                ie = {
                    disc: "disc",
                    square: "square",
                    arrow1: "arrow1",
                    arrow2: "arrow2",
                    arrow3: "arrow3",
                    hand: "hand",
                    crosshair: "crosshair"
                },
                W = [M, I, C, L],
                x = "auto",
                oe = {
                    x: "left",
                    y: "top",
                    width: "width",
                    height: "height"
                },
                B = 80,
                R = 4,
                F = 5,
                G = 120,
                D = 8,
                z = 3.75,
                j = 10,
                H = 30,
                U = .5522847,
                Z = .25 * Math.PI,
                u = /^\s*(\-?[\d\.]+)\s*(\%)?\s*$/,
                b = "http://www.w3.org/2000/svg",
                e = "-ms-scroll-limit" in document.documentElement.style && "-ms-ime-align" in document.documentElement.style && !window.navigator.msPointerEnabled,
                le = !e && !!document.uniqueID,
                re = "MozAppearance" in document.documentElement.style,
                se = !(e || re || !window.chrome || !window.CSS),
                ue = !e && !le && !re && !se && !window.chrome && "WebkitAppearance" in document.documentElement.style,
                he = le || e ? .2 : .1,
                pe = {
                    path: P,
                    lineColor: "coral",
                    lineSize: 4,
                    plugSE: [ne, "arrow1"],
                    plugSizeSE: [1, 1],
                    lineOutlineEnabled: !1,
                    lineOutlineColor: "indianred",
                    lineOutlineSize: .25,
                    plugOutlineEnabledSE: [!1, !1],
                    plugOutlineSizeSE: [1, 1]
                },
                k = (a = {}.toString, i = {}.hasOwnProperty.toString, l = i.call(Object), function(e) {
                    var t, n;
                    return e && "[object Object]" === a.call(e) && (!(t = Object.getPrototypeOf(e)) || (n = t.hasOwnProperty("constructor") && t.constructor) && "function" == typeof n && i.call(n) === l)
                }),
                w = Number.isFinite || function(e) {
                    return "number" == typeof e && window.isFinite(e)
                },
                c = function() {
                    var e, x = {
                            ease: [.25, .1, .25, 1],
                            linear: [0, 0, 1, 1],
                            "ease-in": [.42, 0, 1, 1],
                            "ease-out": [0, 0, .58, 1],
                            "ease-in-out": [.42, 0, .58, 1]
                        },
                        b = 1e3 / 60 / 2,
                        t = window.requestAnimationFrame || window.mozRequestAnimationFrame || window.webkitRequestAnimationFrame || window.msRequestAnimationFrame || function(e) {
                            setTimeout(e, b)
                        },
                        n = window.cancelAnimationFrame || window.mozCancelAnimationFrame || window.webkitCancelAnimationFrame || window.msCancelAnimationFrame || function(e) {
                            clearTimeout(e)
                        },
                        a = Number.isFinite || function(e) {
                            return "number" == typeof e && window.isFinite(e)
                        },
                        k = [],
                        w = 0;

                    function l() {
                        var i = Date.now(),
                            o = !1;
                        e && (n.call(window, e), e = null), k.forEach(function(e) {
                            var t, n, a;
                            if (e.framesStart) {
                                if ((t = i - e.framesStart) >= e.duration && e.count && e.loopsLeft <= 1) return a = e.frames[e.lastFrame = e.reverse ? 0 : e.frames.length - 1], e.frameCallback(a.value, !0, a.timeRatio, a.outputRatio), void(e.framesStart = null);
                                if (t > e.duration) {
                                    if (n = Math.floor(t / e.duration), e.count) {
                                        if (n >= e.loopsLeft) return a = e.frames[e.lastFrame = e.reverse ? 0 : e.frames.length - 1], e.frameCallback(a.value, !0, a.timeRatio, a.outputRatio), void(e.framesStart = null);
                                        e.loopsLeft -= n
                                    }
                                    e.framesStart += e.duration * n, t = i - e.framesStart
                                }
                                e.reverse && (t = e.duration - t), a = e.frames[e.lastFrame = Math.round(t / b)], !1 !== e.frameCallback(a.value, !1, a.timeRatio, a.outputRatio) ? o = !0 : e.framesStart = null
                            }
                        }), o && (e = t.call(window, l))
                    }

                    function O(e, t) {
                        e.framesStart = Date.now(), null != t && (e.framesStart -= e.duration * (e.reverse ? 1 - t : t)), e.loopsLeft = e.count, e.lastFrame = null, l()
                    }
                    return {
                        add: function(n, e, t, a, i, o, l) {
                            var r, s, u, h, p, c, d, f, y, S, m, g, _, v = ++w;

                            function E(e, t) {
                                return {
                                    value: n(t),
                                    timeRatio: e,
                                    outputRatio: t
                                }
                            }
                            if ("string" == typeof i && (i = x[i]), n = n || function() {}, t < b) s = [E(0, 0), E(1, 1)];
                            else {
                                if (u = b / t, s = [E(0, 0)], 0 === i[0] && 0 === i[1] && 1 === i[2] && 1 === i[3])
                                    for (p = u; p <= 1; p += u) s.push(E(p, p));
                                else
                                    for (c = h = (p = u) / 10; c <= 1; c += h) void 0, S = (y = (f = c) * f) * f, _ = 3 * (m = 1 - f) * y, p <= (d = {
                                        x: (g = 3 * (m * m) * f) * i[0] + _ * i[2] + S,
                                        y: g * i[1] + _ * i[3] + S
                                    }).x && (s.push(E(d.x, d.y)), p += u);
                                s.push(E(1, 1))
                            }
                            return r = {
                                animId: v,
                                frameCallback: e,
                                duration: t,
                                count: a,
                                frames: s,
                                reverse: !!o
                            }, k.push(r), !1 !== l && O(r, l), v
                        },
                        remove: function(n) {
                            var a;
                            k.some(function(e, t) {
                                return e.animId === n && (a = t, !(e.framesStart = null))
                            }) && k.splice(a, 1)
                        },
                        start: function(t, n, a) {
                            k.some(function(e) {
                                return e.animId === t && (e.reverse = !!n, O(e, a), !0)
                            })
                        },
                        stop: function(t, n) {
                            var a;
                            return k.some(function(e) {
                                return e.animId === t && (n ? null != e.lastFrame && (a = e.frames[e.lastFrame].timeRatio) : (a = (Date.now() - e.framesStart) / e.duration, e.reverse && (a = 1 - a), a < 0 ? a = 0 : 1 < a && (a = 1)), !(e.framesStart = null))
                            }), a
                        },
                        validTiming: function(t) {
                            return "string" == typeof t ? x[t] : Array.isArray(t) && [0, 1, 2, 3].every(function(e) {
                                return a(t[e]) && 0 <= t[e] && t[e] <= 1
                            }) ? [t[0], t[1], t[2], t[3]] : null
                        }
                    }
                }(),
                d = function(e) {
                    e.SVGPathElement.prototype.getPathData && e.SVGPathElement.prototype.setPathData || function() {
                        var i = {
                                Z: "Z",
                                M: "M",
                                L: "L",
                                C: "C",
                                Q: "Q",
                                A: "A",
                                H: "H",
                                V: "V",
                                S: "S",
                                T: "T",
                                z: "Z",
                                m: "m",
                                l: "l",
                                c: "c",
                                q: "q",
                                a: "a",
                                h: "h",
                                v: "v",
                                s: "s",
                                t: "t"
                            },
                            o = function(e) {
                                this._string = e, this._currentIndex = 0, this._endIndex = this._string.length, this._prevCommand = null, this._skipOptionalSpaces()
                            },
                            l = -1 !== e.navigator.userAgent.indexOf("MSIE ");
                        o.prototype = {
                            parseSegment: function() {
                                var e = this._string[this._currentIndex],
                                    t = i[e] ? i[e] : null;
                                if (null === t) {
                                    if (null === this._prevCommand) return null;
                                    if (null === (t = ("+" === e || "-" === e || "." === e || "0" <= e && e <= "9") && "Z" !== this._prevCommand ? "M" === this._prevCommand ? "L" : "m" === this._prevCommand ? "l" : this._prevCommand : null)) return null
                                } else this._currentIndex += 1;
                                var n = null,
                                    a = (this._prevCommand = t).toUpperCase();
                                return "H" === a || "V" === a ? n = [this._parseNumber()] : "M" === a || "L" === a || "T" === a ? n = [this._parseNumber(), this._parseNumber()] : "S" === a || "Q" === a ? n = [this._parseNumber(), this._parseNumber(), this._parseNumber(), this._parseNumber()] : "C" === a ? n = [this._parseNumber(), this._parseNumber(), this._parseNumber(), this._parseNumber(), this._parseNumber(), this._parseNumber()] : "A" === a ? n = [this._parseNumber(), this._parseNumber(), this._parseNumber(), this._parseArcFlag(), this._parseArcFlag(), this._parseNumber(), this._parseNumber()] : "Z" === a && (this._skipOptionalSpaces(), n = []), null === n || 0 <= n.indexOf(null) ? null : {
                                    type: t,
                                    values: n
                                }
                            },
                            hasMoreData: function() {
                                return this._currentIndex < this._endIndex
                            },
                            peekSegmentType: function() {
                                var e = this._string[this._currentIndex];
                                return i[e] ? i[e] : null
                            },
                            initialCommandIsMoveTo: function() {
                                if (!this.hasMoreData()) return !0;
                                var e = this.peekSegmentType();
                                return "M" === e || "m" === e
                            },
                            _isCurrentSpace: function() {
                                var e = this._string[this._currentIndex];
                                return e <= " " && (" " === e || "\n" === e || "\t" === e || "\r" === e || "\f" === e)
                            },
                            _skipOptionalSpaces: function() {
                                for (; this._currentIndex < this._endIndex && this._isCurrentSpace();) this._currentIndex += 1;
                                return this._currentIndex < this._endIndex
                            },
                            _skipOptionalSpacesOrDelimiter: function() {
                                return !(this._currentIndex < this._endIndex && !this._isCurrentSpace() && "," !== this._string[this._currentIndex]) && (this._skipOptionalSpaces() && this._currentIndex < this._endIndex && "," === this._string[this._currentIndex] && (this._currentIndex += 1, this._skipOptionalSpaces()), this._currentIndex < this._endIndex)
                            },
                            _parseNumber: function() {
                                var e = 0,
                                    t = 0,
                                    n = 1,
                                    a = 0,
                                    i = 1,
                                    o = 1,
                                    l = this._currentIndex;
                                if (this._skipOptionalSpaces(), this._currentIndex < this._endIndex && "+" === this._string[this._currentIndex] ? this._currentIndex += 1 : this._currentIndex < this._endIndex && "-" === this._string[this._currentIndex] && (this._currentIndex += 1, i = -1), this._currentIndex === this._endIndex || (this._string[this._currentIndex] < "0" || "9" < this._string[this._currentIndex]) && "." !== this._string[this._currentIndex]) return null;
                                for (var r = this._currentIndex; this._currentIndex < this._endIndex && "0" <= this._string[this._currentIndex] && this._string[this._currentIndex] <= "9";) this._currentIndex += 1;
                                if (this._currentIndex !== r)
                                    for (var s = this._currentIndex - 1, u = 1; r <= s;) t += u * (this._string[s] - "0"), s -= 1, u *= 10;
                                if (this._currentIndex < this._endIndex && "." === this._string[this._currentIndex]) {
                                    if (this._currentIndex += 1, this._currentIndex >= this._endIndex || this._string[this._currentIndex] < "0" || "9" < this._string[this._currentIndex]) return null;
                                    for (; this._currentIndex < this._endIndex && "0" <= this._string[this._currentIndex] && this._string[this._currentIndex] <= "9";) n *= 10, a += (this._string.charAt(this._currentIndex) - "0") / n, this._currentIndex += 1
                                }
                                if (this._currentIndex !== l && this._currentIndex + 1 < this._endIndex && ("e" === this._string[this._currentIndex] || "E" === this._string[this._currentIndex]) && "x" !== this._string[this._currentIndex + 1] && "m" !== this._string[this._currentIndex + 1]) {
                                    if (this._currentIndex += 1, "+" === this._string[this._currentIndex] ? this._currentIndex += 1 : "-" === this._string[this._currentIndex] && (this._currentIndex += 1, o = -1), this._currentIndex >= this._endIndex || this._string[this._currentIndex] < "0" || "9" < this._string[this._currentIndex]) return null;
                                    for (; this._currentIndex < this._endIndex && "0" <= this._string[this._currentIndex] && this._string[this._currentIndex] <= "9";) e *= 10, e += this._string[this._currentIndex] - "0", this._currentIndex += 1
                                }
                                var h = t + a;
                                return h *= i, e && (h *= Math.pow(10, o * e)), l === this._currentIndex ? null : (this._skipOptionalSpacesOrDelimiter(), h)
                            },
                            _parseArcFlag: function() {
                                if (this._currentIndex >= this._endIndex) return null;
                                var e = null,
                                    t = this._string[this._currentIndex];
                                if (this._currentIndex += 1, "0" === t) e = 0;
                                else {
                                    if ("1" !== t) return null;
                                    e = 1
                                }
                                return this._skipOptionalSpacesOrDelimiter(), e
                            }
                        };
                        var a = function(e) {
                                if (!e || 0 === e.length) return [];
                                var t = new o(e),
                                    n = [];
                                if (t.initialCommandIsMoveTo())
                                    for (; t.hasMoreData();) {
                                        var a = t.parseSegment();
                                        if (null === a) break;
                                        n.push(a)
                                    }
                                return n
                            },
                            n = e.SVGPathElement.prototype.setAttribute,
                            r = e.SVGPathElement.prototype.removeAttribute,
                            d = e.Symbol ? e.Symbol() : "__cachedPathData",
                            f = e.Symbol ? e.Symbol() : "__cachedNormalizedPathData",
                            U = function(e, t, n, a, i, o, l, r, s, u) {
                                var h, p, c, d, f, y = function(e, t, n) {
                                        return {
                                            x: e * Math.cos(n) - t * Math.sin(n),
                                            y: e * Math.sin(n) + t * Math.cos(n)
                                        }
                                    },
                                    S = (h = l, Math.PI * h / 180),
                                    m = [];
                                if (u) p = u[0], c = u[1], d = u[2], f = u[3];
                                else {
                                    var g = y(e, t, -S);
                                    e = g.x, t = g.y;
                                    var _ = y(n, a, -S),
                                        v = (e - (n = _.x)) / 2,
                                        E = (t - (a = _.y)) / 2,
                                        x = v * v / (i * i) + E * E / (o * o);
                                    1 < x && (i *= x = Math.sqrt(x), o *= x);
                                    var b = i * i,
                                        k = o * o,
                                        w = b * k - b * E * E - k * v * v,
                                        O = b * E * E + k * v * v,
                                        M = (r === s ? -1 : 1) * Math.sqrt(Math.abs(w / O));
                                    d = M * i * E / o + (e + n) / 2, f = M * -o * v / i + (t + a) / 2, p = Math.asin(parseFloat(((t - f) / o).toFixed(9))), c = Math.asin(parseFloat(((a - f) / o).toFixed(9))), e < d && (p = Math.PI - p), n < d && (c = Math.PI - c), p < 0 && (p = 2 * Math.PI + p), c < 0 && (c = 2 * Math.PI + c), s && c < p && (p -= 2 * Math.PI), !s && p < c && (c -= 2 * Math.PI)
                                }
                                var I = c - p;
                                if (Math.abs(I) > 120 * Math.PI / 180) {
                                    var C = c,
                                        L = n,
                                        A = a;
                                    c = s && p < c ? p + 120 * Math.PI / 180 * 1 : p + 120 * Math.PI / 180 * -1, n = d + i * Math.cos(c), a = f + o * Math.sin(c), m = U(n, a, L, A, i, o, l, 0, s, [c, C, d, f])
                                }
                                I = c - p;
                                var V = Math.cos(p),
                                    P = Math.sin(p),
                                    N = Math.cos(c),
                                    T = Math.sin(c),
                                    W = Math.tan(I / 4),
                                    B = 4 / 3 * i * W,
                                    R = 4 / 3 * o * W,
                                    F = [e, t],
                                    G = [e + B * P, t - R * V],
                                    D = [n + B * T, a - R * N],
                                    z = [n, a];
                                if (G[0] = 2 * F[0] - G[0], G[1] = 2 * F[1] - G[1], u) return [G, D, z].concat(m);
                                m = [G, D, z].concat(m).join().split(",");
                                var j = [],
                                    H = [];
                                return m.forEach(function(e, t) {
                                    t % 2 ? H.push(y(m[t - 1], m[t], S).y) : H.push(y(m[t], m[t + 1], S).x), 6 === H.length && (j.push(H), H = [])
                                }), j
                            },
                            y = function(e) {
                                return e.map(function(e) {
                                    return {
                                        type: e.type,
                                        values: Array.prototype.slice.call(e.values)
                                    }
                                })
                            },
                            S = function(e) {
                                var S = [],
                                    m = null,
                                    g = null,
                                    _ = null,
                                    v = null,
                                    E = null,
                                    x = null,
                                    b = null;
                                return e.forEach(function(e) {
                                    if ("M" === e.type) {
                                        var t = e.values[0],
                                            n = e.values[1];
                                        S.push({
                                            type: "M",
                                            values: [t, n]
                                        }), v = x = t, E = b = n
                                    } else if ("C" === e.type) {
                                        var a = e.values[0],
                                            i = e.values[1],
                                            o = e.values[2],
                                            l = e.values[3];
                                        t = e.values[4], n = e.values[5];
                                        S.push({
                                            type: "C",
                                            values: [a, i, o, l, t, n]
                                        }), g = o, _ = l, v = t, E = n
                                    } else if ("L" === e.type) {
                                        t = e.values[0], n = e.values[1];
                                        S.push({
                                            type: "L",
                                            values: [t, n]
                                        }), v = t, E = n
                                    } else if ("H" === e.type) {
                                        t = e.values[0];
                                        S.push({
                                            type: "L",
                                            values: [t, E]
                                        }), v = t
                                    } else if ("V" === e.type) {
                                        n = e.values[0];
                                        S.push({
                                            type: "L",
                                            values: [v, n]
                                        }), E = n
                                    } else if ("S" === e.type) {
                                        o = e.values[0], l = e.values[1], t = e.values[2], n = e.values[3];
                                        "C" === m || "S" === m ? (r = v + (v - g), s = E + (E - _)) : (r = v, s = E), S.push({
                                            type: "C",
                                            values: [r, s, o, l, t, n]
                                        }), g = o, _ = l, v = t, E = n
                                    } else if ("T" === e.type) {
                                        t = e.values[0], n = e.values[1];
                                        "Q" === m || "T" === m ? (a = v + (v - g), i = E + (E - _)) : (a = v, i = E);
                                        var r = v + 2 * (a - v) / 3,
                                            s = E + 2 * (i - E) / 3,
                                            u = t + 2 * (a - t) / 3,
                                            h = n + 2 * (i - n) / 3;
                                        S.push({
                                            type: "C",
                                            values: [r, s, u, h, t, n]
                                        }), g = a, _ = i, v = t, E = n
                                    } else if ("Q" === e.type) {
                                        a = e.values[0], i = e.values[1], t = e.values[2], n = e.values[3], r = v + 2 * (a - v) / 3, s = E + 2 * (i - E) / 3, u = t + 2 * (a - t) / 3, h = n + 2 * (i - n) / 3;
                                        S.push({
                                            type: "C",
                                            values: [r, s, u, h, t, n]
                                        }), g = a, _ = i, v = t, E = n
                                    } else if ("A" === e.type) {
                                        var p = e.values[0],
                                            c = e.values[1],
                                            d = e.values[2],
                                            f = e.values[3],
                                            y = e.values[4];
                                        t = e.values[5], n = e.values[6];
                                        if (0 === p || 0 === c) S.push({
                                            type: "C",
                                            values: [v, E, t, n, t, n]
                                        }), v = t, E = n;
                                        else if (v !== t || E !== n) U(v, E, t, n, p, c, d, f, y).forEach(function(e) {
                                            S.push({
                                                type: "C",
                                                values: e
                                            }), v = t, E = n
                                        })
                                    } else "Z" === e.type && (S.push(e), v = x, E = b);
                                    m = e.type
                                }), S
                            };
                        e.SVGPathElement.prototype.setAttribute = function(e, t) {
                            "d" === e && (this[d] = null, this[f] = null), n.call(this, e, t)
                        }, e.SVGPathElement.prototype.removeAttribute = function(e, t) {
                            "d" === e && (this[d] = null, this[f] = null), r.call(this, e)
                        }, e.SVGPathElement.prototype.getPathData = function(e) {
                            if (e && e.normalize) {
                                if (this[f]) return y(this[f]);
                                this[d] ? n = y(this[d]) : (n = a(this.getAttribute("d") || ""), this[d] = y(n));
                                var t = S((s = [], c = p = h = u = null, n.forEach(function(e) {
                                    var t = e.type;
                                    if ("M" === t) {
                                        var n = e.values[0],
                                            a = e.values[1];
                                        s.push({
                                            type: "M",
                                            values: [n, a]
                                        }), u = p = n, h = c = a
                                    } else if ("m" === t) n = u + e.values[0], a = h + e.values[1], s.push({
                                        type: "M",
                                        values: [n, a]
                                    }), u = p = n, h = c = a;
                                    else if ("L" === t) n = e.values[0], a = e.values[1], s.push({
                                        type: "L",
                                        values: [n, a]
                                    }), u = n, h = a;
                                    else if ("l" === t) n = u + e.values[0], a = h + e.values[1], s.push({
                                        type: "L",
                                        values: [n, a]
                                    }), u = n, h = a;
                                    else if ("C" === t) {
                                        var i = e.values[0],
                                            o = e.values[1],
                                            l = e.values[2],
                                            r = e.values[3];
                                        n = e.values[4], a = e.values[5], s.push({
                                            type: "C",
                                            values: [i, o, l, r, n, a]
                                        }), u = n, h = a
                                    } else "c" === t ? (i = u + e.values[0], o = h + e.values[1], l = u + e.values[2], r = h + e.values[3], n = u + e.values[4], a = h + e.values[5], s.push({
                                        type: "C",
                                        values: [i, o, l, r, n, a]
                                    }), u = n, h = a) : "Q" === t ? (i = e.values[0], o = e.values[1], n = e.values[2], a = e.values[3], s.push({
                                        type: "Q",
                                        values: [i, o, n, a]
                                    }), u = n, h = a) : "q" === t ? (i = u + e.values[0], o = h + e.values[1], n = u + e.values[2], a = h + e.values[3], s.push({
                                        type: "Q",
                                        values: [i, o, n, a]
                                    }), u = n, h = a) : "A" === t ? (n = e.values[5], a = e.values[6], s.push({
                                        type: "A",
                                        values: [e.values[0], e.values[1], e.values[2], e.values[3], e.values[4], n, a]
                                    }), u = n, h = a) : "a" === t ? (n = u + e.values[5], a = h + e.values[6], s.push({
                                        type: "A",
                                        values: [e.values[0], e.values[1], e.values[2], e.values[3], e.values[4], n, a]
                                    }), u = n, h = a) : "H" === t ? (n = e.values[0], s.push({
                                        type: "H",
                                        values: [n]
                                    }), u = n) : "h" === t ? (n = u + e.values[0], s.push({
                                        type: "H",
                                        values: [n]
                                    }), u = n) : "V" === t ? (a = e.values[0], s.push({
                                        type: "V",
                                        values: [a]
                                    }), h = a) : "v" === t ? (a = h + e.values[0], s.push({
                                        type: "V",
                                        values: [a]
                                    }), h = a) : "S" === t ? (l = e.values[0], r = e.values[1], n = e.values[2], a = e.values[3], s.push({
                                        type: "S",
                                        values: [l, r, n, a]
                                    }), u = n, h = a) : "s" === t ? (l = u + e.values[0], r = h + e.values[1], n = u + e.values[2], a = h + e.values[3], s.push({
                                        type: "S",
                                        values: [l, r, n, a]
                                    }), u = n, h = a) : "T" === t ? (n = e.values[0], a = e.values[1], s.push({
                                        type: "T",
                                        values: [n, a]
                                    }), u = n, h = a) : "t" === t ? (n = u + e.values[0], a = h + e.values[1], s.push({
                                        type: "T",
                                        values: [n, a]
                                    }), u = n, h = a) : "Z" !== t && "z" !== t || (s.push({
                                        type: "Z",
                                        values: []
                                    }), u = p, h = c)
                                }), s));
                                return this[f] = y(t), t
                            }
                            if (this[d]) return y(this[d]);
                            var s, u, h, p, c, n = a(this.getAttribute("d") || "");
                            return this[d] = y(n), n
                        }, e.SVGPathElement.prototype.setPathData = function(e) {
                            if (0 === e.length) l ? this.setAttribute("d", "") : this.removeAttribute("d");
                            else {
                                for (var t = "", n = 0, a = e.length; n < a; n += 1) {
                                    var i = e[n];
                                    0 < n && (t += " "), t += i.type, i.values && 0 < i.values.length && (t += " " + i.values.join(" "))
                                }
                                this.setAttribute("d", t)
                            }
                        }, e.SVGRectElement.prototype.getPathData = function(e) {
                            var t = this.x.baseVal.value,
                                n = this.y.baseVal.value,
                                a = this.width.baseVal.value,
                                i = this.height.baseVal.value,
                                o = this.hasAttribute("rx") ? this.rx.baseVal.value : this.ry.baseVal.value,
                                l = this.hasAttribute("ry") ? this.ry.baseVal.value : this.rx.baseVal.value;
                            a / 2 < o && (o = a / 2), i / 2 < l && (l = i / 2);
                            var r = [{
                                type: "M",
                                values: [t + o, n]
                            }, {
                                type: "H",
                                values: [t + a - o]
                            }, {
                                type: "A",
                                values: [o, l, 0, 0, 1, t + a, n + l]
                            }, {
                                type: "V",
                                values: [n + i - l]
                            }, {
                                type: "A",
                                values: [o, l, 0, 0, 1, t + a - o, n + i]
                            }, {
                                type: "H",
                                values: [t + o]
                            }, {
                                type: "A",
                                values: [o, l, 0, 0, 1, t, n + i - l]
                            }, {
                                type: "V",
                                values: [n + l]
                            }, {
                                type: "A",
                                values: [o, l, 0, 0, 1, t + o, n]
                            }, {
                                type: "Z",
                                values: []
                            }];
                            return r = r.filter(function(e) {
                                return "A" !== e.type || 0 !== e.values[0] && 0 !== e.values[1]
                            }), e && !0 === e.normalize && (r = S(r)), r
                        }, e.SVGCircleElement.prototype.getPathData = function(e) {
                            var t = this.cx.baseVal.value,
                                n = this.cy.baseVal.value,
                                a = this.r.baseVal.value,
                                i = [{
                                    type: "M",
                                    values: [t + a, n]
                                }, {
                                    type: "A",
                                    values: [a, a, 0, 0, 1, t, n + a]
                                }, {
                                    type: "A",
                                    values: [a, a, 0, 0, 1, t - a, n]
                                }, {
                                    type: "A",
                                    values: [a, a, 0, 0, 1, t, n - a]
                                }, {
                                    type: "A",
                                    values: [a, a, 0, 0, 1, t + a, n]
                                }, {
                                    type: "Z",
                                    values: []
                                }];
                            return e && !0 === e.normalize && (i = S(i)), i
                        }, e.SVGEllipseElement.prototype.getPathData = function(e) {
                            var t = this.cx.baseVal.value,
                                n = this.cy.baseVal.value,
                                a = this.rx.baseVal.value,
                                i = this.ry.baseVal.value,
                                o = [{
                                    type: "M",
                                    values: [t + a, n]
                                }, {
                                    type: "A",
                                    values: [a, i, 0, 0, 1, t, n + i]
                                }, {
                                    type: "A",
                                    values: [a, i, 0, 0, 1, t - a, n]
                                }, {
                                    type: "A",
                                    values: [a, i, 0, 0, 1, t, n - i]
                                }, {
                                    type: "A",
                                    values: [a, i, 0, 0, 1, t + a, n]
                                }, {
                                    type: "Z",
                                    values: []
                                }];
                            return e && !0 === e.normalize && (o = S(o)), o
                        }, e.SVGLineElement.prototype.getPathData = function() {
                            return [{
                                type: "M",
                                values: [this.x1.baseVal.value, this.y1.baseVal.value]
                            }, {
                                type: "L",
                                values: [this.x2.baseVal.value, this.y2.baseVal.value]
                            }]
                        }, e.SVGPolylineElement.prototype.getPathData = function() {
                            for (var e = [], t = 0; t < this.points.numberOfItems; t += 1) {
                                var n = this.points.getItem(t);
                                e.push({
                                    type: 0 === t ? "M" : "L",
                                    values: [n.x, n.y]
                                })
                            }
                            return e
                        }, e.SVGPolygonElement.prototype.getPathData = function() {
                            for (var e = [], t = 0; t < this.points.numberOfItems; t += 1) {
                                var n = this.points.getItem(t);
                                e.push({
                                    type: 0 === t ? "M" : "L",
                                    values: [n.x, n.y]
                                })
                            }
                            return e.push({
                                type: "Z",
                                values: []
                            }), e
                        }
                    }()
                },
                O = function(n) {
                    var a = {};

                    function i(e) {
                        if (a[e]) return a[e].exports;
                        var t = a[e] = {
                            i: e,
                            l: !1,
                            exports: {}
                        };
                        return n[e].call(t.exports, t, t.exports, i), t.l = !0, t.exports
                    }
                    return i.m = n, i.c = a, i.d = function(e, t, n) {
                        i.o(e, t) || Object.defineProperty(e, t, {
                            configurable: !1,
                            enumerable: !0,
                            get: n
                        })
                    }, i.r = function(e) {
                        Object.defineProperty(e, "__esModule", {
                            value: !0
                        })
                    }, i.n = function(e) {
                        var t = e && e.__esModule ? function() {
                            return e.default
                        } : function() {
                            return e
                        };
                        return i.d(t, "a", t), t
                    }, i.o = function(e, t) {
                        return Object.prototype.hasOwnProperty.call(e, t)
                    }, i.p = "", i(i.s = 0)
                }([function(e, t, n) {
                    n.r(t);
                    var a = 500,
                        i = [],
                        o = window.requestAnimationFrame || window.mozRequestAnimationFrame || window.webkitRequestAnimationFrame || window.msRequestAnimationFrame || function(e) {
                            return setTimeout(e, 1e3 / 60)
                        },
                        l = window.cancelAnimationFrame || window.mozCancelAnimationFrame || window.webkitCancelAnimationFrame || window.msCancelAnimationFrame || function(e) {
                            return clearTimeout(e)
                        },
                        r = void 0,
                        s = Date.now();

                    function u() {
                        var t = void 0,
                            e = void 0;
                        r && (l.call(window, r), r = null), i.forEach(function(e) {
                            e.event && (e.listener(e.event), e.event = null, t = !0)
                        }), t ? (s = Date.now(), e = !0) : Date.now() - s < a && (e = !0), e && (r = o.call(window, u))
                    }

                    function h(n) {
                        var a = -1;
                        return i.some(function(e, t) {
                            return e.listener === n && (a = t, !0)
                        }), a
                    }
                    var p = {
                        add: function(e) {
                            var t = void 0;
                            return -1 === h(e) ? (i.push(t = {
                                listener: e
                            }), function(e) {
                                t.event = e, r || u()
                            }) : null
                        },
                        remove: function(e) {
                            var t; - 1 < (t = h(e)) && (i.splice(t, 1), !i.length && r && (l.call(window, r), r = null))
                        }
                    };
                    t.default = p
                }]).default,
                Y = {
                    line_altColor: {
                        iniValue: !1
                    },
                    line_color: {},
                    line_colorTra: {
                        iniValue: !1
                    },
                    line_strokeWidth: {},
                    plug_enabled: {
                        iniValue: !1
                    },
                    plug_enabledSE: {
                        hasSE: !0,
                        iniValue: !1
                    },
                    plug_plugSE: {
                        hasSE: !0,
                        iniValue: ne
                    },
                    plug_colorSE: {
                        hasSE: !0
                    },
                    plug_colorTraSE: {
                        hasSE: !0,
                        iniValue: !1
                    },
                    plug_markerWidthSE: {
                        hasSE: !0
                    },
                    plug_markerHeightSE: {
                        hasSE: !0
                    },
                    lineOutline_enabled: {
                        iniValue: !1
                    },
                    lineOutline_color: {},
                    lineOutline_colorTra: {
                        iniValue: !1
                    },
                    lineOutline_strokeWidth: {},
                    lineOutline_inStrokeWidth: {},
                    plugOutline_enabledSE: {
                        hasSE: !0,
                        iniValue: !1
                    },
                    plugOutline_plugSE: {
                        hasSE: !0,
                        iniValue: ne
                    },
                    plugOutline_colorSE: {
                        hasSE: !0
                    },
                    plugOutline_colorTraSE: {
                        hasSE: !0,
                        iniValue: !1
                    },
                    plugOutline_strokeWidthSE: {
                        hasSE: !0
                    },
                    plugOutline_inStrokeWidthSE: {
                        hasSE: !0
                    },
                    position_socketXYSE: {
                        hasSE: !0,
                        hasProps: !0
                    },
                    position_plugOverheadSE: {
                        hasSE: !0
                    },
                    position_path: {},
                    position_lineStrokeWidth: {},
                    position_socketGravitySE: {
                        hasSE: !0
                    },
                    path_pathData: {},
                    path_edge: {
                        hasProps: !0
                    },
                    viewBox_bBox: {
                        hasProps: !0
                    },
                    viewBox_plugBCircleSE: {
                        hasSE: !0
                    },
                    lineMask_enabled: {
                        iniValue: !1
                    },
                    lineMask_outlineMode: {
                        iniValue: !1
                    },
                    lineMask_x: {},
                    lineMask_y: {},
                    lineOutlineMask_x: {},
                    lineOutlineMask_y: {},
                    maskBGRect_x: {},
                    maskBGRect_y: {},
                    capsMaskAnchor_enabledSE: {
                        hasSE: !0,
                        iniValue: !1
                    },
                    capsMaskAnchor_pathDataSE: {
                        hasSE: !0
                    },
                    capsMaskAnchor_strokeWidthSE: {
                        hasSE: !0
                    },
                    capsMaskMarker_enabled: {
                        iniValue: !1
                    },
                    capsMaskMarker_enabledSE: {
                        hasSE: !0,
                        iniValue: !1
                    },
                    capsMaskMarker_plugSE: {
                        hasSE: !0,
                        iniValue: ne
                    },
                    capsMaskMarker_markerWidthSE: {
                        hasSE: !0
                    },
                    capsMaskMarker_markerHeightSE: {
                        hasSE: !0
                    },
                    caps_enabled: {
                        iniValue: !1
                    },
                    attach_plugSideLenSE: {
                        hasSE: !0
                    },
                    attach_plugBackLenSE: {
                        hasSE: !0
                    }
                },
                X = {
                    show_on: {},
                    show_effect: {},
                    show_animOptions: {},
                    show_animId: {},
                    show_inAnim: {}
                },
                q = "fade",
                Q = [],
                K = {},
                J = 0,
                $ = {},
                ee = 0;

            function ce(t, n) {
                var e, a;
                return typeof t != typeof n || (e = k(t) ? "obj" : Array.isArray(t) ? "array" : "") != (k(n) ? "obj" : Array.isArray(n) ? "array" : "") || ("obj" === e ? ce(a = Object.keys(t).sort(), Object.keys(n).sort()) || a.some(function(e) {
                    return ce(t[e], n[e])
                }) : "array" === e ? t.length !== n.length || t.some(function(e, t) {
                    return ce(e, n[t])
                }) : t !== n)
            }

            function de(n) {
                return n ? k(n) ? Object.keys(n).reduce(function(e, t) {
                    return e[t] = de(n[t]), e
                }, {}) : Array.isArray(n) ? n.map(de) : n : n
            }

            function fe(e) {
                var t, n, a, i = 1,
                    o = e = (e + "").trim();

                function l(e) {
                    var t = 1,
                        n = u.exec(e);
                    return n && (t = parseFloat(n[1]), n[2] ? t = 0 <= t && t <= 100 ? t / 100 : 1 : (t < 0 || 1 < t) && (t = 1)), t
                }
                return (t = /^(rgba|hsla|hwb|gray|device\-cmyk)\s*\(([\s\S]+)\)$/i.exec(e)) ? (n = t[1].toLowerCase(), a = t[2].trim().split(/\s*,\s*/), "rgba" === n && 4 === a.length ? (i = l(a[3]), o = "rgb(" + a.slice(0, 3).join(", ") + ")") : "hsla" === n && 4 === a.length ? (i = l(a[3]), o = "hsl(" + a.slice(0, 3).join(", ") + ")") : "hwb" === n && 4 === a.length ? (i = l(a[3]), o = "hwb(" + a.slice(0, 3).join(", ") + ")") : "gray" === n && 2 === a.length ? (i = l(a[1]), o = "gray(" + a[0] + ")") : "device-cmyk" === n && 5 <= a.length && (i = l(a[4]), o = "device-cmyk(" + a.slice(0, 4).join(", ") + ")")) : (t = /^\#(?:([\da-f]{6})([\da-f]{2})|([\da-f]{3})([\da-f]))$/i.exec(e)) ? t[1] ? (i = parseInt(t[2], 16) / 255, o = "#" + t[1]) : (i = parseInt(t[4] + t[4], 16) / 255, o = "#" + t[3]) : "transparent" === e.toLocaleLowerCase() && (i = 0), [i, o]
            }

            function ye(e) {
                return !(!e || e.nodeType !== Node.ELEMENT_NODE || "function" != typeof e.getBoundingClientRect)
            }

            function Se(e, t) {
                var n, a, i, o, l = {};
                if (!(i = e.ownerDocument)) return console.error("Cannot get document that contains the element."), null;
                if (e.compareDocumentPosition(i) & Node.DOCUMENT_POSITION_DISCONNECTED) return console.error("A disconnected element was passed."), null;
                for (a in n = e.getBoundingClientRect()) l[a] = n[a];
                if (!t) {
                    if (!(o = i.defaultView)) return console.error("Cannot get window that contains the element."), null;
                    l.left += o.pageXOffset, l.right += o.pageXOffset, l.top += o.pageYOffset, l.bottom += o.pageYOffset
                }
                return l
            }

            function me(e, t) {
                var n, a, i = [],
                    o = e;
                for (t = t || window;;) {
                    if (!(n = o.ownerDocument)) return console.error("Cannot get document that contains the element."), null;
                    if (!(a = n.defaultView)) return console.error("Cannot get window that contains the element."), null;
                    if (a === t) break;
                    if (!(o = a.frameElement)) return console.error("`baseWindow` was not found."), null;
                    i.unshift(o)
                }
                return i
            }

            function ge(e, t) {
                var n, a, o = 0,
                    l = 0;
                return (a = me(e, t = t || window)) ? a.length ? (a.forEach(function(e, t) {
                    var n, a, i = Se(e, 0 < t);
                    o += i.left, l += i.top, a = (n = e).ownerDocument.defaultView.getComputedStyle(n, ""), i = {
                        left: n.clientLeft + parseFloat(a.paddingLeft),
                        top: n.clientTop + parseFloat(a.paddingTop)
                    }, o += i.left, l += i.top
                }), (n = Se(e, !0)).left += o, n.right += o, n.top += l, n.bottom += l, n) : Se(e) : null
            }

            function _e(e, t) {
                var n = e.x - t.x,
                    a = e.y - t.y;
                return Math.sqrt(n * n + a * a)
            }

            function ve(e, t, n) {
                var a = t.x - e.x,
                    i = t.y - e.y;
                return {
                    x: e.x + a * n,
                    y: e.y + i * n,
                    angle: Math.atan2(i, a) / (Math.PI / 180)
                }
            }

            function Ee(e, t, n) {
                var a = Math.atan2(e.y - t.y, t.x - e.x);
                return {
                    x: t.x + Math.cos(a) * n,
                    y: t.y + Math.sin(a) * n * -1
                }
            }

            function xe(e, t, n, a, i) {
                var o = i * i,
                    l = o * i,
                    r = 1 - i,
                    s = r * r,
                    u = s * r,
                    h = u * e.x + 3 * s * i * t.x + 3 * r * o * n.x + l * a.x,
                    p = u * e.y + 3 * s * i * t.y + 3 * r * o * n.y + l * a.y,
                    c = e.x + 2 * i * (t.x - e.x) + o * (n.x - 2 * t.x + e.x),
                    d = e.y + 2 * i * (t.y - e.y) + o * (n.y - 2 * t.y + e.y),
                    f = t.x + 2 * i * (n.x - t.x) + o * (a.x - 2 * n.x + t.x),
                    y = t.y + 2 * i * (n.y - t.y) + o * (a.y - 2 * n.y + t.y),
                    S = r * e.x + i * t.x,
                    m = r * e.y + i * t.y,
                    g = r * n.x + i * a.x,
                    _ = r * n.y + i * a.y,
                    v = 90 - 180 * Math.atan2(c - f, d - y) / Math.PI;
                return {
                    x: h,
                    y: p,
                    fromP2: {
                        x: c,
                        y: d
                    },
                    toP1: {
                        x: f,
                        y: y
                    },
                    fromP1: {
                        x: S,
                        y: m
                    },
                    toP2: {
                        x: g,
                        y: _
                    },
                    angle: v += 180 < v ? -180 : 180
                }
            }

            function be(n, a, i, o, e) {
                function l(e, t, n, a, i) {
                    return e * (e * (-3 * t + 9 * n - 9 * a + 3 * i) + 6 * t - 12 * n + 6 * a) - 3 * t + 3 * n
                }
                var r, s, u, h, p, c = [.2491, .2491, .2335, .2335, .2032, .2032, .1601, .1601, .1069, .1069, .0472, .0472],
                    d = 0;
                return r = (e = null == e || 1 < e ? 1 : e < 0 ? 0 : e) / 2, [-.1252, .1252, -.3678, .3678, -.5873, .5873, -.7699, .7699, -.9041, .9041, -.9816, .9816].forEach(function(e, t) {
                    u = l(s = r * e + r, n.x, a.x, i.x, o.x), h = l(s, n.y, a.y, i.y, o.y), p = u * u + h * h, d += c[t] * Math.sqrt(p)
                }), r * d
            }

            function ke(e, t, n, a, i) {
                for (var o, l = .5, r = 1 - l; o = be(e, t, n, a, r), !(Math.abs(o - i) <= .01);) r += (o < i ? 1 : -1) * (l /= 2);
                return r
            }

            function we(e, n) {
                var a;
                return e.forEach(function(e) {
                    var t = n ? e.map(function(e) {
                        var t = {
                            x: e.x,
                            y: e.y
                        };
                        return n(t), t
                    }) : e;
                    a || (a = [{
                        type: "M",
                        values: [t[0].x, t[0].y]
                    }]), a.push(t.length ? 2 === t.length ? {
                        type: "L",
                        values: [t[1].x, t[1].y]
                    } : {
                        type: "C",
                        values: [t[1].x, t[1].y, t[2].x, t[2].y, t[3].x, t[3].y]
                    } : {
                        type: "Z",
                        values: []
                    })
                }), a
            }

            function Oe(e) {
                var n = [],
                    a = 0;
                return e.forEach(function(e) {
                    var t = (2 === e.length ? _e : be).apply(null, e);
                    n.push(t), a += t
                }), {
                    segsLen: n,
                    lenAll: a
                }
            }

            function Me(e, a) {
                return null == e || null == a || e.length !== a.length || e.some(function(e, t) {
                    var n = a[t];
                    return e.type !== n.type || e.values.some(function(e, t) {
                        return e !== n.values[t]
                    })
                })
            }

            function Ie(e, t, n) {
                e.events[t] ? e.events[t].indexOf(n) < 0 && e.events[t].push(n) : e.events[t] = [n]
            }

            function Ce(e, t, n) {
                var a;
                e.events[t] && -1 < (a = e.events[t].indexOf(n)) && e.events[t].splice(a, 1)
            }

            function Le(e) {
                t && clearTimeout(t), Q.push(e), t = setTimeout(function() {
                    Q.forEach(function(e) {
                        e()
                    }), Q = []
                }, 0)
            }

            function Ae(e, t) {
                e.reflowTargets.indexOf(t) < 0 && e.reflowTargets.push(t)
            }

            function Ve(e) {
                e.reflowTargets.forEach(function(e) {
                    var n;
                    n = e, setTimeout(function() {
                        var e = n.parentNode,
                            t = n.nextSibling;
                        e.insertBefore(e.removeChild(n), t)
                    }, 0)
                }), e.reflowTargets = []
            }

            function Pe(e, t, n, a, i, o, l) {
                var r, s, u;
                "auto-start-reverse" === n ? ("boolean" != typeof h && (t.setAttribute("orient", "auto-start-reverse"), h = t.orientType.baseVal === SVGMarkerElement.SVG_MARKER_ORIENT_UNKNOWN), h ? t.setAttribute("orient", n) : ((r = i.createSVGTransform()).setRotate(180, 0, 0), o.transform.baseVal.appendItem(r), t.setAttribute("orient", "auto"), u = !0)) : (t.setAttribute("orient", n), !1 === h && o.transform.baseVal.clear()), s = t.viewBox.baseVal, u ? (s.x = -a.right, s.y = -a.bottom) : (s.x = a.left, s.y = a.top), s.width = a.width, s.height = a.height, le && Ae(e, l)
            }

            function Ne(e, t) {
                return {
                    prop: e ? "markerEnd" : "markerStart",
                    orient: t ? t.noRotate ? "0" : e ? "auto" : "auto-start-reverse" : null
                }
            }

            function Te(n, a) {
                Object.keys(a).forEach(function(e) {
                    var t = a[e];
                    n[e] = null != t.iniValue ? t.hasSE ? [t.iniValue, t.iniValue] : t.iniValue : t.hasSE ? t.hasProps ? [{}, {}] : [] : t.hasProps ? {} : null
                })
            }

            function We(t, e, n, a, i) {
                return a !== e[n] && (e[n] = a, i && i.forEach(function(e) {
                    e(t, a, n)
                }), !0)
            }

            function Be(e) {
                function t(e, t) {
                    return e + parseFloat(t)
                }
                var n = e.document,
                    a = e.getComputedStyle(n.documentElement, ""),
                    i = e.getComputedStyle(n.body, ""),
                    o = {
                        x: 0,
                        y: 0
                    };
                return "static" !== i.position ? (o.x -= [a.marginLeft, a.borderLeftWidth, a.paddingLeft, i.marginLeft, i.borderLeftWidth].reduce(t, 0), o.y -= [a.marginTop, a.borderTopWidth, a.paddingTop, i.marginTop, i.borderTopWidth].reduce(t, 0)) : "static" !== a.position && (o.x -= [a.marginLeft, a.borderLeftWidth].reduce(t, 0), o.y -= [a.marginTop, a.borderTopWidth].reduce(t, 0)), o
            }

            function Re(e) {
                var t, n = e.document;
                n.getElementById(r) || (t = (new e.DOMParser).parseFromString(s, "image/svg+xml"), n.body.appendChild(t.documentElement), d(e))
            }

            function Fe(u) {
                var _, f, v, e, n, a, i, y, s, h, p, t, o, l, r, c, d, S, m, g = u.options,
                    E = u.curStats,
                    x = u.aplStats,
                    b = E.position_socketXYSE,
                    k = !1;

                function w(e, t) {
                    var n = t === M ? {
                        x: e.left + e.width / 2,
                        y: e.top
                    } : t === I ? {
                        x: e.right,
                        y: e.top + e.height / 2
                    } : t === C ? {
                        x: e.left + e.width / 2,
                        y: e.bottom
                    } : {
                        x: e.left,
                        y: e.top + e.height / 2
                    };
                    return n.socketId = t, n
                }

                function O(e) {
                    return {
                        x: e.x,
                        y: e.y
                    }
                }
                if (E.position_path = g.path, E.position_lineStrokeWidth = E.line_strokeWidth, E.position_socketGravitySE = _ = de(g.socketGravitySE), f = [0, 1].map(function(e) {
                        var t, n, a, i = g.anchorSE[e],
                            o = u.optionIsAttach.anchorSE[e],
                            l = !1 !== o ? $[i._id] : null,
                            r = !1 !== o && l.conf.getStrokeWidth ? l.conf.getStrokeWidth(l, u) : 0,
                            s = !1 !== o && l.conf.getBBoxNest ? l.conf.getBBoxNest(l, u, r) : ge(i, u.baseWindow);
                        return E.capsMaskAnchor_pathDataSE[e] = !1 !== o && l.conf.getPathData ? l.conf.getPathData(l, u, r) : (n = null != (t = s).right ? t.right : t.left + t.width, a = null != t.bottom ? t.bottom : t.top + t.height, [{
                            type: "M",
                            values: [t.left, t.top]
                        }, {
                            type: "L",
                            values: [n, t.top]
                        }, {
                            type: "L",
                            values: [n, a]
                        }, {
                            type: "L",
                            values: [t.left, a]
                        }, {
                            type: "Z",
                            values: []
                        }]), E.capsMaskAnchor_strokeWidthSE[e] = r, s
                    }), i = -1, g.socketSE[0] && g.socketSE[1] ? (b[0] = w(f[0], g.socketSE[0]), b[1] = w(f[1], g.socketSE[1])) : (g.socketSE[0] || g.socketSE[1] ? (g.socketSE[0] ? (n = 0, a = 1) : (n = 1, a = 0), b[n] = w(f[n], g.socketSE[n]), (e = W.map(function(e) {
                        return w(f[a], e)
                    })).forEach(function(e) {
                        var t = _e(e, b[n]);
                        (t < i || -1 === i) && (b[a] = e, i = t)
                    })) : (e = W.map(function(e) {
                        return w(f[1], e)
                    }), W.map(function(e) {
                        return w(f[0], e)
                    }).forEach(function(n) {
                        e.forEach(function(e) {
                            var t = _e(n, e);
                            (t < i || -1 === i) && (b[0] = n, b[1] = e, i = t)
                        })
                    })), [0, 1].forEach(function(e) {
                        var t, n;
                        g.socketSE[e] || (f[e].width || f[e].height ? f[e].width || b[e].socketId !== L && b[e].socketId !== I ? f[e].height || b[e].socketId !== M && b[e].socketId !== C || (b[e].socketId = 0 <= b[e ? 0 : 1].y - f[e].top ? C : M) : b[e].socketId = 0 <= b[e ? 0 : 1].x - f[e].left ? I : L : (t = b[e ? 0 : 1].x - f[e].left, n = b[e ? 0 : 1].y - f[e].top, b[e].socketId = Math.abs(t) >= Math.abs(n) ? 0 <= t ? I : L : 0 <= n ? C : M))
                    })), E.position_path !== x.position_path || E.position_lineStrokeWidth !== x.position_lineStrokeWidth || [0, 1].some(function(e) {
                        return E.position_plugOverheadSE[e] !== x.position_plugOverheadSE[e] || (i = b[e], o = x.position_socketXYSE[e], i.x !== o.x || i.y !== o.y || i.socketId !== o.socketId) || (t = _[e], n = x.position_socketGravitySE[e], (a = null == t ? "auto" : Array.isArray(t) ? "array" : "number") !== (null == n ? "auto" : Array.isArray(n) ? "array" : "number") || ("array" === a ? t[0] !== n[0] || t[1] !== n[1] : t !== n));
                        var t, n, a, i, o
                    })) {
                    switch (u.pathList.baseVal = v = [], u.pathList.animVal = null, E.position_path) {
                        case A:
                            v.push([O(b[0]), O(b[1])]);
                            break;
                        case V:
                            t = "number" == typeof _[0] && 0 < _[0] || "number" == typeof _[1] && 0 < _[1], o = Z * (t ? -1 : 1), l = Math.atan2(b[1].y - b[0].y, b[1].x - b[0].x), r = -l + o, c = Math.PI - l - o, d = _e(b[0], b[1]) / Math.sqrt(2) * U, S = {
                                x: b[0].x + Math.cos(r) * d,
                                y: b[0].y + Math.sin(r) * d * -1
                            }, m = {
                                x: b[1].x + Math.cos(c) * d,
                                y: b[1].y + Math.sin(c) * d * -1
                            }, v.push([O(b[0]), S, m, O(b[1])]);
                            break;
                        case P:
                        case N:
                            s = [_[0], E.position_path === N ? 0 : _[1]], h = [], p = [], b.forEach(function(e, t) {
                                var n, a, i, o, l, r = s[t];
                                Array.isArray(r) ? n = {
                                    x: r[0],
                                    y: r[1]
                                } : "number" == typeof r ? n = e.socketId === M ? {
                                    x: 0,
                                    y: -r
                                } : e.socketId === I ? {
                                    x: r,
                                    y: 0
                                } : e.socketId === C ? {
                                    x: 0,
                                    y: r
                                } : {
                                    x: -r,
                                    y: 0
                                } : (a = b[t ? 0 : 1], o = 0 < (i = E.position_plugOverheadSE[t]) ? G + (D < i ? (i - D) * z : 0) : B + (E.position_lineStrokeWidth > R ? (E.position_lineStrokeWidth - R) * F : 0), e.socketId === M ? ((l = (e.y - a.y) / 2) < o && (l = o), n = {
                                    x: 0,
                                    y: -l
                                }) : e.socketId === I ? ((l = (a.x - e.x) / 2) < o && (l = o), n = {
                                    x: l,
                                    y: 0
                                }) : e.socketId === C ? ((l = (a.y - e.y) / 2) < o && (l = o), n = {
                                    x: 0,
                                    y: l
                                }) : ((l = (e.x - a.x) / 2) < o && (l = o), n = {
                                    x: -l,
                                    y: 0
                                })), h[t] = e.x + n.x, p[t] = e.y + n.y
                            }), v.push([O(b[0]), {
                                x: h[0],
                                y: p[0]
                            }, {
                                x: h[1],
                                y: p[1]
                            }, O(b[1])]);
                            break;
                        case T:
                            ! function() {
                                var a, o = 1,
                                    l = 2,
                                    r = 3,
                                    s = 4,
                                    u = [
                                        [],
                                        []
                                    ],
                                    h = [];

                                function p(e) {
                                    return e === o ? r : e === l ? s : e === r ? o : l
                                }

                                function c(e) {
                                    return e === l || e === s ? "x" : "y"
                                }

                                function d(e, t, n) {
                                    var a = {
                                        x: e.x,
                                        y: e.y
                                    };
                                    if (n) {
                                        if (n === p(e.dirId)) throw new Error("Invalid dirId: " + n);
                                        a.dirId = n
                                    } else a.dirId = e.dirId;
                                    return a.dirId === o ? a.y -= t : a.dirId === l ? a.x += t : a.dirId === r ? a.y += t : a.x -= t, a
                                }

                                function f(e, t) {
                                    return t.dirId === o ? e.y <= t.y : t.dirId === l ? e.x >= t.x : t.dirId === r ? e.y >= t.y : e.x <= t.x
                                }

                                function y(e, t) {
                                    return t.dirId === o || t.dirId === r ? e.x === t.x : e.y === t.y
                                }

                                function S(e) {
                                    return e[0] ? {
                                        contain: 0,
                                        notContain: 1
                                    } : {
                                        contain: 1,
                                        notContain: 0
                                    }
                                }

                                function m(e, t, n) {
                                    return Math.abs(t[n] - e[n])
                                }

                                function g(e, t, n) {
                                    return "x" === n ? e.x < t.x ? l : s : e.y < t.y ? r : o
                                }

                                function e() {
                                    var e, t, a, i, n = [f(h[1], h[0]), f(h[0], h[1])],
                                        o = [c(h[0].dirId), c(h[1].dirId)];
                                    if (o[0] === o[1]) {
                                        if (n[0] && n[1]) return y(h[1], h[0]) || (h[0][o[0]] === h[1][o[1]] ? (u[0].push(h[0]), u[1].push(h[1])) : (e = h[0][o[0]] + (h[1][o[1]] - h[0][o[0]]) / 2, u[0].push(d(h[0], Math.abs(e - h[0][o[0]]))), u[1].push(d(h[1], Math.abs(e - h[1][o[1]]))))), !1;
                                        n[0] !== n[1] ? (t = S(n), (a = m(h[t.notContain], h[t.contain], o[t.notContain])) < H && (h[t.notContain] = d(h[t.notContain], H - a)), u[t.notContain].push(h[t.notContain]), h[t.notContain] = d(h[t.notContain], H, y(h[t.contain], h[t.notContain]) ? "x" === o[t.notContain] ? r : l : g(h[t.notContain], h[t.contain], "x" === o[t.notContain] ? "y" : "x"))) : (a = m(h[0], h[1], "x" === o[0] ? "y" : "x"), u.forEach(function(e, t) {
                                            var n = 0 === t ? 1 : 0;
                                            e.push(h[t]), h[t] = d(h[t], H, 2 * H <= a ? g(h[t], h[n], "x" === o[t] ? "y" : "x") : "x" === o[t] ? r : l)
                                        }))
                                    } else {
                                        if (n[0] && n[1]) return y(h[1], h[0]) ? u[1].push(h[1]) : y(h[0], h[1]) ? u[0].push(h[0]) : u[0].push("x" === o[0] ? {
                                            x: h[1].x,
                                            y: h[0].y
                                        } : {
                                            x: h[0].x,
                                            y: h[1].y
                                        }), !1;
                                        n[0] !== n[1] ? (t = S(n), u[t.notContain].push(h[t.notContain]), h[t.notContain] = d(h[t.notContain], H, m(h[t.notContain], h[t.contain], o[t.contain]) >= H ? g(h[t.notContain], h[t.contain], o[t.contain]) : h[t.contain].dirId)) : (i = [{
                                            x: h[0].x,
                                            y: h[0].y
                                        }, {
                                            x: h[1].x,
                                            y: h[1].y
                                        }], u.forEach(function(e, t) {
                                            var n = 0 === t ? 1 : 0,
                                                a = m(i[t], i[n], o[t]);
                                            a < H && (h[t] = d(h[t], H - a)), e.push(h[t]), h[t] = d(h[t], H, g(h[t], h[n], o[n]))
                                        }))
                                    }
                                    return !0
                                }
                                for (b.forEach(function(e, t) {
                                        var n, a = O(e),
                                            i = _[t];
                                        n = Array.isArray(i) ? i[0] < 0 ? [s, -i[0]] : 0 < i[0] ? [l, i[0]] : i[1] < 0 ? [o, -i[1]] : 0 < i[1] ? [r, i[1]] : [e.socketId, 0] : "number" != typeof i ? [e.socketId, H] : 0 <= i ? [e.socketId, i] : [p(e.socketId), -i], a.dirId = n[0], i = n[1], u[t].push(a), h[t] = d(a, i)
                                    }); e(););
                                u[1].reverse(), u[0].concat(u[1]).forEach(function(e, t) {
                                    var n = {
                                        x: e.x,
                                        y: e.y
                                    };
                                    0 < t && v.push([a, n]), a = n
                                })
                            }()
                    }
                    y = [], E.position_plugOverheadSE.forEach(function(e, t) {
                        var n, a, i, o, l, r, s, u, h, p, c, d = !t;
                        0 < e ? 2 === (n = v[a = d ? 0 : v.length - 1]).length ? (y[a] = y[a] || _e.apply(null, n), y[a] > j && (y[a] - e < j && (e = y[a] - j), i = ve(n[0], n[1], (d ? e : y[a] - e) / y[a]), v[a] = d ? [i, n[1]] : [n[0], i], y[a] -= e)) : (y[a] = y[a] || be.apply(null, n), y[a] > j && (y[a] - e < j && (e = y[a] - j), i = xe(n[0], n[1], n[2], n[3], ke(n[0], n[1], n[2], n[3], d ? e : y[a] - e)), d ? (o = n[0], l = i.toP1) : (o = n[3], l = i.fromP2), r = Math.atan2(o.y - i.y, i.x - o.x), s = _e(i, l), i.x = o.x + Math.cos(r) * e, i.y = o.y + Math.sin(r) * e * -1, l.x = i.x + Math.cos(r) * s, l.y = i.y + Math.sin(r) * s * -1, v[a] = d ? [i, i.toP1, i.toP2, n[3]] : [n[0], i.fromP1, i.fromP2, i], y[a] = null)) : e < 0 && (n = v[a = d ? 0 : v.length - 1], u = b[t].socketId, h = u === L || u === I ? "x" : "y", e < (c = -f[t]["x" === h ? "width" : "height"]) && (e = c), p = e * (u === L || u === M ? -1 : 1), 2 === n.length ? n[d ? 0 : n.length - 1][h] += p : (d ? [0, 1] : [n.length - 2, n.length - 1]).forEach(function(e) {
                            n[e][h] += p
                        }), y[a] = null)
                    }), x.position_socketXYSE = de(b), x.position_plugOverheadSE = de(E.position_plugOverheadSE), x.position_path = E.position_path, x.position_lineStrokeWidth = E.position_lineStrokeWidth, x.position_socketGravitySE = de(_), k = !0, u.events.apl_position && u.events.apl_position.forEach(function(e) {
                        e(u, v)
                    })
                }
                return k
            }

            function Ge(t, n) {
                n !== t.isShown && (!!n != !!t.isShown && (t.svg.style.visibility = n ? "" : "hidden"), t.isShown = n, t.events && t.events.svgShow && t.events.svgShow.forEach(function(e) {
                    e(t, n)
                }))
            }

            function De(e, t) {
                var n, a, i, o, l, h, p, c, d, f, r, s, u, y, S, m, g, _, v, E, x, b, k, w, O, M, I, C, L, A, V, P, N, T, W, B, R, F, G, D, z, j, H, U, Z, Y, X, q, Q, K, J, $, ee = {};
                t.line && (ee.line = (a = (n = e).options, i = n.curStats, o = n.events, l = !1, l = We(n, i, "line_color", a.lineColor, o.cur_line_color) || l, l = We(n, i, "line_colorTra", fe(i.line_color)[0] < 1) || l, l = We(n, i, "line_strokeWidth", a.lineSize, o.cur_line_strokeWidth) || l)), (t.plug || ee.line) && (ee.plug = (p = (h = e).options, c = h.curStats, d = h.events, f = !1, [0, 1].forEach(function(e) {
                    var t, n, a, i, o, l, r, s, u = p.plugSE[e];
                    f = We(h, c.plug_enabledSE, e, u !== ne) || f, f = We(h, c.plug_plugSE, e, u) || f, f = We(h, c.plug_colorSE, e, s = p.plugColorSE[e] || c.line_color, d.cur_plug_colorSE) || f, f = We(h, c.plug_colorTraSE, e, fe(s)[0] < 1) || f, u !== ne && (i = n = (t = ae[ie[u]]).widthR * p.plugSizeSE[e], o = a = t.heightR * p.plugSizeSE[e], ue && (i *= c.line_strokeWidth, o *= c.line_strokeWidth), f = We(h, c.plug_markerWidthSE, e, i) || f, f = We(h, c.plug_markerHeightSE, e, o) || f, c.capsMaskMarker_markerWidthSE[e] = n, c.capsMaskMarker_markerHeightSE[e] = a), c.plugOutline_plugSE[e] = c.capsMaskMarker_plugSE[e] = u, c.plug_enabledSE[e] ? (s = c.line_strokeWidth / pe.lineSize * p.plugSizeSE[e], c.position_plugOverheadSE[e] = t.overhead * s, c.viewBox_plugBCircleSE[e] = t.bCircle * s, l = t.sideLen * s, r = t.backLen * s) : (c.position_plugOverheadSE[e] = -c.line_strokeWidth / 2, c.viewBox_plugBCircleSE[e] = l = r = 0), We(h, c.attach_plugSideLenSE, e, l, d.cur_attach_plugSideLenSE), We(h, c.attach_plugBackLenSE, e, r, d.cur_attach_plugBackLenSE), c.capsMaskAnchor_enabledSE[e] = !c.plug_enabledSE[e]
                }), f = We(h, c, "plug_enabled", c.plug_enabledSE[0] || c.plug_enabledSE[1]) || f)), (t.lineOutline || ee.line) && (ee.lineOutline = (u = (r = e).options, y = r.curStats, S = !1, S = We(r, y, "lineOutline_enabled", u.lineOutlineEnabled) || S, S = We(r, y, "lineOutline_color", u.lineOutlineColor) || S, S = We(r, y, "lineOutline_colorTra", fe(y.lineOutline_color)[0] < 1) || S, s = y.line_strokeWidth * u.lineOutlineSize, S = We(r, y, "lineOutline_strokeWidth", y.line_strokeWidth - 2 * s) || S, S = We(r, y, "lineOutline_inStrokeWidth", y.lineOutline_colorTra ? y.lineOutline_strokeWidth + 2 * he : y.line_strokeWidth - s) || S)), (t.plugOutline || ee.line || ee.plug || ee.lineOutline) && (ee.plugOutline = (g = (m = e).options, _ = m.curStats, v = !1, [0, 1].forEach(function(e) {
                    var t, n = _.plugOutline_plugSE[e],
                        a = n !== ne ? ae[ie[n]] : null;
                    v = We(m, _.plugOutline_enabledSE, e, g.plugOutlineEnabledSE[e] && _.plug_enabled && _.plug_enabledSE[e] && !!a && !!a.outlineBase) || v, v = We(m, _.plugOutline_colorSE, e, t = g.plugOutlineColorSE[e] || _.lineOutline_color) || v, v = We(m, _.plugOutline_colorTraSE, e, fe(t)[0] < 1) || v, a && a.outlineBase && ((t = g.plugOutlineSizeSE[e]) > a.outlineMax && (t = a.outlineMax), t *= 2 * a.outlineBase, v = We(m, _.plugOutline_strokeWidthSE, e, t) || v, v = We(m, _.plugOutline_inStrokeWidthSE, e, _.plugOutline_colorTraSE[e] ? t - he / (_.line_strokeWidth / pe.lineSize) / g.plugSizeSE[e] * 2 : t / 2) || v)
                }), v)), (t.faces || ee.line || ee.plug || ee.lineOutline || ee.plugOutline) && (ee.faces = (b = (E = e).curStats, k = E.aplStats, w = E.events, O = !1, !b.line_altColor && We(E, k, "line_color", x = b.line_color, w.apl_line_color) && (E.lineFace.style.stroke = x, O = !0), We(E, k, "line_strokeWidth", x = b.line_strokeWidth, w.apl_line_strokeWidth) && (E.lineShape.style.strokeWidth = x + "px", O = !0, (re || le) && (Ae(E, E.lineShape), le && (Ae(E, E.lineFace), Ae(E, E.lineMaskCaps)))), We(E, k, "lineOutline_enabled", x = b.lineOutline_enabled, w.apl_lineOutline_enabled) && (E.lineOutlineFace.style.display = x ? "inline" : "none", O = !0), b.lineOutline_enabled && (We(E, k, "lineOutline_color", x = b.lineOutline_color, w.apl_lineOutline_color) && (E.lineOutlineFace.style.stroke = x, O = !0), We(E, k, "lineOutline_strokeWidth", x = b.lineOutline_strokeWidth, w.apl_lineOutline_strokeWidth) && (E.lineOutlineMaskShape.style.strokeWidth = x + "px", O = !0, le && (Ae(E, E.lineOutlineMaskCaps), Ae(E, E.lineOutlineFace))), We(E, k, "lineOutline_inStrokeWidth", x = b.lineOutline_inStrokeWidth, w.apl_lineOutline_inStrokeWidth) && (E.lineMaskShape.style.strokeWidth = x + "px", O = !0, le && (Ae(E, E.lineOutlineMaskCaps), Ae(E, E.lineOutlineFace)))), We(E, k, "plug_enabled", x = b.plug_enabled, w.apl_plug_enabled) && (E.plugsFace.style.display = x ? "inline" : "none", O = !0), b.plug_enabled && [0, 1].forEach(function(n) {
                    var e = b.plug_plugSE[n],
                        t = e !== ne ? ae[ie[e]] : null,
                        a = Ne(n, t);
                    We(E, k.plug_enabledSE, n, x = b.plug_enabledSE[n], w.apl_plug_enabledSE) && (E.plugsFace.style[a.prop] = x ? "url(#" + E.plugMarkerIdSE[n] + ")" : "none", O = !0), b.plug_enabledSE[n] && (We(E, k.plug_plugSE, n, e, w.apl_plug_plugSE) && (E.plugFaceSE[n].href.baseVal = "#" + t.elmId, Pe(E, E.plugMarkerSE[n], a.orient, t.bBox, E.svg, E.plugMarkerShapeSE[n], E.plugsFace), O = !0, re && Ae(E, E.plugsFace)), We(E, k.plug_colorSE, n, x = b.plug_colorSE[n], w.apl_plug_colorSE) && (E.plugFaceSE[n].style.fill = x, O = !0, (se || ue || le) && !b.line_colorTra && Ae(E, le ? E.lineMaskCaps : E.capsMaskLine)), ["markerWidth", "markerHeight"].forEach(function(e) {
                        var t = "plug_" + e + "SE";
                        We(E, k[t], n, x = b[t][n], w["apl_" + t]) && (E.plugMarkerSE[n][e].baseVal.value = x, O = !0)
                    }), We(E, k.plugOutline_enabledSE, n, x = b.plugOutline_enabledSE[n], w.apl_plugOutline_enabledSE) && (x ? (E.plugFaceSE[n].style.mask = "url(#" + E.plugMaskIdSE[n] + ")", E.plugOutlineFaceSE[n].style.display = "inline") : (E.plugFaceSE[n].style.mask = "none", E.plugOutlineFaceSE[n].style.display = "none"), O = !0), b.plugOutline_enabledSE[n] && (We(E, k.plugOutline_plugSE, n, e, w.apl_plugOutline_plugSE) && (E.plugOutlineFaceSE[n].href.baseVal = E.plugMaskShapeSE[n].href.baseVal = E.plugOutlineMaskShapeSE[n].href.baseVal = "#" + t.elmId, [E.plugMaskSE[n], E.plugOutlineMaskSE[n]].forEach(function(e) {
                        e.x.baseVal.value = t.bBox.left, e.y.baseVal.value = t.bBox.top, e.width.baseVal.value = t.bBox.width, e.height.baseVal.value = t.bBox.height
                    }), O = !0), We(E, k.plugOutline_colorSE, n, x = b.plugOutline_colorSE[n], w.apl_plugOutline_colorSE) && (E.plugOutlineFaceSE[n].style.fill = x, O = !0, le && (Ae(E, E.lineMaskCaps), Ae(E, E.lineOutlineMaskCaps))), We(E, k.plugOutline_strokeWidthSE, n, x = b.plugOutline_strokeWidthSE[n], w.apl_plugOutline_strokeWidthSE) && (E.plugOutlineMaskShapeSE[n].style.strokeWidth = x + "px", O = !0), We(E, k.plugOutline_inStrokeWidthSE, n, x = b.plugOutline_inStrokeWidthSE[n], w.apl_plugOutline_inStrokeWidthSE) && (E.plugMaskShapeSE[n].style.strokeWidth = x + "px", O = !0)))
                }), O)), (t.position || ee.line || ee.plug) && (ee.position = Fe(e)), (t.path || ee.position) && (ee.path = (C = (M = e).curStats, L = M.aplStats, A = M.pathList.animVal || M.pathList.baseVal, V = C.path_edge, P = !1, A && (V.x1 = V.x2 = A[0][0].x, V.y1 = V.y2 = A[0][0].y, C.path_pathData = I = we(A, function(e) {
                    e.x < V.x1 && (V.x1 = e.x), e.y < V.y1 && (V.y1 = e.y), e.x > V.x2 && (V.x2 = e.x), e.y > V.y2 && (V.y2 = e.y)
                }), Me(I, L.path_pathData) && (M.linePath.setPathData(I), L.path_pathData = I, P = !0, le ? (Ae(M, M.plugsFace), Ae(M, M.lineMaskCaps)) : re && Ae(M, M.linePath), M.events.apl_path && M.events.apl_path.forEach(function(e) {
                    e(M, I)
                }))), P)), ee.viewBox = (B = (N = e).curStats, R = N.aplStats, F = B.path_edge, G = B.viewBox_bBox, D = R.viewBox_bBox, z = N.svg.viewBox.baseVal, j = N.svg.style, H = !1, T = Math.max(B.line_strokeWidth / 2, B.viewBox_plugBCircleSE[0] || 0, B.viewBox_plugBCircleSE[1] || 0), W = {
                    x1: F.x1 - T,
                    y1: F.y1 - T,
                    x2: F.x2 + T,
                    y2: F.y2 + T
                }, N.events.new_edge4viewBox && N.events.new_edge4viewBox.forEach(function(e) {
                    e(N, W)
                }), G.x = B.lineMask_x = B.lineOutlineMask_x = B.maskBGRect_x = W.x1, G.y = B.lineMask_y = B.lineOutlineMask_y = B.maskBGRect_y = W.y1, G.width = W.x2 - W.x1, G.height = W.y2 - W.y1, ["x", "y", "width", "height"].forEach(function(e) {
                    var t;
                    (t = G[e]) !== D[e] && (z[e] = D[e] = t, j[oe[e]] = t + ("x" === e || "y" === e ? N.bodyOffset[e] : 0) + "px", H = !0)
                }), H), ee.mask = (Y = (U = e).curStats, X = U.aplStats, q = !1, Y.plug_enabled ? [0, 1].forEach(function(e) {
                    Y.capsMaskMarker_enabledSE[e] = Y.plug_enabledSE[e] && Y.plug_colorTraSE[e] || Y.plugOutline_enabledSE[e] && Y.plugOutline_colorTraSE[e]
                }) : Y.capsMaskMarker_enabledSE[0] = Y.capsMaskMarker_enabledSE[1] = !1, Y.capsMaskMarker_enabled = Y.capsMaskMarker_enabledSE[0] || Y.capsMaskMarker_enabledSE[1], Y.lineMask_outlineMode = Y.lineOutline_enabled, Y.caps_enabled = Y.capsMaskMarker_enabled || Y.capsMaskAnchor_enabledSE[0] || Y.capsMaskAnchor_enabledSE[1], Y.lineMask_enabled = Y.caps_enabled || Y.lineMask_outlineMode, (Y.lineMask_enabled && !Y.lineMask_outlineMode || Y.lineOutline_enabled) && ["x", "y"].forEach(function(e) {
                    var t = "maskBGRect_" + e;
                    We(U, X, t, Z = Y[t]) && (U.maskBGRect[e].baseVal.value = Z, q = !0)
                }), We(U, X, "lineMask_enabled", Z = Y.lineMask_enabled) && (U.lineFace.style.mask = Z ? "url(#" + U.lineMaskId + ")" : "none", q = !0, ue && Ae(U, U.lineMask)), Y.lineMask_enabled && (We(U, X, "lineMask_outlineMode", Z = Y.lineMask_outlineMode) && (Z ? (U.lineMaskBG.style.display = "none", U.lineMaskShape.style.display = "inline") : (U.lineMaskBG.style.display = "inline", U.lineMaskShape.style.display = "none"), q = !0), ["x", "y"].forEach(function(e) {
                    var t = "lineMask_" + e;
                    We(U, X, t, Z = Y[t]) && (U.lineMask[e].baseVal.value = Z, q = !0)
                }), We(U, X, "caps_enabled", Z = Y.caps_enabled) && (U.lineMaskCaps.style.display = U.lineOutlineMaskCaps.style.display = Z ? "inline" : "none", q = !0, ue && Ae(U, U.capsMaskLine)), Y.caps_enabled && ([0, 1].forEach(function(e) {
                    var t;
                    We(U, X.capsMaskAnchor_enabledSE, e, Z = Y.capsMaskAnchor_enabledSE[e]) && (U.capsMaskAnchorSE[e].style.display = Z ? "inline" : "none", q = !0, ue && Ae(U, U.lineMask)), Y.capsMaskAnchor_enabledSE[e] && (Me(t = Y.capsMaskAnchor_pathDataSE[e], X.capsMaskAnchor_pathDataSE[e]) && (U.capsMaskAnchorSE[e].setPathData(t), X.capsMaskAnchor_pathDataSE[e] = t, q = !0), We(U, X.capsMaskAnchor_strokeWidthSE, e, Z = Y.capsMaskAnchor_strokeWidthSE[e]) && (U.capsMaskAnchorSE[e].style.strokeWidth = Z + "px", q = !0))
                }), We(U, X, "capsMaskMarker_enabled", Z = Y.capsMaskMarker_enabled) && (U.capsMaskLine.style.display = Z ? "inline" : "none", q = !0), Y.capsMaskMarker_enabled && [0, 1].forEach(function(n) {
                    var e = Y.capsMaskMarker_plugSE[n],
                        t = e !== ne ? ae[ie[e]] : null,
                        a = Ne(n, t);
                    We(U, X.capsMaskMarker_enabledSE, n, Z = Y.capsMaskMarker_enabledSE[n]) && (U.capsMaskLine.style[a.prop] = Z ? "url(#" + U.lineMaskMarkerIdSE[n] + ")" : "none", q = !0), Y.capsMaskMarker_enabledSE[n] && (We(U, X.capsMaskMarker_plugSE, n, e) && (U.capsMaskMarkerShapeSE[n].href.baseVal = "#" + t.elmId, Pe(U, U.capsMaskMarkerSE[n], a.orient, t.bBox, U.svg, U.capsMaskMarkerShapeSE[n], U.capsMaskLine), q = !0, re && (Ae(U, U.capsMaskLine), Ae(U, U.lineFace))), ["markerWidth", "markerHeight"].forEach(function(e) {
                        var t = "capsMaskMarker_" + e + "SE";
                        We(U, X[t], n, Z = Y[t][n]) && (U.capsMaskMarkerSE[n][e].baseVal.value = Z, q = !0)
                    }))
                }))), Y.lineOutline_enabled && ["x", "y"].forEach(function(e) {
                    var t = "lineOutlineMask_" + e;
                    We(U, X, t, Z = Y[t]) && (U.lineOutlineMask[e].baseVal.value = Z, q = !0)
                }), q), t.effect && (J = (Q = e).curStats, $ = Q.aplStats, Object.keys(te).forEach(function(e) {
                    var t = te[e],
                        n = e + "_enabled",
                        a = e + "_options",
                        i = J[a];
                    We(Q, $, n, K = J[n]) ? (K && ($[a] = de(i)), t[K ? "init" : "remove"](Q)) : K && ce(i, $[a]) && (t.remove(Q), $[n] = !0, $[a] = de(i), t.init(Q))
                })), (se || ue) && ee.line && !ee.path && Ae(e, e.lineShape), se && ee.plug && !ee.line && Ae(e, e.plugsFace), Ve(e)
            }

            function ze(e, t) {
                return {
                    duration: w(e.duration) && 0 < e.duration ? e.duration : t.duration,
                    timing: c.validTiming(e.timing) ? e.timing : de(t.timing)
                }
            }

            function je(e, t, n, a) {
                var i, o = e.curStats,
                    l = e.aplStats,
                    r = {};

                function s() {
                    ["show_on", "show_effect", "show_animOptions"].forEach(function(e) {
                        l[e] = o[e]
                    })
                }
                o.show_on = t, n && g[n] && (o.show_effect = n, o.show_animOptions = ze(k(a) ? a : {}, g[n].defaultAnimOptions)), r.show_on = o.show_on !== l.show_on, r.show_effect = o.show_effect !== l.show_effect, r.show_animOptions = ce(o.show_animOptions, l.show_animOptions), r.show_effect || r.show_animOptions ? o.show_inAnim ? (i = r.show_effect ? g[l.show_effect].stop(e, !0, !0) : g[l.show_effect].stop(e), s(), g[l.show_effect].init(e, i)) : r.show_on && (l.show_effect && r.show_effect && g[l.show_effect].stop(e, !0, !0), s(), g[l.show_effect].init(e)) : r.show_on && (s(), g[l.show_effect].start(e))
            }

            function He(e, t, n) {
                var a = {
                    props: e,
                    optionName: n
                };
                return !(!(e.attachments.indexOf(t) < 0) || t.conf.bind && !t.conf.bind(t, a)) && (e.attachments.push(t), t.boundTargets.push(a), !0)
            }

            function Ue(n, a, e) {
                var i = n.attachments.indexOf(a); - 1 < i && n.attachments.splice(i, 1), a.boundTargets.some(function(e, t) {
                    return e.props === n && (a.conf.unbind && a.conf.unbind(a, e), i = t, !0)
                }) && (a.boundTargets.splice(i, 1), e || Le(function() {
                    a.boundTargets.length || o(a)
                }))
            }

            function Ze(s, u) {
                var e, i, h = s.options,
                    p = {};

                function f(e, t, n, a, i) {
                    var o = {};
                    return n ? null != a ? (o.container = e[n], o.key = a) : (o.container = e, o.key = n) : (o.container = e, o.key = t), o.default = i, o.acceptsAuto = null == o.default, o
                }

                function c(e, t, n, a, i, o, l) {
                    var r, s, u, h = f(e, n, i, o, l);
                    return null != t[n] && (s = (t[n] + "").toLowerCase()) && (h.acceptsAuto && s === x || (u = a[s])) && u !== h.container[h.key] && (h.container[h.key] = u, r = !0), null != h.container[h.key] || h.acceptsAuto || (h.container[h.key] = h.default, r = !0), r
                }

                function d(e, t, n, a, i, o, l, r, s) {
                    var u, h, p, c, d = f(e, n, i, o, l);
                    if (!a) {
                        if (null == d.default) throw new Error("Invalid `type`: " + n);
                        a = typeof d.default
                    }
                    return null != t[n] && (d.acceptsAuto && (t[n] + "").toLowerCase() === x || (p = h = t[n], ("number" === (c = a) ? w(p) : typeof p === c) && (h = s && "string" === a && h ? h.trim() : h, 1) && (!r || r(h)))) && h !== d.container[d.key] && (d.container[d.key] = h, u = !0), null != d.container[d.key] || d.acceptsAuto || (d.container[d.key] = d.default, u = !0), u
                }
                if (u = u || {}, ["start", "end"].forEach(function(e, t) {
                        var n = u[e],
                            a = !1;
                        if (n && (ye(n) || (a = _(n, "anchor"))) && n !== h.anchorSE[t]) {
                            if (!1 !== s.optionIsAttach.anchorSE[t] && Ue(s, $[h.anchorSE[t]._id]), a && !He(s, $[n._id], e)) throw new Error("Can't bind attachment");
                            h.anchorSE[t] = n, s.optionIsAttach.anchorSE[t] = a, i = p.position = !0
                        }
                    }), !h.anchorSE[0] || !h.anchorSE[1] || h.anchorSE[0] === h.anchorSE[1]) throw new Error("`start` and `end` are required.");
                i && (e = function(e, t) {
                    var n, a, i;
                    if (!(n = me(e)) || !(a = me(t))) throw new Error("Cannot get frames.");
                    return n.length && a.length && (n.reverse(), a.reverse(), n.some(function(t) {
                        return a.some(function(e) {
                            return e === t && (i = e.contentWindow, !0)
                        })
                    })), i || window
                }(!1 !== s.optionIsAttach.anchorSE[0] ? $[h.anchorSE[0]._id].element : h.anchorSE[0], !1 !== s.optionIsAttach.anchorSE[1] ? $[h.anchorSE[1]._id].element : h.anchorSE[1])) !== s.baseWindow && (! function(a, e) {
                    var t, n, i, o, l, r, s, u, h, p, c = a.aplStats,
                        d = e.document,
                        f = v + "-" + a._id;

                    function y(e) {
                        var t = n.appendChild(d.createElementNS(b, "mask"));
                        return t.id = e, t.maskUnits.baseVal = SVGUnitTypes.SVG_UNIT_TYPE_USERSPACEONUSE, [t.x, t.y, t.width, t.height].forEach(function(e) {
                            e.baseVal.newValueSpecifiedUnits(SVGLength.SVG_LENGTHTYPE_PX, 0)
                        }), t
                    }

                    function S(e) {
                        var t = n.appendChild(d.createElementNS(b, "marker"));
                        return t.id = e, t.markerUnits.baseVal = SVGMarkerElement.SVG_MARKERUNITS_STROKEWIDTH, t.viewBox.baseVal || t.setAttribute("viewBox", "0 0 0 0"), t
                    }

                    function m(e) {
                        return [e.width, e.height].forEach(function(e) {
                            e.baseVal.newValueSpecifiedUnits(SVGLength.SVG_LENGTHTYPE_PERCENTAGE, 100)
                        }), e
                    }
                    a.pathList = {}, Te(c, Y), Object.keys(te).forEach(function(e) {
                        var t = e + "_enabled";
                        c[t] && (te[e].remove(a), c[t] = !1)
                    }), a.baseWindow && a.svg && a.baseWindow.document.body.removeChild(a.svg), Re(a.baseWindow = e), a.bodyOffset = Be(e), a.svg = t = d.createElementNS(b, "svg"), t.className.baseVal = v, t.viewBox.baseVal || t.setAttribute("viewBox", "0 0 0 0"), a.defs = n = t.appendChild(d.createElementNS(b, "defs")), a.linePath = o = n.appendChild(d.createElementNS(b, "path")), o.id = l = f + "-line-path", o.className.baseVal = v + "-line-path", ue && (o.style.fill = "none"), a.lineShape = o = n.appendChild(d.createElementNS(b, "use")), o.id = r = f + "-line-shape", o.href.baseVal = "#" + l, (i = n.appendChild(d.createElementNS(b, "g"))).id = s = f + "-caps", a.capsMaskAnchorSE = [0, 1].map(function() {
                        var e = i.appendChild(d.createElementNS(b, "path"));
                        return e.className.baseVal = v + "-caps-mask-anchor", e
                    }), a.lineMaskMarkerIdSE = [f + "-caps-mask-marker-0", f + "-caps-mask-marker-1"], a.capsMaskMarkerSE = [0, 1].map(function(e) {
                        return S(a.lineMaskMarkerIdSE[e])
                    }), a.capsMaskMarkerShapeSE = [0, 1].map(function(e) {
                        var t = a.capsMaskMarkerSE[e].appendChild(d.createElementNS(b, "use"));
                        return t.className.baseVal = v + "-caps-mask-marker-shape", t
                    }), a.capsMaskLine = o = i.appendChild(d.createElementNS(b, "use")), o.className.baseVal = v + "-caps-mask-line", o.href.baseVal = "#" + r, a.maskBGRect = o = m(n.appendChild(d.createElementNS(b, "rect"))), o.id = u = f + "-mask-bg-rect", o.className.baseVal = v + "-mask-bg-rect", ue && (o.style.fill = "white"), a.lineMask = m(y(a.lineMaskId = f + "-line-mask")), a.lineMaskBG = o = a.lineMask.appendChild(d.createElementNS(b, "use")), o.href.baseVal = "#" + u, a.lineMaskShape = o = a.lineMask.appendChild(d.createElementNS(b, "use")), o.className.baseVal = v + "-line-mask-shape", o.href.baseVal = "#" + l, o.style.display = "none", a.lineMaskCaps = o = a.lineMask.appendChild(d.createElementNS(b, "use")), o.href.baseVal = "#" + s, a.lineOutlineMask = m(y(h = f + "-line-outline-mask")), (o = a.lineOutlineMask.appendChild(d.createElementNS(b, "use"))).href.baseVal = "#" + u, a.lineOutlineMaskShape = o = a.lineOutlineMask.appendChild(d.createElementNS(b, "use")), o.className.baseVal = v + "-line-outline-mask-shape", o.href.baseVal = "#" + l, a.lineOutlineMaskCaps = o = a.lineOutlineMask.appendChild(d.createElementNS(b, "use")), o.href.baseVal = "#" + s, a.face = t.appendChild(d.createElementNS(b, "g")), a.lineFace = o = a.face.appendChild(d.createElementNS(b, "use")), o.href.baseVal = "#" + r, a.lineOutlineFace = o = a.face.appendChild(d.createElementNS(b, "use")), o.href.baseVal = "#" + r, o.style.mask = "url(#" + h + ")", o.style.display = "none", a.plugMaskIdSE = [f + "-plug-mask-0", f + "-plug-mask-1"], a.plugMaskSE = [0, 1].map(function(e) {
                        return y(a.plugMaskIdSE[e])
                    }), a.plugMaskShapeSE = [0, 1].map(function(e) {
                        var t = a.plugMaskSE[e].appendChild(d.createElementNS(b, "use"));
                        return t.className.baseVal = v + "-plug-mask-shape", t
                    }), p = [], a.plugOutlineMaskSE = [0, 1].map(function(e) {
                        return y(p[e] = f + "-plug-outline-mask-" + e)
                    }), a.plugOutlineMaskShapeSE = [0, 1].map(function(e) {
                        var t = a.plugOutlineMaskSE[e].appendChild(d.createElementNS(b, "use"));
                        return t.className.baseVal = v + "-plug-outline-mask-shape", t
                    }), a.plugMarkerIdSE = [f + "-plug-marker-0", f + "-plug-marker-1"], a.plugMarkerSE = [0, 1].map(function(e) {
                        var t = S(a.plugMarkerIdSE[e]);
                        return ue && (t.markerUnits.baseVal = SVGMarkerElement.SVG_MARKERUNITS_USERSPACEONUSE), t
                    }), a.plugMarkerShapeSE = [0, 1].map(function(e) {
                        return a.plugMarkerSE[e].appendChild(d.createElementNS(b, "g"))
                    }), a.plugFaceSE = [0, 1].map(function(e) {
                        return a.plugMarkerShapeSE[e].appendChild(d.createElementNS(b, "use"))
                    }), a.plugOutlineFaceSE = [0, 1].map(function(e) {
                        var t = a.plugMarkerShapeSE[e].appendChild(d.createElementNS(b, "use"));
                        return t.style.mask = "url(#" + p[e] + ")", t.style.display = "none", t
                    }), a.plugsFace = o = a.face.appendChild(d.createElementNS(b, "use")), o.className.baseVal = v + "-plugs-face", o.href.baseVal = "#" + r, o.style.display = "none", a.curStats.show_inAnim ? (a.isShown = 1, g[c.show_effect].stop(a, !0)) : a.isShown || (t.style.visibility = "hidden"), d.body.appendChild(t), [0, 1, 2].forEach(function(e) {
                        var t, n = a.options.labelSEM[e];
                        n && _(n, "label") && (t = $[n._id]).conf.initSvg && t.conf.initSvg(t, a)
                    })
                }(s, e), p.line = p.plug = p.lineOutline = p.plugOutline = p.faces = p.effect = !0), p.position = c(h, u, "path", m, null, null, pe.path) || p.position, p.position = c(h, u, "startSocket", n, "socketSE", 0) || p.position, p.position = c(h, u, "endSocket", n, "socketSE", 1) || p.position, [u.startSocketGravity, u.endSocketGravity].forEach(function(e, t) {
                    var n, a, i = !1;
                    null != e && (Array.isArray(e) ? w(e[0]) && w(e[1]) && (i = [e[0], e[1]], Array.isArray(h.socketGravitySE[t]) && (n = i, a = h.socketGravitySE[t], n.length === a.length && n.every(function(e, t) {
                        return e === a[t]
                    })) && (i = !1)) : ((e + "").toLowerCase() === x ? i = null : w(e) && 0 <= e && (i = e), i === h.socketGravitySE[t] && (i = !1)), !1 !== i && (h.socketGravitySE[t] = i, p.position = !0))
                }), p.line = d(h, u, "color", null, "lineColor", null, pe.lineColor, null, !0) || p.line, p.line = d(h, u, "size", null, "lineSize", null, pe.lineSize, function(e) {
                    return 0 < e
                }) || p.line, ["startPlug", "endPlug"].forEach(function(e, t) {
                    p.plug = c(h, u, e, E, "plugSE", t, pe.plugSE[t]) || p.plug, p.plug = d(h, u, e + "Color", "string", "plugColorSE", t, null, null, !0) || p.plug, p.plug = d(h, u, e + "Size", null, "plugSizeSE", t, pe.plugSizeSE[t], function(e) {
                        return 0 < e
                    }) || p.plug
                }), p.lineOutline = d(h, u, "outline", null, "lineOutlineEnabled", null, pe.lineOutlineEnabled) || p.lineOutline, p.lineOutline = d(h, u, "outlineColor", null, "lineOutlineColor", null, pe.lineOutlineColor, null, !0) || p.lineOutline, p.lineOutline = d(h, u, "outlineSize", null, "lineOutlineSize", null, pe.lineOutlineSize, function(e) {
                    return 0 < e && e <= .48
                }) || p.lineOutline, ["startPlugOutline", "endPlugOutline"].forEach(function(e, t) {
                    p.plugOutline = d(h, u, e, null, "plugOutlineEnabledSE", t, pe.plugOutlineEnabledSE[t]) || p.plugOutline, p.plugOutline = d(h, u, e + "Color", "string", "plugOutlineColorSE", t, null, null, !0) || p.plugOutline, p.plugOutline = d(h, u, e + "Size", null, "plugOutlineSizeSE", t, pe.plugOutlineSizeSE[t], function(e) {
                        return 1 <= e
                    }) || p.plugOutline
                }), ["startLabel", "endLabel", "middleLabel"].forEach(function(e, t) {
                    var n, a, i, o = u[e],
                        l = h.labelSEM[t] && !s.optionIsAttach.labelSEM[t] ? $[h.labelSEM[t]._id].text : h.labelSEM[t],
                        r = !1;
                    if ((n = "string" == typeof o) && (o = o.trim()), (n || o && (r = _(o, "label"))) && o !== l) {
                        if (h.labelSEM[t] && (Ue(s, $[h.labelSEM[t]._id]), h.labelSEM[t] = ""), o) {
                            if (r ? (a = $[(i = o)._id]).boundTargets.slice().forEach(function(e) {
                                    a.conf.removeOption(a, e)
                                }) : i = new S(y.captionLabel, [o]), !He(s, $[i._id], e)) throw new Error("Can't bind attachment");
                            h.labelSEM[t] = i
                        }
                        s.optionIsAttach.labelSEM[t] = r
                    }
                }), Object.keys(te).forEach(function(a) {
                    var e, t, o = te[a],
                        n = a + "_enabled",
                        i = a + "_options";

                    function l(a) {
                        var i = {};
                        return o.optionsConf.forEach(function(e) {
                            var t = e[0],
                                n = e[3];
                            null == e[4] || i[n] || (i[n] = []), ("function" == typeof t ? t : "id" === t ? c : d).apply(null, [i, a].concat(e.slice(1)))
                        }), i
                    }

                    function r(e) {
                        var t, n = a + "_animOptions";
                        return e.hasOwnProperty("animation") ? k(e.animation) ? t = s.curStats[n] = ze(e.animation, o.defaultAnimOptions) : (t = !!e.animation, s.curStats[n] = t ? ze({}, o.defaultAnimOptions) : null) : (t = !!o.defaultEnabled, s.curStats[n] = t ? ze({}, o.defaultAnimOptions) : null), t
                    }
                    u.hasOwnProperty(a) && (e = u[a], k(e) ? (s.curStats[n] = !0, t = s.curStats[i] = l(e), o.anim && (s.curStats[i].animation = r(e))) : (t = s.curStats[n] = !!e) && (s.curStats[i] = l({}), o.anim && (s.curStats[i].animation = r({}))), ce(t, h[a]) && (h[a] = t, p.effect = !0))
                }), De(s, p)
            }

            function Ye(e, t, n) {
                var a = {
                    options: {
                        anchorSE: [],
                        socketSE: [],
                        socketGravitySE: [],
                        plugSE: [],
                        plugColorSE: [],
                        plugSizeSE: [],
                        plugOutlineEnabledSE: [],
                        plugOutlineColorSE: [],
                        plugOutlineSizeSE: [],
                        labelSEM: ["", "", ""]
                    },
                    optionIsAttach: {
                        anchorSE: [!1, !1],
                        labelSEM: [!1, !1, !1]
                    },
                    curStats: {},
                    aplStats: {},
                    attachments: [],
                    events: {},
                    reflowTargets: []
                };
                Te(a.curStats, Y), Te(a.aplStats, Y), Object.keys(te).forEach(function(e) {
                    var t = te[e].stats;
                    Te(a.curStats, t), Te(a.aplStats, t), a.options[e] = !1
                }), Te(a.curStats, X), Te(a.aplStats, X), a.curStats.show_effect = q, a.curStats.show_animOptions = de(g[q].defaultAnimOptions), Object.defineProperty(this, "_id", {
                    value: ++J
                }), a._id = this._id, K[this._id] = a, 1 === arguments.length && (n = e, e = null), n = n || {}, (e || t) && (n = de(n), e && (n.start = e), t && (n.end = t)), a.isShown = a.aplStats.show_on = !n.hide, this.setOptions(n)
            }
            return te = {
                    dash: {
                        stats: {
                            dash_len: {},
                            dash_gap: {},
                            dash_maxOffset: {}
                        },
                        anim: !0,
                        defaultAnimOptions: {
                            duration: 1e3,
                            timing: "linear"
                        },
                        optionsConf: [
                            ["type", "len", "number", null, null, null, function(e) {
                                return 0 < e
                            }],
                            ["type", "gap", "number", null, null, null, function(e) {
                                return 0 < e
                            }]
                        ],
                        init: function(e) {
                            Ie(e, "apl_line_strokeWidth", te.dash.update), e.lineFace.style.strokeDashoffset = 0, te.dash.update(e)
                        },
                        remove: function(e) {
                            var t = e.curStats;
                            Ce(e, "apl_line_strokeWidth", te.dash.update), t.dash_animId && (c.remove(t.dash_animId), t.dash_animId = null), e.lineFace.style.strokeDasharray = "none", e.lineFace.style.strokeDashoffset = 0, Te(e.aplStats, te.dash.stats)
                        },
                        update: function(t) {
                            var e, n = t.curStats,
                                a = t.aplStats,
                                i = a.dash_options,
                                o = !1;
                            n.dash_len = i.len || 2 * a.line_strokeWidth, n.dash_gap = i.gap || a.line_strokeWidth, n.dash_maxOffset = n.dash_len + n.dash_gap, o = We(t, a, "dash_len", n.dash_len) || o, (o = We(t, a, "dash_gap", n.dash_gap) || o) && (t.lineFace.style.strokeDasharray = a.dash_len + "," + a.dash_gap), n.dash_animOptions ? (o = We(t, a, "dash_maxOffset", n.dash_maxOffset), a.dash_animOptions && (o || ce(n.dash_animOptions, a.dash_animOptions)) && (n.dash_animId && (e = c.stop(n.dash_animId), c.remove(n.dash_animId)), a.dash_animOptions = null), a.dash_animOptions || (n.dash_animId = c.add(function(e) {
                                return (1 - e) * a.dash_maxOffset + "px"
                            }, function(e) {
                                t.lineFace.style.strokeDashoffset = e
                            }, n.dash_animOptions.duration, 0, n.dash_animOptions.timing, !1, e), a.dash_animOptions = de(n.dash_animOptions))) : a.dash_animOptions && (n.dash_animId && (c.remove(n.dash_animId), n.dash_animId = null), t.lineFace.style.strokeDashoffset = 0, a.dash_animOptions = null)
                        }
                    },
                    gradient: {
                        stats: {
                            gradient_colorSE: {
                                hasSE: !0
                            },
                            gradient_pointSE: {
                                hasSE: !0,
                                hasProps: !0
                            }
                        },
                        optionsConf: [
                            ["type", "startColor", "string", "colorSE", 0, null, null, !0],
                            ["type", "endColor", "string", "colorSE", 1, null, null, !0]
                        ],
                        init: function(e) {
                            var t, a = e.baseWindow.document,
                                n = e.defs,
                                i = v + "-" + e._id + "-gradient";
                            e.efc_gradient_gradient = t = n.appendChild(a.createElementNS(b, "linearGradient")), t.id = i, t.gradientUnits.baseVal = SVGUnitTypes.SVG_UNIT_TYPE_USERSPACEONUSE, [t.x1, t.y1, t.x2, t.y2].forEach(function(e) {
                                e.baseVal.newValueSpecifiedUnits(SVGLength.SVG_LENGTHTYPE_PX, 0)
                            }), e.efc_gradient_stopSE = [0, 1].map(function(t) {
                                var n = e.efc_gradient_gradient.appendChild(a.createElementNS(b, "stop"));
                                try {
                                    n.offset.baseVal = t
                                } catch (e) {
                                    if (e.code !== DOMException.NO_MODIFICATION_ALLOWED_ERR) throw e;
                                    n.setAttribute("offset", t)
                                }
                                return n
                            }), Ie(e, "cur_plug_colorSE", te.gradient.update), Ie(e, "apl_path", te.gradient.update), e.curStats.line_altColor = !0, e.lineFace.style.stroke = "url(#" + i + ")", te.gradient.update(e)
                        },
                        remove: function(e) {
                            e.efc_gradient_gradient && (e.defs.removeChild(e.efc_gradient_gradient), e.efc_gradient_gradient = e.efc_gradient_stopSE = null), Ce(e, "cur_plug_colorSE", te.gradient.update), Ce(e, "apl_path", te.gradient.update), e.curStats.line_altColor = !1, e.lineFace.style.stroke = e.curStats.line_color, Te(e.aplStats, te.gradient.stats)
                        },
                        update: function(a) {
                            var e, t, i = a.curStats,
                                o = a.aplStats,
                                n = o.gradient_options,
                                l = a.pathList.animVal || a.pathList.baseVal;
                            [0, 1].forEach(function(e) {
                                i.gradient_colorSE[e] = n.colorSE[e] || i.plug_colorSE[e]
                            }), t = l[0][0], i.gradient_pointSE[0] = {
                                x: t.x,
                                y: t.y
                            }, t = (e = l[l.length - 1])[e.length - 1], i.gradient_pointSE[1] = {
                                x: t.x,
                                y: t.y
                            }, [0, 1].forEach(function(t) {
                                var n;
                                We(a, o.gradient_colorSE, t, n = i.gradient_colorSE[t]) && (ue ? (n = fe(n), a.efc_gradient_stopSE[t].style.stopColor = n[1], a.efc_gradient_stopSE[t].style.stopOpacity = n[0]) : a.efc_gradient_stopSE[t].style.stopColor = n), ["x", "y"].forEach(function(e) {
                                    (n = i.gradient_pointSE[t][e]) !== o.gradient_pointSE[t][e] && (a.efc_gradient_gradient[e + (t + 1)].baseVal.value = o.gradient_pointSE[t][e] = n)
                                })
                            })
                        }
                    },
                    dropShadow: {
                        stats: {
                            dropShadow_dx: {},
                            dropShadow_dy: {},
                            dropShadow_blur: {},
                            dropShadow_color: {},
                            dropShadow_opacity: {},
                            dropShadow_x: {},
                            dropShadow_y: {}
                        },
                        optionsConf: [
                            ["type", "dx", null, null, null, 2],
                            ["type", "dy", null, null, null, 4],
                            ["type", "blur", null, null, null, 3, function(e) {
                                return 0 <= e
                            }],
                            ["type", "color", null, null, null, "#000", null, !0],
                            ["type", "opacity", null, null, null, .8, function(e) {
                                return 0 <= e && e <= 1
                            }]
                        ],
                        init: function(t) {
                            var e, n, a, i, o, l = t.baseWindow.document,
                                r = t.defs,
                                s = v + "-" + t._id + "-dropShadow",
                                u = (e = l, n = s, o = {}, "boolean" != typeof p && (p = !!window.SVGFEDropShadowElement && !ue), o.elmsAppend = [o.elmFilter = a = e.createElementNS(b, "filter")], a.filterUnits.baseVal = SVGUnitTypes.SVG_UNIT_TYPE_USERSPACEONUSE, a.x.baseVal.newValueSpecifiedUnits(SVGLength.SVG_LENGTHTYPE_PX, 0), a.y.baseVal.newValueSpecifiedUnits(SVGLength.SVG_LENGTHTYPE_PX, 0), a.width.baseVal.newValueSpecifiedUnits(SVGLength.SVG_LENGTHTYPE_PERCENTAGE, 100), a.height.baseVal.newValueSpecifiedUnits(SVGLength.SVG_LENGTHTYPE_PERCENTAGE, 100), a.id = n, p ? (o.elmOffset = o.elmBlur = i = a.appendChild(e.createElementNS(b, "feDropShadow")), o.styleFlood = i.style) : (o.elmBlur = a.appendChild(e.createElementNS(b, "feGaussianBlur")), o.elmOffset = i = a.appendChild(e.createElementNS(b, "feOffset")), i.result.baseVal = "offsetblur", i = a.appendChild(e.createElementNS(b, "feFlood")), o.styleFlood = i.style, (i = a.appendChild(e.createElementNS(b, "feComposite"))).in2.baseVal = "offsetblur", i.operator.baseVal = SVGFECompositeElement.SVG_FECOMPOSITE_OPERATOR_IN, (i = a.appendChild(e.createElementNS(b, "feMerge"))).appendChild(e.createElementNS(b, "feMergeNode")), i.appendChild(e.createElementNS(b, "feMergeNode")).in1.baseVal = "SourceGraphic"), o);
                            ["elmFilter", "elmOffset", "elmBlur", "styleFlood", "elmsAppend"].forEach(function(e) {
                                t["efc_dropShadow_" + e] = u[e]
                            }), u.elmsAppend.forEach(function(e) {
                                r.appendChild(e)
                            }), t.face.setAttribute("filter", "url(#" + s + ")"), Ie(t, "new_edge4viewBox", te.dropShadow.adjustEdge), te.dropShadow.update(t)
                        },
                        remove: function(e) {
                            var t = e.defs;
                            e.efc_dropShadow_elmsAppend && (e.efc_dropShadow_elmsAppend.forEach(function(e) {
                                t.removeChild(e)
                            }), e.efc_dropShadow_elmFilter = e.efc_dropShadow_elmOffset = e.efc_dropShadow_elmBlur = e.efc_dropShadow_styleFlood = e.efc_dropShadow_elmsAppend = null), Ce(e, "new_edge4viewBox", te.dropShadow.adjustEdge), De(e, {}), e.face.removeAttribute("filter"), Te(e.aplStats, te.dropShadow.stats)
                        },
                        update: function(e) {
                            var t, n, a = e.curStats,
                                i = e.aplStats,
                                o = i.dropShadow_options;
                            a.dropShadow_dx = t = o.dx, We(e, i, "dropShadow_dx", t) && (e.efc_dropShadow_elmOffset.dx.baseVal = t, n = !0), a.dropShadow_dy = t = o.dy, We(e, i, "dropShadow_dy", t) && (e.efc_dropShadow_elmOffset.dy.baseVal = t, n = !0), a.dropShadow_blur = t = o.blur, We(e, i, "dropShadow_blur", t) && (e.efc_dropShadow_elmBlur.setStdDeviation(t, t), n = !0), n && De(e, {}), a.dropShadow_color = t = o.color, We(e, i, "dropShadow_color", t) && (e.efc_dropShadow_styleFlood.floodColor = t), a.dropShadow_opacity = t = o.opacity, We(e, i, "dropShadow_opacity", t) && (e.efc_dropShadow_styleFlood.floodOpacity = t)
                        },
                        adjustEdge: function(a, i) {
                            var e, t, o = a.curStats,
                                l = a.aplStats;
                            null != o.dropShadow_dx && (e = 3 * o.dropShadow_blur, (t = {
                                x1: i.x1 - e + o.dropShadow_dx,
                                y1: i.y1 - e + o.dropShadow_dy,
                                x2: i.x2 + e + o.dropShadow_dx,
                                y2: i.y2 + e + o.dropShadow_dy
                            }).x1 < i.x1 && (i.x1 = t.x1), t.y1 < i.y1 && (i.y1 = t.y1), t.x2 > i.x2 && (i.x2 = t.x2), t.y2 > i.y2 && (i.y2 = t.y2), ["x", "y"].forEach(function(e) {
                                var t, n = "dropShadow_" + e;
                                o[n] = t = i[e + "1"], We(a, l, n, t) && (a.efc_dropShadow_elmFilter[e].baseVal.value = t)
                            }))
                        }
                    }
                }, Object.keys(te).forEach(function(e) {
                    var t = te[e],
                        n = t.stats;
                    n[e + "_enabled"] = {
                        iniValue: !1
                    }, n[e + "_options"] = {
                        hasProps: !0
                    }, t.anim && (n[e + "_animOptions"] = {}, n[e + "_animId"] = {})
                }), g = {
                    none: {
                        defaultAnimOptions: {},
                        init: function(e, t) {
                            var n = e.curStats;
                            n.show_animId && (c.remove(n.show_animId), n.show_animId = null), g.none.start(e, t)
                        },
                        start: function(e, t) {
                            g.none.stop(e, !0)
                        },
                        stop: function(e, t, n) {
                            var a = e.curStats;
                            return n = null != n ? n : e.aplStats.show_on, a.show_inAnim = !1, t && Ge(e, n), n ? 1 : 0
                        }
                    },
                    fade: {
                        defaultAnimOptions: {
                            duration: 300,
                            timing: "linear"
                        },
                        init: function(n, e) {
                            var t = n.curStats,
                                a = n.aplStats;
                            t.show_animId && c.remove(t.show_animId), t.show_animId = c.add(function(e) {
                                return e
                            }, function(e, t) {
                                t ? g.fade.stop(n, !0) : (n.svg.style.opacity = e + "", le && (Ae(n, n.svg), Ve(n)))
                            }, a.show_animOptions.duration, 1, a.show_animOptions.timing, null, !1), g.fade.start(n, e)
                        },
                        start: function(e, t) {
                            var n, a = e.curStats;
                            a.show_inAnim && (n = c.stop(a.show_animId)), Ge(e, 1), a.show_inAnim = !0, c.start(a.show_animId, !e.aplStats.show_on, null != t ? t : n)
                        },
                        stop: function(e, t, n) {
                            var a, i = e.curStats;
                            return n = null != n ? n : e.aplStats.show_on, a = i.show_inAnim ? c.stop(i.show_animId) : n ? 1 : 0, i.show_inAnim = !1, t && (e.svg.style.opacity = n ? "" : "0", Ge(e, n)), a
                        }
                    },
                    draw: {
                        defaultAnimOptions: {
                            duration: 500,
                            timing: [.58, 0, .42, 1]
                        },
                        init: function(n, e) {
                            var t = n.curStats,
                                a = n.aplStats,
                                l = n.pathList.baseVal,
                                i = Oe(l),
                                r = i.segsLen,
                                s = i.lenAll;
                            t.show_animId && c.remove(t.show_animId), t.show_animId = c.add(function(e) {
                                var t, n, a, i, o = -1;
                                if (0 === e) n = [
                                    [l[0][0], l[0][0]]
                                ];
                                else if (1 === e) n = l;
                                else {
                                    for (t = s * e, n = []; t >= r[++o];) n.push(l[o]), t -= r[o];
                                    t && (2 === (a = l[o]).length ? n.push([a[0], ve(a[0], a[1], t / r[o])]) : (i = xe(a[0], a[1], a[2], a[3], ke(a[0], a[1], a[2], a[3], t)), n.push([a[0], i.fromP1, i.fromP2, i])))
                                }
                                return n
                            }, function(e, t) {
                                t ? g.draw.stop(n, !0) : (n.pathList.animVal = e, De(n, {
                                    path: !0
                                }))
                            }, a.show_animOptions.duration, 1, a.show_animOptions.timing, null, !1), g.draw.start(n, e)
                        },
                        start: function(e, t) {
                            var n, a = e.curStats;
                            a.show_inAnim && (n = c.stop(a.show_animId)), Ge(e, 1), a.show_inAnim = !0, Ie(e, "apl_position", g.draw.update), c.start(a.show_animId, !e.aplStats.show_on, null != t ? t : n)
                        },
                        stop: function(e, t, n) {
                            var a, i = e.curStats;
                            return n = null != n ? n : e.aplStats.show_on, a = i.show_inAnim ? c.stop(i.show_animId) : n ? 1 : 0, i.show_inAnim = !1, t && (e.pathList.animVal = n ? null : [
                                [e.pathList.baseVal[0][0], e.pathList.baseVal[0][0]]
                            ], De(e, {
                                path: !0
                            }), Ge(e, n)), a
                        },
                        update: function(e) {
                            Ce(e, "apl_position", g.draw.update), e.curStats.show_inAnim ? g.draw.init(e, g.draw.stop(e)) : e.aplStats.show_animOptions = {}
                        }
                    }
                },
                function() {
                    function r(n) {
                        return function(e) {
                            var t = {};
                            t[n] = e, this.setOptions(t)
                        }
                    } [
                        ["start", "anchorSE", 0],
                        ["end", "anchorSE", 1],
                        ["color", "lineColor"],
                        ["size", "lineSize"],
                        ["startSocketGravity", "socketGravitySE", 0],
                        ["endSocketGravity", "socketGravitySE", 1],
                        ["startPlugColor", "plugColorSE", 0],
                        ["endPlugColor", "plugColorSE", 1],
                        ["startPlugSize", "plugSizeSE", 0],
                        ["endPlugSize", "plugSizeSE", 1],
                        ["outline", "lineOutlineEnabled"],
                        ["outlineColor", "lineOutlineColor"],
                        ["outlineSize", "lineOutlineSize"],
                        ["startPlugOutline", "plugOutlineEnabledSE", 0],
                        ["endPlugOutline", "plugOutlineEnabledSE", 1],
                        ["startPlugOutlineColor", "plugOutlineColorSE", 0],
                        ["endPlugOutlineColor", "plugOutlineColorSE", 1],
                        ["startPlugOutlineSize", "plugOutlineSizeSE", 0],
                        ["endPlugOutlineSize", "plugOutlineSizeSE", 1]
                    ].forEach(function(e) {
                        var t = e[0],
                            n = e[1],
                            a = e[2];
                        Object.defineProperty(Ye.prototype, t, {
                            get: function() {
                                var e = null != a ? K[this._id].options[n][a] : n ? K[this._id].options[n] : K[this._id].options[t];
                                return null == e ? x : de(e)
                            },
                            set: r(t),
                            enumerable: !0
                        })
                    }), [
                        ["path", m],
                        ["startSocket", n, "socketSE", 0],
                        ["endSocket", n, "socketSE", 1],
                        ["startPlug", E, "plugSE", 0],
                        ["endPlug", E, "plugSE", 1]
                    ].forEach(function(e) {
                        var a = e[0],
                            i = e[1],
                            o = e[2],
                            l = e[3];
                        Object.defineProperty(Ye.prototype, a, {
                            get: function() {
                                var t, n = null != l ? K[this._id].options[o][l] : o ? K[this._id].options[o] : K[this._id].options[a];
                                return n ? Object.keys(i).some(function(e) {
                                    return i[e] === n && (t = e, !0)
                                }) ? t : new Error("It's broken") : x
                            },
                            set: r(a),
                            enumerable: !0
                        })
                    }), Object.keys(te).forEach(function(n) {
                        var a = te[n];
                        Object.defineProperty(Ye.prototype, n, {
                            get: function() {
                                var u, e, t = K[this._id].options[n];
                                return k(t) ? (u = t, e = a.optionsConf.reduce(function(e, t) {
                                    var n, a = t[0],
                                        i = t[1],
                                        o = t[2],
                                        l = t[3],
                                        r = t[4],
                                        s = null != r ? u[l][r] : l ? u[l] : u[i];
                                    return e[i] = "id" === a ? s ? Object.keys(o).some(function(e) {
                                        return o[e] === s && (n = e, !0)
                                    }) ? n : new Error("It's broken") : x : null == s ? x : de(s), e
                                }, {}), a.anim && (e.animation = de(u.animation)), e) : t
                            },
                            set: r(n),
                            enumerable: !0
                        })
                    }), ["startLabel", "endLabel", "middleLabel"].forEach(function(e, n) {
                        Object.defineProperty(Ye.prototype, e, {
                            get: function() {
                                var e = K[this._id],
                                    t = e.options;
                                return t.labelSEM[n] && !e.optionIsAttach.labelSEM[n] ? $[t.labelSEM[n]._id].text : t.labelSEM[n] || ""
                            },
                            set: r(e),
                            enumerable: !0
                        })
                    })
                }(), Ye.prototype.setOptions = function(e) {
                    return Ze(K[this._id], e), this
                }, Ye.prototype.position = function() {
                    return De(K[this._id], {
                        position: !0
                    }), this
                }, Ye.prototype.remove = function() {
                    var t = K[this._id],
                        n = t.curStats;
                    Object.keys(te).forEach(function(e) {
                        var t = e + "_animId";
                        n[t] && c.remove(n[t])
                    }), n.show_animId && c.remove(n.show_animId), t.attachments.slice().forEach(function(e) {
                        Ue(t, e)
                    }), t.baseWindow && t.svg && t.baseWindow.document.body.removeChild(t.svg), delete K[this._id]
                }, Ye.prototype.show = function(e, t) {
                    return je(K[this._id], !0, e, t), this
                }, Ye.prototype.hide = function(e, t) {
                    return je(K[this._id], !1, e, t), this
                }, o = function(t) {
                    t && $[t._id] && (t.boundTargets.slice().forEach(function(e) {
                        Ue(e.props, t, !0)
                    }), t.conf.remove && t.conf.remove(t), delete $[t._id])
                }, S = function() {
                    function e(e, t) {
                        var n, a = {
                                conf: e,
                                curStats: {},
                                aplStats: {},
                                boundTargets: []
                            },
                            i = {};
                        e.argOptions.every(function(e) {
                            return !(!t.length || ("string" == typeof e.type ? typeof t[0] !== e.type : "function" != typeof e.type || !e.type(t[0]))) && (i[e.optionName] = t.shift(), !0)
                        }), n = t.length && k(t[0]) ? de(t[0]) : {}, Object.keys(i).forEach(function(e) {
                            n[e] = i[e]
                        }), e.stats && (Te(a.curStats, e.stats), Te(a.aplStats, e.stats)), Object.defineProperty(this, "_id", {
                            value: ++ee
                        }), Object.defineProperty(this, "isRemoved", {
                            get: function() {
                                return !$[this._id]
                            }
                        }), a._id = this._id, e.init && !e.init(a, n) || ($[this._id] = a)
                    }
                    return e.prototype.remove = function() {
                        var t = this,
                            n = $[t._id];
                        n && (n.boundTargets.slice().forEach(function(e) {
                            n.conf.removeOption(n, e)
                        }), Le(function() {
                            var e = $[t._id];
                            e && (console.error("LeaderLineAttachment was not removed by removeOption"), o(e))
                        }))
                    }, e
                }(), window.LeaderLineAttachment = S, _ = function(e, t) {
                    return e instanceof S && (!(e.isRemoved || t && $[e._id].conf.type !== t) || null)
                }, y = {
                    pointAnchor: {
                        type: "anchor",
                        argOptions: [{
                            optionName: "element",
                            type: ye
                        }],
                        init: function(e, t) {
                            return e.element = y.pointAnchor.checkElement(t.element), e.x = y.pointAnchor.parsePercent(t.x, !0) || [.5, !0], e.y = y.pointAnchor.parsePercent(t.y, !0) || [.5, !0], !0
                        },
                        removeOption: function(e, t) {
                            var n = t.props,
                                a = {},
                                i = e.element,
                                o = n.options.anchorSE["start" === t.optionName ? 1 : 0];
                            i === o && (i = o === document.body ? new S(y.pointAnchor, [i]) : document.body), a[t.optionName] = i, Ze(n, a)
                        },
                        getBBoxNest: function(e, t) {
                            var n = ge(e.element, t.baseWindow),
                                a = n.width,
                                i = n.height;
                            return n.width = n.height = 0, n.left = n.right = n.left + e.x[0] * (e.x[1] ? a : 1), n.top = n.bottom = n.top + e.y[0] * (e.y[1] ? i : 1), n
                        },
                        parsePercent: function(e, t) {
                            var n, a, i = !1;
                            return w(e) ? a = e : "string" == typeof e && (n = u.exec(e)) && n[2] && (i = 0 !== (a = parseFloat(n[1]) / 100)), null != a && (t || 0 <= a) ? [a, i] : null
                        },
                        checkElement: function(e) {
                            if (null == e) e = document.body;
                            else if (!ye(e)) throw new Error("`element` must be Element");
                            return e
                        }
                    },
                    areaAnchor: {
                        type: "anchor",
                        argOptions: [{
                            optionName: "element",
                            type: ye
                        }, {
                            optionName: "shape",
                            type: "string"
                        }],
                        stats: {
                            color: {},
                            strokeWidth: {},
                            elementWidth: {},
                            elementHeight: {},
                            elementLeft: {},
                            elementTop: {},
                            pathListRel: {},
                            bBoxRel: {},
                            pathData: {},
                            viewBoxBBox: {
                                hasProps: !0
                            },
                            dashLen: {},
                            dashGap: {}
                        },
                        init: function(i, e) {
                            var t, n, a, o = [];
                            return i.element = y.pointAnchor.checkElement(e.element), "string" == typeof e.color && (i.color = e.color.trim()), "string" == typeof e.fillColor && (i.fill = e.fillColor.trim()), w(e.size) && 0 <= e.size && (i.size = e.size), e.dash && (i.dash = !0, w(e.dash.len) && 0 < e.dash.len && (i.dashLen = e.dash.len), w(e.dash.gap) && 0 < e.dash.gap && (i.dashGap = e.dash.gap)), "circle" === e.shape ? i.shape = e.shape : "polygon" === e.shape && Array.isArray(e.points) && 3 <= e.points.length && e.points.every(function(e) {
                                var t = {};
                                return !(!(t.x = y.pointAnchor.parsePercent(e[0], !0)) || !(t.y = y.pointAnchor.parsePercent(e[1], !0))) && (o.push(t), (t.x[1] || t.y[1]) && (i.hasRatio = !0), !0)
                            }) ? (i.shape = e.shape, i.points = o) : (i.shape = "rect", i.radius = w(e.radius) && 0 <= e.radius ? e.radius : 0), "rect" !== i.shape && "circle" !== i.shape || (i.x = y.pointAnchor.parsePercent(e.x, !0) || [-.05, !0], i.y = y.pointAnchor.parsePercent(e.y, !0) || [-.05, !0], i.width = y.pointAnchor.parsePercent(e.width) || [1.1, !0], i.height = y.pointAnchor.parsePercent(e.height) || [1.1, !0], (i.x[1] || i.y[1] || i.width[1] || i.height[1]) && (i.hasRatio = !0)), t = i.element.ownerDocument, i.svg = n = t.createElementNS(b, "svg"), n.className.baseVal = v + "-areaAnchor", n.viewBox.baseVal || n.setAttribute("viewBox", "0 0 0 0"), i.path = n.appendChild(t.createElementNS(b, "path")), i.path.style.fill = i.fill || "none", i.isShown = !1, n.style.visibility = "hidden", t.body.appendChild(n), Re(a = t.defaultView), i.bodyOffset = Be(a), i.updateColor = function() {
                                var e, t = i.curStats,
                                    n = i.aplStats,
                                    a = i.boundTargets.length ? i.boundTargets[0].props.curStats : null;
                                t.color = e = i.color || (a ? a.line_color : pe.lineColor), We(i, n, "color", e) && (i.path.style.stroke = e)
                            }, i.updateShow = function() {
                                Ge(i, i.boundTargets.some(function(e) {
                                    return !0 === e.props.isShown
                                }))
                            }, !0
                        },
                        bind: function(e, t) {
                            var n = t.props;
                            return e.color || Ie(n, "cur_line_color", e.updateColor), Ie(n, "svgShow", e.updateShow), Le(function() {
                                e.updateColor(), e.updateShow()
                            }), !0
                        },
                        unbind: function(e, t) {
                            var n = t.props;
                            e.color || Ce(n, "cur_line_color", e.updateColor), Ce(n, "svgShow", e.updateShow), 1 < e.boundTargets.length && Le(function() {
                                e.updateColor(), e.updateShow(), y.areaAnchor.update(e) && e.boundTargets.forEach(function(e) {
                                    De(e.props, {
                                        position: !0
                                    })
                                })
                            })
                        },
                        removeOption: function(e, t) {
                            y.pointAnchor.removeOption(e, t)
                        },
                        remove: function(t) {
                            t.boundTargets.length && (console.error("LeaderLineAttachment was not unbound by remove"), t.boundTargets.forEach(function(e) {
                                y.areaAnchor.unbind(t, e)
                            })), t.svg.parentNode.removeChild(t.svg)
                        },
                        getStrokeWidth: function(e, t) {
                            return y.areaAnchor.update(e) && 1 < e.boundTargets.length && Le(function() {
                                e.boundTargets.forEach(function(e) {
                                    e.props !== t && De(e.props, {
                                        position: !0
                                    })
                                })
                            }), e.curStats.strokeWidth
                        },
                        getPathData: function(e, t) {
                            var n = ge(e.element, t.baseWindow);
                            return we(e.curStats.pathListRel, function(e) {
                                e.x += n.left, e.y += n.top
                            })
                        },
                        getBBoxNest: function(e, t) {
                            var n = ge(e.element, t.baseWindow),
                                a = e.curStats.bBoxRel;
                            return {
                                left: a.left + n.left,
                                top: a.top + n.top,
                                right: a.right + n.left,
                                bottom: a.bottom + n.top,
                                width: a.width,
                                height: a.height
                            }
                        },
                        update: function(t) {
                            var a, n, i, o, e, l, r, s, u, h, p, c, d, f, y, S, m, g, _, v, E, x, b, k, w, O, M, I, C, L, A, V, P = t.curStats,
                                N = t.aplStats,
                                T = t.boundTargets.length ? t.boundTargets[0].props.curStats : null,
                                W = {};
                            if (W.strokeWidth = We(t, P, "strokeWidth", null != t.size ? t.size : T ? T.line_strokeWidth : pe.lineSize), a = Se(t.element), W.elementWidth = We(t, P, "elementWidth", a.width), W.elementHeight = We(t, P, "elementHeight", a.height), W.elementLeft = We(t, P, "elementLeft", a.left), W.elementTop = We(t, P, "elementTop", a.top), W.strokeWidth || t.hasRatio && (W.elementWidth || W.elementHeight)) {
                                switch (t.shape) {
                                    case "rect":
                                        (v = {
                                            left: t.x[0] * (t.x[1] ? a.width : 1),
                                            top: t.y[0] * (t.y[1] ? a.height : 1),
                                            width: t.width[0] * (t.width[1] ? a.width : 1),
                                            height: t.height[0] * (t.height[1] ? a.height : 1)
                                        }).right = v.left + v.width, v.bottom = v.top + v.height, k = P.strokeWidth / 2, x = (b = Math.min(v.width, v.height)) ? b / 2 * Math.SQRT2 + k : 0, (E = t.radius ? t.radius <= x ? t.radius : x : 0) ? (O = E - (w = (E - k) / Math.SQRT2), I = E * U, M = [{
                                            x: v.left - O,
                                            y: v.top + w
                                        }, {
                                            x: v.left + w,
                                            y: v.top - O
                                        }, {
                                            x: v.right - w,
                                            y: v.top - O
                                        }, {
                                            x: v.right + O,
                                            y: v.top + w
                                        }, {
                                            x: v.right + O,
                                            y: v.bottom - w
                                        }, {
                                            x: v.right - w,
                                            y: v.bottom + O
                                        }, {
                                            x: v.left + w,
                                            y: v.bottom + O
                                        }, {
                                            x: v.left - O,
                                            y: v.bottom - w
                                        }], P.pathListRel = [
                                            [M[0], {
                                                x: M[0].x,
                                                y: M[0].y - I
                                            }, {
                                                x: M[1].x - I,
                                                y: M[1].y
                                            }, M[1]]
                                        ], M[1].x !== M[2].x && P.pathListRel.push([M[1], M[2]]), P.pathListRel.push([M[2], {
                                            x: M[2].x + I,
                                            y: M[2].y
                                        }, {
                                            x: M[3].x,
                                            y: M[3].y - I
                                        }, M[3]]), M[3].y !== M[4].y && P.pathListRel.push([M[3], M[4]]), P.pathListRel.push([M[4], {
                                            x: M[4].x,
                                            y: M[4].y + I
                                        }, {
                                            x: M[5].x + I,
                                            y: M[5].y
                                        }, M[5]]), M[5].x !== M[6].x && P.pathListRel.push([M[5], M[6]]), P.pathListRel.push([M[6], {
                                            x: M[6].x - I,
                                            y: M[6].y
                                        }, {
                                            x: M[7].x,
                                            y: M[7].y + I
                                        }, M[7]]), M[7].y !== M[0].y && P.pathListRel.push([M[7], M[0]]), P.pathListRel.push([]), O = E - w + P.strokeWidth / 2, M = [{
                                            x: v.left - O,
                                            y: v.top - O
                                        }, {
                                            x: v.right + O,
                                            y: v.bottom + O
                                        }]) : (O = P.strokeWidth / 2, M = [{
                                            x: v.left - O,
                                            y: v.top - O
                                        }, {
                                            x: v.right + O,
                                            y: v.bottom + O
                                        }], P.pathListRel = [
                                            [M[0], {
                                                x: M[1].x,
                                                y: M[0].y
                                            }],
                                            [{
                                                x: M[1].x,
                                                y: M[0].y
                                            }, M[1]],
                                            [M[1], {
                                                x: M[0].x,
                                                y: M[1].y
                                            }],
                                            []
                                        ], M = [{
                                            x: v.left - P.strokeWidth,
                                            y: v.top - P.strokeWidth
                                        }, {
                                            x: v.right + P.strokeWidth,
                                            y: v.bottom + P.strokeWidth
                                        }]), P.bBoxRel = {
                                            left: M[0].x,
                                            top: M[0].y,
                                            right: M[1].x,
                                            bottom: M[1].y,
                                            width: M[1].x - M[0].x,
                                            height: M[1].y - M[0].y
                                        };
                                        break;
                                    case "circle":
                                        (r = {
                                            left: t.x[0] * (t.x[1] ? a.width : 1),
                                            top: t.y[0] * (t.y[1] ? a.height : 1),
                                            width: t.width[0] * (t.width[1] ? a.width : 1),
                                            height: t.height[0] * (t.height[1] ? a.height : 1)
                                        }).width || r.height || (r.width = r.height = 10), r.width || (r.width = r.height), r.height || (r.height = r.width), r.right = r.left + r.width, r.bottom = r.top + r.height, s = r.left + r.width / 2, u = r.top + r.height / 2, f = P.strokeWidth / 2, y = r.width / 2, S = r.height / 2, h = y * Math.SQRT2 + f, p = S * Math.SQRT2 + f, c = h * U, d = p * U, _ = [{
                                            x: s - h,
                                            y: u
                                        }, {
                                            x: s,
                                            y: u - p
                                        }, {
                                            x: s + h,
                                            y: u
                                        }, {
                                            x: s,
                                            y: u + p
                                        }], P.pathListRel = [
                                            [_[0], {
                                                x: _[0].x,
                                                y: _[0].y - d
                                            }, {
                                                x: _[1].x - c,
                                                y: _[1].y
                                            }, _[1]],
                                            [_[1], {
                                                x: _[1].x + c,
                                                y: _[1].y
                                            }, {
                                                x: _[2].x,
                                                y: _[2].y - d
                                            }, _[2]],
                                            [_[2], {
                                                x: _[2].x,
                                                y: _[2].y + d
                                            }, {
                                                x: _[3].x + c,
                                                y: _[3].y
                                            }, _[3]],
                                            [_[3], {
                                                x: _[3].x - c,
                                                y: _[3].y
                                            }, {
                                                x: _[0].x,
                                                y: _[0].y + d
                                            }, _[0]],
                                            []
                                        ], m = h - y + P.strokeWidth / 2, g = p - S + P.strokeWidth / 2, _ = [{
                                            x: r.left - m,
                                            y: r.top - g
                                        }, {
                                            x: r.right + m,
                                            y: r.bottom + g
                                        }], P.bBoxRel = {
                                            left: _[0].x,
                                            top: _[0].y,
                                            right: _[1].x,
                                            bottom: _[1].y,
                                            width: _[1].x - _[0].x,
                                            height: _[1].y - _[0].y
                                        };
                                        break;
                                    case "polygon":
                                        t.points.forEach(function(e) {
                                            var t = e.x[0] * (e.x[1] ? a.width : 1),
                                                n = e.y[0] * (e.y[1] ? a.height : 1);
                                            i ? (t < i.left && (i.left = t), t > i.right && (i.right = t), n < i.top && (i.top = n), n > i.bottom && (i.bottom = n)) : i = {
                                                left: t,
                                                right: t,
                                                top: n,
                                                bottom: n
                                            }, o ? P.pathListRel.push([o, {
                                                x: t,
                                                y: n
                                            }]) : P.pathListRel = [], o = {
                                                x: t,
                                                y: n
                                            }
                                        }), P.pathListRel.push([]), e = P.strokeWidth / 2, l = [{
                                            x: i.left - e,
                                            y: i.top - e
                                        }, {
                                            x: i.right + e,
                                            y: i.bottom + e
                                        }], P.bBoxRel = {
                                            left: l[0].x,
                                            top: l[0].y,
                                            right: l[1].x,
                                            bottom: l[1].y,
                                            width: l[1].x - l[0].x,
                                            height: l[1].y - l[0].y
                                        }
                                }
                                W.pathListRel = W.bBoxRel = !0
                            }
                            return (W.pathListRel || W.elementLeft || W.elementTop) && (P.pathData = we(P.pathListRel, function(e) {
                                e.x += a.left, e.y += a.top
                            })), We(t, N, "strokeWidth", n = P.strokeWidth) && (t.path.style.strokeWidth = n + "px"), Me(n = P.pathData, N.pathData) && (t.path.setPathData(n), N.pathData = n, W.pathData = !0), t.dash && (!W.pathData && (!W.strokeWidth || t.dashLen && t.dashGap) || (P.dashLen = t.dashLen || 2 * P.strokeWidth, P.dashGap = t.dashGap || P.strokeWidth), W.dash = We(t, N, "dashLen", P.dashLen) || W.dash, W.dash = We(t, N, "dashGap", P.dashGap) || W.dash, W.dash && (t.path.style.strokeDasharray = N.dashLen + "," + N.dashGap)), C = P.viewBoxBBox, L = N.viewBoxBBox, A = t.svg.viewBox.baseVal, V = t.svg.style, C.x = P.bBoxRel.left + a.left, C.y = P.bBoxRel.top + a.top, C.width = P.bBoxRel.width, C.height = P.bBoxRel.height, ["x", "y", "width", "height"].forEach(function(e) {
                                (n = C[e]) !== L[e] && (A[e] = L[e] = n, V[oe[e]] = n + ("x" === e || "y" === e ? t.bodyOffset[e] : 0) + "px")
                            }), W.strokeWidth || W.pathListRel || W.bBoxRel
                        }
                    },
                    mouseHoverAnchor: {
                        type: "anchor",
                        argOptions: [{
                            optionName: "element",
                            type: ye
                        }, {
                            optionName: "showEffectName",
                            type: "string"
                        }],
                        style: {
                            backgroundImage: "url('data:image/svg+xml;charset=utf-8;base64,PHN2ZyB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjI0IiBoZWlnaHQ9IjI0Ij48cG9seWdvbiBwb2ludHM9IjI0LDAgMCw4IDgsMTEgMCwxOSA1LDI0IDEzLDE2IDE2LDI0IiBmaWxsPSJjb3JhbCIvPjwvc3ZnPg==')",
                            backgroundSize: "",
                            backgroundRepeat: "no-repeat",
                            backgroundColor: "#f8f881",
                            cursor: "default"
                        },
                        hoverStyle: {
                            backgroundImage: "none",
                            backgroundColor: "#fadf8f"
                        },
                        padding: {
                            top: 1,
                            right: 15,
                            bottom: 1,
                            left: 2
                        },
                        minHeight: 15,
                        backgroundPosition: {
                            right: 2,
                            top: 2
                        },
                        backgroundSize: {
                            width: 12,
                            height: 12
                        },
                        dirKeys: [
                            ["top", "Top"],
                            ["right", "Right"],
                            ["bottom", "Bottom"],
                            ["left", "Left"]
                        ],
                        init: function(a, i) {
                            var o, t, e, n, l, r, s, u, h, p, c, d = y.mouseHoverAnchor,
                                f = {};
                            if (a.element = y.pointAnchor.checkElement(i.element), u = a.element, !((p = u.ownerDocument) && (h = p.defaultView) && h.HTMLElement && u instanceof h.HTMLElement)) throw new Error("`element` must be HTML element");
                            return d.style.backgroundSize = d.backgroundSize.width + "px " + d.backgroundSize.height + "px", ["style", "hoverStyle"].forEach(function(e) {
                                var n = d[e];
                                a[e] = Object.keys(n).reduce(function(e, t) {
                                    return e[t] = n[t], e
                                }, {})
                            }), "inline" === (o = a.element.ownerDocument.defaultView.getComputedStyle(a.element, "")).display ? a.style.display = "inline-block" : "none" === o.display && (a.style.display = "block"), y.mouseHoverAnchor.dirKeys.forEach(function(e) {
                                var t = e[0],
                                    n = "padding" + e[1];
                                parseFloat(o[n]) < d.padding[t] && (a.style[n] = d.padding[t] + "px")
                            }), a.style.display && (n = a.element.style.display, a.element.style.display = a.style.display), y.mouseHoverAnchor.dirKeys.forEach(function(e) {
                                var t = "padding" + e[1];
                                a.style[t] && (f[t] = a.element.style[t], a.element.style[t] = a.style[t])
                            }), (e = a.element.getBoundingClientRect()).height < d.minHeight && (le ? (c = d.minHeight, "content-box" === o.boxSizing ? c -= parseFloat(o.borderTopWidth) + parseFloat(o.borderBottomWidth) + parseFloat(o.paddingTop) + parseFloat(o.paddingBottom) : "padding-box" === o.boxSizing && (c -= parseFloat(o.borderTopWidth) + parseFloat(o.borderBottomWidth)), a.style.height = c + "px") : a.style.height = parseFloat(o.height) + (d.minHeight - e.height) + "px"), a.style.backgroundPosition = ue ? e.width - d.backgroundSize.width - d.backgroundPosition.right + "px " + d.backgroundPosition.top + "px" : "right " + d.backgroundPosition.right + "px top " + d.backgroundPosition.top + "px", a.style.display && (a.element.style.display = n), y.mouseHoverAnchor.dirKeys.forEach(function(e) {
                                var t = "padding" + e[1];
                                a.style[t] && (a.element.style[t] = f[t])
                            }), ["style", "hoverStyle"].forEach(function(e) {
                                var t = a[e],
                                    n = i[e];
                                k(n) && Object.keys(n).forEach(function(e) {
                                    "string" == typeof n[e] || w(n[e]) ? t[e] = n[e] : null == n[e] && delete t[e]
                                })
                            }), "function" == typeof i.onSwitch && (s = i.onSwitch), i.showEffectName && g[i.showEffectName] && (a.showEffectName = l = i.showEffectName), r = i.animOptions, a.elmStyle = t = a.element.style, a.mouseenter = function(e) {
                                a.hoverStyleSave = d.getStyles(t, Object.keys(a.hoverStyle)), d.setStyles(t, a.hoverStyle), a.boundTargets.forEach(function(e) {
                                    je(e.props, !0, l, r)
                                }), s && s(e)
                            }, a.mouseleave = function(e) {
                                d.setStyles(t, a.hoverStyleSave), a.boundTargets.forEach(function(e) {
                                    je(e.props, !1, l, r)
                                }), s && s(e)
                            }, !0
                        },
                        bind: function(e, t) {
                            var n, a, i, o, l;
                            return t.props.svg ? y.mouseHoverAnchor.llShow(t.props, !1, e.showEffectName) : Le(function() {
                                y.mouseHoverAnchor.llShow(t.props, !1, e.showEffectName)
                            }), e.enabled || (e.styleSave = y.mouseHoverAnchor.getStyles(e.elmStyle, Object.keys(e.style)), y.mouseHoverAnchor.setStyles(e.elmStyle, e.style), e.removeEventListener = (n = e.element, a = e.mouseenter, i = e.mouseleave, "onmouseenter" in n && "onmouseleave" in n ? (n.addEventListener("mouseenter", a, !1), n.addEventListener("mouseleave", i, !1), function() {
                                n.removeEventListener("mouseenter", a, !1), n.removeEventListener("mouseleave", i, !1)
                            }) : (console.warn("mouseenter and mouseleave events polyfill is enabled."), o = function(e) {
                                e.relatedTarget && (e.relatedTarget === this || this.compareDocumentPosition(e.relatedTarget) & Node.DOCUMENT_POSITION_CONTAINED_BY) || a.apply(this, arguments)
                            }, n.addEventListener("mouseover", o), l = function(e) {
                                e.relatedTarget && (e.relatedTarget === this || this.compareDocumentPosition(e.relatedTarget) & Node.DOCUMENT_POSITION_CONTAINED_BY) || i.apply(this, arguments)
                            }, n.addEventListener("mouseout", l), function() {
                                n.removeEventListener("mouseover", o, !1), n.removeEventListener("mouseout", l, !1)
                            })), e.enabled = !0), !0
                        },
                        unbind: function(e, t) {
                            e.enabled && e.boundTargets.length <= 1 && (e.removeEventListener(), y.mouseHoverAnchor.setStyles(e.elmStyle, e.styleSave), e.enabled = !1), y.mouseHoverAnchor.llShow(t.props, !0, e.showEffectName)
                        },
                        removeOption: function(e, t) {
                            y.pointAnchor.removeOption(e, t)
                        },
                        remove: function(t) {
                            t.boundTargets.length && (console.error("LeaderLineAttachment was not unbound by remove"), t.boundTargets.forEach(function(e) {
                                y.mouseHoverAnchor.unbind(t, e)
                            }))
                        },
                        getBBoxNest: function(e, t) {
                            return ge(e.element, t.baseWindow)
                        },
                        llShow: function(e, t, n) {
                            g[n || e.curStats.show_effect].stop(e, !0, t), e.aplStats.show_on = t
                        },
                        getStyles: function(n, e) {
                            return e.reduce(function(e, t) {
                                return e[t] = n[t], e
                            }, {})
                        },
                        setStyles: function(t, n) {
                            Object.keys(n).forEach(function(e) {
                                t[e] = n[e]
                            })
                        }
                    },
                    captionLabel: {
                        type: "label",
                        argOptions: [{
                            optionName: "text",
                            type: "string"
                        }],
                        stats: {
                            color: {},
                            x: {},
                            y: {}
                        },
                        textStyleProps: ["fontFamily", "fontStyle", "fontVariant", "fontWeight", "fontStretch", "fontSize", "fontSizeAdjust", "kerning", "letterSpacing", "wordSpacing", "textDecoration"],
                        init: function(u, t) {
                            return "string" == typeof t.text && (u.text = t.text.trim()), !!u.text && ("string" == typeof t.color && (u.color = t.color.trim()), u.outlineColor = "string" == typeof t.outlineColor ? t.outlineColor.trim() : "#fff", Array.isArray(t.offset) && w(t.offset[0]) && w(t.offset[1]) && (u.offset = {
                                x: t.offset[0],
                                y: t.offset[1]
                            }), w(t.lineOffset) && (u.lineOffset = t.lineOffset), y.captionLabel.textStyleProps.forEach(function(e) {
                                null != t[e] && (u[e] = t[e])
                            }), u.updateColor = function(e) {
                                y.captionLabel.updateColor(u, e)
                            }, u.updateSocketXY = function(e) {
                                var t, n, a, i, o = u.curStats,
                                    l = u.aplStats,
                                    r = e.curStats,
                                    s = r.position_socketXYSE[u.socketIndex];
                                null != s.x && (u.offset ? (o.x = s.x + u.offset.x, o.y = s.y + u.offset.y) : (t = u.height / 2, n = Math.max(r.attach_plugSideLenSE[u.socketIndex] || 0, r.line_strokeWidth / 2), a = r.position_socketXYSE[u.socketIndex ? 0 : 1], s.socketId === L || s.socketId === I ? (o.x = s.socketId === L ? s.x - t - u.width : s.x + t, o.y = a.y < s.y ? s.y + n + t : s.y - n - t - u.height) : (o.x = a.x < s.x ? s.x + n + t : s.x - n - t - u.width, o.y = s.socketId === M ? s.y - t - u.height : s.y + t)), We(u, l, "x", i = o.x) && (u.elmPosition.x.baseVal.getItem(0).value = i), We(u, l, "y", i = o.y) && (u.elmPosition.y.baseVal.getItem(0).value = i + u.height))
                            }, u.updatePath = function(e) {
                                var t, n, a = u.curStats,
                                    i = u.aplStats,
                                    o = e.pathList.animVal || e.pathList.baseVal;
                                o && (t = y.captionLabel.getMidPoint(o, u.lineOffset), a.x = t.x - u.width / 2, a.y = t.y - u.height / 2, We(u, i, "x", n = a.x) && (u.elmPosition.x.baseVal.getItem(0).value = n), We(u, i, "y", n = a.y) && (u.elmPosition.y.baseVal.getItem(0).value = n + u.height))
                            }, u.updateShow = function(e) {
                                y.captionLabel.updateShow(u, e)
                            }, ue && (u.adjustEdge = function(e, t) {
                                var n = u.curStats;
                                null != n.x && y.captionLabel.adjustEdge(t, {
                                    x: n.x,
                                    y: n.y,
                                    width: u.width,
                                    height: u.height
                                }, u.strokeWidth / 2)
                            }), !0)
                        },
                        updateColor: function(e, t) {
                            var n, a = e.curStats,
                                i = e.aplStats,
                                o = t.curStats;
                            a.color = n = e.color || o.line_color, We(e, i, "color", n) && (e.styleFill.fill = n)
                        },
                        updateShow: function(e, t) {
                            var n = !0 === t.isShown;
                            n !== e.isShown && (e.styleShow.visibility = n ? "" : "hidden", e.isShown = n)
                        },
                        adjustEdge: function(e, t, n) {
                            var a = {
                                x1: t.x - n,
                                y1: t.y - n,
                                x2: t.x + t.width + n,
                                y2: t.y + t.height + n
                            };
                            a.x1 < e.x1 && (e.x1 = a.x1), a.y1 < e.y1 && (e.y1 = a.y1), a.x2 > e.x2 && (e.x2 = a.x2), a.y2 > e.y2 && (e.y2 = a.y2)
                        },
                        newText: function(e, t, n, a, i) {
                            var o, l, r, s, u, h;
                            return (o = t.createElementNS(b, "text")).textContent = e, [o.x, o.y].forEach(function(e) {
                                var t = n.createSVGLength();
                                t.newValueSpecifiedUnits(SVGLength.SVG_LENGTHTYPE_PX, 0), e.baseVal.initialize(t)
                            }), "boolean" != typeof f && (f = "paintOrder" in o.style), i && !f ? (r = t.createElementNS(b, "defs"), o.id = a, r.appendChild(o), (u = (l = t.createElementNS(b, "g")).appendChild(t.createElementNS(b, "use"))).href.baseVal = "#" + a, (s = l.appendChild(t.createElementNS(b, "use"))).href.baseVal = "#" + a, (h = u.style).strokeLinejoin = "round", {
                                elmPosition: o,
                                styleText: o.style,
                                styleFill: s.style,
                                styleStroke: h,
                                styleShow: l.style,
                                elmsAppend: [r, l]
                            }) : (h = o.style, i && (h.strokeLinejoin = "round", h.paintOrder = "stroke"), {
                                elmPosition: o,
                                styleText: h,
                                styleFill: h,
                                styleStroke: i ? h : null,
                                styleShow: h,
                                elmsAppend: [o]
                            })
                        },
                        getMidPoint: function(e, t) {
                            var n, a, i, o = Oe(e),
                                l = o.segsLen,
                                r = o.lenAll,
                                s = -1;
                            if ((n = r / 2 + (t || 0)) <= 0) return 2 === (a = e[0]).length ? ve(a[0], a[1], 0) : xe(a[0], a[1], a[2], a[3], 0);
                            if (r <= n) return 2 === (a = e[e.length - 1]).length ? ve(a[0], a[1], 1) : xe(a[0], a[1], a[2], a[3], 1);
                            for (i = []; n > l[++s];) i.push(e[s]), n -= l[s];
                            return 2 === (a = e[s]).length ? ve(a[0], a[1], n / l[s]) : xe(a[0], a[1], a[2], a[3], ke(a[0], a[1], a[2], a[3], n))
                        },
                        initSvg: function(t, n) {
                            var e, a, i = y.captionLabel.newText(t.text, n.baseWindow.document, n.svg, v + "-captionLabel-" + t._id, t.outlineColor);
                            ["elmPosition", "styleFill", "styleShow", "elmsAppend"].forEach(function(e) {
                                t[e] = i[e]
                            }), t.isShown = !1, t.styleShow.visibility = "hidden", y.captionLabel.textStyleProps.forEach(function(e) {
                                null != t[e] && (i.styleText[e] = t[e])
                            }), i.elmsAppend.forEach(function(e) {
                                n.svg.appendChild(e)
                            }), e = i.elmPosition.getBBox(), t.width = e.width, t.height = e.height, t.outlineColor && (a = 10 < (a = e.height / 9) ? 10 : a < 2 ? 2 : a, i.styleStroke.strokeWidth = a + "px", i.styleStroke.stroke = t.outlineColor), t.strokeWidth = a || 0, Te(t.aplStats, y.captionLabel.stats), t.updateColor(n), t.refSocketXY ? t.updateSocketXY(n) : t.updatePath(n), ue && De(n, {}), t.updateShow(n)
                        },
                        bind: function(e, t) {
                            var n = t.props;
                            return e.color || Ie(n, "cur_line_color", e.updateColor), (e.refSocketXY = "startLabel" === t.optionName || "endLabel" === t.optionName) ? (e.socketIndex = "startLabel" === t.optionName ? 0 : 1, Ie(n, "apl_position", e.updateSocketXY), e.offset || (Ie(n, "cur_attach_plugSideLenSE", e.updateSocketXY), Ie(n, "cur_line_strokeWidth", e.updateSocketXY))) : Ie(n, "apl_path", e.updatePath), Ie(n, "svgShow", e.updateShow), ue && Ie(n, "new_edge4viewBox", e.adjustEdge), y.captionLabel.initSvg(e, n), !0
                        },
                        unbind: function(e, t) {
                            var n = t.props;
                            e.elmsAppend && (e.elmsAppend.forEach(function(e) {
                                n.svg.removeChild(e)
                            }), e.elmPosition = e.styleFill = e.styleShow = e.elmsAppend = null), Te(e.curStats, y.captionLabel.stats), Te(e.aplStats, y.captionLabel.stats), e.color || Ce(n, "cur_line_color", e.updateColor), e.refSocketXY ? (Ce(n, "apl_position", e.updateSocketXY), e.offset || (Ce(n, "cur_attach_plugSideLenSE", e.updateSocketXY), Ce(n, "cur_line_strokeWidth", e.updateSocketXY))) : Ce(n, "apl_path", e.updatePath), Ce(n, "svgShow", e.updateShow), ue && (Ce(n, "new_edge4viewBox", e.adjustEdge), De(n, {}))
                        },
                        removeOption: function(e, t) {
                            var n = t.props,
                                a = {};
                            a[t.optionName] = "", Ze(n, a)
                        },
                        remove: function(t) {
                            t.boundTargets.length && (console.error("LeaderLineAttachment was not unbound by remove"), t.boundTargets.forEach(function(e) {
                                y.captionLabel.unbind(t, e)
                            }))
                        }
                    },
                    pathLabel: {
                        type: "label",
                        argOptions: [{
                            optionName: "text",
                            type: "string"
                        }],
                        stats: {
                            color: {},
                            startOffset: {},
                            pathData: {}
                        },
                        init: function(s, t) {
                            return "string" == typeof t.text && (s.text = t.text.trim()), !!s.text && ("string" == typeof t.color && (s.color = t.color.trim()), s.outlineColor = "string" == typeof t.outlineColor ? t.outlineColor.trim() : "#fff", w(t.lineOffset) && (s.lineOffset = t.lineOffset), y.captionLabel.textStyleProps.forEach(function(e) {
                                null != t[e] && (s[e] = t[e])
                            }), s.updateColor = function(e) {
                                y.captionLabel.updateColor(s, e)
                            }, s.updatePath = function(e) {
                                var t, n = s.curStats,
                                    a = s.aplStats,
                                    i = e.curStats,
                                    o = e.pathList.animVal || e.pathList.baseVal;
                                o && (n.pathData = t = y.pathLabel.getOffsetPathData(o, i.line_strokeWidth / 2 + s.strokeWidth / 2 + s.height / 4, 1.25 * s.height), Me(t, a.pathData) && (s.elmPath.setPathData(t), a.pathData = t, s.bBox = s.elmPosition.getBBox(), s.updateStartOffset(e)))
                            }, s.updateStartOffset = function(e) {
                                var t, n, a, i, o = s.curStats,
                                    l = s.aplStats,
                                    r = e.curStats;
                                o.pathData && ((2 !== s.semIndex || s.lineOffset) && (t = o.pathData.reduce(function(e, t) {
                                    var n, a = t.values;
                                    switch (t.type) {
                                        case "M":
                                            i = {
                                                x: a[0],
                                                y: a[1]
                                            };
                                            break;
                                        case "L":
                                            n = {
                                                x: a[0],
                                                y: a[1]
                                            }, i && (e += _e(i, n)), i = n;
                                            break;
                                        case "C":
                                            n = {
                                                x: a[4],
                                                y: a[5]
                                            }, i && (e += be(i, {
                                                x: a[0],
                                                y: a[1]
                                            }, {
                                                x: a[2],
                                                y: a[3]
                                            }, n)), i = n
                                    }
                                    return e
                                }, 0), a = 0 === s.semIndex ? 0 : 1 === s.semIndex ? t : t / 2, 2 !== s.semIndex && (n = Math.max(r.attach_plugBackLenSE[s.semIndex] || 0, r.line_strokeWidth / 2) + s.strokeWidth / 2 + s.height / 4, a = (a += 0 === s.semIndex ? n : -n) < 0 ? 0 : t < a ? t : a), s.lineOffset && (a = (a += s.lineOffset) < 0 ? 0 : t < a ? t : a), o.startOffset = a, We(s, l, "startOffset", a) && (s.elmOffset.startOffset.baseVal.value = a)))
                            }, s.updateShow = function(e) {
                                y.captionLabel.updateShow(s, e)
                            }, ue && (s.adjustEdge = function(e, t) {
                                s.bBox && y.captionLabel.adjustEdge(t, s.bBox, s.strokeWidth / 2)
                            }), !0)
                        },
                        getOffsetPathData: function(e, x, n) {
                            var b, a, i = 3,
                                k = [];

                            function w(e, t) {
                                return Math.abs(e.x - t.x) < i && Math.abs(e.y - t.y) < i
                            }
                            return e.forEach(function(e) {
                                var t, n, a, i, o, l, r, s, u, h, p, c, d, f, y, S, m, g, _, v, E;
                                2 === e.length ? (g = e[0], _ = e[1], v = x, E = Math.atan2(g.y - _.y, _.x - g.x) + .5 * Math.PI, t = [{
                                    x: g.x + Math.cos(E) * v,
                                    y: g.y + Math.sin(E) * v * -1
                                }, {
                                    x: _.x + Math.cos(E) * v,
                                    y: _.y + Math.sin(E) * v * -1
                                }], b ? (a = b.points, 0 <= (i = Math.atan2(a[1].y - a[0].y, a[0].x - a[1].x) - Math.atan2(e[0].y - e[1].y, e[1].x - e[0].x)) && i <= Math.PI ? n = {
                                    type: "line",
                                    points: t,
                                    inside: !0
                                } : (l = Ee(a[0], a[1], x), o = Ee(t[1], t[0], x), s = a[0], h = o, p = t[1], c = (u = l).x - s.x, d = u.y - s.y, f = p.x - h.x, y = p.y - h.y, S = (-d * (s.x - h.x) + c * (s.y - h.y)) / (-f * d + c * y), m = (f * (s.y - h.y) - y * (s.x - h.x)) / (-f * d + c * y), (r = 0 <= S && S <= 1 && 0 <= m && m <= 1 ? {
                                    x: s.x + m * c,
                                    y: s.y + m * d
                                } : null) ? n = {
                                    type: "line",
                                    points: [a[1] = r, t[1]]
                                } : (a[1] = w(o, l) ? o : l, n = {
                                    type: "line",
                                    points: [o, t[1]]
                                }), b.len = _e(a[0], a[1]))) : n = {
                                    type: "line",
                                    points: t
                                }, n.len = _e(n.points[0], n.points[1]), k.push(b = n)) : (k.push({
                                    type: "cubic",
                                    points: function(e, t, n, a, i, o) {
                                        for (var l, r, s = be(e, t, n, a) / o, u = 1 / (o < i ? s * (i / o) : s), h = [], p = 0; r = (90 - (l = xe(e, t, n, a, p)).angle) * (Math.PI / 180), h.push({
                                                x: l.x + Math.cos(r) * i,
                                                y: l.y + Math.sin(r) * i * -1
                                            }), !(1 <= p);) 1 < (p += u) && (p = 1);
                                        return h
                                    }(e[0], e[1], e[2], e[3], x, 16)
                                }), b = null)
                            }), b = null, k.forEach(function(e) {
                                var t;
                                "line" === e.type ? (e.inside && (b.len > x ? ((t = b.points)[1] = Ee(t[0], t[1], -x), b.len = _e(t[0], t[1])) : (b.points = null, b.len = 0), e.len > x + n ? ((t = e.points)[0] = Ee(t[1], t[0], -(x + n)), e.len = _e(t[0], t[1])) : (e.points = null, e.len = 0)), b = e) : b = null
                            }), k.reduce(function(t, e) {
                                var n = e.points;
                                return n && (a && w(n[0], a) || t.push({
                                    type: "M",
                                    values: [n[0].x, n[0].y]
                                }), "line" === e.type ? t.push({
                                    type: "L",
                                    values: [n[1].x, n[1].y]
                                }) : (n.shift(), n.forEach(function(e) {
                                    t.push({
                                        type: "L",
                                        values: [e.x, e.y]
                                    })
                                })), a = n[n.length - 1]), t
                            }, [])
                        },
                        newText: function(e, t, n, a) {
                            var i, o, l, r, s, u, h, p, c, d;
                            return (r = (l = t.createElementNS(b, "defs")).appendChild(t.createElementNS(b, "path"))).id = i = n + "-path", (u = (s = t.createElementNS(b, "text")).appendChild(t.createElementNS(b, "textPath"))).href.baseVal = "#" + i, u.startOffset.baseVal.newValueSpecifiedUnits(SVGLength.SVG_LENGTHTYPE_PX, 0), u.textContent = e, "boolean" != typeof f && (f = "paintOrder" in s.style), a && !f ? (s.id = o = n + "-text", l.appendChild(s), (c = (h = t.createElementNS(b, "g")).appendChild(t.createElementNS(b, "use"))).href.baseVal = "#" + o, (p = h.appendChild(t.createElementNS(b, "use"))).href.baseVal = "#" + o, (d = c.style).strokeLinejoin = "round", {
                                elmPosition: s,
                                elmPath: r,
                                elmOffset: u,
                                styleText: s.style,
                                styleFill: p.style,
                                styleStroke: d,
                                styleShow: h.style,
                                elmsAppend: [l, h]
                            }) : (d = s.style, a && (d.strokeLinejoin = "round", d.paintOrder = "stroke"), {
                                elmPosition: s,
                                elmPath: r,
                                elmOffset: u,
                                styleText: d,
                                styleFill: d,
                                styleStroke: a ? d : null,
                                styleShow: d,
                                elmsAppend: [l, s]
                            })
                        },
                        initSvg: function(t, n) {
                            var e, a, i = y.pathLabel.newText(t.text, n.baseWindow.document, v + "-pathLabel-" + t._id, t.outlineColor);
                            ["elmPosition", "elmPath", "elmOffset", "styleFill", "styleShow", "elmsAppend"].forEach(function(e) {
                                t[e] = i[e]
                            }), t.isShown = !1, t.styleShow.visibility = "hidden", y.captionLabel.textStyleProps.forEach(function(e) {
                                null != t[e] && (i.styleText[e] = t[e])
                            }), i.elmsAppend.forEach(function(e) {
                                n.svg.appendChild(e)
                            }), i.elmPath.setPathData([{
                                type: "M",
                                values: [0, 100]
                            }, {
                                type: "h",
                                values: [100]
                            }]), e = i.elmPosition.getBBox(), i.styleText.textAnchor = ["start", "end", "middle"][t.semIndex], 2 !== t.semIndex || t.lineOffset || i.elmOffset.startOffset.baseVal.newValueSpecifiedUnits(SVGLength.SVG_LENGTHTYPE_PERCENTAGE, 50), t.height = e.height, t.outlineColor && (a = 10 < (a = e.height / 9) ? 10 : a < 2 ? 2 : a, i.styleStroke.strokeWidth = a + "px", i.styleStroke.stroke = t.outlineColor), t.strokeWidth = a || 0, Te(t.aplStats, y.pathLabel.stats), t.updateColor(n), t.updatePath(n), t.updateStartOffset(n), ue && De(n, {}), t.updateShow(n)
                        },
                        bind: function(e, t) {
                            var n = t.props;
                            return e.color || Ie(n, "cur_line_color", e.updateColor), Ie(n, "cur_line_strokeWidth", e.updatePath), Ie(n, "apl_path", e.updatePath), e.semIndex = "startLabel" === t.optionName ? 0 : "endLabel" === t.optionName ? 1 : 2, (2 !== e.semIndex || e.lineOffset) && Ie(n, "cur_attach_plugBackLenSE", e.updateStartOffset), Ie(n, "svgShow", e.updateShow), ue && Ie(n, "new_edge4viewBox", e.adjustEdge), y.pathLabel.initSvg(e, n), !0
                        },
                        unbind: function(e, t) {
                            var n = t.props;
                            e.elmsAppend && (e.elmsAppend.forEach(function(e) {
                                n.svg.removeChild(e)
                            }), e.elmPosition = e.elmPath = e.elmOffset = e.styleFill = e.styleShow = e.elmsAppend = null), Te(e.curStats, y.pathLabel.stats), Te(e.aplStats, y.pathLabel.stats), e.color || Ce(n, "cur_line_color", e.updateColor), Ce(n, "cur_line_strokeWidth", e.updatePath), Ce(n, "apl_path", e.updatePath), (2 !== e.semIndex || e.lineOffset) && Ce(n, "cur_attach_plugBackLenSE", e.updateStartOffset), Ce(n, "svgShow", e.updateShow), ue && (Ce(n, "new_edge4viewBox", e.adjustEdge), De(n, {}))
                        },
                        removeOption: function(e, t) {
                            var n = t.props,
                                a = {};
                            a[t.optionName] = "", Ze(n, a)
                        },
                        remove: function(t) {
                            t.boundTargets.length && (console.error("LeaderLineAttachment was not unbound by remove"), t.boundTargets.forEach(function(e) {
                                y.pathLabel.unbind(t, e)
                            }))
                        }
                    }
                }, Object.keys(y).forEach(function(e) {
                    Ye[e] = function() {
                        return new S(y[e], Array.prototype.slice.call(arguments))
                    }
                }), Ye.positionByWindowResize = !0, window.addEventListener("resize", O.add(function() {
                    Ye.positionByWindowResize && Object.keys(K).forEach(function(e) {
                        De(K[e], {
                            position: !0
                        })
                    })
                }), !1), Ye
        }();
    </script>
</head>

<body>

    <?php

    if (!$data || !$data->uplinks) {
        echo "<p>failed to receive</p>";
        exit;
    }


    $conn = mab_db_connect();
    $configs = mab_db_query($conn, 'SELECT node_address, node_type from hardware_config');

    //insert into sandbox from page (for backup)
    //if backup to live becomes necessary then simply run the replace statement and change the databases around
    // mab_db_query($conn, 'REPLACE INTO `agri_sandbox`.`node_data` SELECT * FROM `agri`.`node_data` WHERE `date_time` >= DATE_SUB(CURDATE(), INTERVAL 30 MINUTE) AND `message_id_1` LIKE "int"');

    $node_type_by_address = [];
    if ($configs) {
        foreach ($configs as $row) {
            $node_type_by_address[$row['node_address']] = $row['node_type'];
        }
    }

    $i = 1;

    foreach ($data->uplinks as $record) {
        if (!empty($record->datagramUplinkEvent)) {
            $node_address = $record->datagramUplinkEvent->nodeId;
            // optional filter
            if ($node_id_filter && $node_address != $node_id_filter) {
                $i++;
                continue;
            }

            $messageId = $record->messageId;
            $node_type = !empty($node_type_by_address[$node_address]) ? $node_type_by_address[$node_address] : 'Unregistered';
            $linkNext = "https://dev.myagbuddy.com/mab_intellect_output.php?message_id={$messageId}" . ($node_id_filter ? "&node_id={$node_id_filter}" : "");

            $payload_hex = bin2hex(base64_decode($record->datagramUplinkEvent->payload));
            $payload_hex_fmt = chunk_split($payload_hex, 2, ' ');
            $total_bytes = strlen($payload_hex) / 2;
            $output_hex = intellect_payload_unserialize($payload_hex);
            $output_formatted = intellect_payload_unserialize($payload_hex, true, $node_address);
            $dateDecoded = gmdate("Y-m-d H:i:s", round($record->datagramUplinkEvent->timestamp / 1000));
            insert_b64($node_address, $record->datagramUplinkEvent->payload, $dateDecoded, $messageId);

            echo
            "<div class='row block_{$i}' data-elemcount='" . count($output_formatted) . "'>" .
                "<div class='num'>#$i</div>" .
                "<ul>" .
                "<li>Node Address: <strong style='color:green'>{$node_address} ($node_type)</strong></li>" .
                "<li>Message Type: <strong style='color:green'>" . intellect_payload_get_message_type($payload_hex, true) . "</strong></li>" .
                "<li>timestamp decoded: <strong>" . gmdate("d-m-Y H:i:s", round($record->datagramUplinkEvent->timestamp / 1000)) . "</strong></li>" .
                "<li>messageId: <strong><a href='{$linkNext}'>{$messageId}</a></strong></li>" .
                "<li>messageType: <strong>{$record->messageType}</strong></li>" .
                "<li>applicationId: <strong>{$record->datagramUplinkEvent->applicationId}</strong></li>" .
                "<li>timestamp: <strong>{$record->datagramUplinkEvent->timestamp}</strong></li>" .
                "<li>payload (b64): <strong>{$record->datagramUplinkEvent->payload}</strong></li>" .
                "<li>payload (hex): <strong>" . $payload_hex_fmt . "</strong> ($total_bytes bytes)</li>" .
                "<li>payload (hex grouped): " . intellect_payload_format_hex_pipes_with_ids($output_hex, "end_{$i}_") . " ($total_bytes bytes)</li>" .
                "<li>Message Date: <strong>" . intellect_payload_get_date($payload_hex) . "</strong></li>" .
                "<li class='formatted_values'>Message: " . intellect_payload_format_array($output_formatted, "beg_{$i}_", $dateDecoded, $node_address, $messageId) . "</li>" .
                "</ul>" .
                "</div>";
        } else if (!empty($record->datagramDownlinkResponse)) {
            $node_address = $record->datagramDownlinkResponse->nodeId;
            // optional filter
            if ($node_id_filter && $node_address != $node_id_filter) {
                $i++;
                continue;
            }
            $dateDecoded = gmdate("Y-m-d H:i:s", round($record->datagramUplinkEvent->timestamp / 1000));
            insert_b64($node_address, $record->datagramUplinkEvent->payload, $dateDecoded, $messageId);

            $messageId = $record->messageId;
            $node_type = !empty($node_type_by_address[$node_address]) ? $node_type_by_address[$node_address] : 'Unregistered';
            $linkNext = "https://dev.myagbuddy.com/mab_intellect_output.php?message_id={$messageId}" . ($node_id_filter ? "&node_id={$node_id_filter}" : "");

            echo
            "<div class='row'>" .
                "<ul>" .
                "<li>#: $i</li>" .
                "<li>Node Address: <strong style='color:green'>{$node_address} ($node_type)</strong></li>" .
                "<li>timestamp decoded: <strong>" . gmdate("d-m-Y H:i:s", round($record->datagramDownlinkResponse->timestamp / 1000)) . "</strong></li>" .
                "<li>tag: <strong>{$record->datagramDownlinkResponse->tag}</strong></li>" .
                "<li>messageId: <strong><a href='{$linkNext}'>{$messageId}</a></strong></li>" .
                "<li>messageType: <strong>{$record->messageType}</strong></li>" .
                "<li>status: <strong>{$record->datagramDownlinkResponse->status}</strong></li>" .
                "</ul>" .
                "</div>";
        }
        $i++;
    }
    ?>

    <script>
        (function() {
            var count = <?php echo $i; ?>;
            var lines = {};

            var drawline = function(e) {
                e.stopPropagation();

                var id = e.target.id.replace('beg_', '').replace('end_', '');

                for (const key in lines) {
                    lines[key].remove();
                    delete(lines[key]);
                }

                if (!(id in lines)) {
                    lines[id] = new LeaderLine(document.getElementById('end_' + id), document.getElementById('beg_' + id), {
                        dash: {
                            animation: true
                        },
                        path: 'fluid',
                        endPlug: 'disc'
                    });
                } else {
                    lines[id].show();
                }
            };

            var elems = document.querySelectorAll('.hov');
            for (var i = 0; i < elems.length; i++) {
                elems[i].addEventListener('click', drawline);
            }

            var body = document.querySelector('body');
            body.addEventListener('click', function(e) {
                for (const key in lines) {
                    lines[key].remove();
                    delete(lines[key]);
                }
            });
        })();
    </script>
</body>

</html>