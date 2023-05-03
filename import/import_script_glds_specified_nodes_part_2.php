<pre>

<?php
require_once('db.php');

$db = new db();
//85343/86498/853f2/855c8

$nodeid='0x00085343';
$date1 = date("Y-m-d H:i:s", strtotime('-72 hours', time()));;
$sql = 'DELETE FROM node_data WHERE probe_id=\''.$nodeid.'\' AND date_time > \''.$date1.'\'';
$stmt_1 = $db->unprepared_query( $sql );

$nodeid='0x00086498';
$date1 = date("Y-m-d H:i:s", strtotime('-72 hours', time()));;
$sql = 'DELETE FROM node_data WHERE probe_id=\''.$nodeid.'\' AND date_time > \''.$date1.'\'';
$stmt_1 = $db->unprepared_query( $sql );

$nodeid='0x000855c8';
$date1 = date("Y-m-d H:i:s", strtotime('-72 hours', time()));;
$sql = 'DELETE FROM node_data WHERE probe_id=\''.$nodeid.'\' AND date_time > \''.$date1.'\'';
$stmt_1 = $db->unprepared_query( $sql );

$nodeid='0x000853f2';
$date1 = date("Y-m-d H:i:s", strtotime('-72 hours', time()));;
$sql = 'DELETE FROM node_data WHERE probe_id=\''.$nodeid.'\' AND date_time > \''.$date1.'\'';
$stmt_1 = $db->unprepared_query( $sql );

require 'vendor/autoload.php';
$client = new GuzzleHttp\Client(['verify' => false ]);
$res = $client->request('POST', 'https://glds.ingenu.com/config/v1/session', [

  'headers' => ['username' => 'glds@liquidfibre.com', 'password'=>'P@ssword1234']
 //'headers' => ['username' => 'glds@liquidfibre.com', 'password'=>'P@ssword1234']
]);

$body = $res->getBody();

$body = json_decode($body);
//echo $body->token;
$request = $client->request('GET','https://glds.ingenu.com/data/v1/receive?count=1', [
       'headers' => ['Authorization' => $body->token, 'Content-Type' => 'application/json']
]);
$body1 = $request->getBody();


$body1 = json_decode($body1);
/*print_r($body);
die;*/
display($body1->uplinks[0]->messageId);

function display($messageid)
{
 // echo $messageid;
  $client = new GuzzleHttp\Client(['verify' => false ]);
  $res = $client->request('POST', 'https://glds.ingenu.com/config/v1/session', [

    'headers' => ['username' => 'glds@liquidfibre.com', 'password'=>'P@ssword1234']
  //'headers' => ['username' => 'glds@liquidfibre.com', 'password'=>'P@ssword1234']

]);

$body = $res->getBody();

$body = json_decode($body);

  $request = $client->request('GET','https://glds.ingenu.com/data/v1/receive/'.$messageid, [
    'headers' => ['Authorization' => $body->token, 'Content-Type' => 'application/json']
  ]);
  $body = $request->getBody();
  
  $body = json_decode($body);

/*print_r($body);
die;*/
for($j=0;sizeof($body->uplinks)>$j;$j++)
{
  /*if($j == 0)
  {

    display($body->uplinks[499]->messageId);
  }*/

  if($j == count($body->uplinks)-1)
  {
	$amnt = count($body->uplinks);
	echo 'Amount filtered:' . $amnt . PHP_EOL;
    display($body->uplinks[$amnt-1]->messageId);
  }
  ////85343/86498/853f2/855c8
if($body->uplinks[$j]->datagramUplinkEvent->nodeId == ('0x00085343' || '0x00086498' || '0x000853f2' || '0x000855c8')) {
  /*print_r(  $body->uplinks);

  die;*/

$line = bin2hex(base64_decode($body->uplinks[$j]->datagramUplinkEvent->payload));

//if((substr($line,0,2)=='02')&&(((substr($line,2,2)=='03'))))
{
  $split = str_split($line);



  $EpochTimeStamp = substr($line,4,8);
  //$EpochTimeStamp = '2118f45a';


  $EpochTimeStamp = hexdec(substr($EpochTimeStamp,6,2).substr($EpochTimeStamp,4,2).substr($EpochTimeStamp,2,2).substr($EpochTimeStamp,0,2));
  $dataDate = date("Y-m-d H:i:s",$EpochTimeStamp);

    
      $sensor_count=substr($line,20,2);
      $moist = (substr($line,22,4*$sensor_count));
      $temp = (substr($line,22+4*$sensor_count,4*$sensor_count));
      $rg = (substr($line,22+8*$sensor_count,8));
      $rg = hexdec(substr($rg,6,2).substr($rg,4,2).substr($rg,2,2).substr($rg,0,2));
      //echo $moist.'MOISTURE';
      //echo $temp.'TEMPERATURE';

      $msensor = str_split($moist,4);


      $tsensor = str_split($temp,4);

      
          //calculations for sum and ave
          $sum = 0;
          $ave = 0;

          for($i = 0; $i < count($msensor); $i++)
          {
              $sum += (hexdec(substr($msensor[$i],2,2).substr($msensor[$i],0,2))/100);
          }

          $ave = $sum / count($msensor);


          $sql = 'INSERT into node_data (date_time,probe_id,bv,message_id_2,average,accumulative,rg';

          for($i=1;$i < $sensor_count+1;$i++)
          {
              $sql .= ', sm'.$i;
              $sql .= ', t'.$i;
          }

          $sql .= ') VALUES (\''.$dataDate.'\',\''.$body->uplinks[$j]->datagramUplinkEvent->nodeId;
          $sql .= '\',\''.hexdec(substr($line,14,2).substr($line,12,2));
          $sql .= '\',\''.$body->uplinks[$j]->messageId;
          $sql .= '\',\''.$ave;
          $sql .= '\',\''.$sum;
          $sql .= '\',\''.$rg;
          for($i=0;$i < $sensor_count;$i++)
          {



              $sql .= '\',\''.(hexdec(substr($msensor[$i],2,2).substr($msensor[$i],0,2))/100);




              $sql .= '\',\''.(hexdec(substr($tsensor[$i],2,2).substr($tsensor[$i],0,2))/100);
          }
      

      $sql .= '\')';
      echo 'Inserted row'.PHP_EOL;
      
      require_once('db.php');

      $db = new db();
      $stmt = $db->unprepared_query( $sql );
  

  }

}
}

}


