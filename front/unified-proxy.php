<?php

/**
 * Unified CORS Proxy
 * Ce proxy gère toutes les communications entre le frontend et le backend 
 * en résolvant les problèmes de CORS sur Azure.
 * 
 * Version: 1.0.3
 */

// Configuration proxy
define('API_BASE_URL', 'https://app-backend-esgi-app.azurewebsites.net');
define('AUTH_ENDPOINT', '/api-auth-login.php');  // Endpoint d'authentification spécial
define('USER_ENDPOINT', '/api/auth/user');       // Endpoint pour les infos utilisateur
define('STATUS_ENDPOINT', '/status.php');        // Endpoint de statut
define('LOG_DIR', __DIR__ . '/logs');            // Répertoire des logs
define('DEBUG', true);                           // Mode debug
define('ENABLE_MOCK_DATA', false);                // Désactivation des données simulées

// Création du répertoire de logs si nécessaire
if (!is_dir(LOG_DIR)) {
	mkdir(LOG_DIR, 0755, true);
}

// Initialisation des headers de réponse
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Fonction de journalisation
function logMessage($message, $data = null)
{
	if (!DEBUG) return;

	$logFile = LOG_DIR . '/proxy-' . date('Y-m-d') . '.log';
	$timestamp = date('[Y-m-d H:i:s]');
	$logMessage = $timestamp . ' ' . $message;

	if ($data !== null) {
		// Masquer les mots de passe dans les données de log
		if (is_array($data) && isset($data['password'])) {
			$data['password'] = '********';
		}
		$logMessage .= ' ' . json_encode($data);
	}

	file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
}

// Gestion des requêtes OPTIONS (pré-vol CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	logMessage('Requête OPTIONS traitée');
	exit;
}

// Vérification de la présence du paramètre endpoint
if (!isset($_GET['endpoint'])) {
	http_response_code(400);
	echo json_encode([
		'success' => false,
		'message' => 'Paramètre endpoint manquant'
	]);
	logMessage('Erreur: Paramètre endpoint manquant');
	exit;
}

// Construction de l'URL de l'API
$endpoint = trim($_GET['endpoint'], '/');
$requestUrl = API_BASE_URL;

// Traitement spécial pour les endpoints d'authentification
if (strpos($endpoint, 'auth/login') !== false) {
	$requestUrl .= AUTH_ENDPOINT;
	logMessage('Redirection vers endpoint d\'authentification spécial', ['endpoint' => AUTH_ENDPOINT]);
} elseif (strpos($endpoint, 'auth/user') !== false || $endpoint === 'user/profile') {
	$requestUrl .= USER_ENDPOINT;
	logMessage('Redirection vers endpoint utilisateur', ['endpoint' => USER_ENDPOINT]);
} elseif ($endpoint === 'status') {
	$requestUrl .= STATUS_ENDPOINT;
	logMessage('Redirection vers endpoint de statut', ['endpoint' => STATUS_ENDPOINT]);
} else {
	// Pour tous les autres endpoints, toujours préfixer avec /api/ 
	// Cette partie a été modifiée - on ne fait plus la distinction entre les endpoints principaux et les autres
	// car tous doivent passer par /api/ sur le backend
	$requestUrl .= '/api/' . $endpoint;
	logMessage('Redirection vers endpoint API standard', ['url' => $requestUrl, 'endpoint' => $endpoint]);
}

// Ajout de logs détaillés sur la construction de l'URL
logMessage('URL finale construite', [
	'endpoint_original' => $_GET['endpoint'],
	'endpoint_processed' => $endpoint,
	'base_url' => API_BASE_URL,
	'request_url' => $requestUrl,
	'method' => $_SERVER['REQUEST_METHOD']
]);

logMessage('Proxying request', [
	'method' => $_SERVER['REQUEST_METHOD'],
	'url' => $requestUrl,
	'endpoint' => $endpoint
]);

// Initialisation de cURL
$ch = curl_init($requestUrl);

// Configuration générale de cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Désactivé pour le développement
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Désactivé pour le développement
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Préparation des en-têtes à transmettre
$headers = [];
$headers[] = 'Content-Type: application/json';
$headers[] = 'Accept: application/json';
$headers[] = 'X-Requested-With: XMLHttpRequest';

