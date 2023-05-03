<?php
/**
 * myAgBuddy Importer for AquaCheck Logger Data
 * @author fritz@liquidfibre.com
 * @version 1.0
 * 2021-03-03
 * 
 */

class Aquacheck_CSV_Importer {

    private $db; 

    public function __construct($db_info = [], $csv_info = []){
        $this->connect($db_info);
        $this->import($csv_info);
    }

    public function connect($db_info)
    {
        extract($db_info);

        $dsn = "mysql:host={$host};dbname={$db};port={$port};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->db = new \PDO($dsn, $user, $pw, $options);
            echo "Connected successfully.\n";
        } catch(\PDOException $e){
            die('Could not connect to DB:' . $e->getMessage());
        }
    }

    public function import($csv_info)
    {
        extract($csv_info);

        if(!file_exists($filepath)){
            die("Could not open file: '$filepath'\n");
        }

        $line_number = 1;
        $handle = fopen($filepath, "r");
        if(!$handle){
            die("Could not read file: '$filepath'\n");
        }

        // Prepare the insert statement once
        $insert_statement = $this->db->prepare(
            "INSERT INTO `node_data` (" .
                "id, probe_id, date_time, average, accumulative, " .
                "sm1, sm2, sm3, sm4, sm5, sm6, sm7, sm8, sm9, sm10, sm11, sm12, sm13, sm14, sm15, " .
                "t1,  t2,  t3,  t4,  t5,  t6,  t7,  t8,  t9,  t10,  t11,  t12,  t13,  t14,  t15, " .
                "rg, bv, latt, lng, message_id_1, message_id_2" .
            ") VALUES (" .
                ":id,  :probe_id,   :date_time, :average, :accumulative," .
                ":sm1, :sm2, :sm3,  :sm4, :sm5, :sm6, :sm7, :sm8, :sm9, :sm10, :sm11, :sm12, :sm13, :sm14, :sm15," .
                ":t1,  :t2,  :t3,   :t4,  :t5,  :t6,  :t7,  :t8,  :t9,  :t10,  :t11,  :t12,  :t13,  :t14,  :t15," .
                ":rg,  :bv,  :latt, :lng, :message_id_1, :message_id_2" . 
            ")"
        );

        while (($raw_string = fgets($handle)) !== false) {
            $row = str_getcsv($raw_string);
            $this->import_csv_row($row, $line_number, $insert_statement);
            $line_number++;
        }

        fclose($handle);
    }

    protected function import_csv_row($row, $line_number, $insert_statement){

        // INTEGRITY CHECKS

        // COLUMN COUNT CHECK: confirm at least 6 columns exist
        if(count($row) < 6){
            die("Line: $line_number: missing column(s) detected");
        }

        // EMPTY VALUE CHECK: confirm that all columns are non-blank
        if(empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[4]) || empty($row[5])){
            die("Line: $line_number: empty column(s) detected");
        }

        // HEX VALUE CHECK: confirm pure hex values only for certain columns
        if(!ctype_xdigit($row[3]) || !ctype_xdigit($row[4]) || !ctype_xdigit(str_replace('#', '', $row[5]))){
            die("Line: $line_number: columns contain non-hex characters");
        }

        // 1 @LOAD, ignored
        // 2 Probe
        $probe_id = $row[1];

        // 3 Timestamp
        $yy = substr($row[2], 0, 2); // Year
        $mm = substr($row[2], 2, 2); // Month
        $dd = substr($row[2], 4, 2); // Day
        $hh = substr($row[2], 6, 2); // Hour
        $ii = substr($row[2], 8, 2); // Minute
        $date_time = "20{$yy}-{$mm}-{$dd} {$hh}:{$ii}:00";

        // 4 Soil Moisture Readings
        $sm1 = number_format(100 - (hexdec(substr($row[3], 0,  4)) / 327.67), 2, '.', ''); // 2 bytes
        $sm2 = number_format(100 - (hexdec(substr($row[3], 4,  4)) / 327.67), 2, '.', ''); // 2 bytes
        $sm3 = number_format(100 - (hexdec(substr($row[3], 8,  4)) / 327.67), 2, '.', ''); // 2 bytes
        $sm4 = number_format(100 - (hexdec(substr($row[3], 12, 4)) / 327.67), 2, '.', ''); // 2 bytes
        $sm5 = number_format(100 - (hexdec(substr($row[3], 16, 4)) / 327.67), 2, '.', ''); // 2 bytes
        $sm6 = number_format(100 - (hexdec(substr($row[3], 20, 4)) / 327.67), 2, '.', ''); // 2 bytes

        // 5 Soil Temperature Readings (Celcius)
        $t1 = number_format(hexdec(substr($row[4], 0,  2)) / 5, 2, '.', ''); // 1 byte
        $t2 = number_format(hexdec(substr($row[4], 2,  2)) / 5, 2, '.', ''); // 1 byte
        $t3 = number_format(hexdec(substr($row[4], 4,  2)) / 5, 2, '.', ''); // 1 byte
        $t4 = number_format(hexdec(substr($row[4], 6,  2)) / 5, 2, '.', ''); // 1 byte
        $t5 = number_format(hexdec(substr($row[4], 8,  2)) / 5, 2, '.', ''); // 1 byte
        $t6 = number_format(hexdec(substr($row[4], 10, 2)) / 5, 2, '.', ''); // 1 byte

        // 6 Aux Data
        $ps   = hexdec(substr($row[5], 0,  4)); // 2 bytes, Plant sense
        $rg   = hexdec(substr($row[5], 4,  4)); // 2 bytes, Rain Gauge
        $rssi = hexdec(substr($row[5], 8,  2)); // 1 byte,  Signal Strength
        $bv   = hexdec(substr($row[5], 10, 2)); // 1 byte,  Batt. Voltage
        $rs   = substr($row[5], 12, 2);         // 1 byte,  Reserved (#)

        // echo "$line_number: [$probe_id, $date_time, [$sm1, $sm2, $sm3, $sm4, $sm5, $sm6], [$t1, $t2, $t3, $t4, $t5, $t6], $ps, $rg, $rssi, $bv, $rs]\n";

        return;

        // bind values to prepared statement and execute (insert into DB)
        $insert_statement->execute([
            ':id' => 'NULL',

            ':probe_id' => $probe_id,
            ':date_time' => $date_time,
            ':average' => 0,               // CALCULATED VALUE
            ':accumulative' => 0,          // CALCULATED VALUE

            ':sm1'  => $sm1, ':sm2'  => $sm2, ':sm3'  => $sm3, ':sm4'  => $sm4, ':sm5'  => $sm5,
            ':sm6'  => $sm6, ':sm7'  => 0,    ':sm8'  => 0,    ':sm9'  => 0,    ':sm10' => 0,
            ':sm11' => 0,    ':sm12' => 0,    ':sm13' => 0,    ':sm14' => 0,    ':sm15' => 0,

            ':t1'   => $t1,  ':t2'   => $t2,  ':t3'   => $t3,  ':t4'   => $t4,  ':t5'   => $t5,
            ':t6'   => $t6,  ':t7'   => 0,    ':t8'   => 0,    ':t9'   => 0,    ':t10'  => 0,
            ':t11'  => 0,    ':t12'  => 0,    ':t13'  => 0,    ':t14'  => 0,    ':t15'  => 0,

            /* PS Column Missing in DB */

            ':rg'   => $rg,  ':bv'   => $bv,
            ':latt' => 0,    ':lng'  => 0, // CALCULATED VALUES

            ':message_id_1' => '',         // CALCULATED VALUE
            ':message_id_2' => ''          // CALCULATED VALUE
        ]);
    }

    public static function usage(){
        echo "Usage: php " . basename(__FILE__) . " <csv-file-path>\n";
    }
}

if(empty($argv[1])){
    Aquacheck_CSV_Importer::usage();
} else {
    $importer = new Aquacheck_CSV_Importer([
        'host'     => 'localhost',
        'db'       => 'agri',
        'port'     => 3306,
        'charset'  => 'utf8mb4',
        'user'     => 'myagbuddy',
        'pw'       => 'N1ckn4ggp4dyw@gg!3212'
    ],[
        'filepath' => $argv[1]
    ]);
}