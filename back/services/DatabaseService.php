<?php
require_once __DIR__ . '/../config/config.php';

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
				array(
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
					PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
				)
			);
		} catch (PDOException $e) {
			error_log("Erreur de connexion à la base de données : " . $e->getMessage());
			throw new Exception("Impossible de se connecter à la base de données. Veuillez réessayer plus tard.", 500);
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
		if (!$this->pdo) {
			throw new Exception("La connexion à la base de données n'est pas initialisée", 500);
		}
		return $this->pdo;
	}
}
