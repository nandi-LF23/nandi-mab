<?php


require_once('db.php');

    $db = new db();


/*
for($i=0;sizeof($body->uplinks)>$i;$i++)
{
if($body->uplinks[$i]->datagramUplinkEvent->nodeId == $_GET['nodeid']) {
  print_r(  $body->uplinks[$i]); }
}
*/
$sql = 'SELECT id,sm1,sm2,sm3,sm4,sm5,sm6,sm7,sm8,sm9,sm10,sm11,sm12,sm13,sm14,sm15 FROM node_data';
$stmt = $db->unprepared_query( $sql );

foreach ($stmt->fetchAll(PDO::FETCH_NUM) as $row)
{
$id = array_shift($row);
//print_r($row);
//calculations for sum and ave
$sum = 0;
$ave = 0;
$sensors = 1;

foreach ($row as $sm) {
if ($sm)
    if ($sm > 0)
    {    
    $sum += $sm;
    $sensors++;
    }
}
$sum = $sum - $row['id'];
$ave = $sum / $sensors;
echo 'SUM'.$sum.'AVE'.$ave ."    ";

$sql = 'UPDATE node_data SET average=\''.$ave.'\',accumulative=\''.$sum;


$sql .= '\' WHERE id='.$id;
//echo $sql;
//die;
$stmt = $db->unprepared_query( $sql );
}
