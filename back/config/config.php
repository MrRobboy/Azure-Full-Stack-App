<?php
// Environnement : 'development' ou 'production'
define('ENVIRONMENT', 'production');

// Gestion des erreurs
if (ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Configuration de la base de données Azure SQL
define('DB_HOST', getenv('DB_HOST'));           // Ex: sql-srv-fullstack-prod.database.windows.net
define('DB_NAME', getenv('DB_NAME'));           // Ex: sql-db-fullstack-prod
define('DB_USER', getenv('DB_USER'));           // Ex: esgi
define('DB_PASS', getenv('DB_PASS'));           // Mot de passe stocké dans App Service

// URL de l’API pour communication front ↔ back
define('API_BASE_URL', 'https://backend-votreapp.azurewebsites.net/api'); // Remplacer avec votre nom exact d’App Service

// Chemins d’accès (à ajuster selon votre structure)
define('BASE_PATH', dirname(__DIR__));
define('FRONT_PATH', dirname(BASE_PATH) . '/front');
define('BACK_PATH', BASE_PATH);

// Initialisation de session (si nécessaire)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Logs utiles pour debug (éviter en production)
if (ENVIRONMENT === 'development') {
    error_log("Configuration de la base de données :");
    error_log("DB_HOST = " . DB_HOST);
    error_log("DB_USER = " . DB_USER);
    error_log("DB_NAME = " . DB_NAME);
    error_log("DB_PASS est " . (empty(DB_PASS) ? "vide" : "défini"));
}
