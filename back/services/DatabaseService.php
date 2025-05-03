<?php
// Activation de l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/ErrorService.php';

class DatabaseService
{
	private static $instance = null;
	private $connection = null;
	private $errorService;

	private function __construct()
	{
		$this->errorService = ErrorService::getInstance();
		try {
			$this->connection = new PDO(
				"mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
				DB_USER,
				DB_PASS,
				array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
			);
		} catch (PDOException $e) {
			$this->errorService->logError("Erreur de connexion à la base de données: " . $e->getMessage(), 'database');
			throw new Exception("Impossible de se connecter à la base de données");
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
		return $this->connection;
	}

	public function testConnection()
	{
		try {
			$this->connection->query("SELECT 1");
			return true;
		} catch (PDOException $e) {
			$this->errorService->logError("Erreur lors du test de connexion: " . $e->getMessage(), 'database');
			return false;
		}
	}
}
