<?php
// Simple JSONP endpoint as fallback when CORS isn't working
error_log("JSONP test script accessed - " . date('Y-m-d H:i:s'));

// Get the callback function name
$callback = isset($_GET['callback']) ? $_GET['callback'] : 'callback';

// Sanitize the callback function name to prevent XSS
$callback = preg_replace("/[^a-zA-Z0-9_]/", "", $callback);

// Data to return
$data = [
	'success' => true,
	'method' => 'JSONP',
	'message' => 'JSONP test successful',
	'timestamp' => date('Y-m-d H:i:s'),
	'remote_addr' => $_SERVER['REMOTE_ADDR'],
	'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
	'php_version' => phpversion()
];

// Set proper content type for JavaScript
header('Content-Type: application/javascript');

// Return JSONP formatted response
echo $callback . '(' . json_encode($data) . ');';
