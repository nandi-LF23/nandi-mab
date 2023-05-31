<?php
$sum = 0;
$ave = 0;
$null = null;
$m_values = [];
$t_values = [];

// Time of Measurement
$timestamp = substr($line, 4, 8); // 12
$timestamp = hexdec(substr($timestamp, 6, 2) . substr($timestamp, 4, 2) . substr($timestamp, 2, 2) . substr($timestamp, 0, 2));
$dataDate = gmdate("Y-m-d H:i:s", $timestamp);

$probe_address = 0;
$probe_address = 0;

$sensor_count = hexdec(substr($line, 20, 2));  // 22

// Battery Voltage
$bv = hexdec(substr($line, 14, 2) . substr($line, 12, 2)); // 16
$bp = $calcBatt($bv, 3200, 4200);

// 16-19 Unused (Internal Temp(2chars/1byte) + Flags(2chars/1byte))
$ambient_temp = hexdec(substr($line, 16, 2));

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


// calculate sum and average values

// $nodeId = "{$nodeId}-{$probe_address}";
 $nodeAddress = $nodeId . '-' .$probe_address;
file_put_contents('/var/www/live_rpma_import_log.log', '0205 node addess: ' . $nodeAddress . PHP_EOL, FILE_APPEND);
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
//$db->unprepared_query('ALTER TABLE raw_data_b64 ADD UNIQUE(b64_data)');
//$db->unprepared_query('INSERT INTO raw_data_b64 (device_id, b64_data, timestamp, message_id) VALUES ("' . $nodeId . '" , "' . $b64 . '" , "' . $dataDate . '", "' . $messageId . '") ON DUPLICATE KEY UPDATE message_id = VALUES(message_id)');

//insert into sandbox (for backup purposes)
// $db->unprepared_query('REPLACE INTO `agri_sandbox`.`node_data` SELECT * FROM `agri`.`node_data` WHERE `date_time` >= DATE_SUB(CURDATE(), INTERVAL 30 minute) AND `message_id_1` LIKE "int"');
/*
$sql1 = 'SELECT COUNT(*) FROM raw_data_b64 WHERE message_id=\''.$messageId.'\'';
$stmt = $db->unprepared_query( $sql1 );
$row = $stmt->fetch(PDO::FETCH_NUM);
if ($row[0] == 0) {

$db->unprepared_query('INSERT INTO raw_data_b64 (device_id, b64_data, timestamp, message_id) VALUES ("' . $nodeId . '" , "' . $b64 . '" , "' . $timestamp . '", "' . $messageId . '") ON DUPLICATE KEY UPDATE message_id = VALUES(message_id)');
//print_r($stmt);
 $db->unprepared_query('UPDATE hardware_config SET date_time=\'' . $DateTime . '\' WHERE node_address=\'' . $nodeAddress . '\'');
}
*/
