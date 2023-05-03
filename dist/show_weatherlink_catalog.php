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

function weatherlink_create_product_dropdown_data($data = null, $echo = false)
{
    if($data == null){
        $data = mab_get_url(weatherlink_build_api_url('sensor-catalog'));
        $data = json_decode($data, true);
        if(empty($data)){ if($echo){ echo "No Catalog data"; } return []; }
    }

    $products = [];

    foreach($data['sensor_types'] as $sensor_type){
        if(empty($sensor_type['product_number'])){ continue; }
        $products[$sensor_type['manufacturer']][$sensor_type['product_number']] = $sensor_type['product_name'];
    }

    if($echo){ echo json_encode($products); }

    return $products;
}

function weatherlink_create_product_eto_data($data = null, $echo = false)
{
    if($data == null){
        $data = mab_get_url(weatherlink_build_api_url('sensor-catalog'));
        $data = json_decode($data, true);
        if(empty($data)){ if($echo){ echo "No Catalog data"; } return []; }
    }
        
    $products_eto = [];

    foreach($data['sensor_types'] as $sensor_type){

        $product_number = $sensor_type['product_number'];

        if(empty($product_number)){ continue; }

        $prod_info = [
            'name' => $sensor_type['product_name'],
            'ds' => []
        ];
        
        if(array_key_exists('data_structures', $sensor_type)){
            foreach($sensor_type['data_structures'] as $ds){

                $has_eto = false;

                $ds_info = [
                    'type'   => $ds['data_structure_type'],
                    'desc'   => $ds['description'],
                    'et_day' => false
                ];

                foreach($ds['data_structure'] as $key => $data){
                    if($key == 'et_day'){
                        $has_eto = true;
                        $ds_info['et_day'] = true;
                    }
                }

                if($has_eto){
                    $prod_info['ds'][] = $ds_info;
                }
            }
        }

        if(!empty($prod_info['ds'])){
            $products_eto[$product_number] = $prod_info;
        }
    }

    if($echo){ echo json_encode($products_eto); }

    return $products_eto;
}

function weatherlink_create_product_eto_dropdown_data($eto_data)
{
    if(!$eto_data) return [];

    $dropdown_data = [];

    foreach($eto_data as $key => $row){
        $dropdown_data[$key] = $row['name'];
    }
    return $dropdown_data;
}

function weatherlink_get_product_eto_data($echo = false, $json_encode = false)
{
    $data = mab_get_url(weatherlink_build_api_url('sensor-catalog'));
    $data = json_decode($data, true);
    if(empty($data)){ if($echo){ echo "No Catalog data"; } return []; }

    $eto_data = weatherlink_create_product_eto_data($data);
    $dropdown = weatherlink_create_product_eto_dropdown_data($eto_data);

    if($echo){
        echo json_encode($dropdown);
        echo '---------------------';
        echo json_encode($eto_data);
    }

    $ret = [
        'eto_data' => $eto_data,
        'products' => $dropdown
    ];

    return $json_encode ? json_encode($ret) : $ret;
}

weatherlink_get_product_eto_data(true);