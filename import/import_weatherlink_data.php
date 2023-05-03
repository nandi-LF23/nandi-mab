<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// abort if system build is in progress
clearstatcache(true, 'mab_build_in_progress'); if(file_exists('mab_build_in_progress')){ return 1; }

function mab_db_connect(
    $engine = 'mysql',
    $host = '127.0.0.1',
    $database = 'agri',
    $user = 'myagbuddy',
    $pass='N1ckn4ggp4dyw@gg!3212'
){
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
        echo 'MAB Query Error: ' . $e->getMessage();
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

function mab_is_valid_json($data){
    $json = json_decode($data);
    if($json && json_last_error() == JSON_ERROR_NONE){
        return json_encode($json);
    }
    return false;
}

function mab_get_fields_wl_station_names($conn)
{
    $stmt = mab_db_query($conn, "SELECT DISTINCT wl_station_name FROM fields WHERE wl_station_name IS NOT NULL AND wl_station_name!=''");
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $row;
}

$conn = mab_db_connect();
if($conn){
    $names = mab_get_fields_wl_station_names($conn);
    if($names){
        $names = array_filter(array_map(function($row){ return trim(strtolower($row['wl_station_name'])); }, $names));
        $data = json_decode(mab_get_url(weatherlink_build_api_url("stations")), true);
        if($names && $data){
            foreach($data['stations'] as $station){
                if(in_array(strtolower(trim($station['station_name'])), $names)){
                    $station_id = $station['station_id'];
                    $station_name = $station['station_name'];
                    $product_number = $station['product_number'];
                    $station_data = json_encode($station);
                    $sensor_data = mab_get_url(weatherlink_build_api_url("current/$station_id", ['station-id' => $station_id]));

                    if($station_id && $product_number && mab_is_valid_json($station_data) && mab_is_valid_json($sensor_data)){
                        // save copy of data
                        mab_db_query(
                            $conn,
                            'INSERT INTO raw_data_weatherstation (id, station_id, station_data, sensor_data, created_at, updated_at) ' . 
                            'VALUES (NULL,' . $station_id . ',\'' . $station_data . '\',\'' . $sensor_data . '\', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');

                        mab_db_query(
                            $conn,
                            "UPDATE fields " . 
                            "SET wl_product_number='{$product_number}', wl_station_data='{$station_data}', wl_sensor_data='{$sensor_data}', wl_last_updated=NOW() " .
                            "WHERE wl_station_name='{$station_name}'"
                        );
                    }
                }
            }
        }
    }
}