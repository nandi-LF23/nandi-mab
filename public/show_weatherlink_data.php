<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// abort if system build is in progress
clearstatcache(true, 'mab_build_in_progress'); if(file_exists('mab_build_in_progress')){ return 1; }

function mab_db_connect(){

    $engine = 'mysql';
    $host = '127.0.0.1';
    $database = 'agri';
    $user = 'myagbuddy';
    $pass = 'N1ckn4ggp4dyw@gg!3212';

    $connection_string = $engine.':dbname='.$database.";host=".$host;
    try {
        $conn = new PDO($connection_string, $user, $pass);

    } catch (PDOException $e) {
        echo 'Connection failed: ' . $e->getMessage();
        $conn = null;
    }
    return $conn;
}

function mab_db_query($conn, $query, &$last_id = null)
{
    try {
        $stmt = $conn->query($query);
        if($last_id){ $last_id = $conn->lastInsertId(); }
        return $stmt;
    } catch (PDOException $e)
    {
        echo 'Connection failed: ' . $e->getMessage();
    }
    return null;
}

/* AUTHENTICATE: returns token on success, false on failure */
function mab_get_url($url, $post = false)
{
    $ch = curl_init( $url );
    if($post){
        curl_setopt( $ch, CURLOPT_POST, 1);
    }
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    
    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if($result !== false && $httpcode == 200){
        return $result;
    }
    return false;
}


function weatherlink_build_api_url($endpoint = '', $path_params = []){

    $apiurl = 'https://api.weatherlink.com/v2/';
    $apikey = 'si4vr90sacuplp1hhmbsd6ntel7hp5ni';
    $apisecret = 'n7nvrsuy9hamejg5dfq5q0ryzzl01jly';
    $timestamp = time();

    $params = [
        'api-key' => $apikey,
        't' => $timestamp
    ];

    $params = array_merge($params, $path_params);

    ksort($params);

    $signature = '';

    foreach($params as $k => $v){ $signature .= "$k$v"; }

    $signature = hash_hmac('sha256', $signature, $apisecret);

    return $apiurl . $endpoint . "?api-key={$apikey}&api-signature={$signature}&t={$timestamp}";
}

$data = mab_get_url(weatherlink_build_api_url('stations'));
$data = json_decode($data, true);
if(empty($data) || empty($data['stations'])){ echo "No Station data"; exit; }

echo "<h2>Stations Info</h2>";
echo "<pre>" . var_export($data, true) . "</pre>";
echo "<hr><br>";

$stations = $data['stations'];

foreach($stations as $station){
    $station_id = $station['station_id'];
    $data = mab_get_url(weatherlink_build_api_url("current/$station_id", ['station-id' => $station_id]));
    $s_data = json_decode($data, true);
    if($s_data){
        echo "<h3>" . $station['station_name'] . "</h3>";
        echo "<pre>" . var_export($s_data, true) . "</pre>";
        echo "<hr><br>";
    }
}