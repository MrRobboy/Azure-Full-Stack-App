<?php

/**
 * Enhanced API Proxy
 * This proxy handles all API requests to the backend
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit();
}

// Get the endpoint from the query string
$endpoint = $_GET['endpoint'] ?? '';
if (empty($endpoint)) {
	http_response_code(400);
	echo json_encode(['error' => 'No endpoint specified']);
	exit();
}

// Base URL for the backend API
$baseUrl = 'https://app-backend-esgi-app.azurewebsites.net/api';

// Construct the full URL
$url = $baseUrl . '/' . $endpoint;
error_log("Making request to: " . $url);

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];
error_log("Request method: " . $method);

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

// Forward headers
$headers = [
	'Content-Type: application/json',
	'Accept: application/json',
	'X-Requested-With: XMLHttpRequest'
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Forward request body for POST, PUT, etc.
if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
	$input = file_get_contents('php://input');
	error_log("Request body: " . $input);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
}

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$errorCode = curl_errno($ch);

// Log response details
error_log("Response code: " . $httpCode);
error_log("Response body: " . $response);

// Check for cURL errors
if ($errorCode > 0) {
	error_log("cURL error: " . $error . " (code: " . $errorCode . ")");
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'error' => "cURL Error: $error ($errorCode)",
		'source' => 'matieres-proxy.php'
	]);
	exit();
}

// Close cURL
curl_close($ch);

// Set the response code
http_response_code($httpCode);

// Forward the response
echo $response;
