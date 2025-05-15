<?php
// Simple status file for proxy testing
header('Content-Type: application/json');
echo json_encode([
	'success' => true,
	'status' => 'ok',
	'server' => $_SERVER['SERVER_NAME'],
	'message' => 'API status endpoint is operational',
	'timestamp' => date('Y-m-d H:i:s'),
	'version' => '1.0',
	'environment' => strpos($_SERVER['HTTP_HOST'] ?? '', 'azurewebsites.net') !== false ? 'azure' : 'local'
]);
