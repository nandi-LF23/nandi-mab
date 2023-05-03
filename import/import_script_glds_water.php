<?php
require 'vendor/autoload.php';

// abort if system build is in progress
clearstatcache(true, 'mab_build_in_progress'); if(file_exists('mab_build_in_progress')){ return 1; }

// Authenticate with Ingenu
$client = new GuzzleHttp\Client(['verify' => false ]);
$res = $client->request('POST', 'https://glds.ingenu.com/config/v1/session', [
    'headers' => ['username' => 'ag02@liquidfibre.com', 'password'=>'LFAG02p@ss']
]);
$body = json_decode($res->getBody());

// DB Library
require_once('db.php');
$db = new db();

// Get the latest row of data
$stmt = $db->unprepared_query( 'SELECT * FROM node_data_meters ORDER BY idwm DESC LIMIT 1' );

$row = $stmt->fetch(PDO::FETCH_ASSOC);

// Get the latest device messages by message id (uuid)
$request = $client->request('GET','https://glds.ingenu.com/data/v1/receive/'.$row['message_id'], [
    'headers' => ['Authorization' => $body->token, 'Content-Type' => 'application/json']
]);
$body = json_decode($request->getBody());

for($j = 0; $j < sizeof($body->uplinks); $j++)
{
    if(empty($body->uplinks[$j]->datagramUplinkEvent)){ continue; }

    $line = bin2hex(base64_decode($body->uplinks[$j]->datagramUplinkEvent->payload));
    $cid_sid = substr($line, 0, 4);

    $calcBatt = function($reading, $lower, $upper){
        $range = $upper - $lower; 
        $delta = $reading - $lower;
        if($delta <= 0) $delta = 0;
        $level = ($delta / $range) * 100;
        return $level < 0 ? 0 : ($level > 100 ? 100 : $level);
    };

    if ($cid_sid == '0201') { // (2,1) Sensor Reading Message

        $bv = hexdec(substr($line,14,2).substr($line,12,2));
        $bp = $calcBatt($bv, 3500, 4500);

        $EpochTimeStamp = substr($line,4,8); // timestamp
        $EpochTimeStamp = hexdec(substr($EpochTimeStamp,6,2).substr($EpochTimeStamp,4,2).substr($EpochTimeStamp,2,2).substr($EpochTimeStamp,0,2));
        $dataDate = gmdate("Y-m-d H:i:s", $EpochTimeStamp);
        $date1 = gmdate("Y-m-d H:i:s", strtotime('-72 hours', time()));
        $date2 = gmdate("Y-m-d H:i:s", strtotime('+72 hours', time()));

        if(($dataDate > $date1) && ($dataDate < $date2))
        {
            // duplicate check
            $stmt_1 = $db->unprepared_query( 'SELECT COUNT(*) as total FROM node_data_meters WHERE message_id = \''.$body->uplinks[$j]->messageId.'\'' );
            $row_1 = $stmt_1->fetch(PDO::FETCH_ASSOC);

            if ( $row_1['total'] == 0 )
            {
                $sql = 'INSERT into node_data_meters ' . 
                '(date_time, node_id, batt_volt, bp, message_id, deg_c, power_state, pulse_1, pulse_2, state_of_measure_1, state_of_measure_2, pulse_1_mA, pulse_2_mA, ultrasonic)';

                $sql .= ' VALUES (\''.$dataDate; // date_time, 
                $sql .= '\',\''.$body->uplinks[$j]->datagramUplinkEvent->nodeId . '-0'; // node_id
                $sql .= '\',\''.$bv; // battery voltage
                $sql .= '\',\''.$bp; // battery percentage
                $sql .= '\',\''.$body->uplinks[$j]->messageId; // message_id
                $sql .= '\',\''.hexdec(substr($line,16,2)); // deg_c (1 byte)
                $sql .= '\',\''.hexdec(substr($line,18,2)); // power_state (1 byte)

                $sql .= '\',\''.hexdec(substr($line,26,2).substr($line,24,2).substr($line,22,2).substr($line,20,2)); // pulse_1
                $sql .= '\',\''.hexdec(substr($line,34,2).substr($line,32,2).substr($line,30,2).substr($line,28,2)); // pulse_2
                $sql .= '\',\''.hexdec(substr($line,36,2)); // state_of_measure_1
                $sql .= '\',\''.hexdec(substr($line,38,2)); // state_of_measure_2
                $sql .= '\',\''.hexdec(substr($line,42,2).substr($line,40,2)); // pulse_1_mA
                $sql .= '\',\''.hexdec(substr($line,46,2).substr($line,44,2)); // pulse_2_mA
                $sql .= '\',\''.hexdec(substr($line,50,2).substr($line,48,2)); // ultrasonic
                $sql .= '\')';
                $stmt = $db->unprepared_query( $sql );
            }
        }
    } else if($cid_sid == '0204') { // (2,4) Probe with Pulse Reading Message

        $bv = hexdec(substr($line,14,2).substr($line,12,2));
        $bp = $calcBatt($bv, 3500, 4500);

        $EpochTimeStamp = substr($line,4,8); // timestamp
        $EpochTimeStamp = hexdec(substr($EpochTimeStamp,6,2).substr($EpochTimeStamp,4,2).substr($EpochTimeStamp,2,2).substr($EpochTimeStamp,0,2));
        $dataDate = gmdate("Y-m-d H:i:s", $EpochTimeStamp);
        $date1 = gmdate("Y-m-d H:i:s", strtotime('-72 hours', time()));
        $date2 = gmdate("Y-m-d H:i:s", strtotime('+72 hours', time()));

        if(($dataDate > $date1) && ($dataDate < $date2))
        {
            // duplicate check
            $stmt_1 = $db->unprepared_query( 'SELECT COUNT(*) as total FROM node_data_meters WHERE message_id = \''.$body->uplinks[$j]->messageId.'\'' );
            $row_1 = $stmt_1->fetch(PDO::FETCH_ASSOC);

            if ( $row_1['total'] == 0 )
            {
                $sql = 'INSERT into node_data_meters ' . 
                '(date_time, node_id, batt_volt, bp, message_id, deg_c, power_state, pulse_1, pulse_2, state_of_measure_1, state_of_measure_2, pulse_1_mA, pulse_2_mA, ultrasonic)';
                
                $sql .= ' VALUES (\''.$dataDate; // date_time, 
                $sql .= '\',\''.$body->uplinks[$j]->datagramUplinkEvent->nodeId . '-0'; // node_id
                $sql .= '\',\''.$bv; // battery voltage
                $sql .= '\',\''.$bp; // battery percentage
                $sql .= '\',\''.$body->uplinks[$j]->messageId; // message_id
                $sql .= '\',\''.hexdec(substr($line,16,2)); // deg_c
                $sql .= '\',\''.hexdec(substr($line,18,2)); // power_state

                $sc = hexdec(substr($line,20, 2)); // sensor count (1 byte)
                $offset = ($sc * 8) + ($sc * 4);

                $sql .= '\',\''.hexdec(substr($line,28+$offset,2).substr($line,26+$offset,2).substr($line,24+$offset,2).substr($line,22+$offset,2)); // pulse_1
                $sql .= '\',\''.hexdec(substr($line,36+$offset,2).substr($line,34+$offset,2).substr($line,32+$offset,2).substr($line,30+$offset,2)); // pulse_2
                $sql .= '\',\''.hexdec(substr($line,38+$offset,2)); // state_of_measure_1
                $sql .= '\',\''.hexdec(substr($line,40+$offset,2)); // state_of_measure_2
                $sql .= '\',\''.hexdec(substr($line,44+$offset,2).substr($line,42+$offset,2)); // pulse_1_mA
                $sql .= '\',\''.hexdec(substr($line,48+$offset,2).substr($line,46+$offset,2)); // pulse_2_mA
                $sql .= '\',\'0'; // ultrasonic
                $sql .= '\')';
                $stmt = $db->unprepared_query( $sql );
            }
        }
    }
}
