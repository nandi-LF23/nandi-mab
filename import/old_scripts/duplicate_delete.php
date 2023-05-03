<?php
require_once('db.php');

$db = new db();


$sql = 'SELECT * FROM ac_usa';
$stmt = $db->unprepared_query( $sql );
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($data as $row)
{

    $query3 = 'INSERT INTO readings SELECT * FROM ac_usa WHERE id='.$row['id'];
    echo $query3;
    
    $stmt3 = $db->unprepared_query( $query3 );
    echo 'inserted into readings<br>';

}