<?php
// Environnement : 'development' ou 'production'
define('ENVIRONMENT', 'development'); // Changé en development pour le debug

// Gestion des erreurs
if (ENVIRONMENT === 'development') {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Configuration de la base de données MariaDB
define('DB_HOST', 'localhost');           // Hôte local de MariaDB
define('DB_NAME', 'gestion_notes');           // Nom de votre base de données
define('DB_USER', 'root');               // Utilisateur par défaut (à changer en production)
define('DB_PASS', 'Respons11');                   // Mot de passe (à configurer en production)

// URL de l'API pour communication front ↔ back
define('API_BASE_URL', 'http://localhost:727/api');

// Chemins d'accès (à ajuster selon votre structure)
define('BASE_PATH', dirname(__DIR__));
define('FRONT_PATH', dirname(BASE_PATH) . '/front');
define('BACK_PATH', BASE_PATH);

// Initialisation de session (si nécessaire)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Logs utiles pour debug
if (ENVIRONMENT === 'development') {
    error_log("Configuration de la base de données :");
    error_log("DB_HOST = " . DB_HOST);
    error_log("DB_USER = " . DB_USER);
    error_log("DB_NAME = " . DB_NAME);
    error_log("DB_PASS est " . (empty(DB_PASS) ? "vide" : "défini"));
}
