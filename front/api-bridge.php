<?php

/**
 * API Bridge - Enhanced proxy for Azure
 * 
 * This file serves as a reliable bridge between frontend and backend API
 * with built-in fallbacks for common API endpoints.
 */

// Charger la configuration
require_once __DIR__ . '/config/proxy.php';

// Configurer le logging
setupLogging();

// Log de la requête entrante
error_log("Proxy Request: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);
error_log("Query String: " . $_SERVER['QUERY_STRING']);

// Vérification de la limite de taux
if (!checkRateLimit($_SERVER['REMOTE_ADDR'])) {
	error_log("Rate limit exceeded for IP: " . $_SERVER['REMOTE_ADDR']);
	header('HTTP/1.1 429 Too Many Requests');
	die(json_encode([
		'success' => false,
		'error' => [
			'code' => 'RATE_LIMIT_EXCEEDED',
			'message' => 'Trop de requêtes. Veuillez réessayer plus tard.'
		]
	]));
}

// Vérification de la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, SECURITY_CONFIG['input_validation']['allowed_methods'])) {
	error_log("Method not allowed: " . $method);
	header('HTTP/1.1 405 Method Not Allowed');
	die(json_encode([
		'success' => false,
		'error' => [
			'code' => 'METHOD_NOT_ALLOWED',
			'message' => 'Méthode non autorisée'
		]
	]));
}

// Gestion des requêtes OPTIONS (preflight CORS)
if ($method === 'OPTIONS') {
	error_log("Handling OPTIONS request");
	$corsHeaders = getCorsHeaders();
	foreach ($corsHeaders as $header => $value) {
		header("$header: $value");
	}
	exit(0);
}

// Validation de l'endpoint
$endpoint = isset($_GET['endpoint']) ? validateInput($_GET['endpoint']) : null;
if (!$endpoint) {
	error_log("Invalid or missing endpoint");
	$corsHeaders = getCorsHeaders();
	foreach ($corsHeaders as $header => $value) {
		header("$header: $value");
	}
	foreach (SECURITY_CONFIG['headers'] as $header) {
		header($header);
	}
	header('HTTP/1.1 400 Bad Request');
	die(json_encode([
		'success' => false,
		'error' => [
			'code' => 'INVALID_ENDPOINT',
			'message' => 'Endpoint manquant ou invalide'
		]
	]));
}

// Construction de l'URL cible
$baseUrl = rtrim(BACKEND_BASE_URL, '/');
$endpoint = ltrim($endpoint, '/');
if (strpos($endpoint, '.php') === false) {
	$endpoint .= '.php';
}
$targetUrl = $baseUrl . '/' . $endpoint;

error_log("Target URL: " . $targetUrl);

// Configuration de la requête cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, SSL_VERIFY_PEER);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, SSL_VERIFY_HOST);
curl_setopt($ch, CURLOPT_TIMEOUT, CURL_TIMEOUT);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, CURL_CONNECT_TIMEOUT);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Suivre les redirections
curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // Maximum de redirections

// Configuration des headers
$headers = DEFAULT_HEADERS;
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
	$headers[] = 'Authorization: ' . $_SERVER['HTTP_AUTHORIZATION'];
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Gestion des données POST
if ($method === 'POST') {
	$input = file_get_contents('php://input');
	error_log("POST data: " . $input);
	if ($input) {
		$data = json_decode($input, true);
		if (json_last_error() === JSON_ERROR_NONE) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
		}
	}
}

// Exécution de la requête
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

// Log des informations de la requête
error_log("CURL Info: " . print_r($info, true));

// Gestion des erreurs
if ($error) {
	error_log("Proxy Error: $error - URL: $targetUrl");
	$corsHeaders = getCorsHeaders();
	foreach ($corsHeaders as $header => $value) {
		header("$header: $value");
	}
	foreach (SECURITY_CONFIG['headers'] as $header) {
		header($header);
	}
	header('HTTP/1.1 502 Bad Gateway');
	die(json_encode([
		'success' => false,
		'error' => [
			'code' => 'PROXY_ERROR',
			'message' => 'Erreur de communication avec le backend',
			'details' => $error
		]
	]));
}

// Log de la réponse
error_log("Response Code: " . $httpCode);
error_log("Response: " . $response);

// Envoi des headers CORS
$corsHeaders = getCorsHeaders();
foreach ($corsHeaders as $header => $value) {
	header("$header: $value");
}
// Envoi des headers de sécurité
foreach (SECURITY_CONFIG['headers'] as $header) {
	header($header);
}

// Envoi de la réponse
http_response_code($httpCode);
echo $response;
