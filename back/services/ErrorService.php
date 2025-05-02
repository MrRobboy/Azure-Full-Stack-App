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

	private function __construct()
	{
		// Essayer d'abord le répertoire temporaire système
		$this->logDir = sys_get_temp_dir() . '/azure-app-logs/';

		// Si le répertoire temporaire n'est pas accessible, essayer le répertoire de l'application
		if (!is_writable($this->logDir)) {
			$this->logDir = __DIR__ . '/../../logs/';
		}

		$this->ensureLogDirectories();
	}

	private function ensureLogDirectories()
	{
		try {
			if (!file_exists($this->logDir)) {
				@mkdir($this->logDir, 0755, true);
			}

			foreach ($this->logFiles as $type => $file) {
				$dir = dirname($this->logDir . $file);
				if (!file_exists($dir)) {
					@mkdir($dir, 0755, true);
				}
			}
		} catch (Exception $e) {
			// En cas d'échec, utiliser error_log() comme fallback
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

	public function logError($type, $message, $details = [])
	{
		try {
			$timestamp = date('Y-m-d H:i:s');
			$logMessage = "[$timestamp] $message\n";

			if (!empty($details)) {
				$logMessage .= "Details: " . json_encode($details, JSON_PRETTY_PRINT) . "\n";
			}

			$logMessage .= "----------------------------------------\n";

			$logFile = $this->logDir . $this->logFiles[$type];
			if (is_writable(dirname($logFile))) {
				@file_put_contents($logFile, $logMessage, FILE_APPEND);
			} else {
				error_log($logMessage);
			}
		} catch (Exception $e) {
			error_log("Erreur lors de l'écriture des logs: " . $e->getMessage());
		}

		return $this->formatErrorResponse($type, $message, $details);
	}

	private function formatErrorResponse($type, $message, $details)
	{
		return [
			'success' => false,
			'error' => [
				'type' => $type,
				'message' => $message,
				'details' => $details,
				'timestamp' => date('Y-m-d H:i:s')
			]
		];
	}
}
