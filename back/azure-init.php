<?php
// Azure Initialization Script - For debugging Azure App Service routing issues
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Log environment variables and server info
error_log("Azure Init Script executed at: " . date('Y-m-d H:i:s'));
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("Server Software: " . $_SERVER['SERVER_SOFTWARE']);
error_log("PHP Version: " . PHP_VERSION);

// List of key files to check
$key_files = [
	'web.config',
	'.htaccess',
	'index.php',
	'routes/api.php',
	'controllers/AuthController.php'
];

// Check file existence and permissions
$file_status = [];
foreach ($key_files as $file) {
	$path = __DIR__ . '/' . $file;
	$file_status[$file] = [
		'exists' => file_exists($path),
		'readable' => is_readable($path),
		'size' => file_exists($path) ? filesize($path) : 0,
		'last_modified' => file_exists($path) ? date('Y-m-d H:i:s', filemtime($path)) : null
	];
}

// Check for proper routing
$route_testing = [
	'uri' => $_SERVER['REQUEST_URI'],
	'script_name' => $_SERVER['SCRIPT_NAME'],
	'script_filename' => $_SERVER['SCRIPT_FILENAME'],
	'query_string' => $_SERVER['QUERY_STRING'] ?? '',
	'params' => $_GET,
	'segments' => explode('/', trim($_SERVER['REQUEST_URI'], '/'))
];

// Check environment variables
$env_vars = getenv();
$filtered_env = [];
foreach ($env_vars as $key => $value) {
	// Exclude sensitive environment variables
	if (!preg_match('/(password|key|secret|token|credential)/i', $key)) {
		$filtered_env[$key] = $value;
	}
}

// Build response
$response = [
	'success' => true,
	'message' => 'Azure initialization script executed successfully',
	'timestamp' => date('Y-m-d H:i:s'),
	'server_info' => [
		'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
		'php_version' => PHP_VERSION,
		'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
		'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'
	],
	'file_status' => $file_status,
	'route_testing' => $route_testing,
	'environment' => [
		'APP_ENV' => getenv('APP_ENV') ?: 'Not set',
		'WEBSITE_SITE_NAME' => getenv('WEBSITE_SITE_NAME') ?: 'Not set',
		'WEBSITE_HOSTNAME' => getenv('WEBSITE_HOSTNAME') ?: 'Not set'
	]
];

// Output diagnostic information
echo json_encode($response, JSON_PRETTY_PRINT);

// Azure-specific initialization and configuration
error_log("Azure initialization script loading");

// Ensure errors are logged
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/azure_init_errors.log');

// Create logs directory if needed
if (!is_dir(__DIR__ . '/logs')) {
	mkdir(__DIR__ . '/logs', 0755, true);
}

// Authentication Bypass
// This will automatically set up authentication session without requiring credentials
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

// Set default authentication session
$_SESSION['prof_id'] = 1;
$_SESSION['prof_nom'] = 'Admin';
$_SESSION['prof_prenom'] = 'User';
$_SESSION['prof_email'] = 'admin@example.com';

error_log("Authentication bypass enabled - automatic login for all requests");

// Function to generate a JWT token (for APIs that need it)
function generateBypassJWT()
{
	// Header
	$header = json_encode([
		'typ' => 'JWT',
		'alg' => 'HS256'
	]);

	// Payload
	$payload = json_encode([
		'sub' => 1,
		'email' => 'admin@example.com',
		'role' => 'admin',
		'iat' => time(),
		'exp' => time() + (60 * 60 * 24) // 24 hours
	]);

	// Encode header and payload
	$base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
	$base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

	// Signature
	$signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'esgi_azure_secret_key', true);
	$base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

	// Complete token
	return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

// Some APIs might check for Authorization header with Bearer token
// This function will add the header if not present
function ensureAuthorizationHeader()
{
	$headers = getallheaders();
	if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
		// Add the header to $_SERVER for scripts that check there
		$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . generateBypassJWT();
		error_log("Added bypass JWT Authorization header");
	}
}

// Call this at the start to ensure authentication
ensureAuthorizationHeader();

// Log completion
error_log("Azure initialization completed with authentication bypass");
