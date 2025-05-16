<?php

/**
 * Session Debug Tool
 * Shows the current session data and server environment information
 */
session_start();

// Set JSON content type by default
$format = isset($_GET['format']) ? $_GET['format'] : 'json';

if ($format === 'html') {
	header('Content-Type: text/html');
} else {
	header('Content-Type: application/json');
}

// Compile debug information
$debug_info = [
	'timestamp' => date('Y-m-d H:i:s'),
	'session' => [
		'id' => session_id(),
		'status' => session_status(),
		'active' => session_status() === PHP_SESSION_ACTIVE,
		'data' => $_SESSION,
		'isset_user' => isset($_SESSION['user']),
		'isset_token' => isset($_SESSION['token'])
	],
	'cookies' => $_COOKIE,
	'server' => [
		'php_version' => PHP_VERSION,
		'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
		'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
		'http_host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
		'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
		'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
	]
];

// Test session write
if (isset($_GET['test'])) {
	$_SESSION['test_value'] = 'Session write test at ' . date('Y-m-d H:i:s');
	$debug_info['test'] = 'Session write attempted';
}

// Output format
if ($format === 'html') {
	// HTML output for human readability
	echo '<!DOCTYPE html>
<html>
<head>
    <title>Session Debug Information</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #2c3e50; }
        h2 { color: #3498db; margin-top: 20px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow: auto; }
        .key { color: #e74c3c; font-weight: bold; }
        .string { color: #27ae60; }
        .number { color: #f39c12; }
        .boolean { color: #8e44ad; }
        .null { color: #95a5a6; }
        .toolbar { margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Session Debug Information</h1>
    
    <div class="toolbar">
        <a href="?format=html">HTML View</a> | 
        <a href="?format=json">JSON View</a> | 
        <a href="?format=html&test=1">Test Session Write</a> |
        <a href="dashboard.php">Return to Dashboard</a> |
        <a href="login.php">Go to Login</a>
    </div>
    
    <h2>Session Information</h2>
    <pre>Session ID: ' . htmlspecialchars(session_id()) . '
Status: ' . session_status() . ' (Active: ' . (session_status() === PHP_SESSION_ACTIVE ? 'Yes' : 'No') . ')
User Set: ' . (isset($_SESSION['user']) ? 'Yes' : 'No') . '
Token Set: ' . (isset($_SESSION['token']) ? 'Yes' : 'No') . '</pre>
    
    <h2>Session Data</h2>
    <pre>' . htmlspecialchars(var_export($_SESSION, true)) . '</pre>
    
    <h2>Cookies</h2>
    <pre>' . htmlspecialchars(var_export($_COOKIE, true)) . '</pre>
    
    <h2>Server Information</h2>
    <pre>' . htmlspecialchars(var_export($debug_info['server'], true)) . '</pre>
</body>
</html>';
} else {
	// JSON output for API use
	echo json_encode($debug_info, JSON_PRETTY_PRINT);
}
