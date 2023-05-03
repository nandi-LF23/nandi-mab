<?php

class pdo_dblib_mssql{

    public $db;

    public function __construct($hostname, $port, $dbname, $username, $pwd){

        $this->hostname = $hostname;
        $this->port = $port;
        $this->dbname = $dbname;
        $this->username = $username;
        $this->pwd = $pwd;

        $this->connect();
       
    }


    public function close(){
        $this->db = null;
    }

    public function connect(){

        try {
            $this->db = new PDO ("dblib:host=$this->hostname:$this->port;dbname=$this->dbname", "$this->username", "$this->pwd");

          

        } catch (PDOException $e) {
            $this->logsys .= "Failed to get DB handle: " . $e->getMessage() . "\n";
        }

    }

}

$connection = new pdo_dblib_mssql('oa5rj2illc.database.windows.net',1433,'dss-stream','liquidfibre@oa5rj2illc','40f#8fe23!8e19');


$link = mysqli_connect('liquidfibre.com', 'liquidptv_8', 'QcYyL549mq9ei15jSBb8');
if (!$link) {
    die('Not connected : ' . mysqli_error());
}

// make foo the current db
$db_selected = mysqli_select_db($link,'ac_usa');
if (!$db_selected) {
    die ('Can\'t use ac_usa : ' . mysqli_error());
}


$result = $link->query("SELECT * FROM trimble WHERE (probe_id='0x0008536d' OR probe_id='0x0008563d' OR probe_id='0x0008691d' OR probe_id='0x00086913' OR probe_id='0x00085a84') AND imported=0");
    
    foreach ($result as $rw)
    {
        print_r($rw);
        
        $connection->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $statement = $connection->db->prepare("INSERT INTO tbl_ranch_systems(
            probe_id,
            date_time,
            soil_moisture_1,
            soil_moisture_2,
            soil_moisture_3,
            soil_moisture_4,
            soil_moisture_5,
            soil_moisture_6,
            soil_moisture_7,
            soil_moisture_8,
            soil_moisture_9,
            soil_moisture_10,
            soil_moisture_11,
            soil_moisture_12,
            soil_moisture_13,
            soil_moisture_14,
            soil_moisture_15,
            soil_temperature_1,
            soil_temperature_2,
            soil_temperature_3,
            soil_temperature_4,
            soil_temperature_5,
            soil_temperature_6,
            soil_temperature_7,
            soil_temperature_8,
            soil_temperature_9,
            soil_temperature_10,
            soil_temperature_11,
            soil_temperature_12,
            soil_temperature_13,
            soil_temperature_14,
            soil_temperature_15,
            rain_gauge,
            battery_voltage,
            latitude,
            longitude
            
        )
            VALUES(
            ?,?,?,?,?
            ,?,?,?,?,?
            ,?,?,?,?,?
            ,?,?,?,?,?
            ,?,?,?,?,?
            ,?,?,?,?,?
            ,?,?,?,?,?,?
            )");

        ($statement->execute(array(
            $rw['probe_id'],
            $rw['date_time'],
            $rw['sm1'],
            $rw['sm2'],
            $rw['sm3'],
            $rw['sm4'],
            $rw['sm5'],
            $rw['sm6'],
            $rw['sm7'],
            $rw['sm8'],
            $rw['sm9'],
            $rw['sm10'],
            $rw['sm11'],
            $rw['sm12'],
            $rw['sm13'],
            $rw['sm14'],
            $rw['sm15'],
            $rw['t1'],
            $rw['t2'],
            $rw['t3'],
            $rw['t4'],
            $rw['t5'],
            $rw['t6'],
            $rw['t7'],
            $rw['t8'],
            $rw['t9'],
            $rw['t10'],
            $rw['t11'],
            $rw['t12'],
            $rw['t13'],
            $rw['t14'],
            $rw['t15'],
            $rw['rg'],
            $rw['bv'],
            $rw['latt'],
            $rw['lng'])));

            

            $link->query('UPDATE trimble SET imported = 1 WHERE id = '.$rw['id']);
    }
    
    

    

?>