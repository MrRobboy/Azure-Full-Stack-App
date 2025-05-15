<?php

/**
 * Comprehensive Server Diagnostics Tool
 * 
 * This script tests various aspects of the server configuration, routing,
 * and API endpoints to help diagnose issues in the Azure environment.
 */

// Disable error display but log everything
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/diagnostics.log');

// Set appropriate headers for the response
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle OPTIONS requests for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// Start logging
$log = [];
$log[] = "[" . date('Y-m-d H:i:s') . "] Starting server diagnostics...";

/**
 * Helper Functions
 */

// Function to log a message
function log_message($message, $type = 'info')
{
	global $log;
	$log[] = "[" . date('Y-m-d H:i:s') . "][$type] $message";
	error_log($message);
}

// Function to test if a file exists and is readable
function test_file($filepath)
{
	$exists = file_exists($filepath);
	$readable = is_readable($filepath);
	$size = $exists ? filesize($filepath) : 0;

	log_message("Testing file: $filepath - " .
		($exists ? "EXISTS" : "MISSING") . " - " .
		($readable ? "READABLE" : "NOT READABLE") . " - " .
		"Size: $size bytes");

	return [
		'exists' => $exists,
		'readable' => $readable,
		'size' => $size,
		'path' => $filepath
	];
}

// Function to test an API endpoint
function test_endpoint($url, $method = 'GET', $data = null)
{
	log_message("Testing endpoint: $url (Method: $method)");

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

	if ($method === 'POST') {
		curl_setopt($ch, CURLOPT_POST, true);
		if ($data) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
		}
	}

	$response = curl_exec($ch);
	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$headers = substr($response, 0, $header_size);
	$body = substr($response, $header_size);
	$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
	$error = curl_error($ch);

	curl_close($ch);

	$json_data = null;
	$is_json = false;
	$parse_error = null;

	// Try to parse JSON
	if ($body) {
		try {
			$json_data = json_decode($body, true);
			$is_json = (json_last_error() === JSON_ERROR_NONE);
			if (!$is_json) {
				$parse_error = json_last_error_msg();
			}
		} catch (Exception $e) {
			$parse_error = $e->getMessage();
		}
	}

	$result = [
		'url' => $url,
		'method' => $method,
		'status_code' => $status_code,
		'content_type' => $content_type,
		'is_json' => $is_json,
		'headers' => $headers,
		'error' => $error ?: null,
		'parse_error' => $parse_error,
		'raw_body_preview' => $is_json ? null : substr($body, 0, 500),
		'json_data' => $is_json ? $json_data : null
	];

	log_message("Endpoint result: $url - Status: $status_code - " .
		($is_json ? "Valid JSON" : "Not JSON" . ($parse_error ? " ($parse_error)" : "")));

	return $result;
}

/**
 * Diagnostic Tests
 */

// 1. Server Information
log_message("Collecting server information");
$server_info = [
	'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
	'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
	'server_addr' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
	'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
	'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown',
	'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
	'php_version' => phpversion(),
	'php_sapi' => php_sapi_name(),
	'php_modules' => get_loaded_extensions(),
	'php_disabled_functions' => explode(',', ini_get('disable_functions')),
	'webserver_type' => (stripos($_SERVER['SERVER_SOFTWARE'] ?? '', 'nginx') !== false) ? 'nginx' : ((stripos($_SERVER['SERVER_SOFTWARE'] ?? '', 'apache') !== false) ? 'apache' : ((stripos($_SERVER['SERVER_SOFTWARE'] ?? '', 'iis') !== false) ? 'iis' : 'unknown')),
	'timestamp' => date('Y-m-d H:i:s'),
	'directory_structure' => [],
];

// 2. Configuration Files
log_message("Testing configuration files");
$config_files = [
	'index.php' => test_file(__DIR__ . '/index.php'),
	'web.config' => test_file(__DIR__ . '/web.config'),
	'htaccess' => test_file(__DIR__ . '/.htaccess'),
	'nginx_config' => test_file(__DIR__ . '/nginx_config'),
	'api_router' => test_file(__DIR__ . '/routes/api.php'),
	'status_php' => test_file(__DIR__ . '/status.php'),
	'api_test_php' => test_file(__DIR__ . '/api-test.php'),
];

