<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/ErrorService.php';
require_once __DIR__ . '/SqlHelper.php';

class DatabaseService
{
	private static $instance = null;
	private $connection;
	private $errorService;

	private function __construct()
	{
		$this->errorService = ErrorService::getInstance();
		try {
			// Vérifier le type de base de données
			if (defined('DB_TYPE') && DB_TYPE === 'sqlsrv') {
				// Connexion SQL Server
				$this->connectToSqlServer();
			} else {
				// Connexion MySQL/MariaDB par défaut pour rétrocompatibilité
				$this->connectToMySql();
			}

			// Test de la connexion
			$this->testConnection();
		} catch (PDOException $e) {
			$this->errorService->logError("Erreur de connexion à la base de données: " . $e->getMessage(), 'database');
			throw new Exception("Impossible de se connecter à la base de données: " . $e->getMessage());
		}
	}

	private function connectToSqlServer()
	{
		// Configurer la connexion SQL Server
		$port = defined('DB_PORT') ? DB_PORT : '1433';
		$dsn = "sqlsrv:Server=" . DB_HOST . "," . $port . ";Database=" . DB_NAME;

		$options = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
		];

		$this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
	}

	private function connectToMySql()
	{
		// Configurer la connexion MySQL/MariaDB
		$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

		$options = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES => false
		];

		$this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
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
				if (defined('DB_TYPE') && DB_TYPE === 'sqlsrv') {
					$this->connectToSqlServer();
				} else {
					$this->connectToMySql();
				}
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

	/**
	 * Récupère l'ID du dernier enregistrement inséré, compatible avec SQL Server et MySQL
	 *
	 * @param string $table Nom de la table (utilisé uniquement pour SQL Server si besoin)
	 * @param string $idColumn Nom de la colonne ID (utilisé uniquement pour SQL Server si besoin)
	 * @return string|int L'ID de la dernière insertion
	 */
	public function lastInsertId($table = null, $idColumn = null)
	{
		// Pour MySQL/MariaDB, on utilise la méthode standard
		if (!defined('DB_TYPE') || DB_TYPE !== 'sqlsrv') {
			return $this->connection->lastInsertId();
		}

		// Pour SQL Server, on utilise SCOPE_IDENTITY()
		try {
			$stmt = $this->connection->query("SELECT SCOPE_IDENTITY() AS last_id");
			$result = $stmt->fetch(PDO::FETCH_ASSOC);

			if ($result && isset($result['last_id'])) {
				return $result['last_id'];
			}

			// Fallback vers la méthode standard si SCOPE_IDENTITY() ne fonctionne pas
			return $this->connection->lastInsertId();
		} catch (PDOException $e) {
			$this->errorService->logError("Erreur lors de la récupération du dernier ID inséré: " . $e->getMessage(), 'database');
			return $this->connection->lastInsertId(); // Tenter d'utiliser la méthode standard comme fallback
		}
	}
}
