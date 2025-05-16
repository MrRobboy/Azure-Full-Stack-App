<?php
// Test des proxies disponibles
header('Content-Type: application/json');

// La réponse contient l'état des proxies
$response = [
	'status' => 'ok',
	'message' => 'Test proxy is working',
	'timestamp' => date('Y-m-d H:i:s'),
	'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
	'php_version' => PHP_VERSION,
	'proxies' => [
		'api-bridge.php' => file_exists(__DIR__ . '/api-bridge.php'),
		'azure-proxy.php' => file_exists(__DIR__ . '/azure-proxy.php'),
		'simple-proxy.php' => file_exists(__DIR__ . '/simple-proxy.php'),
		'simplified-jwt-bridge.php' => file_exists(__DIR__ . '/simplified-jwt-bridge.php')
	]
];

// Journalisation
error_log("Test proxy accessed: " . json_encode($response));

// Envoi de la réponse
echo json_encode($response, JSON_PRETTY_PRINT);
