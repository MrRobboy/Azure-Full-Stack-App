<?php
// Simple file to test CORS configuration
error_log("CORS test script accessed - " . date('Y-m-d H:i:s'));
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Headers: " . json_encode(getallheaders()));

// Always set CORS headers, for all request types
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://app-frontend-esgi-app.azurewebsites.net');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');  // Cache preflight for 24 hours

// Handle preflight OPTIONS request specifically
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	error_log("Responding to OPTIONS preflight request");
	http_response_code(200);
	exit;
}

// For other requests, return a simple response with debugging info
$response = [
	'success' => true,
	'message' => 'CORS test successful',
	'timestamp' => date('Y-m-d H:i:s'),
	'method' => $_SERVER['REQUEST_METHOD'],
	'headers' => getallheaders(),
	'remote_addr' => $_SERVER['REMOTE_ADDR'],
	'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
	'php_version' => phpversion()
];

error_log("Responding with data: " . json_encode($response));
echo json_encode($response);
