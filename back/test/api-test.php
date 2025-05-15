<?php

/**
 * API Test Script
 * 
 * Test script to validate API functionality and debug issues.
 */

header('Content-Type: application/json');

// Test parameters
$apiTests = [
	'environment' => [
		'php_version' => phpversion(),
		'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
		'request_time' => date('Y-m-d H:i:s'),
		'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
		'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
		'http_host' => $_SERVER['HTTP_HOST'] ?? 'Unknown',
	],
	'database' => [
		'status' => 'pending',
		'message' => 'Testing database connection...'
	],
	'routes' => [
		'status' => 'pending',
		'message' => 'Testing route functionality...'
	]
];

// Test database connection
try {
	require_once __DIR__ . '/../config/config.php';
	require_once __DIR__ . '/../services/DatabaseService.php';

	$db = DatabaseService::getInstance();
	$connection = $db->getConnection();

	// Simple query to test connection
	$query = "SELECT 1 AS test";
	$stmt = $connection->query($query);
	$result = $stmt->fetch(PDO::FETCH_ASSOC);

	if ($result && isset($result['test']) && $result['test'] === 1) {
		$apiTests['database']['status'] = 'success';
		$apiTests['database']['message'] = 'Database connection successful';

		// Try to get table information
		try {
			$query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
			$stmt = $connection->query($query);
			$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
			$apiTests['database']['tables'] = $tables;
			$apiTests['database']['tables_count'] = count($tables);
		} catch (Exception $e) {
			$apiTests['database']['tables_error'] = $e->getMessage();
		}
	} else {
		$apiTests['database']['status'] = 'error';
		$apiTests['database']['message'] = 'Database query returned unexpected result';
	}
} catch (Exception $e) {
	$apiTests['database']['status'] = 'error';
	$apiTests['database']['message'] = 'Database connection failed: ' . $e->getMessage();
}

// Test API routes
try {
	// Check if API router file exists
	if (file_exists(__DIR__ . '/../routes/api.php')) {
		$apiTests['routes']['status'] = 'success';
		$apiTests['routes']['message'] = 'API router file exists';

		// List of expected endpoints
		$expectedEndpoints = ['classes', 'profs', 'matieres', 'examens', 'notes', 'eleves', 'users'];
		$apiTests['routes']['endpoints'] = $expectedEndpoints;
	} else {
		$apiTests['routes']['status'] = 'error';
		$apiTests['routes']['message'] = 'API router file not found';
	}
} catch (Exception $e) {
	$apiTests['routes']['status'] = 'error';
	$apiTests['routes']['message'] = 'Error testing routes: ' . $e->getMessage();
}

// Add overall status
$apiTests['status'] = $apiTests['database']['status'] === 'success' && $apiTests['routes']['status'] === 'success'
	? 'success'
	: 'error';
$apiTests['message'] = $apiTests['status'] === 'success'
	? 'All tests passed successfully'
	: 'Some tests failed. See details.';

// Return test results
echo json_encode($apiTests, JSON_PRETTY_PRINT);
