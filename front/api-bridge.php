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

// Définir les headers CORS
$corsHeaders = getCorsHeaders();
foreach ($corsHeaders as $header) {
	header($header);
}

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// Get the target endpoint from query parameter
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
error_log("Received request for endpoint: " . $endpoint);

if (empty($endpoint)) {
	error_log("No endpoint provided in request");
	sendErrorResponse('No endpoint specified', 400);
	exit;
}

// Build the full target URL
$targetUrl = BACKEND_BASE_URL;

// Ensure we have a slash between base URL and endpoint if needed
if (!str_starts_with($endpoint, '/') && !str_ends_with(BACKEND_BASE_URL, '/')) {
	$targetUrl .= '/';
}

$targetUrl .= $endpoint;
error_log("Making request to: " . $targetUrl);

// Get request method and body
$method = $_SERVER['REQUEST_METHOD'];
error_log("Request method: " . $method);

$requestBody = file_get_contents('php://input');
if ($requestBody) {
	error_log("Request body: " . $requestBody);
}

// Create a new cURL resource
$ch = curl_init();

// Setup cURL options
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, CURL_TIMEOUT);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, CURL_CONNECT_TIMEOUT);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, SSL_VERIFY_PEER);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, SSL_VERIFY_HOST);

// Create a temporary file handle for CURL debug output
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

// Set request method
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

// Add request headers
$headers = DEFAULT_HEADERS;

// Add Authorization header if present in original request
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
	$headers[] = 'Authorization: ' . $_SERVER['HTTP_AUTHORIZATION'];
}

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Add request body for POST, PUT, PATCH
if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($requestBody)) {
	curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
}

// Special case: status.php endpoint for health check
if (strpos($endpoint, 'status.php') !== false) {
	echo json_encode([
		'status' => 'ok',
		'message' => 'API Bridge is working',
		'timestamp' => date('Y-m-d H:i:s'),
		'source' => 'api-bridge.php',
		'endpoint' => $endpoint,
		'method' => $method
	]);
	exit;
}

// Special case handling for login endpoint
if (strpos($endpoint, 'auth/login') !== false) {
	error_log("Handling login request");
	// Ensure we're using POST method
	if ($method !== 'POST') {
		sendErrorResponse('Login requires POST method', 405);
		exit;
	}

	// Validate request body
	if (empty($requestBody)) {
		sendErrorResponse('Login request requires email and password', 400);
		exit;
	}

	// Parse request body
	$loginData = json_decode($requestBody, true);
	if (!isset($loginData['email']) || !isset($loginData['password'])) {
		sendErrorResponse('Login request must include email and password', 400);
		exit;
	}
}

// Execute cURL request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$error = curl_error($ch);
$errorCode = curl_errno($ch);

// Get verbose debug information
rewind($verbose);
$verboseLog = stream_get_contents($verbose);
error_log("CURL Verbose Log: " . $verboseLog);

// Close cURL resource
curl_close($ch);
fclose($verbose);

// Log detailed response information
error_log("Response HTTP Code: " . $httpCode);
error_log("Response Content-Type: " . $contentType);
error_log("Response Body: " . $response);

// Handle cURL errors
if ($errorCode > 0) {
	error_log("cURL error: " . $error . " (code: " . $errorCode . ")");
	sendErrorResponse("cURL Error: $error ($errorCode)", 500);
	exit;
}

// Forward the response
http_response_code($httpCode);
echo $response;
exit;

// Helper function to send error response
function sendErrorResponse($message, $code = 500)
{
	http_response_code($code);
	echo json_encode([
		'success' => false,
		'message' => $message,
		'timestamp' => date('Y-m-d H:i:s'),
		'source' => 'api-bridge.php',
		'endpoint' => $endpoint ?? 'unknown',
		'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
	]);
	exit;
}
