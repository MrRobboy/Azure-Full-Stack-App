<?php

/**
 * Direct Auth - Solution spécialisée pour l'authentification
 * 
 * Ce fichier est conçu spécifiquement pour l'authentification et résout
 * les problèmes spécifiques à ce processus
 */

// Configuration des erreurs et de la journalisation
error_reporting(E_ALL);
ini_set('display_errors', 0); // Désactivé en production
ini_set('log_errors', 1);

// Création du répertoire de logs si nécessaire
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
	mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/direct-auth.log');

// Journalisation de base pour le débogage
error_log("Direct Auth accessed: " . $_SERVER['REQUEST_URI']);
error_log("Method: " . $_SERVER['REQUEST_METHOD']);

// Configuration de base
$apiBaseUrl = 'https://app-backend-esgi-app.azurewebsites.net';
$authEndpoint = 'api-auth-login.php';

// Déterminer l'origine
$allowedOrigins = [
	'https://app-frontend-esgi-app.azurewebsites.net',
	'http://localhost:3000',
	'http://localhost'
];
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
if ($origin !== '*' && !in_array($origin, $allowedOrigins)) {
	$origin = $allowedOrigins[0];
}

// Définir les en-têtes CORS
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json');

// Traiter les requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode([
		'success' => false,
		'error' => [
			'code' => 'METHOD_NOT_ALLOWED',
			'message' => 'Seule la méthode POST est autorisée pour l\'authentification'
		]
	]);
	exit;
}

// Récupérer les données de la requête
$input = file_get_contents('php://input');
$credentials = json_decode($input, true);

if (!$credentials || !isset($credentials['email']) || !isset($credentials['password'])) {
	http_response_code(400);
	echo json_encode([
		'success' => false,
		'error' => [
			'code' => 'INVALID_CREDENTIALS',
			'message' => 'Les identifiants de connexion sont manquants ou invalides'
		]
	]);
	exit;
}

// Masquer le mot de passe dans les logs
$logCredentials = $credentials;
$logCredentials['password'] = '********';
error_log("Tentative d'authentification avec: " . json_encode($logCredentials));

// Construction de l'URL d'authentification
$authUrl = $apiBaseUrl . '/' . $authEndpoint;

// Initialisation de cURL
$ch = curl_init($authUrl);

// Configuration des options cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
curl_setopt($ch, CURLOPT_ENCODING, '');

// En-têtes de la requête
$headers = [
	'Content-Type: application/json',
	'Accept: application/json',
	'X-Requested-With: XMLHttpRequest',
	'Origin: ' . $origin,
	'User-Agent: ESGI-App-Direct-Auth/1.0'
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Activation du stockage des cookies pour le suivi de session
curl_setopt($ch, CURLOPT_COOKIEFILE, '');
curl_setopt($ch, CURLOPT_COOKIEJAR, '');
curl_setopt($ch, CURLOPT_HEADER, true);

// Exécution de la requête
$response = curl_exec($ch);

// Gestion des erreurs cURL
if ($response === false) {
	$error = curl_error($ch);
	curl_close($ch);
	error_log("Erreur cURL lors de l'authentification: " . $error);
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'error' => [
			'code' => 'AUTH_REQUEST_FAILED',
			'message' => 'La requête d\'authentification a échoué: ' . $error
		]
	]);
	exit;
}

// Récupération des informations de la réponse
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

// Séparation des en-têtes et du corps
$headerText = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

error_log("Code de réponse d'authentification: " . $httpCode);
error_log("Type de contenu de la réponse: " . $contentType);

// Fermeture de cURL
curl_close($ch);

// Transmission des cookies de la réponse
$headers = explode("\r\n", $headerText);
foreach ($headers as $header) {
	if (strpos($header, 'Set-Cookie:') === 0) {
		error_log("Transmission du cookie: " . $header);
		header($header, false);
	}
}

// Vérification du format de la réponse
if (strpos($body, '<html') !== false) {
	error_log("Erreur: Réponse HTML reçue alors que JSON attendu pour l'authentification");
	error_log("Corps de la réponse HTML: " . substr($body, 0, 500) . "...");

	http_response_code(500);
	echo json_encode([
		'success' => false,
		'error' => [
			'code' => 'INVALID_AUTH_RESPONSE',
			'message' => 'Le serveur d\'authentification a renvoyé une page HTML au lieu de JSON',
			'debug_info' => 'Contacter l\'administrateur système'
		]
	]);
	exit;
}

// Vérification que la réponse est du JSON valide
$jsonData = json_decode($body);
if (json_last_error() !== JSON_ERROR_NONE) {
	error_log("Erreur: Réponse d'authentification non-JSON: " . json_last_error_msg());
	error_log("Corps de la réponse non-JSON: " . substr($body, 0, 500) . "...");

	// Si le corps est vide ou minuscule, c'est probablement une erreur de connexion
	if (strlen($body) < 10) {
		error_log("Réponse d'authentification vide ou très courte");

		// Créer une réponse de secours basée sur le code HTTP
		http_response_code($httpCode ?: 500);
		echo json_encode([
			'success' => false,
			'error' => [
				'code' => 'EMPTY_AUTH_RESPONSE',
				'message' => 'Le serveur d\'authentification a renvoyé une réponse vide ou incomplète',
				'http_code' => $httpCode
			]
		]);
	} else {
		// Tenter de renvoyer le corps tel quel, avec un avertissement
		http_response_code($httpCode ?: 500);
		echo json_encode([
			'success' => false,
			'error' => [
				'code' => 'INVALID_AUTH_RESPONSE',
				'message' => 'La réponse d\'authentification n\'est pas au format JSON valide: ' . json_last_error_msg(),
				'raw_response_preview' => substr($body, 0, 100) . '...'
			]
		]);
	}
	exit;
}

// Si nous arrivons ici, la réponse est du JSON valide
http_response_code($httpCode);
echo $body;
