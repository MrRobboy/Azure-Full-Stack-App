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

// Parse the endpoint to separate path and query string
$endpointParts = explode('?', $endpoint, 2);
$endpointPath = $endpointParts[0];
$endpointQuery = isset($endpointParts[1]) ? $endpointParts[1] : '';

// Combine endpoint query with additional query params
$combinedQueryParams = [];

// Parse the endpoint query string
if (!empty($endpointQuery)) {
	parse_str($endpointQuery, $endpointQueryParams);
	$combinedQueryParams = array_merge($combinedQueryParams, $endpointQueryParams);
}

// Add the remaining GET parameters
if (!empty($queryParams)) {
	$combinedQueryParams = array_merge($combinedQueryParams, $queryParams);
}

// Build the full URL
$url = $api_base_url . '/' . $endpointPath;

// Add query string if we have parameters
if (!empty($combinedQueryParams)) {
	$url .= '?' . http_build_query($combinedQueryParams);
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

// Output the response
echo $response;
