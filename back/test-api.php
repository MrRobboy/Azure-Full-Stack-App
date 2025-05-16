<?php
// API Testing Tool
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Log access for debugging
error_log("API Test tool accessed: " . date('Y-m-d H:i:s'));
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request URI: " . $_SERVER['REQUEST_URI']);

$api_base_url = 'https://app-backend-esgi-app.azurewebsites.net';

// Define test endpoints
$test_endpoints = [
	'api/auth/login',
	'api/auth/check-credentials',
	'api/status',
	'api/notes',
	'api/matieres',
	'api/classes',
	'api/examens',
	'api/profs',
	'api/users'
];

// Test a specific endpoint if provided
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : null;
if ($endpoint) {
	$test_endpoints = [$endpoint];
}

// Test results container
$results = [
	'success' => true,
	'timestamp' => date('Y-m-d H:i:s'),
	'tests' => []
];

// Function to test an endpoint
function test_endpoint($url, $method = 'GET')
{
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

	if ($method === 'POST') {
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['test' => true]));
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	}

	curl_setopt($ch, CURLOPT_TIMEOUT, 5);

	$response = curl_exec($ch);
	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$error = curl_error($ch);
	curl_close($ch);

	return [
		'status' => $status,
		'response' => $response,
		'error' => $error,
		'success' => ($status >= 200 && $status < 500)
	];
}

// Run tests
foreach ($test_endpoints as $endpoint) {
	$url = "$api_base_url/$endpoint";

	// Test GET
	$get_result = test_endpoint($url, 'GET');

	// Test POST
	$post_result = test_endpoint($url, 'POST');

	// Store results
	$results['tests'][$endpoint] = [
		'url' => $url,
		'get' => [
			'status' => $get_result['status'],
			'success' => $get_result['success'],
			'error' => $get_result['error']
		],
		'post' => [
			'status' => $post_result['status'],
			'success' => $post_result['success'],
			'error' => $post_result['error']
		]
	];

	// Include response for easier debugging if requested
	if (isset($_GET['include_response']) && $_GET['include_response'] === 'true') {
		$results['tests'][$endpoint]['get']['response'] = json_decode($get_result['response'], true);
		$results['tests'][$endpoint]['post']['response'] = json_decode($post_result['response'], true);
	}

	// Update overall success status
	if (!$get_result['success'] && !$post_result['success']) {
		$results['success'] = false;
	}
}

// Include configuration info
$results['configuration'] = [
	'web_config' => file_exists(__DIR__ . '/web.config'),
	'htaccess' => file_exists(__DIR__ . '/.htaccess'),
	'api_route' => file_exists(__DIR__ . '/routes/api.php'),
	'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
	'php_version' => PHP_VERSION
];

// Output results
echo json_encode($results, JSON_PRETTY_PRINT);
