<pre>

<?php
require 'vendor/autoload.php';
$client = new GuzzleHttp\Client();
$res = $client->request('POST', 'https://intellect.themachinenetwork.co.za/config/v1/session', [

        'headers' => ['username' => 'Intellect@usa.liquidfibre.com', 'password'=>'PassIntellect@2019']

]);

$body = $res->getBody();

$body = json_decode($body);
$request = $client->request('GET','https://intellect.themachinenetwork.co.za/data/v1/receive?count=500', [
        'headers' => ['Authorization' => $body->token, 'Content-Type' => 'application/json']
]);
$body = $request->getBody();

$body = json_decode($body);

echo '<table>';

for($j=0;sizeof($body->uplinks)>$j;$j++)
{
if($body->uplinks[$j]->datagramUplinkEvent->nodeId == $_GET['nodeid']) {
  //print_r(  $body->uplinks[$i]); 



$line = bin2hex(base64_decode($body->uplinks[$j]->datagramUplinkEvent->payload));
$split = str_split($line);
echo '<tr>';


	$EpochTimeStamp = substr($line,4,8);
    //$EpochTimeStamp = '2118f45a';


    $EpochTimeStamp = hexdec(substr($EpochTimeStamp,6,2).substr($EpochTimeStamp,4,2).substr($EpochTimeStamp,2,2).substr($EpochTimeStamp,0,2));
		$dataDate = date("Y-m-d H:i:s",$EpochTimeStamp);

$date1 = date("Y-m-d H:i:s", strtotime('-72 hours', time()));;
$date2 = date("Y-m-d H:i:s", strtotime('+72 hours', time()));;

if(($dataDate > $date1)&&($dataDate < $date2)){
		echo '<td>'.$dataDate.'</td>';
echo '<td>'.hexdec(substr($line,14,2).substr($line,12,2)).'</td>';

echo '<td>'.hexdec(substr($line,16,2)).'</td>';
echo '<td>'.(substr($line,18,2)).'</td>';
echo '<td>'.(substr($line,20,2)).'</td>';
$sensor_count=substr($line,20,2);
//echo $sensor_count.'SENSOR COUNT';
							$moist = (substr($line,22,4*$sensor_count));
							$temp = (substr($line,22+4*$sensor_count,4*$sensor_count));

//echo $moist.'MOISTURE';
//echo $temp.'TEMPERATURE';

							$msensor = str_split($moist,4);


							$tsensor = str_split($temp,4);
							
							
							
							for($i=0;$i < $sensor_count;$i++)
							{

echo '<td>';

									echo (hexdec(substr($msensor[$i],2,2).substr($msensor[$i],0,2))/100);

echo '</td>';

echo '<td>';
									

echo (hexdec(substr($tsensor[$i],2,2).substr($tsensor[$i],0,2))/100);
echo '</td>';
	

							
								
							}


echo '</td>';
echo '</tr>';
}
}
}
echo '</table>';
