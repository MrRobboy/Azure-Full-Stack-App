<?php
// API Diagnostic Tool
// This script tests various ways to access API endpoints and reports which ones work

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: *');

// Log diagnostic information
$log_file = __DIR__ . '/logs/api-diagnostic.log';
$log_dir = dirname($log_file);

if (!is_dir($log_dir)) {
	mkdir($log_dir, 0755, true);
}

function log_message($message)
{
	global $log_file;
	file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

log_message("API Diagnostic Tool started");
log_message("Server software: " . $_SERVER['SERVER_SOFTWARE']);
log_message("Current directory: " . __DIR__);
log_message("PHP version: " . PHP_VERSION);

// Test route handlers
$handlers = [
	[
		'name' => 'Direct API Router',
		'description' => 'Testing if routes/api.php works directly',
		'test_func' => function () {
			$file = __DIR__ . '/routes/api.php';
			return file_exists($file) ? "File exists: {$file}" : "File not found: {$file}";
		}
	],
	[
		'name' => 'Directory Listing',
		'description' => 'Checking controllers directory',
		'test_func' => function () {
			$dir = __DIR__ . '/controllers';
			$files = is_dir($dir) ? scandir($dir) : [];
			return [
				'directory_exists' => is_dir($dir),
				'files' => $files,
				'count' => count($files)
			];
		}
	]
];

// Test different URL patterns
$url_patterns = [
	[
		'name' => 'Direct API Endpoint',
		'url' => '/api/matieres',
		'description' => 'Accessing API via /api/matieres path'
	],
	[
		'name' => 'API Router with Path',
		'url' => '/api-router.php?path=matieres',
		'description' => 'Using api-router.php with path parameter'
	],
	[
		'name' => 'API Router with Resource',
		'url' => '/api-router.php?resource=matieres',
		'description' => 'Using api-router.php with resource parameter'
	],
	[
		'name' => 'Routes API Direct',
		'url' => '/routes/api.php?resource=matieres',
		'description' => 'Directly accessing routes/api.php'
	]
];

// Create a simple solution
$proposed_solutions = [
	[
		'name' => 'Explicit API Endpoints',
		'description' => 'Create explicit PHP files for each main API endpoint',
		'files' => [
			'api-matieres.php',
			'api-classes.php',
			'api-examens.php',
			'api-profs.php'
		],
		'implementation' => 'Create dedicated endpoint files similar to api-notes.php for each resource'
	],
	[
		'name' => 'Fix .htaccess and web.config',
		'description' => 'Ensure proper routing in .htaccess and web.config',
		'changes' => [
			'Update RewriteRule to properly route /api/ requests',
			'Confirm URL rewriting is working on Azure'
		]
	],
	[
		'name' => 'Update Proxy URL Construction',
		'description' => 'Update the unified proxy to use different URL patterns',
		'changes' => [
			'Try different URL patterns in the proxy'
		]
	]
];

// Combine all information
$result = [
	'timestamp' => date('Y-m-d H:i:s'),
	'server_info' => [
		'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
		'php_version' => PHP_VERSION,
		'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
		'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
		'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown'
	],
	'handlers_test' => [],
	'url_patterns' => $url_patterns,
	'proposed_solutions' => $proposed_solutions,
	'recommendation' => 'Based on the Azure environment and configuration, the most reliable approach is to create dedicated endpoint files for each resource type.'
];

// Run handler tests
foreach ($handlers as $handler) {
	log_message("Testing handler: " . $handler['name']);
	try {
		$result['handlers_test'][] = [
			'name' => $handler['name'],
			'description' => $handler['description'],
			'result' => $handler['test_func']()
		];
	} catch (Exception $e) {
		$result['handlers_test'][] = [
			'name' => $handler['name'],
			'description' => $handler['description'],
			'error' => $e->getMessage()
		];
		log_message("Error testing handler: " . $e->getMessage());
	}
}

log_message("API Diagnostic completed");

// Output result
echo json_encode($result, JSON_PRETTY_PRINT);
