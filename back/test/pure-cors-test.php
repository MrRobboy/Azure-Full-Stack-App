<?php

/**
 * Pure CORS Test
 * 
 * Ce script utilise directement les en-têtes PHP pour gérer CORS,
 * sans dépendre de web.config ou de règles de serveur.
 */

// Set CORS headers
$allowedOrigins = [
	'https://app-frontend-esgi-app.azurewebsites.net',
	'http://localhost',
	'http://localhost:727',
	'http://127.0.0.1',
	'http://127.0.0.1:727'
];

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if (in_array($origin, $allowedOrigins)) {
	header("Access-Control-Allow-Origin: $origin");
} else {
	// Default to the main frontend
	header("Access-Control-Allow-Origin: https://app-frontend-esgi-app.azurewebsites.net");
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 86400"); // 24 hours cache for preflight

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit();
}

// Set content type
header('Content-Type: application/json');

// Return JSON response
echo json_encode([
	'success' => true,
	'message' => 'Pure CORS test succeeded',
	'timestamp' => date('Y-m-d H:i:s'),
	'method' => $_SERVER['REQUEST_METHOD'],
	'origin' => $origin,
	'headers_sent' => headers_list(),
	'request_headers' => getallheaders()
]);
