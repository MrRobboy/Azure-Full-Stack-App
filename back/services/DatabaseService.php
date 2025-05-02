<?php
class DatabaseService
{
	private static $instance = null;
	private $pdo;

	private function __construct()
	{
		try {
			$this->pdo = new PDO(
				"mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
				DB_USER,
				DB_PASS,
				array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
			);
		} catch (PDOException $e) {
			die("Erreur de connexion : " . $e->getMessage());
		}
	}

	public static function getInstance()
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function getConnection()
	{
		return $this->pdo;
	}
}
