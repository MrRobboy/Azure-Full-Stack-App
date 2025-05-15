<?php
// Simple diagnostic file to verify deployment and PHP functionality
header('Content-Type: application/json');

// Get server information
$server_info = [
	'php_version' => PHP_VERSION,
	'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
	'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
	'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown',
	'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
	'time' => date('Y-m-d H:i:s'),
	'files_in_directory' => []
];

// Check if backend-proxy.php exists
$proxy_path = __DIR__ . '/backend-proxy.php';
$server_info['proxy_exists'] = file_exists($proxy_path);
$server_info['proxy_path'] = $proxy_path;
$server_info['proxy_readable'] = is_readable($proxy_path);
$server_info['proxy_size'] = $server_info['proxy_exists'] ? filesize($proxy_path) : 0;

// List files in the current directory
if ($handle = opendir(__DIR__)) {
	while (false !== ($entry = readdir($handle))) {
		if ($entry != "." && $entry != "..") {
			$server_info['files_in_directory'][] = [
				'name' => $entry,
				'size' => filesize(__DIR__ . '/' . $entry),
				'is_file' => is_file(__DIR__ . '/' . $entry),
				'is_readable' => is_readable(__DIR__ . '/' . $entry)
			];
		}
	}
	closedir($handle);
}

// Check curl availability
$server_info['curl_enabled'] = function_exists('curl_init');
$server_info['allow_url_fopen'] = ini_get('allow_url_fopen');

// Output the information
echo json_encode($server_info, JSON_PRETTY_PRINT);
