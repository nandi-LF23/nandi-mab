<?php
error_reporting(E_ERROR | E_PARSE);
//the class which accesses the database using pdo
class db {

	private $engine;
	private $host;
	private $database;
	private $user;
	private $pass;
	public $conn;
	public $last;

	public function __construct(){
		$this->engine = 'mysql';
		$this->host = '127.0.0.1';
		$this->database = 'agri';
		$this->user = 'root';
		$this->pass = 'bczwucpq';


		$connection_string = $this->engine.':dbname='.$this->database.";host=".$this->host;
		try {
			$this->conn = new PDO($connection_string, $this->user, $this->pass);

		} catch (PDOException $e) {
			echo 'Connection failed: ' . $e->getMessage();
		}
        
	}
	function unprepared_query($query)
	{
		try {
			$stmt = $this->conn->query($query);
			$this->last = $this->conn->lastInsertId();

			return $stmt;
		} catch (PDOException $e)
		{
			echo 'Connection failed: ' . $e->getMessage();
		}
	}
	function prepared_query($query, $array = array()) {
		try {
			//echo $query;
			$stmt = $this->conn->prepare($query);
			$stmt->execute($array);

			$this->last = $this->conn->lastInsertId();

			return $stmt;
		} catch (PDOException $e)
		{
			echo 'Connection failed: ' . $e->getMessage();


		}
	}
}
?>
