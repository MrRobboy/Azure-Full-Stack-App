<?php

/**
 * Azure-Proxy - Optimized proxy for Azure
 * 
 * This file combines the working parts of simple-proxy.php with
 * enhanced security features from api-bridge.php
 */

// Configure error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display errors in production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/azure-proxy.log');

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/logs')) {
	mkdir(__DIR__ . '/logs', 0755, true);
}

// Log basic information for debugging
error_log("Azure proxy accessed: " . $_SERVER['REQUEST_URI']);
error_log("Query string: " . $_SERVER['QUERY_STRING']);
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);

// Load security configuration if available
$securityConfigFile = __DIR__ . '/config/security.php';
if (file_exists($securityConfigFile)) {
	require_once $securityConfigFile;
} else {
	// Fallback security configuration
	define('CORS_CONFIG', [
		'allowed_origins' => ['*'],
		'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
		'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'Origin'],
		'exposed_headers' => ['X-Rate-Limit-Remaining', 'X-Rate-Limit-Reset'],
		'max_age' => 86400,
		'allow_credentials' => true
	]);

	define('SECURITY_HEADERS', [
		'X-Content-Type-Options: nosniff',
		'X-Frame-Options: DENY',
		'X-XSS-Protection: 1; mode=block',
		'Strict-Transport-Security: max-age=31536000; includeSubDomains',
		'Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\'; style-src \'self\' \'unsafe-inline\';'
	]);

	define('RATE_LIMIT_CONFIG', [
		'enabled' => true,
		'max_requests' => 1000,
		'window' => 3600
	]);

	define('INPUT_VALIDATION_CONFIG', [
		'enabled' => true,
		'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
		'max_length' => 1000
	]);

	if (!function_exists('getCorsHeaders')) {
		function getCorsHeaders()
		{
			$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
			return [
				'Access-Control-Allow-Origin' => $origin,
				'Access-Control-Allow-Methods' => implode(', ', CORS_CONFIG['allowed_methods']),
				'Access-Control-Allow-Headers' => implode(', ', CORS_CONFIG['allowed_headers']),
				'Access-Control-Expose-Headers' => implode(', ', CORS_CONFIG['exposed_headers']),
				'Access-Control-Max-Age' => CORS_CONFIG['max_age'],
				'Access-Control-Allow-Credentials' => CORS_CONFIG['allow_credentials'] ? 'true' : 'false'
			];
		}
	}

	if (!function_exists('setSecurityHeaders')) {
		function setSecurityHeaders()
		{
			foreach (SECURITY_HEADERS as $header) {
				header($header);
			}
		}
	}

	if (!function_exists('validateInput')) {
		function validateInput($input)
		{
			if (!INPUT_VALIDATION_CONFIG['enabled']) {
				return $input;
			}

			if (strlen($input) > INPUT_VALIDATION_CONFIG['max_length']) {
				error_log("Input too long: " . substr($input, 0, 100) . "...");
				return false;
			}

			// Basic sanitization
			$input = strip_tags($input);
			$input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

			return $input;
		}
	}

	if (!function_exists('checkRateLimit')) {
		function checkRateLimit($ip)
		{
			if (!RATE_LIMIT_CONFIG['enabled']) {
				return true;
			}

			$cacheDir = sys_get_temp_dir() . '/rate_limit';
			if (!is_dir($cacheDir)) {
				mkdir($cacheDir, 0755, true);
			}

			$cacheFile = $cacheDir . '/' . md5($ip) . '.json';
			$now = time();
			$window = RATE_LIMIT_CONFIG['window'];
			$maxRequests = RATE_LIMIT_CONFIG['max_requests'];

			if (file_exists($cacheFile)) {
				$data = json_decode(file_get_contents($cacheFile), true);
				if ($data && $data['timestamp'] > ($now - $window)) {
					if ($data['count'] >= $maxRequests) {
						error_log("Rate limit exceeded for IP: $ip");
						return false;
					}
					$data['count']++;
				} else {
					$data = ['count' => 1, 'timestamp' => $now];
				}
			} else {
				$data = ['count' => 1, 'timestamp' => $now];
			}

			file_put_contents($cacheFile, json_encode($data));
			return true;
		}
	}
}

// Set CORS headers
$corsHeaders = getCorsHeaders();
foreach ($corsHeaders as $header => $value) {
	header("$header: $value");
}

// Set security headers
setSecurityHeaders();

// Handle OPTIONS requests (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// Check rate limit
if (!checkRateLimit($_SERVER['REMOTE_ADDR'])) {
	error_log("Rate limit exceeded for IP: " . $_SERVER['REMOTE_ADDR']);
	header('HTTP/1.1 429 Too Many Requests');
	die(json_encode([
		'success' => false,
		'error' => [
			'code' => 'RATE_LIMIT_EXCEEDED',
			'message' => 'Trop de requêtes. Veuillez réessayer plus tard.'
		]
	]));
}

