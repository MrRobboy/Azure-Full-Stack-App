<?php
header('Content-Type: application/json');

// Check if the unified proxy file exists
$unifiedProxyPath = __DIR__ . '/unified-proxy.php';
$unifiedLoginPath = __DIR__ . '/unified-login.php';

$results = [
	'success' => true,
	'tests' => [
		'unified_proxy_exists' => file_exists($unifiedProxyPath),
		'unified_login_exists' => file_exists($unifiedLoginPath),
		'unified_proxy_size' => file_exists($unifiedProxyPath) ? filesize($unifiedProxyPath) : 0,
		'unified_login_size' => file_exists($unifiedLoginPath) ? filesize($unifiedLoginPath) : 0,
		'server_root' => $_SERVER['DOCUMENT_ROOT'],
		'script_filename' => $_SERVER['SCRIPT_FILENAME'],
		'current_directory' => __DIR__,
		'file_list' => scandir(__DIR__)
	]
];

echo json_encode($results, JSON_PRETTY_PRINT);
