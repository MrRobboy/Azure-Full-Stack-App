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
		$this->logDir = __DIR__ . '/../logs';
		$this->ensureLogDirectories();
	}

	private function ensureLogDirectories()
	{
		if (!file_exists($this->logDir)) {
			mkdir($this->logDir, 0777, true);
		}

		foreach ($this->logFiles as $type => $file) {
			$dir = dirname($this->logDir . '/' . $file);
			if (!file_exists($dir)) {
				mkdir($dir, 0777, true);
			}
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
		$timestamp = date('Y-m-d H:i:s');
		$logMessage = "[$timestamp] $message\n";

		if (!empty($details)) {
			$logMessage .= "Details: " . json_encode($details, JSON_PRETTY_PRINT) . "\n";
		}

		$logMessage .= "----------------------------------------\n";

		$logFile = $this->logDir . '/' . $this->logFiles[$type];
		file_put_contents($logFile, $logMessage, FILE_APPEND);

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
