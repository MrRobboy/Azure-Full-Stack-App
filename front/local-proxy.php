<?php

/**
 * Simple Proxy PHP minimal 
 * Version minimale pour essayer de résoudre les problèmes de connexion
 */

// Activer les logs d'erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'proxy_errors.log');

// Récupérer l'endpoint demandé
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
if (empty($endpoint)) {
	http_response_code(400);
	echo json_encode(['error' => 'Endpoint non spécifié']);
	exit;
}

// URL backend de base
$backend_url = 'https://app-backend-esgi-app.azurewebsites.net';

// Construire l'URL complète
$url = $backend_url;
if (strpos($endpoint, 'http') === 0) {
	// Endpoint complet
	$url = $endpoint;
} else {
	// Endpoint relatif
	if ($endpoint[0] !== '/' && substr($backend_url, -1) !== '/') {
		$url .= '/';
	}
	$url .= $endpoint;
}

// Récupérer la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Log des informations de requête
error_log("Proxying request to: $url");
error_log("Method: $method");
error_log("Query: " . $_SERVER['QUERY_STRING']);

// Initialiser cURL
$ch = curl_init($url);

// Configurer les options de base
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Copier les entêtes
$headers = [];
$requestHeaders = getallheaders();
foreach ($requestHeaders as $name => $value) {
	if (strtolower($name) !== 'host') {
		$headers[] = "$name: $value";
	}
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Pour POST, PUT ou PATCH, copier le body
if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
	$input = file_get_contents("php://input");
	error_log("Request body: $input");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
}

// Récupérer les headers dans la réponse
curl_setopt($ch, CURLOPT_HEADER, true);

// Exécuter la requête
$response = curl_exec($ch);

// Vérifier les erreurs
if ($response === false) {
	$error = curl_error($ch);
	error_log("cURL error: $error");
	http_response_code(500);
	echo json_encode(['error' => "Proxy error: $error"]);
	curl_close($ch);
	exit;
}

// Traiter la réponse
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$body = substr($response, $headerSize);
$headers = substr($response, 0, $headerSize);

// Fermer la connexion cURL
curl_close($ch);

// Envoyer le code de statut
http_response_code($statusCode);

// Ajouter les headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Traiter les headers de la réponse
foreach (explode("\r\n", $headers) as $header) {
	if (
		strpos($header, 'Content-Type:') === 0 ||
		strpos($header, 'Content-Length:') === 0 ||
		strpos($header, 'Set-Cookie:') === 0
	) {
		header($header);
	}
}

// Envoyer le body
echo $body;
