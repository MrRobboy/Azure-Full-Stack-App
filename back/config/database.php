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
			// Vérifier si on utilise SQL Server
			if (defined('DB_TYPE') && DB_TYPE === 'sqlsrv') {
				$port = defined('DB_PORT') ? DB_PORT : '1433';
				$dsn = "sqlsrv:Server=$this->host,$port;Database=$this->db_name";
				$this->conn = new PDO($dsn, $this->username, $this->password);
				$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			} else {
				// Par défaut, utiliser MySQL/MariaDB pour la rétrocompatibilité
				$dsn = "mysql:host=$this->host;dbname=$this->db_name;charset=utf8mb4";
				$this->conn = new PDO($dsn, $this->username, $this->password);
				$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			}
		} catch (PDOException $e) {
			$dbType = defined('DB_TYPE') ? DB_TYPE : 'mysql';
			throw new Exception("Erreur de connexion à $dbType: " . $e->getMessage());
		}

		return $this->conn;
	}
}
