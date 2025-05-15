<?php
// Simple file to test CORS configuration

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	header('Content-Type: application/json');
	header('Access-Control-Allow-Origin: https://app-frontend-esgi-app.azurewebsites.net');
	header('Access-Control-Allow-Credentials: true');
	header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
	header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
	header('Access-Control-Max-Age: 86400');  // Cache for 24 hours
	http_response_code(200);
	exit;
}

// For normal requests
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://app-frontend-esgi-app.azurewebsites.net');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Return a simple response
echo json_encode([
	'success' => true,
	'message' => 'CORS test successful',
	'timestamp' => date('Y-m-d H:i:s'),
	'method' => $_SERVER['REQUEST_METHOD'],
	'headers' => getallheaders(),
	'remote_addr' => $_SERVER['REMOTE_ADDR']
]);
