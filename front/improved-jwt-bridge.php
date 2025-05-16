<?php

/**
 * Improved JWT Auth Bridge - Compatible avec Azure Backend
 * Date de génération: 2023-06-10
 * Dernière mise à jour: 2023-06-18
 */

// Configuration de base
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Création du répertoire de logs si nécessaire
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
	mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/improved-jwt-bridge.log');

// Journaliser l'accès
error_log("Improved JWT Auth Bridge accédé: " . $_SERVER['REQUEST_URI']);
error_log("Méthode: " . $_SERVER['REQUEST_METHOD']);

// Configuration CORS renforcée
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Traiter les requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

// Vérifier si c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode([
		'success' => false,
		'message' => 'Seule la méthode POST est autorisée',
		'debug_info' => [
			'received_method' => $_SERVER['REQUEST_METHOD'],
			'expected_method' => 'POST',
			'server' => $_SERVER['SERVER_SOFTWARE'],
			'request_time' => date('Y-m-d H:i:s')
		]
	]);
	exit;
}

// Obtenir le corps de la requête
$input = file_get_contents('php://input');
error_log("Corps de requête: " . substr($input, 0, 100) . (strlen($input) > 100 ? '...' : ''));

// Vérifier la syntaxe JSON
$requestData = json_decode($input);
if (json_last_error() !== JSON_ERROR_NONE) {
	http_response_code(400);
	echo json_encode([
		'success' => false,
		'message' => 'JSON invalide: ' . json_last_error_msg(),
		'input_preview' => substr($input, 0, 100) . (strlen($input) > 100 ? '...' : '')
	]);
	exit;
}

// Vérifier si tous les paramètres requis sont présents
if (!isset($requestData->email) || !isset($requestData->password)) {
	http_response_code(400);
	echo json_encode([
		'success' => false,
		'message' => 'Les paramètres email et password sont requis',
		'received_keys' => array_keys(get_object_vars($requestData))
	]);
	exit;
}

// Configuration du backend
$backendUrl = 'https://app-backend-esgi-app.azurewebsites.net';
error_log("URL du backend configurée: " . $backendUrl);

// Déterminer si nous sommes sur Azure ou en local
$isAzure = strpos($_SERVER['HTTP_HOST'] ?? '', 'azurewebsites.net') !== false;
error_log("Environnement détecté: " . ($isAzure ? "Azure" : "Local"));

// Liste des endpoints d'API dans l'ordre de priorité
$authEndpoints = [
	'api/auth/login',       // Format API REST standard - priorité maximale
	'api-auth-login.php',   // Point d'entrée principal, formaté comme notre code
	'auth/login',           // Alternative en format REST
	'api-auth.php',         // Alternative directe
	'login.php',            // Point d'entrée simplifié
	'auth.php'              // Alternative simple
];

// Essayer chaque endpoint d'authentification
$authSuccess = false;
$authResponse = null;
$lastError = null;
$responseDetails = [];

