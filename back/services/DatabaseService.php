<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/ErrorService.php';

class DatabaseService
{
	private static $instance = null;
	private $connection;
	private $errorService;

	private function __construct()
	{
		$this->errorService = ErrorService::getInstance();
		try {
			$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
			$options = [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				PDO::ATTR_EMULATE_PREPARES => false
			];
			$this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);

			// Test de la connexion
			$this->testConnection();
		} catch (PDOException $e) {
			$this->errorService->logError("Erreur de connexion à la base de données: " . $e->getMessage(), 'database');
			throw new Exception("Impossible de se connecter à la base de données: " . $e->getMessage());
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
		if ($this->connection === null) {
			throw new Exception("La connexion à la base de données n'est pas initialisée");
		}

		// Vérifier si la connexion est toujours active
		try {
			$this->testConnection();
		} catch (Exception $e) {
			// Tenter de reconnecter
			try {
				$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
				$options = [
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
					PDO::ATTR_EMULATE_PREPARES => false
				];
				$this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
			} catch (PDOException $e) {
				$this->errorService->logError("Erreur de reconnexion à la base de données: " . $e->getMessage(), 'database');
				throw new Exception("Impossible de se reconnecter à la base de données");
			}
		}

		return $this->connection;
	}

	public function testConnection()
	{
		try {
			$this->connection->query("SELECT 1");
			return true;
		} catch (PDOException $e) {
			$this->errorService->logError("Erreur lors du test de connexion: " . $e->getMessage(), 'database');
			throw new Exception("La connexion à la base de données a été perdue");
		}
	}
}
