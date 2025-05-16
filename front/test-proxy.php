<?php
// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set headers
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// Test response
echo json_encode([
	'status' => 'ok',
	'message' => 'Test proxy is working',
	'timestamp' => date('Y-m-d H:i:s'),
	'server' => $_SERVER['SERVER_SOFTWARE'],
	'php_version' => PHP_VERSION,
	'request_method' => $_SERVER['REQUEST_METHOD'],
	'request_uri' => $_SERVER['REQUEST_URI'],
	'query_string' => $_SERVER['QUERY_STRING'] ?? 'none'
]);
