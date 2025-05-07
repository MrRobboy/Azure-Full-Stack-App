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
			$dsn = "sqlsrv:Server=$this->host;Database=$this->db_name";
			$this->conn = new PDO($dsn, $this->username, $this->password);
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			throw new Exception("Erreur de connexion à SQL Server: " . $e->getMessage());
		}

		return $this->conn;
	}
}