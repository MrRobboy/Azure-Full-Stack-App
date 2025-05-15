<?php
// Special file for handling CORS in Azure App Service
error_log("Azure CORS handler accessed - " . date('Y-m-d H:i:s'));
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);

// Always set these headers regardless of the request type
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://app-frontend-esgi-app.azurewebsites.net');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400'); // 24 hours

// For OPTIONS requests, return 200 OK immediately
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	error_log("Responding to OPTIONS request with 200 OK");
	http_response_code(200);
	exit;
}

// Check for database test
if (isset($_GET['type']) && $_GET['type'] === 'db') {
	require_once __DIR__ . '/config/config.php';

	try {
		error_log("Testing database connection");
		require_once __DIR__ . '/services/DatabaseService.php';

		$db = DatabaseService::getInstance();
		$connection = $db->getConnection();

		// Get database info
		$db_info = [
			'success' => true,
			'message' => 'Connexion à la base de données réussie',
			'timestamp' => date('Y-m-d H:i:s'),
			'method' => $_SERVER['REQUEST_METHOD'],
			'db_type' => defined('DB_TYPE') ? DB_TYPE : 'sqlsrv',
			'db_host' => defined('SQL_SERVER') ? SQL_SERVER : 'Inconnu',
			'db_name' => defined('SQL_DATABASE') ? SQL_DATABASE : 'Inconnu',
		];

		// Try to get table information if possible
		try {
			$query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
			$stmt = $connection->query($query);
			$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
			$db_info['tables_count'] = count($tables);
			$db_info['tables'] = $tables;
		} catch (Exception $e) {
			$db_info['tables_error'] = "Impossible de lister les tables: " . $e->getMessage();
		}

		echo json_encode($db_info);
		exit;
	} catch (Exception $e) {
		echo json_encode([
			'success' => false,
			'message' => 'Échec de connexion à la base de données',
			'error' => $e->getMessage(),
			'timestamp' => date('Y-m-d H:i:s'),
			'method' => $_SERVER['REQUEST_METHOD'],
			'db_type' => defined('DB_TYPE') ? DB_TYPE : 'sqlsrv',
			'db_host' => defined('SQL_SERVER') ? SQL_SERVER : 'Inconnu',
			'db_name' => defined('SQL_DATABASE') ? SQL_DATABASE : 'Inconnu'
		]);
		exit;
	}
}

// Check for specific API resources
if (isset($_GET['resource'])) {
	require_once __DIR__ . '/config/config.php';
	require_once __DIR__ . '/routes/api.php';
	exit;
}

// For actual requests, return some data
echo json_encode([
	'success' => true,
	'message' => 'Azure CORS system is working',
	'timestamp' => date('Y-m-d H:i:s'),
	'method' => $_SERVER['REQUEST_METHOD'],
	'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
	'headers_sent' => headers_sent(),
	'headers_list' => headers_list()
]);
