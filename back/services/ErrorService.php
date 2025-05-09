<?php
class ErrorService
{
	private static $instance = null;
	private $logDir;
	private $logFiles = [
		'api' => 'api/errors.log',
		'database' => 'database/errors.log',
		'auth' => 'auth/errors.log',
		'general' => 'general/errors.log'
	];

	private $errorMessages = [
		'CONNECTION_FAILED' => [
			'message' => 'Impossible de se connecter au serveur',
			'details' => 'Vérifiez que le serveur est en cours d\'exécution et que vous avez les permissions nécessaires'
		],
		'DATABASE_ERROR' => [
			'message' => 'Erreur de connexion à la base de données',
			'details' => 'Vérifiez les identifiants de connexion et que le serveur MySQL est en cours d\'exécution'
		],
		'AUTH_FAILED' => [
			'message' => 'Échec de l\'authentification',
			'details' => 'Vérifiez vos identifiants de connexion'
		],
		'PERMISSION_DENIED' => [
			'message' => 'Permission refusée',
			'details' => 'Vous n\'avez pas les droits nécessaires pour effectuer cette action'
		],
		'INVALID_REQUEST' => [
			'message' => 'Requête invalide',
			'details' => 'La requête envoyée au serveur est incorrecte'
		],
		'SERVER_ERROR' => [
			'message' => 'Erreur interne du serveur',
			'details' => 'Une erreur inattendue s\'est produite sur le serveur'
		]
	];

	private function __construct()
	{
		// Déterminer si nous sommes sur Windows ou Linux
		$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

		// Chemin de base pour les logs
		$basePath = dirname(dirname(__DIR__));

		// Sur Windows, utiliser le chemin Windows, sinon utiliser le chemin Linux
		if ($isWindows) {
			$this->logDir = str_replace('/', '\\', $basePath . '\\logs\\');
		} else {
			$this->logDir = $basePath . '/logs/';
		}

		$this->ensureLogDirectories();
	}

	private function ensureLogDirectories()
	{
		try {
			// Créer le dossier principal des logs s'il n'existe pas
			if (!file_exists($this->logDir)) {
				@mkdir($this->logDir, 0777, true);
			}

			// Créer les sous-dossiers et fichiers de log
			foreach ($this->logFiles as $type => $file) {
				$dir = dirname($this->logDir . $file);
				if (!file_exists($dir)) {
					@mkdir($dir, 0777, true);
				}

				$logFile = $this->logDir . $file;
				if (!file_exists($logFile)) {
					@file_put_contents($logFile, '');
					@chmod($logFile, 0666);
				}
			}
		} catch (Exception $e) {
			// En cas d'erreur, utiliser le journal d'erreurs système
			error_log("Impossible de créer les répertoires de logs: " . $e->getMessage());
		}
	}

	public static function getInstance()
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function logError($message, $type = 'general', $details = [])
	{
		$timestamp = date('Y-m-d H:i:s');
		$logMessage = "[$timestamp] [$type] $message\n";

		if (!empty($details)) {
			$logMessage .= "Détails: " . print_r($details, true) . "\n";
		}

		$logMessage .= "Trace: " . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5), true) . "\n";
		$logMessage .= "----------------------------------------\n";

		$logFile = $this->logDir . ($this->logFiles[$type] ?? 'general/errors.log');

		try {
			// Vérifier si le fichier existe et est accessible en écriture
			if (!file_exists($logFile)) {
				@file_put_contents($logFile, '');
				@chmod($logFile, 0666);
			}

			if (!is_writable($logFile)) {
				@chmod($logFile, 0666);
			}

			// Essayer d'écrire dans le fichier de log
			@file_put_contents($logFile, $logMessage, FILE_APPEND);
		} catch (Exception $e) {
			// En cas d'échec, utiliser le journal d'erreurs système
			error_log("Erreur lors de l'écriture dans le fichier de log: " . $e->getMessage());
			error_log($logMessage);
		}
	}

	public function getErrorResponse($errorCode, $additionalDetails = [])
	{
		if (!isset($this->errorMessages[$errorCode])) {
			$errorCode = 'SERVER_ERROR';
		}

		$error = $this->errorMessages[$errorCode];
		$response = [
			'error' => true,
			'code' => $errorCode,
			'message' => $error['message'],
			'details' => $error['details']
		];

		if (!empty($additionalDetails)) {
			$response['additional_details'] = $additionalDetails;
		}

		return $response;
	}

	public function sendErrorResponse($errorCode, $additionalDetails = [], $httpCode = 500)
	{
		$response = $this->getErrorResponse($errorCode, $additionalDetails);
		http_response_code($httpCode);
		header('Content-Type: application/json');
		echo json_encode($response);
		exit;
	}
}
