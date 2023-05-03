<pre>

<?php
require 'vendor/autoload.php';
$client = new GuzzleHttp\Client();
$res = $client->request('POST', 'https://rpma.ingenicas.com/config/v1/session', [

        'headers' => ['username' => 'intellect@liquidfibre.com', 'password'=>'Intellect@2019']

]);

$body = $res->getBody();

$body = json_decode($body);



require_once('db.php');

    $db = new db();

$sql = 'SELECT * FROM ac_usa ORDER BY message_id_2 DESC LIMIT 1';
$stmt = $db->unprepared_query( $sql );
$row = $stmt->fetch(PDO::FETCH_ASSOC);
//echo $body->token;
$request = $client->request('GET','https://rpma.ingenicas.com/data/v1/receive/'.$row['message_id_2'], [
        'headers' => ['Authorization' => $body->token, 'Content-Type' => 'application/json']
]);
$body = $request->getBody();

$body = json_decode($body);
/*
for($i=0;sizeof($body->uplinks)>$i;$i++)
{
if($body->uplinks[$i]->datagramUplinkEvent->nodeId == $_GET['nodeid']) {
  print_r(  $body->uplinks[$i]); }
}
*/

for($j=0;sizeof($body->uplinks)>$j;$j++)
{

  //print_r(  $body->uplinks[$i]);



$line = bin2hex(base64_decode($body->uplinks[$j]->datagramUplinkEvent->payload));
$split = str_split($line);



        $EpochTimeStamp = substr($line,4,8);
    //$EpochTimeStamp = '2118f45a';


    $EpochTimeStamp = hexdec(substr($EpochTimeStamp,6,2).substr($EpochTimeStamp,4,2).substr($EpochTimeStamp,2,2).substr($EpochTimeStamp,0,2));
                $dataDate = date("Y-m-d H:i:s",$EpochTimeStamp);

$date1 = date("Y-m-d H:i:s", strtotime('-72 hours', time()));;
$date2 = date("Y-m-d H:i:s", strtotime('+72 hours', time()));;

if(($dataDate > $date1)&&($dataDate < $date2)){
               
                $sensor_count=substr($line,20,2);
 $moist = (substr($line,22,4*$sensor_count));
$temp = (substr($line,22+4*$sensor_count,4*$sensor_count));

//echo $moist.'MOISTURE';
//echo $temp.'TEMPERATURE';

$msensor = str_split($moist,4);


$tsensor = str_split($temp,4);


$sql_1 = 'SELECT COUNT(*) as total FROM ac_usa WHERE message_id_2 = \''.$body->uplinks[$j]->messageId.'\'';
//echo $sql_1;
$stmt_1 = $db->unprepared_query( $sql_1 );
//print_r($stmt_1);
$row_1 = $stmt_1->fetch(PDO::FETCH_ASSOC);
if ($row_1['total']==0)
{

$sql = 'INSERT into ac_usa (date_time,probe_id,bv,message_id_2';

for($i=1;$i < $sensor_count+1;$i++)
{
        $sql .= ', sm'.$i;
        $sql .= ', t'.$i;
}

$sql .= ') VALUES (\''.$dataDate.'\',\''.$body->uplinks[$j]->datagramUplinkEvent->nodeId;
$sql .= '\',\''.hexdec(substr($line,14,2).substr($line,12,2));
$sql .= '\',\''.$body->uplinks[$j]->messageId;
for($i=0;$i < $sensor_count;$i++)
{



        $sql .= '\',\''.(hexdec(substr($msensor[$i],2,2).substr($msensor[$i],0,2))/100);




        $sql .= '\',\''.(hexdec(substr($tsensor[$i],2,2).substr($tsensor[$i],0,2))/100);
}
}

$sql .= '\')';
echo $sql;

require_once('db.php');
$db = new db();

$stmt = $db->unprepared_query( $sql );
			
			
                        print_r($stmt);
                        }}