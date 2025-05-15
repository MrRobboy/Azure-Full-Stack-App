<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Environnement : 'development' ou 'production'
define('ENVIRONMENT', 'development');

// Gestion des erreurs
if (ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Configuration de la base de données SQL Server
define('SQL_SERVER', 'sql-esgi-app.database.windows.net'); // Azure SQL Server host
define('SQL_DATABASE', 'sqldb-esgi-app');
define('SQL_USER', 'sqladmin'); // Modifiez selon votre configuration
define('SQL_PASSWORD', 'Cisco123');
define('APP_ENV', 'production');
define('CORS_ALLOWED', 'app-frontend-esgi-app.azurewebsite.net'); // Port standard pour SQL Server

// Définition pour la rétrocompatibilité avec le code existant
define('DB_HOST', SQL_SERVER);
define('DB_NAME', SQL_DATABASE);
define('DB_USER', SQL_USER);
define('DB_PASS', SQL_PASSWORD);
define('DB_PORT', '1433'); // Port standard pour SQL Server

// Indiquer le type de base de données
define('DB_TYPE', 'sqlsrv'); // sqlsrv pour SQL Server

// URL de l'API pour communication front ↔ back
define('API_BASE_URL', 'https://app-backend-esgi-app.azurewebsites.net/api');

// Chemins d'accès
define('BASE_PATH', dirname(__DIR__));
define('FRONT_PATH', dirname(BASE_PATH) . '/front');
define('BACK_PATH', BASE_PATH);

// Initialisation de session


// Logs utiles pour debug
if (ENVIRONMENT === 'development') {
    error_log("Configuration de la base de données :");
    error_log("SQL_SERVER = " . SQL_SERVER);
    error_log("SQL_USER = " . SQL_USER);
    error_log("SQL_DATABASE = " . SQL_DATABASE);
    error_log("SQL_PASSWORD est " . (empty(SQL_PASSWORD) ? "vide" : "défini"));
    error_log("DB_HOST = " . DB_HOST);
    error_log("DB_NAME = " . DB_NAME);
    error_log("API_BASE_URL = " . API_BASE_URL);
}
