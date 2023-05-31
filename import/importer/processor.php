<?php
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

$sql = 'SELECT message_id FROM raw_data_b64 ORDER BY id DESC LIMIT 1';
$stmt = $db->unprepared_query( $sql );
$row = $stmt->fetch(PDO::FETCH_ASSOC);
//print_r($row);
// Get the latest device messages by message id (uuid)
//$request = $client->request('GET', 'https://glds.ingenu.com/data/v1/receive/' . file_get_contents("MessageId_UUID.txt"), [

$request = $client->request('GET', 'https://glds.ingenu.com/data/v1/receive/' . $row['message_id'], [

    'headers' => [
        'Authorization' => $body->token,
        'Content-Type' => 'application/json'
    ]
]);
$body = json_decode($request->getBody());

require_once('utils/calcBatt.php');
require_once('utils/integers.php');
require_once('utils/reverse_bytes.php');

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
    $cid = substr($line, 0, 2);
    $sid = substr($line, 2, 2);

    // Time of Measurement
    $measurement = substr($line, 4, 8); // 12
    $measurement = hexdec(substr($measurement, 6, 2) . substr($measurement, 4, 2) . substr($measurement, 2, 2) . substr($measurement, 0, 2));
    $DateTime = gmdate("Y-m-d H:i:s", $measurement);

    $timestamp = gmdate("Y-m-d H:i:s", round($body->uplinks[$j]->datagramUplinkEvent->timestamp / 1000));

    echo 'CID: ' . $cid . ' SID: '. $sid . ' Node ID: '. $nodeId . ' Timestamp: ' . $timestamp . PHP_EOL;
    file_put_contents('/var/www/live_rpma_import_log.log', 'CID: ' . $cid . ' SID: ' . $sid . ' Node ID: ' . $nodeId . ' Timestamp: ' . $timestamp . PHP_EOL, FILE_APPEND);
    switch($cid)
    {
        case 1:
            {
                switch($sid)
                {
                    case 2: { require('spec_files/0102.php'); break; }// (1,2) GPS Position Message
                    case 3: { require('spec_files/0103.php'); break; }
                    case 4: { require('spec_files/0104.php'); break; }//node boot v2
                }
                break;
            }
        case 2:
            {
                switch($sid)
                {
                    case 1: break;
                    case 2: break;
                    case 3: { require('spec_files/0203.php'); break; }//(2,3) Probe Reading Message
                    case 4: { require('spec_files/0204.php'); break; }
                    case 5: { require('spec_files/0205.php'); break; }
                    case 6: { require('spec_files/0206.php'); break; }
                }
                break;
            }
        case 3:
            {
                switch($sid)
                {
                    case 1: { require('spec_files/0800.php');  break; }
                }
                break;
            }
        case 5:break;
        case 7:break;
        case 8: { require('spec_files/0800.php'); break; }// (8,0) Probe with Pulse Reading Message (legacy MSF)
    }
    $sql1 = 'SELECT COUNT(*) FROM raw_data_b64 WHERE message_id=\'' . $messageId . '\'';
    $stmt = $db->unprepared_query($sql1);
    $row = $stmt->fetch(PDO::FETCH_NUM);
    if ($row[0] == 0) {
        $db->unprepared_query('INSERT INTO raw_data_b64 (device_id, b64_data, timestamp, message_id) VALUES ("' . $nodeId . '" , "' . $b64 . '" , "' . $timestamp . '", "' . $messageId . '") ON DUPLICATE KEY UPDATE message_id = VALUES(message_id)');
        // print_r($stmt);
        $db->unprepared_query('UPDATE hardware_config SET date_time=\'' . $DateTime . '\' WHERE node_address=\'' . $nodeAddress . '\'');

    }

    // file_put_contents("MessageId_UUID.txt",$messageId);

}

