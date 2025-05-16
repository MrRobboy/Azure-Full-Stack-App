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

// Vérification de la limite de taux
if (!checkRateLimit($_SERVER['REMOTE_ADDR'])) {
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
	$corsHeaders = getCorsHeaders();
	foreach ($corsHeaders as $header => $value) {
		header("$header: $value");
	}
	exit(0);
}

// Validation de l'endpoint
$endpoint = isset($_GET['endpoint']) ? validateInput($_GET['endpoint']) : null;
if (!$endpoint) {
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
$targetUrl = rtrim(BACKEND_BASE_URL, '/') . '/' . ltrim($endpoint, '/');

// Configuration de la requête cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, SSL_VERIFY_PEER);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, SSL_VERIFY_HOST);
curl_setopt($ch, CURLOPT_TIMEOUT, CURL_TIMEOUT);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, CURL_CONNECT_TIMEOUT);

// Configuration des headers
$headers = DEFAULT_HEADERS;
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
	$headers[] = 'Authorization: ' . $_SERVER['HTTP_AUTHORIZATION'];
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Gestion des données POST
if ($method === 'POST') {
	$input = file_get_contents('php://input');
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
curl_close($ch);

// Gestion des erreurs
if ($error) {
	error_log("Proxy Error: $error - URL: $targetUrl");
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
