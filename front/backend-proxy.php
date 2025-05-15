<?php

/**
 * Backend API Proxy
 * 
 * This script serves as a proxy to the backend API, eliminating CORS issues
 * by making the requests from the same domain.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log access to this proxy with more details
error_log("Backend proxy accessed: " . $_SERVER['REQUEST_URI']);
error_log("Raw query string: " . $_SERVER['QUERY_STRING']);

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

// Log original endpoint and query string
error_log("Original endpoint requested: " . $endpoint);

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

// Handle direct access to PHP files vs API endpoints
if (stripos($endpoint, '.php') !== false) {
	// This is a direct script access, not an API endpoint
	$isDirectScript = true;
} else {
	// This is likely an API endpoint
	$isDirectScript = false;
}

// Generate URL variants to try
$urlVariants = [];

// Handle special case for URLs with query parameters
if (strpos($endpoint, '?') !== false) {
	// For endpoints like "azure-cors.php?resource=classes"
	$baseUrl = $api_base_url . '/' . $endpoint;

	// Add any additional query params from the proxy request
	if (!empty($queryParams)) {
		$baseUrl .= (strpos($baseUrl, '?') !== false ? '&' : '?') . http_build_query($queryParams);
	}

	$urlVariants[] = $baseUrl;
} else {
	// If it's a direct script, just use the direct URL
	if ($isDirectScript) {
		$baseUrl = $api_base_url . '/' . $endpoint;

		// Add query string if we have parameters
		if (!empty($queryParams)) {
			$baseUrl .= '?' . http_build_query($queryParams);
		}

		$urlVariants[] = $baseUrl;
	} else {
		// For API endpoints, try multiple routing patterns

		// 1. Direct endpoint without api/ prefix
		$directUrl = $api_base_url . '/' . $endpoint;
		if (!empty($queryParams)) {
			$directUrl .= '?' . http_build_query($queryParams);
		}
		$urlVariants[] = $directUrl;

		// 2. With api/ prefix
		$apiUrl = $api_base_url . '/api/' . $endpoint;
		if (!empty($queryParams)) {
			$apiUrl .= '?' . http_build_query($queryParams);
		}
		$urlVariants[] = $apiUrl;

		// 3. Using the API router directly with resource parameter
		$routerUrl = $api_base_url . '/routes/api.php?resource=' . $endpoint;
		if (!empty($queryParams)) {
			$routerUrl .= '&' . http_build_query($queryParams);
		}
		$urlVariants[] = $routerUrl;

		// 4. Using status.php directly for status endpoints
		if ($endpoint === 'status' || $endpoint === 'db-status') {
			$statusUrl = $api_base_url . '/status.php';
			if ($endpoint === 'db-status') {
				$statusUrl .= '?type=db';
			}
			if (!empty($queryParams)) {
				$statusUrl .= (strpos($statusUrl, '?') !== false ? '&' : '?') . http_build_query($queryParams);
			}
			$urlVariants[] = $statusUrl;
		}
	}
}

// Log the URL variants we're going to try
error_log("URL variants to try: " . print_r($urlVariants, true));

// Get the HTTP method
$method = $_SERVER['REQUEST_METHOD'];

// Try each URL variant in sequence
$response = null;
$http_code = 0;
$content_type = null;
$successful_url = null;
$verbose_logs = [];

foreach ($urlVariants as $url) {
	// Debug log
	error_log("Trying URL: $url (Method: $method)");

	// Initialize cURL session
	$ch = curl_init();

	// Set cURL options
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification for testing
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Disable SSL host verification for testing
	curl_setopt($ch, CURLOPT_VERBOSE, true); // Enable verbose output

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

	// Create a temporary file to store verbose output
	$verbose = fopen('php://temp', 'w+');
	curl_setopt($ch, CURLOPT_STDERR, $verbose);

	// Execute the cURL request
	$current_response = curl_exec($ch);
	$current_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$current_content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

	// Get verbose information
	rewind($verbose);
	$verboseLog = stream_get_contents($verbose);
	$verbose_logs[$url] = substr($verboseLog, 0, 500); // Keep only first 500 chars to avoid huge logs

	// Close the cURL session
	curl_close($ch);

	// Check if this attempt was successful
	if ($current_http_code >= 200 && $current_http_code < 300) {
		$response = $current_response;
		$http_code = $current_http_code;
		$content_type = $current_content_type;
		$successful_url = $url;
		error_log("Success with URL: $url (Status: $http_code)");
		break; // Stop trying more URLs
	} else {
		error_log("Failed with URL: $url (Status: $current_http_code)");

		// If this is our first attempt and it failed, save the response for possible use if all attempts fail
		if ($response === null) {
			$response = $current_response;
			$http_code = $current_http_code;
			$content_type = $current_content_type;
		}
	}
}

// Debug log the final response
error_log("Final response: HTTP $http_code, Content-Type: $content_type, Successful URL: " . ($successful_url ?? 'None'));

// Forward the HTTP status code
http_response_code($http_code);

// Forward the content type if available
if ($content_type) {
	header("Content-Type: $content_type");
} else {
	header('Content-Type: application/json');
}

// If we got an error, provide additional debug info
if ($http_code >= 400) {
	error_log("Proxy error details: HTTP $http_code");

	// Append debug info to the response
	$debug_info = [
		'proxy_debug' => [
			'original_status' => $http_code,
			'original_endpoint' => $endpoint,
			'tried_urls' => $urlVariants,
			'successful_url' => $successful_url,
			'method' => $method,
			'timestamp' => date('Y-m-d H:i:s'),
			'verbose_logs' => $verbose_logs
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
