<?php
// Test SQL Server Connection Script for Azure SQL Database
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to output JSON response
function output_json($success, $message, $data = [])
{
	$response = [
		'success' => $success,
		'message' => $message,
		'timestamp' => date('Y-m-d H:i:s'),
		'data' => $data
	];

	echo json_encode($response, JSON_PRETTY_PRINT);
	exit;
}

// Log function
function log_message($message)
{
	error_log("[" . date('Y-m-d H:i:s') . "] SQL Test: " . $message);
}

// Try to load configuration
try {
	if (file_exists(__DIR__ . '/../config/config.php')) {
		require_once __DIR__ . '/../config/config.php';
		log_message("Configuration file loaded successfully");
	} else {
		output_json(false, "Configuration file not found", [
			'error' => "The file config.php could not be found in " . __DIR__ . "/../config/"
		]);
	}
} catch (Exception $e) {
	output_json(false, "Error loading configuration", [
		'error' => $e->getMessage(),
		'trace' => $e->getTraceAsString()
	]);
}

// Check if SQL Server constants are defined
if (!defined('SQL_SERVER') || !defined('SQL_DATABASE') || !defined('SQL_USER') || !defined('SQL_PASSWORD')) {
	output_json(false, "SQL Server configuration constants are not defined", [
		'defined_constants' => array_intersect_key(get_defined_constants(true)['user'], array_flip(['SQL_SERVER', 'SQL_DATABASE', 'SQL_USER', 'DB_TYPE']))
	]);
}

// Test connection using PDO
try {
	log_message("Attempting to connect to SQL Server: " . SQL_SERVER);

	$connection_string = "sqlsrv:Server=" . SQL_SERVER . ";Database=" . SQL_DATABASE;
	$pdo = new PDO($connection_string, SQL_USER, SQL_PASSWORD);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	log_message("Connection successful");

	// Test simple query
	$query = "SELECT 1 AS test";
	$stmt = $pdo->query($query);
	$result = $stmt->fetch(PDO::FETCH_ASSOC);

	if ($result && isset($result['test']) && $result['test'] === 1) {
		log_message("Basic query test successful");

		// Get database info
		$info = [
			'server_version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
			'client_version' => $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION),
			'connection_status' => $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS),
			'driver_name' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME)
		];

		// Test table existence
		try {
			$query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
			$stmt = $pdo->query($query);
			$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
			$info['tables'] = $tables;
			$info['tables_count'] = count($tables);

			log_message("Found " . count($tables) . " tables");

			// Check if important tables exist
			$required_tables = ['classes', 'profs', 'matieres', 'examens', 'notes', 'eleves', 'users'];
			$missing_tables = array_diff($required_tables, array_map('strtolower', $tables));

			if (empty($missing_tables)) {
				output_json(true, "SQL Server connection and database schema test successful", [
					'connection' => $info,
					'server' => SQL_SERVER,
					'database' => SQL_DATABASE
				]);
			} else {
				output_json(false, "SQL Server connection successful but some important tables are missing", [
					'connection' => $info,
					'missing_tables' => $missing_tables,
					'server' => SQL_SERVER,
					'database' => SQL_DATABASE
				]);
			}
		} catch (Exception $e) {
			output_json(false, "SQL Server connection successful but error checking tables", [
				'connection' => $info,
				'error' => $e->getMessage(),
				'server' => SQL_SERVER,
				'database' => SQL_DATABASE
			]);
		}
	} else {
		output_json(false, "SQL Server connection successful but basic query test failed", [
			'server' => SQL_SERVER,
			'database' => SQL_DATABASE,
			'query_result' => $result
		]);
	}
} catch (PDOException $e) {
	log_message("PDO Connection error: " . $e->getMessage());

	output_json(false, "Failed to connect to SQL Server", [
		'error' => $e->getMessage(),
		'code' => $e->getCode(),
		'server' => SQL_SERVER ?? 'Not defined',
		'database' => SQL_DATABASE ?? 'Not defined',
		'trace' => $e->getTraceAsString()
	]);
} catch (Exception $e) {
	log_message("General error: " . $e->getMessage());

	output_json(false, "An unexpected error occurred", [
		'error' => $e->getMessage(),
		'code' => $e->getCode(),
		'trace' => $e->getTraceAsString()
	]);
}
