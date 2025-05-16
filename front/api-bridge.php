<?php

/**
 * API Bridge - Enhanced proxy for Azure
 * 
 * This file serves as a reliable bridge between frontend and backend API
 * with built-in fallbacks for common API endpoints.
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set headers for CORS and JSON response
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

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

// Backend API base URL
$backendBaseUrl = 'https://app-backend-esgi-app.azurewebsites.net/api';

// Build the full target URL
$targetUrl = $backendBaseUrl;

// Ensure we have a slash between base URL and endpoint if needed
if (!str_starts_with($endpoint, '/') && !str_ends_with($backendBaseUrl, '/')) {
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
curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10-second timeout
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification (not recommended for production)
curl_setopt($ch, CURLOPT_VERBOSE, true); // Enable verbose output

// Create a temporary file handle for CURL debug output
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

// Set request method
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

// Add request headers
$headers = [
	'Content-Type: application/json',
	'Accept: application/json',
	'X-Requested-With: XMLHttpRequest'
];
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
		'source' => 'api-bridge.php'
	]);
	exit;
}

// Special case handling for some endpoints
if ($method === 'GET') {
	$isUserProfile = strpos($endpoint, 'user/profile') !== false;
	$sessionUser = getSessionUser();

	// Handle user profile request - return session data
	if ($isUserProfile && !empty($sessionUser)) {
		echo json_encode([
			'success' => true,
			'user' => $sessionUser,
			'source' => 'api-bridge.php (session data)'
		]);
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
	// Try to use direct-matieres.php as fallback for some endpoints
	tryFallbackEndpoint($endpoint, $method, $requestBody);

	// If we reached here, fallback didn't work either
	sendErrorResponse("cURL Error: $error ($errorCode)", 500);
	exit;
}

// Forward the response
http_response_code($httpCode);
echo $response;
exit;

// Helper function to get user data from session
function getSessionUser()
{
	session_start();
	if (isset($_SESSION['user'])) {
		return $_SESSION['user'];
	}
	return null;
}

// Helper function to try fallback endpoint
function tryFallbackEndpoint($endpoint, $method, $requestBody)
{
	error_log("Trying fallback endpoint for: " . $endpoint);
	// Check if the endpoint is one we can handle with our direct provider
	if (
		strpos($endpoint, 'matieres') !== false ||
		strpos($endpoint, 'classes') !== false ||
		strpos($endpoint, 'examens') !== false ||
		strpos($endpoint, 'professeurs') !== false
	) {
		// Redirect to our direct data provider
		$fallbackUrl = "direct-matieres.php?endpoint=" . urlencode($endpoint);
		error_log("Using fallback URL: " . $fallbackUrl);

		// Execute a local request to our fallback
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $fallbackUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

		if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($requestBody)) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
		}

		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($httpCode >= 200 && $httpCode < 300) {
			// Successfully got response from fallback
			http_response_code($httpCode);
			echo $response;
			exit;
		}
	}
}

// Helper function to send error response
function sendErrorResponse($message, $code = 500)
{
	error_log("Error response: " . $message . " (code: " . $code . ")");
	http_response_code($code);
	echo json_encode([
		'success' => false,
		'error' => $message,
		'source' => 'api-bridge.php',
		'debug' => [
			'endpoint' => $endpoint ?? null,
			'method' => $method ?? null,
			'requestBody' => $requestBody ?? null,
			'targetUrl' => $targetUrl ?? null
		]
	]);
}

// PHP 8 compatibility function
if (!function_exists('str_starts_with')) {
	function str_starts_with($haystack, $needle)
	{
		return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
	}
}

if (!function_exists('str_ends_with')) {
	function str_ends_with($haystack, $needle)
	{
		return $needle !== '' && substr($haystack, -strlen($needle)) === (string)$needle;
	}
}
