<?php

/**
 * Azure CORS Proxy - Solution for Azure App Service CORS issues
 * 
 * This proxy handles requests from the frontend JavaScript and forwards them to the backend,
 * bypassing CORS restrictions by making the request server-side.
 */

// Enable error reporting for troubleshooting
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'logs/cors_proxy_errors.log');

// Create logs directory if it doesn't exist
if (!file_exists('logs')) {
	mkdir('logs', 0755, true);
}

// Set response content type to JSON
header('Content-Type: application/json');

// Get the backend endpoint from the query string
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

// Target backend URL
$backendUrl = 'https://app-backend-esgi-app.azurewebsites.net';

// Validate the endpoint
if (empty($endpoint)) {
	echo json_encode([
		'success' => false,
		'message' => 'No endpoint specified'
	]);
	exit;
}

// Log the request
$logMessage = sprintf(
	"[%s] CORS Proxy Request: Method=%s, Endpoint=%s\n",
	date('Y-m-d H:i:s'),
	$_SERVER['REQUEST_METHOD'],
	$endpoint
);
error_log($logMessage);

// Construct the full URL
$url = $backendUrl . '/' . $endpoint;

// Initialize cURL
$ch = curl_init();

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Set cURL options based on the request method
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Pass along the request headers (except Host)
$requestHeaders = getallheaders();
$headers = [];
foreach ($requestHeaders as $key => $value) {
	if (strtolower($key) !== 'host' && strtolower($key) !== 'content-length') {
		$headers[] = "$key: $value";
	}
}
// Add proxy identification header
$headers[] = 'X-Proxy-Forward: true';
$headers[] = 'User-Agent: ESGI-App-Proxy/1.0';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Handle cookies
if (isset($_SERVER['HTTP_COOKIE'])) {
	curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE']);
}

// Handle request methods
switch ($method) {
	case 'GET':
		// GET requests are the default
		break;
	case 'POST':
		curl_setopt($ch, CURLOPT_POST, true);
		$postData = file_get_contents('php://input');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		break;
	case 'PUT':
	case 'DELETE':
	case 'PATCH':
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		$inputData = file_get_contents('php://input');
		curl_setopt($ch, CURLOPT_POSTFIELDS, $inputData);
		break;
	case 'OPTIONS':
		// For OPTIONS requests, return a 200 OK response with CORS headers
		http_response_code(200);
		echo json_encode([
			'success' => true,
			'message' => 'CORS preflight request successful'
		]);
		exit;
}

// Execute the cURL request
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
	$error = curl_error($ch);
	error_log("cURL Error: $error");
	echo json_encode([
		'success' => false,
		'message' => 'Error connecting to backend',
		'error' => $error
	]);
	curl_close($ch);
	exit;
}

// Get response info
$info = curl_getinfo($ch);
$statusCode = $info['http_code'];

// Close the cURL handle
curl_close($ch);

// Enhanced error handling for specific endpoints
if ($statusCode == 404 || $statusCode >= 500) {
	error_log("Error response for endpoint $endpoint: HTTP $statusCode");

	// Special handling for user profile API
	if ($endpoint === 'api/user/profile') {
		error_log("Generating fallback user profile data");

		// If session has user data, use it
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}

		if (isset($_SESSION['user'])) {
			// Generate response from session data
			$fallbackResponse = [
				'success' => true,
				'user' => $_SESSION['user'],
				'message' => 'Profile retrieved from session (backend unavailable)',
				'is_fallback' => true
			];
			http_response_code(200);
			echo json_encode($fallbackResponse);
			exit;
		} else {
			// Send a more helpful error
			http_response_code(401);
			echo json_encode([
				'success' => false,
				'message' => 'User profile unavailable and no session data found',
				'error' => 'Backend returned status ' . $statusCode,
				'endpoint' => $endpoint,
				'url' => $url
			]);
			exit;
		}
	}

	// General fallback for other endpoints
	if (empty($response) || !json_decode($response)) {
		echo json_encode([
			'success' => false,
			'message' => 'Backend returned error status ' . $statusCode,
			'endpoint' => $endpoint,
			'url' => $url
		]);
		exit;
	}
}

// Set the response status code
http_response_code($statusCode);

// Write the response to the log
error_log("Response Status: $statusCode");

// Output the response
echo $response;
exit;
