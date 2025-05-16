<?php

/**
 * Unified CORS Proxy - Consolidated solution for Azure App Service CORS issues
 * 
 * This proxy is a consolidated solution that combines the best features of:
 * - azure-cors-proxy.php
 * - simple-proxy.php
 * - api-bridge.php
 * 
 * This single file handles all proxy requests from the frontend JavaScript to the backend,
 * bypassing CORS restrictions by making the request server-side.
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit();
}

// Get the endpoint from query string
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
error_log("Received request for endpoint: " . $endpoint);

if (empty($endpoint)) {
	error_log("No endpoint provided in request");
	http_response_code(400);
	echo json_encode(['error' => 'No endpoint provided']);
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
$ch = curl_init($url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

// Forward headers
$headers = getallheaders();
$forwardHeaders = [];
foreach ($headers as $key => $value) {
	if (strtolower($key) !== 'host' && strtolower($key) !== 'content-length') {
		$forwardHeaders[] = "$key: $value";
	}
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $forwardHeaders);
error_log("Forwarding headers: " . json_encode($forwardHeaders));

// Forward request body for POST, PUT, PATCH
if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
	$input = file_get_contents('php://input');
	error_log("Request body: " . $input);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
}

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
error_log("Response code: " . $httpCode);

if ($response === false) {
	$error = curl_error($ch);
	error_log("cURL error: " . $error);
	http_response_code(500);
	echo json_encode([
		'error' => 'Failed to connect to API',
		'details' => $error
	]);
	exit();
}

// Close cURL
curl_close($ch);

// Forward the response
http_response_code($httpCode);
error_log("Response body: " . $response);
echo $response;
