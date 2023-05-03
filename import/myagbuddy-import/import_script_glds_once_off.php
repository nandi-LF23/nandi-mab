<pre>

<?php
require 'vendor/autoload.php';
$client = new GuzzleHttp\Client(['verify' => false ]);
$res = $client->request('POST', 'https://glds.ingenu.com/config/v1/session', [

        'headers' => ['username' => 'mab@liquidfibre.com', 'password'=>'M@b123456']

]);

$body = $res->getBody();

$body = json_decode($body);



require_once('db.php');

$db = new db();

$sql = 'SELECT * FROM node_data ORDER BY id DESC LIMIT 1';
$stmt = $db->unprepared_query( $sql );
$row = $stmt->fetch(PDO::FETCH_ASSOC);
//echo $body->token;
$request = $client->request('GET','https://glds.ingenu.com/data/v1/receive?count=500', [
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

    $cid = substr($line,0,2);//c-id
    $sid = substr($line,2,2);//s-id

    switch($cid.$sid)
    {
        case '0102':
            {
                $EpochTimeStamp = substr($line,4,8);
                //$EpochTimeStamp = '2118f45a';
                $latt = substr($line,8+4,8);
                $lng = substr($line,4+16,8);
                $EpochTimeStamp = hexdec(substr($EpochTimeStamp,6,2).substr($EpochTimeStamp,4,2).substr($EpochTimeStamp,2,2).substr($EpochTimeStamp,0,2));
                $dataDate = date("Y-m-d H:i:s",$EpochTimeStamp);
    
                /*$latt = hexdec(substr($latt,6,2).substr($latt,4,2).substr($latt,2,2).substr($latt,0,2));
                $lng = hexdec(substr($lng,6,2).substr($lng,4,2).substr($lng,2,2).substr($lng,0,2));*/
                $latt = unpack("g", pack('H*',$latt))[1];
                $lng = unpack("g", pack('H*',$lng))[1];

                $sql_1 = 'SELECT COUNT(*) as total FROM node_data WHERE message_id_2 = \''.$body->uplinks[$j]->messageId.'\'';
                //echo $sql_1;
                $stmt_1 = $db->unprepared_query( $sql_1 );
                //print_r($stmt_1);
                $row_1 = $stmt_1->fetch(PDO::FETCH_ASSOC);
                if ($row_1['total']==0)
                {

                    $sql = 'INSERT into node_data (date_time,probe_id,message_id_2,latt,lng';

                    

                    $sql .= ') VALUES (\''.$dataDate.'\',\''.$body->uplinks[$j]->datagramUplinkEvent->nodeId;
                    $sql .= '\',\''.$body->uplinks[$j]->messageId;
                    $sql .= '\',\''.$latt;
                    $sql .= '\',\''.$lng;
                    
                    
                }

                $sql .= '\')';
                /*echo $sql;
                die;*/
                $stmt = $db->unprepared_query( $sql );
            break;
            }
       default:
            {
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
                $rg = (substr($line,22+8*$sensor_count,8));
                $rg = hexdec(substr($rg,6,2).substr($rg,4,2).substr($rg,2,2).substr($rg,0,2));
                //echo $moist.'MOISTURE';
                //echo $temp.'TEMPERATURE';

                $msensor = str_split($moist,4);


                $tsensor = str_split($temp,4);

                $sql_1 = 'SELECT COUNT(*) as total FROM node_data WHERE message_id_2 = \''.$body->uplinks[$j]->messageId.'\'';
                //echo $sql_1;
                $stmt_1 = $db->unprepared_query( $sql_1 );
                //print_r($stmt_1);
                $row_1 = $stmt_1->fetch(PDO::FETCH_ASSOC);
                if ($row_1['total']==0)
                {
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
                }

                $sql .= '\')';
                /*echo $sql;
                die;*/
                $stmt = $db->unprepared_query( $sql );
            
            }
        break;
        }
    
        
        
        //print_r($stmt);
    }
}
