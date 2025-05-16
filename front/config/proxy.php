<?php

/**
 * Configuration du Proxy
 * 
 * Ce fichier contient toutes les configurations nécessaires pour le proxy
 * et la gestion de la sécurité.
 */

// Configuration de l'URL du backend
define('BACKEND_BASE_URL', 'https://app-backend-esgi-app.azurewebsites.net/api');

// Configuration CORS
define('CORS_ALLOWED_ORIGINS', [
	'https://app-frontend-esgi-app.azurewebsites.net',
	'http://localhost:8080'  // Pour le développement local
]);

// Configuration des timeouts
define('CURL_TIMEOUT', 30);  // Timeout global en secondes
define('CURL_CONNECT_TIMEOUT', 10);  // Timeout de connexion en secondes

// Configuration du logging
define('LOG_DIR', __DIR__ . '/../logs');
define('LOG_LEVEL', 'INFO');  // DEBUG, INFO, WARNING, ERROR
define('LOG_FILE', LOG_DIR . '/proxy.log');

// Configuration SSL
define('SSL_VERIFY_PEER', true);  // Vérification du certificat SSL
define('SSL_VERIFY_HOST', 2);  // Vérification stricte du nom d'hôte

// Configuration des headers par défaut
define('DEFAULT_HEADERS', [
	'Content-Type: application/json',
	'Accept: application/json',
	'X-Requested-With: XMLHttpRequest'
]);

// Configuration de la sécurité
define('SECURITY_CONFIG', [
	'rate_limit' => [
		'enabled' => true,
		'max_requests' => 100,
		'time_window' => 60 // secondes
	],
	'input_validation' => [
		'enabled' => true,
		'max_length' => 1000,
		'allowed_methods' => ['GET', 'POST', 'OPTIONS']
	],
	'headers' => [
		'X-Content-Type-Options: nosniff',
		'X-Frame-Options: DENY',
		'X-XSS-Protection: 1; mode=block',
		'Strict-Transport-Security: max-age=31536000; includeSubDomains',
		'Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\'; style-src \'self\' \'unsafe-inline\';'
	]
]);

// Configuration des erreurs
define('DISPLAY_ERRORS', false);  // Ne pas afficher les erreurs en production
define('LOG_ERRORS', true);
define('ERROR_REPORTING', E_ALL);

// Fonction pour obtenir les headers CORS
function getCorsHeaders()
{
	$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
	$allowed = in_array($origin, CORS_ALLOWED_ORIGINS);

	return [
		'Access-Control-Allow-Origin' => $allowed ? $origin : CORS_ALLOWED_ORIGINS[0],
		'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
		'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
		'Access-Control-Max-Age' => '86400',  // 24 heures
		'Access-Control-Allow-Credentials' => 'true'
	];
}

// Fonction pour configurer le logging
function setupLogging()
{
	if (!file_exists(LOG_DIR)) {
		mkdir(LOG_DIR, 0755, true);
	}

	error_reporting(ERROR_REPORTING);
	ini_set('display_errors', DISPLAY_ERRORS);
	ini_set('log_errors', LOG_ERRORS);
	ini_set('error_log', LOG_FILE);
}

// Fonction pour valider les entrées
function validateInput($input)
{
	if (empty($input)) {
		return false;
	}

	// Vérifier la longueur
	if (strlen($input) > SECURITY_CONFIG['input_validation']['max_length']) {
		return false;
	}

	// Nettoyer l'entrée
	$input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

	return $input;
}

// Fonction pour vérifier la limite de taux
function checkRateLimit($ip)
{
	if (!SECURITY_CONFIG['rate_limit']['enabled']) {
		return true;
	}

	$cacheFile = LOG_DIR . '/rate_limit_' . md5($ip) . '.json';
	$now = time();

	if (file_exists($cacheFile)) {
		$data = json_decode(file_get_contents($cacheFile), true);
		if ($now - $data['timestamp'] < SECURITY_CONFIG['rate_limit']['time_window']) {
			if ($data['count'] >= SECURITY_CONFIG['rate_limit']['max_requests']) {
				return false;
			}
			$data['count']++;
		} else {
			$data = ['count' => 1, 'timestamp' => $now];
		}
	} else {
		$data = ['count' => 1, 'timestamp' => $now];
	}

	file_put_contents($cacheFile, json_encode($data));
	return true;
}

// Initialisation
setupLogging();
