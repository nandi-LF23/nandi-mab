<?<?php
$tmp = unpack("g", pack("H*", substr($line, 12, 8)));
$latt = reset($tmp);
$tmp = unpack("g", pack("H*", substr($line, 20, 8)));
$lng = reset($tmp);

if ($nodeId) {
    for ($i = 0; $i < 10; $i++) {
        $db->unprepared_query('UPDATE hardware_config SET latt=\'' . $latt . '\', lng=\'' . $lng . '\' WHERE node_address LIKE %\'' . ($nodeId . '-' . $i) . '\'%');
        $db->unprepared_query('UPDATE nutri_data SET latt=\'' . $latt . '\', lng=\'' . $lng . '\' WHERE node_address LIKE %\'' . ($nodeId . '-' . $i) . '\'% ORDER BY date_sampled DESC LIMIT 1');
        $db->unprepared_query('UPDATE node_data SET latt=\'' . $latt . '\', lng=\'' . $lng . '\' WHERE probeid LIKE %\'' . ($nodeId .'-'. $i) . '\'% ORDER BY date_time DESC LIMIT 1');
    }
}
$sql1 = 'SELECT COUNT(*) FROM raw_data_b64 WHERE message_id=\'' . $messageId . '\'';
$stmt = $db->unprepared_query($sql1);
$row = $stmt->fetch(PDO::FETCH_NUM);
if (!$row[0]) {
    $db->unprepared_query('INSERT INTO raw_data_b64 (device_id, b64_data, timestamp, message_id) VALUES ("' . $nodeId . '" , "' . $b64 . '" , "' . $timestamp . '", "' . $messageId . '") ON DUPLICATE KEY UPDATE message_id = VALUES(message_id)');

}