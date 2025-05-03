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
			$this->conn = new PDO(
				"mysql:host=" . $this->host . ";dbname=" . $this->db_name,
				$this->username,
				$this->password
			);
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->conn->exec("set names utf8");
		} catch (PDOException $e) {
			throw new Exception("Erreur de connexion à la base de données: " . $e->getMessage());
		}

		return $this->conn;
	}
}
