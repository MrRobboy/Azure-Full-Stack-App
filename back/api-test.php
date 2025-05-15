<?php
// Simple API test endpoint to troubleshoot routing

// Set CORS headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://app-frontend-esgi-app.azurewebsites.net');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Log access
error_log("API test endpoint accessed: " . date('Y-m-d H:i:s'));
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request URI: " . $_SERVER['REQUEST_URI']);

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// Get any parameters passed to the script
$params = $_GET;
$input = file_get_contents('php://input');
$jsonInput = null;

if (!empty($input)) {
	$jsonInput = json_decode($input, true);
}

// Return a test response with info about the request
echo json_encode([
	'success' => true,
	'message' => 'API test endpoint responding',
	'timestamp' => date('Y-m-d H:i:s'),
	'path' => $_SERVER['REQUEST_URI'],
	'method' => $_SERVER['REQUEST_METHOD'],
	'params' => $params,
	'input' => $jsonInput,
	'headers' => getallheaders(),
	'server_info' => [
		'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
		'host' => $_SERVER['HTTP_HOST'] ?? 'Unknown',
		'script' => $_SERVER['SCRIPT_NAME'] ?? 'Unknown',
	]
]);
