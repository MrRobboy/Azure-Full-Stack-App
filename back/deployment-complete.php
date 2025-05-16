<?php
// Deployment Complete / Diagnostic File
// This file confirms that deployment is complete and tests CORS settings

// IMPORTANT: Set CORS headers before anything else
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://app-frontend-esgi-app.azurewebsites.net');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

// Force cache control headers to prevent caching issues
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// Clear output buffer to ensure headers are sent immediately
if (ob_get_level()) ob_end_clean();

// Handle OPTIONS requests immediately
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

// Log request information
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
	mkdir($logDir, 0755, true);
}

$logFile = $logDir . '/deployment.log';
$logData = sprintf(
	"[%s] Deployment check: Method=%s, Origin=%s\n",
	date('Y-m-d H:i:s'),
	$_SERVER['REQUEST_METHOD'],
	isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'undefined'
);
file_put_contents($logFile, $logData, FILE_APPEND);

// Check all API files to confirm they exist
$apiFiles = [
	'api-auth-login.php',
	'api-notes.php',
	'api-router.php',
	'api-cors.php',
	'cors-test.php'
];

$fileStatus = [];
foreach ($apiFiles as $file) {
	$fileStatus[$file] = file_exists(__DIR__ . '/' . $file);
}

// Collect configuration information
$configInfo = [
	'success' => true,
	'message' => 'Deployment complete and verified',
	'timestamp' => date('Y-m-d H:i:s'),
	'headers_sent' => headers_list(),
	'files_status' => $fileStatus,
	'php_info' => [
		'version' => PHP_VERSION,
		'sapi' => PHP_SAPI,
		'os' => PHP_OS,
		'modules' => get_loaded_extensions()
	],
	'server_info' => [
		'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
		'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown',
		'hostname' => gethostname()
	],
	'cors_config' => [
		'allowed_origin' => 'https://app-frontend-esgi-app.azurewebsites.net',
		'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
		'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'Origin'],
		'allow_credentials' => true,
		'max_age' => 86400
	]
];

// Verify .htaccess exists
$configInfo['htaccess_exists'] = file_exists(__DIR__ . '/.htaccess');

// Verify web.config exists
$configInfo['web_config_exists'] = file_exists(__DIR__ . '/web.config');

// Output the diagnostic information
echo json_encode($configInfo, JSON_PRETTY_PRINT);
exit;
