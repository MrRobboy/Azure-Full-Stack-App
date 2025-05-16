<?php
// Simple status file to test proxy operation
header('Content-Type: application/json');
echo json_encode([
	'success' => true,
	'message' => 'Status check successful',
	'timestamp' => date('Y-m-d H:i:s'),
	'server' => $_SERVER['SERVER_NAME'],
	'info' => 'This status page can be used to test if the proxy is working correctly'
]);