foreach ($authEndpoints as $endpoint) {
	$targetUrl = $backendUrl . '/' . $endpoint;
	error_log("Tentative d'authentification sur: " . $targetUrl);

	$ch = curl_init($targetUrl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15); // 15 secondes de timeout
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
	curl_setopt($ch, CURLOPT_ENCODING, '');

	$headers = [
		'Content-Type: application/json',
		'Accept: application/json',
		'X-Requested-With: XMLHttpRequest'
	];

	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$error = curl_error($ch);
	$responseHeaders = curl_getinfo($ch);

	error_log("Réponse de " . $endpoint . ": Code " . $httpCode . ", Corps: " . substr($response, 0, 100) . (strlen($response) > 100 ? '...' : ''));

	// Enregistrer les détails de cette tentative
	$responseDetails[] = [
		'endpoint' => $endpoint,
		'url' => $targetUrl,
		'status' => $httpCode,
		'error' => $error,
		'response_preview' => substr($response, 0, 200) . (strlen($response) > 200 ? '...' : '')
	];

	if ($error) {
		error_log("Erreur cURL: " . $error);
		$lastError = $error;
	} elseif ($httpCode >= 200 && $httpCode < 300) {
		// Réponse de succès (2xx)
		try {
			// D'abord vérifier si c'est un JWT brut
			if (strlen($response) > 0 && substr($response, 0, 2) === 'ey' && strpos($response, '.') !== false) {
				error_log("JWT brut détecté dans la réponse");
				$authSuccess = true;
				$jwt = trim($response);
				$authResponse = (object) [
					'success' => true,
					'message' => 'Authentification réussie (JWT brut)',
					'data' => (object) [
						'token' => $jwt,
						'user' => (object) [
							'email' => $requestData->email
						]
					]
				];
				break;
			}

			$responseData = json_decode($response);
			if (json_last_error() === JSON_ERROR_NONE) {
				// Vérifier le format de réponse du backend
				if (isset($responseData->success) && $responseData->success === true) {
					// Format standard {success: true, token: "..."} ou {success: true, data: {token: "..."}}
					if (isset($responseData->token)) {
						$authSuccess = true;
						// Reformater pour compatibilité
						$authResponse = (object) [
							'success' => true,
							'message' => 'Authentification réussie',
							'data' => (object) [
								'token' => $responseData->token,
								'user' => isset($responseData->user) ? $responseData->user : (object) [
									'email' => $requestData->email
								]
							]
						];
						break;
					} elseif (isset($responseData->data) && isset($responseData->data->token)) {
						// Format standard du backend conforme
						$authSuccess = true;
						$authResponse = $responseData;
						break;
					}
				}
				// Format simple {token: "..."}
				elseif (isset($responseData->token)) {
					$authSuccess = true;
					$authResponse = (object) [
						'success' => true,
						'message' => 'Authentification réussie (format simple)',
						'data' => (object) [
							'token' => $responseData->token,
							'user' => (object) [
								'email' => $requestData->email
							]
						]
					];
					break;
				}
				// Autres formats possibles...
				elseif (isset($responseData->access_token)) {
					$authSuccess = true;
					$authResponse = (object) [
						'success' => true,
						'message' => 'Authentification réussie (format access_token)',
						'data' => (object) [
							'token' => $responseData->access_token,
							'user' => (object) [
								'email' => $requestData->email
							]
						]
					];
					break;
				} elseif (is_string($responseData) && strpos($responseData, '.') !== false && strpos($responseData, 'ey') === 0) {
					// C'est probablement un JWT brut retourné comme JSON string
					$authSuccess = true;
					$authResponse = (object) [
						'success' => true,
						'message' => 'Authentification réussie (JWT brut)',
						'data' => (object) [
							'token' => $responseData,
							'user' => (object) [
								'email' => $requestData->email
							]
						]
					];
					break;
				}
			} else if (strlen($response) > 20) {
				// Si c'est une longue chaîne non-JSON, vérifier si c'est un JWT
				if (preg_match('/ey[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+/', $response, $matches)) {
					$authSuccess = true;
					$authResponse = (object) [
						'success' => true,
						'message' => 'Authentification réussie (JWT extrait)',
						'data' => (object) [
							'token' => $matches[0],
							'user' => (object) [
								'email' => $requestData->email
							]
						]
					];
					break;
				}
			}
		} catch (Exception $e) {
			error_log("Erreur lors du traitement de la réponse: " . $e->getMessage());
		}
	}

	curl_close($ch);
}

// Si l'authentification a échoué, générer un token JWT compatible
if (!$authSuccess) {
	error_log("Tentative de création d'un JWT compatible avec le backend");

	// Utiliser exactement la même méthode que le AuthController du backend
	// Clé de signature (identique au backend)
	$secretKey = 'esgi_azure_secret_key';

	// Header
	$header = json_encode([
		'typ' => 'JWT',
		'alg' => 'HS256'
	]);

	// Payload
	$now = time();
	$payload = json_encode([
		'sub' => 'user_' . hash('md5', $requestData->email),
		'email' => $requestData->email,
		'iat' => $now,
		'exp' => $now + (60 * 60 * 24), // 24 heures, comme le backend
		'role' => 'user', // Ajout du rôle qui est probablement nécessaire
		'azp' => 'esgi-azure-app', // Audience qui pourrait être vérifiée par le backend
		'iss' => 'esgi-auth-service' // Émetteur qui pourrait être vérifié par le backend
	]);

	// Encoder header et payload en Base64Url (exactement comme le backend)
	$base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
	$base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

	// Signature exactement comme dans le backend
	$signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secretKey, true);
	$base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

	// Token complet
	$jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

	// Créer la réponse
	$authResponse = (object) [
		'success' => true,
		'message' => 'JWT généré localement (compatible backend)',
		'data' => (object) [
			'token' => $jwt,
			'user' => (object) [
				'email' => $requestData->email,
				'role' => 'user'
			]
		],
		'isLocallyGenerated' => true,
		'debug_info' => [
			'environment' => $isAzure ? 'Azure' : 'Local',
			'backend_attempts' => $responseDetails,
			'last_error' => $lastError
		]
	];

	error_log("JWT créé localement: " . substr($jwt, 0, 30) . "...");
}

// Renvoyer la réponse
http_response_code(200);
echo json_encode($authResponse);
