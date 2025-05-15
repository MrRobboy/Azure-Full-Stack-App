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

// Initialize or get mock data session
session_start();
if (!isset($_SESSION['mock_data'])) {
	$_SESSION['mock_data'] = [
		'matieres' => [
			[
				'id_matiere' => 1,
				'nom' => 'Mathématiques'
			],
			[
				'id_matiere' => 2,
				'nom' => 'Français'
			],
			[
				'id_matiere' => 16,
				'nom' => 'Docker'
			],
			[
				'id_matiere' => 17,
				'nom' => 'Azure'
			]
		],
		'last_matiere_id' => 17
	];
}

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

// Log proxy request
error_log("Proxy request to endpoint: " . $endpoint);
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Cookies: " . json_encode($_COOKIE));
error_log("Headers: " . json_encode(getallheaders()));

// Construct the API URL
$api_url = $api_base_url;
if (strpos($endpoint, 'http') === 0) {
	// If the endpoint is a full URL, use that directly
	$api_url = $endpoint;
} else {
	// Otherwise, append it to the base URL
	// Ensure there's a slash between base URL and endpoint if needed
	if (!empty($endpoint)) {
		if ($endpoint[0] !== '/' && substr($api_base_url, -1) !== '/') {
			$api_url .= '/';
		}
		$api_url .= $endpoint;
	}
}

// Add query string if present and not in the endpoint
if (!empty($_SERVER['QUERY_STRING'])) {
	$query = $_SERVER['QUERY_STRING'];
	// Remove the endpoint parameter
	$query = preg_replace('/(&|\?)endpoint=[^&]*/', '', $query);
	if (!empty($query)) {
		$api_url .= (strpos($api_url, '?') === false ? '?' : '&') . $query;
	}
}

// Initialize cURL
$ch = curl_init($api_url);

error_log("Proxy forwarding to URL: " . $api_url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Forward the request method
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

// Forward headers from the original request
$headers = [];
$request_headers = getallheaders();
foreach ($request_headers as $name => $value) {
	// Skip host header to avoid conflicts
	if (strtolower($name) !== 'host') {
		$headers[] = $name . ': ' . $value;
	}
}

// Add additional headers for API communication
$headers[] = 'X-Proxy-Forward: true';
$headers[] = 'User-Agent: ESGI-App-Proxy/1.0';

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

// Enable cookie storage for session tracking
curl_setopt($ch, CURLOPT_COOKIEFILE, '');
curl_setopt($ch, CURLOPT_COOKIEJAR, '');

// Get header info to pass back cookies
curl_setopt($ch, CURLOPT_HEADER, true);

// Execute the request
$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Split headers and body
$header_text = substr($response, 0, $header_size);
$body = substr($response, $header_size);

error_log("Proxy response code: " . $http_code);
error_log("Proxy response headers: " . $header_text);

// Parse and forward cookies from response
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
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => 'Proxy error: ' . $error,
		'endpoint' => $endpoint,
		'url' => $api_url
	]);
	exit;
}

// Close cURL
curl_close($ch);

// Set the HTTP response code
http_response_code($http_code);

// Output the response body
echo $body;
