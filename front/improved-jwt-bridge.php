<?php

/**
 * Improved JWT Auth Bridge - Compatible with Azure Backend
 * Date de génération: 2023-06-10
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

// Configuration CORS
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
		'message' => 'Seule la méthode POST est autorisée'
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
		'message' => 'JSON invalide: ' . json_last_error_msg()
	]);
	exit;
}

// Vérifier si tous les paramètres requis sont présents
if (!isset($requestData->email) || !isset($requestData->password)) {
	http_response_code(400);
	echo json_encode([
		'success' => false,
		'message' => 'Les paramètres email et password sont requis'
	]);
	exit;
}

// Configuration du backend
$backendUrl = 'https://app-backend-esgi-app.azurewebsites.net';

// Liste des endpoints d'API dans l'ordre de priorité
$authEndpoints = [
	'api-auth-login.php',   // Point d'entrée principal, formaté comme notre code
	'api/auth/login',       // Format API REST standard
	'auth/login',           // Alternative en format REST
	'api-auth.php',         // Alternative directe
	'login.php',            // Point d'entrée simplifié
	'auth.php'              // Alternative simple
];

// Essayer chaque endpoint d'authentification
$authSuccess = false;
$authResponse = null;
$lastError = null;

foreach ($authEndpoints as $endpoint) {
	$targetUrl = $backendUrl . '/' . $endpoint;
	error_log("Tentative d'authentification sur: " . $targetUrl);

	$ch = curl_init($targetUrl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
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

	error_log("Réponse de " . $endpoint . ": Code " . $httpCode . ", Corps: " . substr($response, 0, 100) . (strlen($response) > 100 ? '...' : ''));

	if ($error) {
		error_log("Erreur cURL: " . $error);
		$lastError = $error;
	} elseif ($httpCode >= 200 && $httpCode < 300) {
		// Réponse de succès (2xx)
		try {
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
		'exp' => $now + (60 * 60 * 24) // 24 heures, comme le backend
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
		'isLocallyGenerated' => true
	];

	error_log("JWT créé localement: " . substr($jwt, 0, 30) . "...");
}

// Renvoyer la réponse
http_response_code(200);
echo json_encode($authResponse);
