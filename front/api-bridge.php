<?php

/**
 * API Bridge - Last Resort Fallback for Azure Connectivity Issues
 * 
 * This script makes server-side requests to the backend API, bypassing
 * CORS limitations that affect browser-based requests.
 * 
 * It should only be used when all other proxy methods fail.
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'api_bridge_errors.log');

// Log access for debugging
$log_message = sprintf(
	"[%s] API Bridge accessed from %s - URI: %s, Method: %s",
	date('Y-m-d H:i:s'),
	$_SERVER['REMOTE_ADDR'],
	$_SERVER['REQUEST_URI'],
	$_SERVER['REQUEST_METHOD']
);
error_log($log_message);

// CORS headers to allow access from any origin
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE, PATCH');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// Set JSON content type for API responses
header('Content-Type: application/json');

// Configuration
$api_base_url = 'https://app-backend-esgi-app.azurewebsites.net';
$log_requests = true;

// Get endpoint from query string
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
$query_string = $_SERVER['QUERY_STRING'];

// Remove endpoint parameter from query string
$query_string = preg_replace('/(&|\?)endpoint=[^&]*/', '', $query_string);

// Check if endpoint is provided
if (empty($endpoint)) {
	http_response_code(400);
	echo json_encode([
		'success' => false,
		'message' => 'No endpoint specified',
		'debug' => $_GET
	]);
	exit;
}

// Log the request if enabled
if ($log_requests) {
	error_log(sprintf(
		"[%s] API Bridge request: Endpoint=%s, Method=%s",
		date('Y-m-d H:i:s'),
		$endpoint,
		$_SERVER['REQUEST_METHOD']
	));
}

// Construct API URL
$api_url = $api_base_url;
if (strpos($endpoint, 'http') === 0) {
	// If endpoint is a full URL, use that directly
	$api_url = $endpoint;
} else {
	// Append endpoint to base URL
	if (!empty($endpoint)) {
		if ($endpoint[0] !== '/' && substr($api_base_url, -1) !== '/') {
			$api_url .= '/';
		}
		$api_url .= $endpoint;
	}
}

// Add query string if present
if (!empty($query_string)) {
	$api_url .= (strpos($api_url, '?') === false ? '?' : '&') . $query_string;
}

// Log the constructed URL
if ($log_requests) {
	error_log("API Bridge forwarding to URL: " . $api_url);
}

// Set up cURL request
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Forward the request method
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

// Collect headers from the original request
$headers = [];
$request_headers = getallheaders();
foreach ($request_headers as $name => $value) {
	// Skip host header to avoid conflicts
	if (strtolower($name) !== 'host') {
		$headers[] = $name . ': ' . $value;
	}
}

// Add identifying headers
$headers[] = 'X-API-Bridge: true';
$headers[] = 'User-Agent: ESGI-App-Bridge/1.0';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Forward cookies
$cookie_string = '';
foreach ($_COOKIE as $name => $value) {
	$cookie_string .= $name . '=' . $value . '; ';
}
if (!empty($cookie_string)) {
	curl_setopt($ch, CURLOPT_COOKIE, $cookie_string);
}

// Forward request body for POST, PUT, PATCH
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH') {
	$input = file_get_contents('php://input');
	if ($log_requests) {
		error_log("API Bridge forwarding request body: " . $input);
	}
	curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
}

// Get header info to pass back cookies
curl_setopt($ch, CURLOPT_HEADER, true);

// Execute the request
$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Split headers and body
$header_text = substr($response, 0, $header_size);
$body = substr($response, $header_size);

// Log the response
if ($log_requests) {
	error_log("API Bridge response code: " . $http_code);
	error_log("API Bridge response headers: " . $header_text);
}

// Parse and forward cookies from response
$headers = explode("\r\n", $header_text);
foreach ($headers as $header) {
	if (strpos($header, 'Set-Cookie:') === 0) {
		if ($log_requests) {
			error_log("API Bridge forwarding cookie: " . $header);
		}
		header($header, false);
	}
}

// Check for cURL errors
if ($response === false) {
	$error = curl_error($ch);
	curl_close($ch);

	error_log("API Bridge cURL error: " . $error);

	// Return error response
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => 'API Bridge error: ' . $error,
		'endpoint' => $endpoint,
		'url' => $api_url
	]);
	exit;
}

// Close cURL
curl_close($ch);

// Set the HTTP response code
http_response_code($http_code);

// Output the response body
echo $body;
