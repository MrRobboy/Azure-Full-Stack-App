<?php
// Endpoint de statut pour vérifier la disponibilité du backend et de la base de données

// Gérer les requêtes CORS preflight OPTIONS en priorité
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Envoyer les en-têtes CORS pour les requêtes preflight
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: https://app-frontend-esgi-app.azurewebsites.net');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');  // Cache for 24 hours
    http_response_code(200);
    exit;
}

// Pour les requêtes normales
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://app-frontend-esgi-app.azurewebsites.net');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

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
        'type' => defined('DB_TYPE') ? DB_TYPE : 'sqlsrv',
        'host' => defined('SQL_SERVER') ? SQL_SERVER : 'Non défini',
        'name' => defined('SQL_DATABASE') ? SQL_DATABASE : 'Non défini',
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
                'db_type' => defined('DB_TYPE') ? DB_TYPE : 'sqlsrv',
                'db_host' => defined('SQL_SERVER') ? SQL_SERVER : 'Non défini',
                'db_name' => defined('SQL_DATABASE') ? SQL_DATABASE : 'Non défini',
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
                'db_type' => defined('DB_TYPE') ? DB_TYPE : 'sqlsrv',
                'db_host' => defined('SQL_SERVER') ? SQL_SERVER : 'Non défini',
                'db_name' => defined('SQL_DATABASE') ? SQL_DATABASE : 'Non défini'
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
