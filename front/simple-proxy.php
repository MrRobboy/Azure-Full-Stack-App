<?php
// Simple proxy without JWT authentication
header('Content-Type: application/json');

// Configuration
$backendBaseUrl = 'https://app-backend-esgi-app.azurewebsites.net/';

// Get endpoint from query parameter
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

if (empty($endpoint)) {
	echo json_encode(['error' => 'No endpoint specified']);
	exit;
}

// Combine URL
$url = $backendBaseUrl . $endpoint;

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Forward request method
$method = $_SERVER['REQUEST_METHOD'];
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

// Forward request headers
$headers = [];
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
	$headers[] = 'Authorization: ' . $_SERVER['HTTP_AUTHORIZATION'];
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Forward POST data if applicable
if ($method === 'POST' || $method === 'PUT') {
	$postData = file_get_contents('php://input');
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
}

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Set status code
http_response_code($httpCode);

// Log the request details
error_log("Proxy request to: $url, Method: $method, Status: $httpCode");

// Close cURL session
curl_close($ch);

// Return response
echo $response;
