<?php
// Simple test script to check if routing and API configuration is working correctly

// Disable error display but log them
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Set CORS headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://app-frontend-esgi-app.azurewebsites.net');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// Get server information
$server_info = [
	'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
	'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
	'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown',
	'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
	'php_version' => phpversion(),
	'timestamp' => date('Y-m-d H:i:s'),
	'apache_modules' => function_exists('apache_get_modules') ? apache_get_modules() : 'Function not available',
	'loaded_extensions' => get_loaded_extensions(),
	'htaccess_test' => 'active'
];

// Check if routing is working
$routes_test = [
	'direct_access' => true,
	'expected_formats' => [
		'api/classes' => 'Should be routed through index.php to routes/api.php',
		'api/status' => 'Should be routed to status.php',
		'api/db-status' => 'Should be routed to status.php with type=db'
	],
	'url_rewriting' => function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())
];

// Check for .htaccess file
$htaccess_checks = [
	'htaccess_exists' => file_exists(__DIR__ . '/.htaccess'),
	'htaccess_readable' => is_readable(__DIR__ . '/.htaccess'),
	'htaccess_size' => file_exists(__DIR__ . '/.htaccess') ? filesize(__DIR__ . '/.htaccess') : 'File not found'
];

// Check for web.config file
$web_config_checks = [
	'web_config_exists' => file_exists(__DIR__ . '/web.config'),
	'web_config_readable' => is_readable(__DIR__ . '/web.config'),
	'web_config_size' => file_exists(__DIR__ . '/web.config') ? filesize(__DIR__ . '/web.config') : 'File not found'
];

// Check if PHP is running on IIS or Apache or Nginx
$server_software = strtolower($_SERVER['SERVER_SOFTWARE'] ?? '');
$server_type = 'unknown';
if (strpos($server_software, 'apache') !== false) {
	$server_type = 'apache';
} elseif (strpos($server_software, 'microsoft-iis') !== false) {
	$server_type = 'iis';
} elseif (strpos($server_software, 'nginx') !== false) {
	$server_type = 'nginx';
}

// Return routing test information
echo json_encode([
	'success' => true,
	'message' => 'Routing test complete',
	'server_type' => $server_type,
	'server_info' => $server_info,
	'routes_test' => $routes_test,
	'htaccess_checks' => $htaccess_checks,
	'web_config_checks' => $web_config_checks,
	'get_params' => $_GET,
	'post_params' => $_POST
], JSON_PRETTY_PRINT);