// Scan directory structure
log_message("Scanning directory structure");
$directories = ['routes', 'controllers', 'models', 'config', 'services'];
foreach ($directories as $dir) {
	$path = __DIR__ . '/' . $dir;
	if (is_dir($path)) {
		$files = scandir($path);
		$server_info['directory_structure'][$dir] = array_filter($files, function ($file) {
			return $file !== '.' && $file !== '..';
		});
	} else {
		$server_info['directory_structure'][$dir] = "Directory not found";
	}
}

// 3. Endpoint Testing
log_message("Testing API endpoints");
$base_url = isset($_SERVER['HTTP_HOST']) ?
	((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']) :
	'http://localhost';

$endpoints = [
	// Direct PHP script files
	'status.php' => test_endpoint("$base_url/status.php"),
	'api-test.php' => test_endpoint("$base_url/api-test.php"),
	'test-routing.php' => test_endpoint("$base_url/test-routing.php"),
	'info.php' => test_endpoint("$base_url/info.php"),

	// Routed API endpoints
	'api-root' => test_endpoint("$base_url/api"),
	'api-status' => test_endpoint("$base_url/api/status"),
	'api-classes' => test_endpoint("$base_url/api/classes"),
	'api-matieres' => test_endpoint("$base_url/api/matieres"),

	// Direct router access
	'api-router-classes' => test_endpoint("$base_url/routes/api.php?resource=classes"),
	'api-router-status' => test_endpoint("$base_url/routes/api.php?resource=status"),
];

// 4. Session and Authentication Test
log_message("Testing session and authentication mechanisms");
$session_test = [
	'session_enabled' => function_exists('session_start'),
	'session_save_path' => ini_get('session.save_path'),
	'session_status' => session_status(),
	'session_status_string' => ['Disabled', 'None', 'Active'][session_status()],
	'cookies_enabled' => ini_get('session.use_cookies') == 1,
];

// 5. Database Connectivity Test (if applicable)
log_message("Testing database connectivity");
$db_test = [];
try {
	// Try to include status.php if available to get database info
	if (file_exists(__DIR__ . '/status.php')) {
		$db_test['status_response'] = test_endpoint("$base_url/status.php?type=db");
	}
} catch (Exception $e) {
	$db_test['error'] = $e->getMessage();
}

// 6. Test URL Rewriting
log_message("Testing URL rewriting capabilities");
$rewrite_test = [];
$test_marker = "rewrite_test_" . time();
$rewrite_test['test_uri'] = $_SERVER['REQUEST_URI'] ?? 'Unknown';

// 7. Collect additional diagnostics from the environment
log_message("Collecting additional environment diagnostics");
$environment = [
	'memory_limit' => ini_get('memory_limit'),
	'max_execution_time' => ini_get('max_execution_time'),
	'upload_max_filesize' => ini_get('upload_max_filesize'),
	'post_max_size' => ini_get('post_max_size'),
	'environment_variables' => [],
];

// Safe environment variables to include (exclude sensitive ones)
$safe_env_vars = ['APP_ENV', 'APP_DEBUG', 'ENVIRONMENT', 'APPLICATION_ENV', 'AZURE_WEBSITES_DOMAIN'];
foreach ($_ENV as $key => $value) {
	if (in_array($key, $safe_env_vars) || strpos($key, 'AZURE_') === 0) {
		$environment['environment_variables'][$key] = $value;
	}
}

// Fetch log files for the last few lines if available
log_message("Checking log files");
$logs = [];
$log_files = [
	'php_errors' => __DIR__ . '/../logs/php_errors.log',
	'diagnostics' => __DIR__ . '/../logs/diagnostics.log',
];

foreach ($log_files as $name => $file) {
	if (file_exists($file) && is_readable($file)) {
		// Get the last 50 lines
		$logs[$name] = [];
		$log_content = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if ($log_content) {
			$logs[$name] = array_slice($log_content, -50);
		}
	}
}

// Compile all diagnostic results
$diagnostics = [
	'success' => true,
	'timestamp' => date('Y-m-d H:i:s'),
	'test_duration' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
	'server_info' => $server_info,
	'config_files' => $config_files,
	'endpoints' => $endpoints,
	'session_test' => $session_test,
	'db_test' => $db_test,
	'rewrite_test' => $rewrite_test,
	'environment' => $environment,
	'logs' => $logs,
	'diagnostic_log' => $log
];

// Output the diagnostics as JSON
echo json_encode($diagnostics, JSON_PRETTY_PRINT);
