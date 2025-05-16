<?php
// Deployment verification endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Log access for debugging
error_log("Deployment verification accessed: " . date('Y-m-d H:i:s'));

// Return configuration info
echo json_encode([
	'success' => true,
	'message' => 'Backend deployment is properly configured',
	'service' => 'ESGI Azure Backend',
	'api_version' => '1.0',
	'timestamp' => date('Y-m-d H:i:s'),
	'endpoints' => [
		'auth' => '/api/auth/login',
		'auth_get' => '/api/auth/check-credentials',
		'status' => '/api/status',
		'notes' => '/api/notes',
		'classes' => '/api/classes',
		'matieres' => '/api/matieres',
		'examens' => '/api/examens'
	],
	'routes_test' => [
		'direct_login' => 'direct-login.php',
		'api_bridge' => 'api-bridge.php',
		'simple_login' => 'simple-login.php'
	],
	'config' => [
		'cors_allowed_origin' => 'https://app-frontend-esgi-app.azurewebsites.net',
		'request_timeout' => 30,
		'debug_mode' => false
	]
]);
