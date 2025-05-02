<?php
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

// Configuration de la session
session_start();
