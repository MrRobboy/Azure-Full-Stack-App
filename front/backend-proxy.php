<?php

/**
 * Backend API Proxy
 * 
 * This script serves as a proxy to the backend API, eliminating CORS issues
 * by making the requests from the same domain.
 */

// Log access to this proxy
error_log("Backend proxy accessed: " . $_SERVER['REQUEST_URI']);

// Configuration
$api_base_url = 'https://app-backend-esgi-app.azurewebsites.net';
$timeout = 30; // seconds

// Get the target endpoint from the 'endpoint' parameter
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
if (empty($endpoint)) {
	header('HTTP/1.1 400 Bad Request');
	header('Content-Type: application/json');
	echo json_encode(['error' => 'No endpoint specified']);
	exit;
}

// Remove endpoint from the query parameters to avoid duplication
$queryParams = $_GET;
unset($queryParams['endpoint']);

// Sanitize the endpoint (basic security measure)
$endpoint = ltrim($endpoint, '/');
if (strpos($endpoint, '../') !== false || strpos($endpoint, '..\\') !== false) {
	header('HTTP/1.1 400 Bad Request');
	header('Content-Type: application/json');
	echo json_encode(['error' => 'Invalid endpoint path']);
	exit;
}

// Handle special case for URLs with query parameters
if (strpos($endpoint, '?') !== false) {
	// For endpoints like "azure-cors.php?resource=classes"
	$url = $api_base_url . '/' . $endpoint;

	// Add any additional query params from the proxy request
	if (!empty($queryParams)) {
		$url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($queryParams);
	}
} else {
	// Normal case - separate path and query
	$url = $api_base_url . '/' . $endpoint;

	// Add query string if we have parameters
	if (!empty($queryParams)) {
		$url .= '?' . http_build_query($queryParams);
	}
}

// Get the HTTP method
$method = $_SERVER['REQUEST_METHOD'];

// Debug log
error_log("Proxy forwarding to: $url (Method: $method)");

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification for testing
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Disable SSL host verification for testing

// Set the appropriate HTTP method
if ($method === 'POST') {
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents("php://input"));
} elseif ($method === 'PUT') {
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
	curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents("php://input"));
} elseif ($method === 'DELETE') {
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
} elseif ($method === 'OPTIONS') {
	// Handle OPTIONS requests
	header('HTTP/1.1 200 OK');
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
	header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
	exit;
}

// Forward all HTTP headers
$headers = [];
foreach (getallheaders() as $name => $value) {
	if ($name !== 'Host') { // Skip the Host header
		$headers[] = "$name: $value";
	}
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Execute the cURL request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

// Check for cURL errors
if ($response === false) {
	$error = curl_error($ch);
	curl_close($ch);

	header('HTTP/1.1 500 Internal Server Error');
	header('Content-Type: application/json');
	echo json_encode([
		'error' => 'Proxy error',
		'message' => $error,
		'url' => $url,
		'method' => $method
	]);
	exit;
}

// Debug log the response
error_log("Proxy response: HTTP $http_code, Content-Type: $content_type");

// Close the cURL session
curl_close($ch);

// Forward the HTTP status code
http_response_code($http_code);

// Forward the content type if available
if ($content_type) {
	header("Content-Type: $content_type");
} else {
	header('Content-Type: application/json');
}

// If we got a 401 or 404, provide additional debug info
if ($http_code == 401 || $http_code == 404) {
	error_log("Proxy error details: HTTP $http_code for URL: $url");
	// Append debug info to the response
	$debug_info = [
		'proxy_debug' => [
			'original_status' => $http_code,
			'requested_url' => $url,
			'method' => $method,
			'timestamp' => date('Y-m-d H:i:s')
		]
	];

	// Try to decode the original JSON response
	$json_response = json_decode($response, true);
	if (json_last_error() === JSON_ERROR_NONE && is_array($json_response)) {
		// Valid JSON, add our debug info
		$json_response['proxy_debug'] = $debug_info['proxy_debug'];
		$response = json_encode($json_response);
	} else {
		// Not valid JSON, return our debug info as JSON
		$response = json_encode($debug_info);
	}
}

// Output the response
echo $response;
