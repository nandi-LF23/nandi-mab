<pre>

<?php
require 'vendor/autoload.php';
$client = new GuzzleHttp\Client();
$res = $client->request('POST', 'https://intellect.themachinenetwork.co.za/config/v1/session', [

        'headers' => ['username' => 'Intellect@usa.liquidfibre.com', 'password'=>'PassIntellect@2019']

]);


require_once('db.php');

    $db = new db();

$sql = 'SELECT * FROM ac_usa ORDER BY message_id_1 DESC LIMIT 1';


$body = $res->getBody();

$body = json_decode($body);
$request = $client->request('GET','https://intellect.themachinenetwork.co.za/data/v1/receive/'.$row['message_id_2'], [
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

$sql = 'INSERT into ac_usa (date_time,probe_id,bv,message_id_1';

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
			
			
			print_r($stmt);}