<?php

/**
 * Unified CORS Proxy
 * Ce proxy gère toutes les communications entre le frontend et le backend 
 * en résolvant les problèmes de CORS sur Azure.
 * 
 * Version: 1.0.4
 * 
 * Changements dans la v1.0.4:
 * - Utilisation d'endpoints API directs pour les ressources principales
 * - Redirection vers api-matieres.php, api-classes.php, etc. au lieu de /api/*
 * - Amélioration des logs pour un meilleur diagnostic
 */

// Configuration proxy
define('API_BASE_URL', 'https://app-backend-esgi-app.azurewebsites.net');
define('AUTH_ENDPOINT', '/api-auth-login.php');  // Endpoint d'authentification spécial
define('USER_ENDPOINT', '/api-users.php');       // Endpoint pour les infos utilisateur
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
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Request-ID');
header('Access-Control-Allow-Credentials: true');

// Fonction de journalisation améliorée
function logMessage($message, $data = null)
{
	if (!DEBUG) return;

	$logFile = LOG_DIR . '/proxy-' . date('Y-m-d') . '.log';
	$timestamp = date('[Y-m-d H:i:s]');
	$requestId = isset($_SERVER['HTTP_X_REQUEST_ID']) ? $_SERVER['HTTP_X_REQUEST_ID'] : 'NO_ID';
	$logMessage = $timestamp . ' [' . $requestId . '] ' . $message;

	if ($data !== null) {
		// Masquer les informations sensibles
		if (is_array($data)) {
			$sensitiveFields = ['password', 'token', 'authorization'];
			foreach ($sensitiveFields as $field) {
				if (isset($data[$field])) {
					$data[$field] = '********';
				}
			}
		}

		// Tronquer les réponses très longues
		if (is_string($data) && strlen($data) > 1000) {
			$data = substr($data, 0, 1000) . '... [tronqué]';
		} elseif (isset($data['response']) && is_string($data['response']) && strlen($data['response']) > 1000) {
			$data['response'] = substr($data['response'], 0, 1000) . '... [tronqué]';
		}

		$logMessage .= ' ' . json_encode($data, JSON_UNESCAPED_UNICODE);
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

// Mapping des endpoints
$endpointMapping = [
	'auth/login' => AUTH_ENDPOINT,
	'auth/test-credentials' => AUTH_ENDPOINT . '?action=test-credentials',
	'user/profile' => USER_ENDPOINT,
	'status' => STATUS_ENDPOINT,
	'matieres' => '/api-matieres.php',
	'classes' => '/api-classes.php',
	'examens' => '/api-examens.php',
	'profs' => '/api-profs.php',
	'notes' => '/api-notes.php',
	'privileges' => '/api-privileges.php',
	'users' => '/api-users.php'
];

// Déterminer l'URL finale
foreach ($endpointMapping as $pattern => $target) {
	if ($endpoint === $pattern || strpos($endpoint, $pattern . '/') === 0) {
		$requestUrl .= $target;
		logMessage('Redirection vers endpoint spécifique', ['pattern' => $pattern, 'target' => $target]);
		break;
	}
}

// Si aucun mapping n'a été trouvé, utiliser la construction standard
if ($requestUrl === API_BASE_URL) {
	$requestUrl .= '/api/' . $endpoint;
	logMessage('Redirection vers endpoint API standard', ['endpoint' => $endpoint]);
}

// Log de la requête
logMessage('Requête entrante', [
	'method' => $_SERVER['REQUEST_METHOD'],
	'endpoint' => $endpoint,
	'url' => $requestUrl
]);

// Configuration de cURL
$ch = curl_init($requestUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Headers à transmettre
$headers = [
	'Content-Type: application/json',
	'Accept: application/json',
	'X-Requested-With: XMLHttpRequest'
];

// Transférer l'en-tête d'autorisation si présent
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
	$headers[] = 'Authorization: ' . $_SERVER['HTTP_AUTHORIZATION'];
}

// Transférer l'ID de requête si présent
if (isset($_SERVER['HTTP_X_REQUEST_ID'])) {
	$headers[] = 'X-Request-ID: ' . $_SERVER['HTTP_X_REQUEST_ID'];
}

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Configuration selon la méthode HTTP
switch ($_SERVER['REQUEST_METHOD']) {
	case 'GET':
		$queryParams = $_GET;
		unset($queryParams['endpoint']);
		if (!empty($queryParams)) {
			$queryString = http_build_query($queryParams);
			$requestUrl .= (strpos($requestUrl, '?') === false ? '?' : '&') . $queryString;
			curl_setopt($ch, CURLOPT_URL, $requestUrl);
		}
		break;

	case 'POST':
	case 'PUT':
	case 'DELETE':
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);
		$inputData = file_get_contents('php://input');
		if (!empty($inputData)) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $inputData);
			logMessage('Données envoyées', json_decode($inputData, true));
		}
		break;

	default:
		http_response_code(405);
		echo json_encode([
			'success' => false,
			'message' => 'Méthode non autorisée: ' . $_SERVER['REQUEST_METHOD']
		]);
		curl_close($ch);
		exit;
}

// Exécution de la requête
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

// Log de la réponse
logMessage('Réponse reçue', [
	'status' => $httpCode,
	'content_type' => $contentType,
	'response_size' => strlen($response)
]);

// Gestion des erreurs
if ($response === false) {
	$error = curl_error($ch);
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => 'Erreur de communication avec le backend',
		'error' => $error
	]);
	logMessage('Erreur cURL', ['error' => $error]);
} else {
	// Transmettre le code HTTP
	http_response_code($httpCode);

	// Transmettre la réponse
	echo $response;
}

curl_close($ch);
