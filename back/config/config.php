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
define('DB_HOST', 'localhost'); // Ou l'adresse IP/nom du serveur SQL
define('DB_NAME', 'sqldb-esgi-app');
define('DB_USER', 'sqladmin'); // Modifiez selon votre configuration
define('DB_PASS', 'Cisco123');
define('DB_PORT', '1433'); // Port standard pour SQL Server

// Indiquer le type de base de données
define('DB_TYPE', 'sqlsrv'); // sqlsrv pour SQL Server

// URL de l'API pour communication front ↔ back
define('API_BASE_URL', 'http://localhost:727/api');

// Chemins d'accès
define('BASE_PATH', dirname(__DIR__));
define('FRONT_PATH', dirname(BASE_PATH) . '/front');
define('BACK_PATH', BASE_PATH);

// Initialisation de session


// Logs utiles pour debug
if (ENVIRONMENT === 'development') {
    error_log("Configuration de la base de données :");
    error_log("DB_HOST = " . DB_HOST);
    error_log("DB_USER = " . DB_USER);
    error_log("DB_NAME = " . DB_NAME);
    error_log("DB_PASS est " . (empty(DB_PASS) ? "vide" : "défini"));
    error_log("API_BASE_URL = " . API_BASE_URL);
}
