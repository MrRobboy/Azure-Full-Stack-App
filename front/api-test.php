<?php

/**
 * API Test Endpoint
 * Tests direct connections to the backend API and reports results
 */

// Set headers for JSON response
header('Content-Type: application/json');

// Enable error reporting
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'logs/api_test_errors.log');

// Create logs directory if it doesn't exist
if (!file_exists('logs')) {
	mkdir('logs', 0755, true);
}

// Configuration
$backendUrl = 'https://app-backend-esgi-app.azurewebsites.net';
$testEndpoints = [
	[
		'name' => 'Status Check',
		'endpoint' => '/status.php',
		'method' => 'GET'
	],
	[
		'name' => 'API Status',
		'endpoint' => '/api/status',
		'method' => 'GET'
	],
	[
		'name' => 'User Profile',
		'endpoint' => '/api/user/profile',
		'method' => 'GET'
	],
	[
		'name' => 'Auth Check',
		'endpoint' => '/api/auth/check',
		'method' => 'GET'
	]
];

// Results array
$results = [];
$success_count = 0;
$failure_count = 0;

// Test each endpoint
foreach ($testEndpoints as $test) {
	$result = [
		'name' => $test['name'],
		'endpoint' => $test['endpoint'],
		'method' => $test['method'],
		'url' => $backendUrl . $test['endpoint'],
		'success' => false
	];

	try {
		// Log the attempt
		error_log("Testing {$test['name']} at {$test['endpoint']}");

		// Initialize cURL
		$ch = curl_init();

		// Set cURL options
		curl_setopt($ch, CURLOPT_URL, $backendUrl . $test['endpoint']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);

		// Set method specific options
		if ($test['method'] !== 'GET') {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $test['method']);
		}

		// Set headers
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Accept: application/json',
			'X-Requested-With: XMLHttpRequest',
			'X-Proxy-Forward: true',
			'User-Agent: ESGI-App-Proxy/1.0'
		]);

		// Pass cookies if any
		if (isset($_SERVER['HTTP_COOKIE'])) {
			curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE']);
		}

		// Execute the request
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		$error = curl_error($ch);
		curl_close($ch);

		// Process results
		$result['status_code'] = $info['http_code'];
		$result['success'] = ($info['http_code'] >= 200 && $info['http_code'] < 300);
		$result['error'] = $error;
		$result['response_time'] = $info['total_time'];

		// Try to parse response as JSON
		$responseData = json_decode($response, true);
		if ($responseData !== null) {
			$result['response'] = $responseData;
		} else {
			$result['response'] = substr($response, 0, 500); // First 500 chars of response
		}

		// Update counters
		if ($result['success']) {
			$success_count++;
		} else {
			$failure_count++;
		}
	} catch (Exception $e) {
		$result['success'] = false;
		$result['error'] = $e->getMessage();
		$failure_count++;
	}

	$results[] = $result;
}

// Output summary
$summary = [
	'timestamp' => date('Y-m-d H:i:s'),
	'backend_url' => $backendUrl,
	'tests_run' => count($testEndpoints),
	'success_count' => $success_count,
	'failure_count' => $failure_count,
	'success_rate' => (count($testEndpoints) > 0) ? ($success_count / count($testEndpoints) * 100) : 0,
	'results' => $results
];

echo json_encode($summary, JSON_PRETTY_PRINT);
