<?php

/**
 * Special Matieres Proxy
 * This is a simplified proxy specifically for retrieving subject (matiere) data
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit();
}

// Get the endpoint from the query string
$endpoint = $_GET['endpoint'] ?? '';
if (empty($endpoint)) {
	http_response_code(400);
	echo json_encode(['error' => 'No endpoint specified']);
	exit();
}

// Base URL for the backend API
$baseUrl = 'https://app-backend-esgi-app.azurewebsites.net';

// Construct the full URL
$url = $baseUrl . '/' . $endpoint;

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

// Forward headers
$headers = getallheaders();
$forwardHeaders = [];
foreach ($headers as $key => $value) {
	if (strtolower($key) !== 'host' && strtolower($key) !== 'content-length') {
		$forwardHeaders[] = "$key: $value";
	}
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $forwardHeaders);

// Forward request body for POST, PUT, etc.
if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
	$input = file_get_contents('php://input');
	curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
}

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for cURL errors
if (curl_errno($ch)) {
	// If the request fails, return fallback data for matieres
	http_response_code(200);
	echo json_encode([
		'success' => true,
		'data' => [
			['id' => 1, 'nom' => 'Mathématiques'],
			['id' => 2, 'nom' => 'Français'],
			['id' => 3, 'nom' => 'Anglais'],
			['id' => 4, 'nom' => 'Histoire']
		],
		'isFallback' => true
	]);
	exit();
}

// Close cURL
curl_close($ch);

// Set the response code
http_response_code($httpCode);

// Forward the response
echo $response;