// Transférer l'en-tête d'autorisation s'il existe
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
	$headers[] = 'Authorization: ' . $_SERVER['HTTP_AUTHORIZATION'];
	logMessage('Transfert du header Authorization');
}

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Configuration spécifique selon la méthode HTTP
switch ($_SERVER['REQUEST_METHOD']) {
	case 'GET':
		// Transmission des paramètres GET s'il y en a (sauf endpoint)
		$queryParams = $_GET;
		unset($queryParams['endpoint']);

		if (!empty($queryParams)) {
			$queryString = http_build_query($queryParams);
			$separator = (strpos($requestUrl, '?') === false) ? '?' : '&';
			$requestUrl .= $separator . $queryString;
			curl_setopt($ch, CURLOPT_URL, $requestUrl);
			logMessage('Paramètres GET transmis', $queryParams);
		}
		break;

	case 'POST':
		curl_setopt($ch, CURLOPT_POST, true);
		$inputData = file_get_contents('php://input');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $inputData);
		logMessage('POST data transmise', json_decode($inputData, true));
		break;

	case 'PUT':
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		$inputData = file_get_contents('php://input');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $inputData);
		logMessage('PUT data transmise', json_decode($inputData, true));
		break;

	case 'DELETE':
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		logMessage('DELETE request transmise');
		break;

	default:
		http_response_code(405);
		echo json_encode([
			'success' => false,
			'message' => 'Méthode non autorisée: ' . $_SERVER['REQUEST_METHOD']
		]);
		logMessage('Méthode non autorisée', ['method' => $_SERVER['REQUEST_METHOD']]);
		curl_close($ch);
		exit;
}

// Exécution de la requête
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

// Log de la réponse complète pour debug
logMessage('Réponse brute', [
	'status' => $httpCode,
	'content_type' => $contentType,
	'effective_url' => $effectiveUrl,
	'response_size' => strlen($response)
]);

// Ajout de logs détaillés sur la réponse HTTP
if ($httpCode >= 400) {
	logMessage('Erreur HTTP reçue', [
		'http_code' => $httpCode,
		'effective_url' => $effectiveUrl,
		'response' => substr($response, 0, 1000) // Limiter la taille pour éviter des logs trop grands
	]);

	// Ajout d'information de debug cURL
	$curlInfo = curl_getinfo($ch);
	logMessage('Information cURL détaillées', $curlInfo);
}

// Traitement des erreurs cURL
if ($response === false) {
	$error = curl_error($ch);
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => 'Erreur de communication avec l\'API',
		'error' => $error,
		'debug' => [
			'url' => $requestUrl,
			'method' => $_SERVER['REQUEST_METHOD']
		]
	]);
	logMessage('Erreur cURL', ['error' => $error, 'url' => $requestUrl]);
	curl_close($ch);
	exit;
}

// Fermeture de la connexion cURL
curl_close($ch);

// Transmission du code HTTP de la réponse
http_response_code($httpCode);

// Si c'est une requête d'authentification et qu'elle a réussi, on transmet la réponse telle quelle
if (strpos($endpoint, 'auth/login') !== false && $httpCode >= 200 && $httpCode < 300) {
	echo $response;
	logMessage('Réponse d\'authentification transmise', ['status' => $httpCode]);
	exit;
}

// Si c'est un endpoint de statut et qu'il y a une erreur 404, PAS de statut simulé, on transmet l'erreur
if ($endpoint === 'status' && $httpCode === 404) {
	logMessage('Erreur 404 sur l\'endpoint de statut - Transmission de l\'erreur réelle');
}

// Traitement général de la réponse
if (strpos($contentType, 'application/json') !== false) {
	// La réponse est déjà du JSON, on la transmet telle quelle
	echo $response;
	logMessage('Réponse JSON transmise', ['status' => $httpCode]);
} else {
	// La réponse n'est pas du JSON, on l'encapsule
	echo json_encode([
		'success' => $httpCode >= 200 && $httpCode < 300,
		'message' => 'Réponse non-JSON reçue du serveur',
		'status' => $httpCode,
		'raw_response' => $response,
		'url' => $effectiveUrl
	]);
	logMessage('Réponse non-JSON encapsulée', ['status' => $httpCode, 'url' => $effectiveUrl]);
}
