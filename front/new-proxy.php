<?php

/**
 * New Proxy - Solution optimisée pour Azure
 * 
 * Ce proxy combine les éléments essentiels pour une communication
 * efficace entre le frontend et le backend
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
ini_set('error_log', $logDir . '/new-proxy.log');

// Journalisation de base pour le débogage
error_log("Proxy accessed: " . $_SERVER['REQUEST_URI']);
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Query string: " . $_SERVER['QUERY_STRING']);

// Configuration de base
$apiBaseUrl = 'https://app-backend-esgi-app.azurewebsites.net';
$allowedOrigins = [
	'https://app-frontend-esgi-app.azurewebsites.net',
	'http://localhost:3000', // Pour le développement local
	'http://localhost'
];

// Déterminer l'origine
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
if ($origin !== '*' && !in_array($origin, $allowedOrigins)) {
	$origin = $allowedOrigins[0]; // Fallback à l'origine principale
}

// Définir les en-têtes CORS - CRITIQUE pour le bon fonctionnement
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

// En-têtes de sécurité
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Traiter les requêtes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

// Définir le type de contenu pour les réponses non-OPTIONS
header('Content-Type: application/json');

// Obtenir et valider l'endpoint
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : null;
if (!$endpoint) {
	sendErrorResponse(400, 'INVALID_ENDPOINT', 'Endpoint manquant ou invalide');
}

// Construction de l'URL de l'API
$apiUrl = buildApiUrl($apiBaseUrl, $endpoint, $_SERVER['QUERY_STRING']);

// Initialisation de cURL
$ch = curl_init($apiUrl);

error_log("Forwarding request to: " . $apiUrl);

// Configuration des options cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

// Transmission des en-têtes de la requête originale
$headers = buildRequestHeaders();
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Transmission des cookies
$cookieString = buildCookieString();
if (!empty($cookieString)) {
	curl_setopt($ch, CURLOPT_COOKIE, $cookieString);
}

// Transmission du corps de la requête pour POST, PUT, PATCH
if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH'])) {
	$input = file_get_contents('php://input');
	error_log("Request body: " . $input);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
}

// Activation du stockage des cookies pour le suivi de session
curl_setopt($ch, CURLOPT_COOKIEFILE, '');
curl_setopt($ch, CURLOPT_COOKIEJAR, '');

// Récupérer les informations d'en-tête pour transmettre les cookies
curl_setopt($ch, CURLOPT_HEADER, true);

// Exécution de la requête
$response = curl_exec($ch);

// Gestion des erreurs cURL
if ($response === false) {
	$error = curl_error($ch);
	curl_close($ch);
	error_log("cURL error: " . $error);
	sendErrorResponse(500, 'PROXY_ERROR', 'Erreur de proxy: ' . $error);
}

// Récupération de la taille de l'en-tête et du code HTTP
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Séparation des en-têtes et du corps
$headerText = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

error_log("Response code: " . $httpCode);

// Transmission des cookies de la réponse
$headers = explode("\r\n", $headerText);
foreach ($headers as $header) {
	// Transmettre uniquement les en-têtes Set-Cookie
	if (strpos($header, 'Set-Cookie:') === 0) {
		error_log("Forwarding cookie: " . $header);
		header($header, false);
	}
}

// Fermeture de cURL
curl_close($ch);

// Définition du code de réponse HTTP
http_response_code($httpCode);

// Envoi du corps de la réponse
echo $body;

// Fonctions utilitaires

/**
 * Construit l'URL de l'API en fonction de l'endpoint et des paramètres
 */
function buildApiUrl($baseUrl, $endpoint, $queryString)
{
	$url = $baseUrl;

	// Si l'endpoint est une URL complète, l'utiliser directement
	if (strpos($endpoint, 'http') === 0) {
		$url = $endpoint;
	} else {
		// Sinon, l'ajouter à l'URL de base
		if (!empty($endpoint)) {
			if ($endpoint[0] !== '/' && substr($baseUrl, -1) !== '/') {
				$url .= '/';
			}
			$url .= $endpoint;
		}
	}

	// Ajouter la chaîne de requête si présente et non incluse dans l'endpoint
	if (!empty($queryString)) {
		$query = $queryString;
		// Supprimer le paramètre endpoint
		$query = preg_replace('/(&|\?)endpoint=[^&]*/', '', $query);
		if (!empty($query)) {
			$url .= (strpos($url, '?') === false ? '?' : '&') . $query;
		}
	}

	return $url;
}

/**
 * Construit les en-têtes de la requête à transmettre
 */
function buildRequestHeaders()
{
	$headers = [];
	$requestHeaders = getallheaders();

	foreach ($requestHeaders as $name => $value) {
		// Ignorer l'en-tête host pour éviter les conflits
		if (strtolower($name) !== 'host') {
			$headers[] = $name . ': ' . $value;
		}
	}

	// Ajouter des en-têtes supplémentaires pour la communication avec l'API
	$headers[] = 'X-Proxy-Forward: true';
	$headers[] = 'User-Agent: ESGI-App-New-Proxy/1.0';

	return $headers;
}

/**
 * Construit la chaîne de cookie à partir des cookies de la requête
 */
function buildCookieString()
{
	$cookieString = '';
	foreach ($_COOKIE as $name => $value) {
		$cookieString .= $name . '=' . $value . '; ';
	}
	return $cookieString;
}

/**
 * Envoie une réponse d'erreur au format JSON
 */
function sendErrorResponse($statusCode, $errorCode, $errorMessage)
{
	http_response_code($statusCode);
	echo json_encode([
		'success' => false,
		'error' => [
			'code' => $errorCode,
			'message' => $errorMessage
		]
	]);
	exit;
}
