<?php
// Désactivation de l'affichage des erreurs pour l'API
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Respons11');
define('DB_NAME', 'gestion_notes');

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
