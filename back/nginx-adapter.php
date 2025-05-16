<?php
// Nginx Adapter for API Routing
// This file serves as an adapter to properly route all API requests to the correct handlers

// Enable error reporting for troubleshooting
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/api_errors.log');

// CORS Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://app-frontend-esgi-app.azurewebsites.net');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

// Log request details
error_log(sprintf(
	"[%s] API Request: Method=%s, URI=%s, IP=%s",
	date('Y-m-d H:i:s'),
	$_SERVER['REQUEST_METHOD'],
	$_SERVER['REQUEST_URI'],
	$_SERVER['REMOTE_ADDR']
));

// Extract API path
$request_uri = $_SERVER['REQUEST_URI'];

// Clean up URI
$uri = parse_url($request_uri, PHP_URL_PATH);
$uri = rtrim($uri, '/');

// Check if this is an API request
if (strpos($uri, '/api/') === 0) {
	// This is an API request, remove the /api/ prefix
	$api_path = substr($uri, 5); // Remove "/api/"

	// Add the path to $_GET for the API router to use
	$_GET['path'] = $api_path;

	// Include the routes/api.php file to handle the request
	require_once __DIR__ . '/routes/api.php';
	exit;
}

// If not an API request, include the front controller (index.php)
require_once __DIR__ . '/index.php';
