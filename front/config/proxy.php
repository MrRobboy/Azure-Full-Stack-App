<?php

/**
 * Configuration centralisée pour le proxy API
 */

// Configuration du backend
define('BACKEND_BASE_URL', 'https://app-backend-esgi-app.azurewebsites.net/api');

// Configuration CORS
define('CORS_ALLOWED_ORIGINS', [
	'https://app-frontend-esgi-app.azurewebsites.net',
	'http://localhost:8080'  // Pour le développement local
]);

// Configuration des timeouts
define('CURL_TIMEOUT', 30);  // Timeout en secondes
define('CURL_CONNECT_TIMEOUT', 10);  // Timeout de connexion en secondes

// Configuration du logging
define('LOG_DIR', __DIR__ . '/../logs');
define('LOG_LEVEL', 'ERROR');  // DEBUG, INFO, WARNING, ERROR
define('LOG_FILE', LOG_DIR . '/proxy.log');

// Configuration SSL
define('SSL_VERIFY_PEER', true);
define('SSL_VERIFY_HOST', 2);

// Headers par défaut
define('DEFAULT_HEADERS', [
	'Content-Type: application/json',
	'Accept: application/json',
	'X-Requested-With: XMLHttpRequest'
]);

// Configuration des erreurs
define('DISPLAY_ERRORS', false);  // false en production
define('LOG_ERRORS', true);
define('ERROR_REPORTING', E_ALL);

// Fonction pour obtenir les headers CORS appropriés
function getCorsHeaders()
{
	$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
	$allowed = in_array($origin, CORS_ALLOWED_ORIGINS);

	return [
		'Access-Control-Allow-Origin: ' . ($allowed ? $origin : CORS_ALLOWED_ORIGINS[0]),
		'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS',
		'Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With',
		'Access-Control-Max-Age: 86400'  // 24 heures
	];
}

// Fonction pour configurer le logging
function setupLogging()
{
	if (!file_exists(LOG_DIR)) {
		mkdir(LOG_DIR, 0755, true);
	}

	ini_set('log_errors', '1');
	ini_set('error_log', LOG_FILE);
	error_reporting(ERROR_REPORTING);
	ini_set('display_errors', DISPLAY_ERRORS ? '1' : '0');
}
