<?php
// Endpoint de statut pour vérifier la disponibilité du backend et de la base de données
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://app-frontend-esgi-app.azurewebsites.net');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config/config.php';

// Récupérer les informations système pour le status
$status = [
    'success' => true,
    'message' => 'Le serveur backend est opérationnel',
    'timestamp' => date('Y-m-d H:i:s'),
    'environment' => ENVIRONMENT,
    'api_base_url' => API_BASE_URL,
    'php_version' => phpversion(),
    'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Inconnu',
    'database' => [
        'type' => defined('DB_TYPE') ? DB_TYPE : 'mysql',
        'host' => DB_HOST,
        'name' => DB_NAME,
        'connected' => false // Sera mis à jour ci-dessous
    ]
];

// Vérifier si le endpoint spécifique de base de données est demandé
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($requestPath, '/db-status') !== false) {
    try {
        require_once __DIR__ . '/services/DatabaseService.php';

        $db = DatabaseService::getInstance();
        $connection = $db->getConnection();

        // Tester une requête simple
        $query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
        $stmt = $connection->query($query);
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Si aucune exception n'est levée, la connexion est réussie
        echo json_encode([
            'success' => true,
            'message' => 'Connexion à la base de données réussie',
            'data' => [
                'db_type' => defined('DB_TYPE') ? DB_TYPE : 'mysql',
                'db_host' => DB_HOST,
                'db_name' => DB_NAME,
                'tables_count' => count($tables),
                'tables' => $tables
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Échec de connexion à la base de données',
            'error' => $e->getMessage(),
            'data' => [
                'db_type' => defined('DB_TYPE') ? DB_TYPE : 'mysql',
                'db_host' => DB_HOST,
                'db_name' => DB_NAME
            ]
        ]);
    }
    exit;
}

// Pour le endpoint /status standard
try {
    // Vérifier la connexion à la base de données
    require_once __DIR__ . '/services/DatabaseService.php';

    $db = DatabaseService::getInstance();
    $connection = $db->getConnection();

    // Si aucune exception n'est levée, la connexion est réussie
    $status['database']['connected'] = true;
} catch (Exception $e) {
    $status['database']['connected'] = false;
    $status['database']['error'] = $e->getMessage();
}

echo json_encode($status);