// Check HTTP method
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, INPUT_VALIDATION_CONFIG['allowed_methods'])) {
	error_log("Method not allowed: " . $method);
	header('HTTP/1.1 405 Method Not Allowed');
	die(json_encode([
		'success' => false,
		'error' => [
			'code' => 'METHOD_NOT_ALLOWED',
			'message' => 'Méthode non autorisée'
		]
	]));
}

// Get and validate the endpoint
$endpoint = isset($_GET['endpoint']) ? validateInput($_GET['endpoint']) : null;
if (!$endpoint) {
	error_log("Invalid or missing endpoint");
	header('HTTP/1.1 400 Bad Request');
	die(json_encode([
		'success' => false,
		'error' => [
			'code' => 'INVALID_ENDPOINT',
			'message' => 'Endpoint manquant ou invalide'
		]
	]));
}

// Basic configuration
$api_base_url = 'https://app-backend-esgi-app.azurewebsites.net';

// Handle special endpoints
$endpointMap = [
	'auth/login' => 'api/auth/login.php',
	'auth/logout' => 'api/auth/logout.php',
	'matieres' => 'api/matieres.php',
	'notes' => 'api/notes.php',
	'status' => 'api/status.php'
];

// Map endpoints or append .php if needed
if (isset($endpointMap[$endpoint])) {
	$endpoint = $endpointMap[$endpoint];
} else if (strpos($endpoint, '.php') === false) {
	$endpoint = 'api/' . $endpoint . '.php';
} else {
	$endpoint = 'api/' . $endpoint;
}

// Construct the API URL
$api_url = rtrim($api_base_url, '/') . '/' . ltrim($endpoint, '/');

// Add query string if present (except for the endpoint parameter)
if (!empty($_SERVER['QUERY_STRING'])) {
	$query = $_SERVER['QUERY_STRING'];
	// Remove the endpoint parameter
	$query = preg_replace('/(&|\?)endpoint=[^&]*/', '', $query);
	if (!empty($query)) {
		$api_url .= (strpos($api_url, '?') === false ? '?' : '&') . $query;
	}
}

error_log("Proxy forwarding to URL: " . $api_url);

// Initialize cURL
$ch = curl_init($api_url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_VERBOSE, true);

// Forward the request method
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

// Forward headers from the original request
$headers = [];
$request_headers = function_exists('getallheaders') ? getallheaders() : [];
foreach ($request_headers as $name => $value) {
	// Skip host header to avoid conflicts
	if (strtolower($name) !== 'host') {
		$headers[] = $name . ': ' . $value;
	}
}

// Add additional headers
$headers[] = 'Content-Type: application/json';
$headers[] = 'Accept: application/json';
$headers[] = 'X-Requested-With: XMLHttpRequest';
$headers[] = 'X-Proxy-Forward: true';
$headers[] = 'User-Agent: ESGI-App-Proxy/2.0';

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Forward cookies
$cookie_string = '';
foreach ($_COOKIE as $name => $value) {
	$cookie_string .= $name . '=' . $value . '; ';
}
if (!empty($cookie_string)) {
	curl_setopt($ch, CURLOPT_COOKIE, $cookie_string);
}

// Forward request body for POST, PUT, PATCH
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH') {
	$input = file_get_contents('php://input');
	error_log("Forwarding request body: " . $input);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
}

// Enable header retrieval
curl_setopt($ch, CURLOPT_HEADER, true);

// Execute the request
$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Split headers and body
$header_text = substr($response, 0, $header_size);
$body = substr($response, $header_size);

error_log("Proxy response code: " . $http_code);
error_log("Proxy response size: " . strlen($body));

// Forward cookies from response
$headers = explode("\r\n", $header_text);
foreach ($headers as $header) {
	if (strpos($header, 'Set-Cookie:') === 0) {
		error_log("Forwarding cookie: " . $header);
		header($header, false);
	}
}

// Check for cURL errors
if ($response === false) {
	$error = curl_error($ch);
	curl_close($ch);

	error_log("cURL error: " . $error);

	// Return error response
	http_response_code(502);
	echo json_encode([
		'success' => false,
		'error' => [
			'code' => 'PROXY_ERROR',
			'message' => 'Erreur de communication avec le backend',
			'details' => $error
		]
	]);
	exit;
}

// Close cURL
curl_close($ch);

// Set the HTTP response code
http_response_code($http_code);

// Output the response body
echo $body;
