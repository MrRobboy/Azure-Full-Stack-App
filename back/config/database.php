<?php
require_once __DIR__ . '/config.php';

class Database
{
	private $host = DB_HOST;
	private $db_name = DB_NAME;
	private $username = DB_USER;
	private $password = DB_PASS;
	private $conn;

	public function getConnection()
	{
		$this->conn = null;

		try {
			$dsn = "mysql:host=$this->host;dbname=$this->db_name;charset=utf8mb4";
			$this->conn = new PDO($dsn, $this->username, $this->password);
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			throw new Exception("Erreur de connexion à MariaDB: " . $e->getMessage());
		}

		return $this->conn;
	}
}
