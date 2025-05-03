<?php
// Configuration de l'environnement
define('ENVIRONMENT', 'development'); // 'development' ou 'production'

// Configuration de l'affichage des erreurs selon l'environnement
if (ENVIRONMENT === 'development') {
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
} else {
	ini_set('display_errors', 0);
	ini_set('display_startup_errors', 0);
	error_reporting(0);
}

// Configuration de la base de données
define('DB_HOST', 'localhost:3306');
define('DB_NAME', 'gestion_notes');
define('DB_USER', 'root');
define('DB_PASS', 'Respons11');

// Configuration des chemins
define('BASE_PATH', dirname(__DIR__));
define('FRONT_PATH', dirname(BASE_PATH) . '/front');
define('BACK_PATH', BASE_PATH);

// Configuration de l'API
define('API_BASE_URL', 'http://localhost:727/api');

// Log des paramètres de configuration
error_log("Configuration de la base de données :");
error_log("DB_HOST = " . DB_HOST);
error_log("DB_USER = " . DB_USER);
error_log("DB_NAME = " . DB_NAME);
error_log("DB_PASS est " . (empty(DB_PASS) ? "vide" : "défini"));

// Configuration de la session (uniquement si nécessaire)
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}
