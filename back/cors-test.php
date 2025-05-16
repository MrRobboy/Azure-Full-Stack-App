<?php
// CORS Debug and Test File
// This file is specifically designed to test CORS issues on Azure

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

// Create log file if needed
if (!is_dir(__DIR__ . '/logs')) {
	mkdir(__DIR__ . '/logs', 0755, true);
}

// Log the request
$logFile = __DIR__ . '/logs/cors_test.log';
$logMessage = sprintf(
	"[%s] CORS Test: Method=%s, URI=%s, Origin=%s\n",
	date('Y-m-d H:i:s'),
	$_SERVER['REQUEST_METHOD'],
	$_SERVER['REQUEST_URI'],
	isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'undefined'
);
file_put_contents($logFile, $logMessage, FILE_APPEND);

// Get all request headers and log them
$headers = getallheaders();
file_put_contents($logFile, "Headers: " . json_encode($headers, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

// Collect system and server information
$serverInfo = [
	'success' => true,
	'message' => 'CORS test response',
	'timestamp' => date('Y-m-d H:i:s'),
	'headers_sent' => headers_list(),
	'server' => [
		'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
		'php_version' => PHP_VERSION,
		'sapi' => PHP_SAPI,
		'os' => PHP_OS,
	],
	'request' => [
		'method' => $_SERVER['REQUEST_METHOD'],
		'uri' => $_SERVER['REQUEST_URI'],
		'origin' => $headers['Origin'] ?? null,
		'host' => $_SERVER['HTTP_HOST'] ?? null,
		'headers' => $headers
	],
	'cors_config' => [
		'allowed_origin' => 'https://app-frontend-esgi-app.azurewebsites.net',
		'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
		'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'Origin'],
		'allow_credentials' => true,
		'max_age' => 86400
	]
];

// Test if we can detect Nginx-specific headers
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	$serverInfo['server']['forwarded_for'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
}

// Check for known Azure-specific headers
$azureHeaders = [
	'X-ARR-SSL',
	'X-ARR-LOG-ID',
	'X-SITE-DEPLOYMENT-ID',
	'X-WAWS-UNENCODED-URL',
	'X-ORIGINAL-URL',
	'X-MS-REQUEST-ID',
	'WAS-DEFAULT-HOSTNAME',
	'X-FORWARDED-PROTO'
];

$serverInfo['azure'] = [];
foreach ($azureHeaders as $header) {
	$headerKey = 'HTTP_' . str_replace('-', '_', strtoupper($header));
	if (isset($_SERVER[$headerKey])) {
		$serverInfo['azure'][$header] = $_SERVER[$headerKey];
	}
}

// Output the response in JSON format
echo json_encode($serverInfo, JSON_PRETTY_PRINT);
exit;
