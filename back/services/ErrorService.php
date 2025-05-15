<?php
class ErrorService
{
	private static $instance = null;
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
		// Initialisation vide car nous utilisons le journal d'erreurs système
	}

	public static function getInstance()
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function logError($context, $message, $details = [])
	{
		$timestamp = date('Y-m-d H:i:s');
		$logMessage = "[$timestamp] [$context] $message\n";

		if (!empty($details)) {
			$logMessage .= "Détails: " . print_r($details, true) . "\n";
		}

		$logMessage .= "Trace: " . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5), true) . "\n";
		$logMessage .= "----------------------------------------\n";

		// Utiliser le journal d'erreurs système
		error_log($logMessage);
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
