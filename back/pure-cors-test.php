<?php
// Explicitly set CORS headers, should work even if web.config doesn't
error_log("PURE CORS test accessed: " . date('Y-m-d H:i:s'));
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);

// Always set CORS headers for all request types
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://app-frontend-esgi-app.azurewebsites.net');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');

// Special handling for OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	error_log("Responding to OPTIONS with 200 OK");
	exit;
}

// Get all server variables and headers for debugging
$server_info = [];
foreach ($_SERVER as $key => $value) {
	if (!is_array($value)) {
		$server_info[$key] = $value;
	}
}

$request_headers = [];
foreach (getallheaders() as $name => $value) {
	$request_headers[$name] = $value;
}

// Return a simple response with debugging info
echo json_encode([
	'success' => true,
	'message' => 'Pure PHP CORS test successful',
	'timestamp' => date('Y-m-d H:i:s'),
	'method' => $_SERVER['REQUEST_METHOD'],
	'headers_sent' => headers_sent(),
	'headers_list' => headers_list(),
	'server_variables' => $server_info,
	'request_headers' => $request_headers
]);
