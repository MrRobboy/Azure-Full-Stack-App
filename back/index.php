<?php
// Application Router
// This script serves as the main entry point for all API requests

// Disable error display but keep error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Log request details
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);

// Parse the request URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($uri, '/');
$segments = explode('/', $uri);

// Handle API routes
if (!empty($segments[0]) && $segments[0] === 'api') {
	// Remove 'api' prefix
	array_shift($segments);

	if (empty($segments[0])) {
		// API root - return API info
		header('Content-Type: application/json');
		echo json_encode([
			'success' => true,
			'message' => 'API Backend',
			'version' => '1.0',
			'timestamp' => date('Y-m-d H:i:s')
		]);
		exit;
	}

	// Check for special routes
	if ($segments[0] === 'status') {
		// Redirect to status.php
		include __DIR__ . '/status.php';
		exit;
	}

	if ($segments[0] === 'db-status') {
		// Redirect to status.php with type=db
		$_GET['type'] = 'db';
		include __DIR__ . '/status.php';
		exit;
	}

	// For all other API routes, pass to the API router
	$_GET['resource'] = $segments[0];

	// Add any additional path segments to the query
	if (count($segments) > 1) {
		$_GET['id'] = $segments[1];
	}

	include __DIR__ . '/routes/api.php';
	exit;
}

// If not an API request, check if it's a direct file access
$file = __DIR__ . '/' . $uri;
if (file_exists($file) && is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
	// Include the PHP file directly
	include $file;
	exit;
}

// If we get here, the requested resource was not found
header('Content-Type: application/json');
header('HTTP/1.1 404 Not Found');
echo json_encode([
	'success' => false,
	'message' => 'Resource not found',
	'uri' => $uri
]);
exit;
