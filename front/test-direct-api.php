<?php
// Simple direct API tester without using the proxy
// This script helps diagnose if the issue is with the backend or with the proxy

// Disable error display but log them
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Log access to this script
error_log("Direct API test accessed: " . date('Y-m-d H:i:s'));

// Configuration
$api_base_url = 'https://app-backend-esgi-app.azurewebsites.net';
$timeout = 30; // seconds

// Get the target endpoint
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : 'api-test.php';
$use_api_prefix = isset($_GET['use_api_prefix']) ? (bool)$_GET['use_api_prefix'] : false;

// Build the URL
$url = $api_base_url . '/';
if ($use_api_prefix) {
	$url .= 'api/';
}
$url .= $endpoint;

// Append any additional query parameters
$queryParams = $_GET;
unset($queryParams['endpoint']);
unset($queryParams['use_api_prefix']);
if (!empty($queryParams)) {
	$url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($queryParams);
}

// Log the target URL
error_log("Testing direct API URL: " . $url);

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_VERBOSE, true);

// Create a temporary file to store verbose output
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

// Execute the cURL request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

// Get verbose information
rewind($verbose);
$verboseLog = stream_get_contents($verbose);

// Close the cURL session
curl_close($ch);

// Log the response info
error_log("Response: HTTP $http_code, Content-Type: $content_type");

// Check if the response is HTML
$is_html_response = false;
$error_msg = "";
if (
	$content_type && strpos($content_type, 'text/html') !== false ||
	strpos($response, '<html') !== false ||
	strpos($response, '<!DOCTYPE') !== false ||
	strpos($response, '<body') !== false
) {
	$is_html_response = true;

	// Extract error message from HTML if possible
	if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $response, $matches)) {
		$error_msg = trim($matches[1]);
	} elseif (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $response, $matches)) {
		$error_msg = trim($matches[1]);
	}
}

// Prepare the result
$result = [
	'success' => $http_code >= 200 && $http_code < 300,
	'http_status' => $http_code,
	'url_tested' => $url,
	'timestamp' => date('Y-m-d H:i:s'),
];

if ($is_html_response) {
	$result['content_type'] = 'text/html';
	$result['is_html'] = true;
	$result['error_message'] = $error_msg;
	$result['html_preview'] = substr(preg_replace('/\s+/', ' ', $response), 0, 500);
} else {
	// Try to decode JSON
	$json_data = json_decode($response, true);
	if (json_last_error() === JSON_ERROR_NONE) {
		$result['content_type'] = 'application/json';
		$result['data'] = $json_data;
	} else {
		$result['content_type'] = $content_type;
		$result['raw_data'] = substr($response, 0, 1000);
	}
}

// Add curl verbose log (first 500 chars)
$result['curl_log'] = substr($verboseLog, 0, 500);

// Output as JSON
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);
