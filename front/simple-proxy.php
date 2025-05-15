<?php
// Simple proxy for Azure - minimal version to test deployment
error_reporting(E_ALL);
ini_set('display_errors', 1); // Enable error display for debugging
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Log the file path and existence
$self_path = __FILE__;
$parent_dir = dirname($self_path);
error_log("Simple proxy file path: $self_path");
error_log("Parent directory: $parent_dir");
error_log("File exists: " . (file_exists($self_path) ? 'yes' : 'no'));

// Add CORS headers to avoid issues
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Only set content type if not OPTIONS request
if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
	header('Content-Type: application/json');
}

// Log request information
error_log("Simple proxy accessed: " . $_SERVER['REQUEST_URI']);
error_log("Query string: " . $_SERVER['QUERY_STRING']);
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);

// Handle OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// Basic configuration
$api_base_url = 'https://app-backend-esgi-app.azurewebsites.net';

// Get the endpoint parameter
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
if (empty($endpoint)) {
	echo json_encode([
		'success' => false,
		'message' => 'No endpoint specified',
		'debug' => $_GET,
		'request_uri' => $_SERVER['REQUEST_URI'],
		'query_string' => $_SERVER['QUERY_STRING']
	]);
	exit;
}

try {
	// Simple check if URL is valid
	$endpoint = ltrim($endpoint, '/');

	// Try both with and without "api/" prefix for compatibility
	if (!str_starts_with($endpoint, 'api/') && !in_array($endpoint, ['status.php'])) {
		// If endpoint doesn't start with "api/" and is not a known root endpoint like status.php
		// try to add the api/ prefix
		$target_url = $api_base_url . '/api/' . $endpoint;
		error_log("First attempt with api/ prefix: " . $target_url);
	} else {
		$target_url = $api_base_url . '/' . $endpoint;
		error_log("Using endpoint as-is: " . $target_url);
	}

	// Log the target URL and raw post data
	error_log("Proxying to: " . $target_url);
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$raw_post = file_get_contents('php://input');
		error_log("Raw POST data: " . $raw_post);
	}

	// Attempt to use curl if available
	if (function_exists('curl_init')) {
		$ch = curl_init($target_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		// Set proper method
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

		// If it's a POST or PUT, forward the body
		if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
			$body = file_get_contents("php://input");
			if (!empty($body)) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
				curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
			}
		}

		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		// Set the status code
		http_response_code($status);

		// Log and return the response
		error_log("Proxy response: HTTP $status, Response length: " . strlen($response));
		if ($error) {
			error_log("Curl error: " . $error);
		}

		echo $response;
	} else {
		// Fallback to file_get_contents if curl is not available
		$context = stream_context_create([
			'http' => [
				'method' => $_SERVER['REQUEST_METHOD'],
				'ignore_errors' => true
			],
			'ssl' => [
				'verify_peer' => false,
				'verify_peer_name' => false
			]
		]);

		$response = @file_get_contents($target_url, false, $context);
		$status = $http_response_header[0] ?? 'HTTP/1.1 500 Internal Server Error';
		preg_match('#HTTP/\d+\.\d+ (\d+)#', $status, $matches);
		$status_code = $matches[1] ?? 500;

		// Set the status code
		http_response_code($status_code);

		// Log and return the response
		error_log("Proxy response (file_get_contents): $status, Response length: " . strlen($response ?? ''));

		echo $response;
	}
} catch (Exception $e) {
	// Return a proper error response
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => 'Proxy error: ' . $e->getMessage(),
		'endpoint' => $endpoint,
		'target_url' => $target_url ?? null
	]);

	error_log("Proxy exception: " . $e->getMessage());
}
