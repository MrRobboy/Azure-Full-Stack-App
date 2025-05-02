<?php
// Activation de l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';

class DatabaseService
{
	private static $instance = null;
	private $pdo;

	private function __construct()
	{
		try {
			$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;

			$options = array(
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
			);

			$this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
		} catch (PDOException $e) {
			$error_details = [
				'message' => $e->getMessage(),
				'code' => $e->getCode(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace' => $e->getTraceAsString(),
				'dsn' => $dsn,
				'user' => DB_USER,
				'database' => DB_NAME
			];

			throw new Exception(json_encode([
				'message' => "Erreur de connexion à la base de données",
				'details' => $error_details
			]), 500);
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
