<?php

/**
 * Configuration du Proxy
 * 
 * Ce fichier contient toutes les configurations nécessaires pour le proxy
 * et la gestion de la sécurité.
 */

// Vérification des extensions requises
if (!extension_loaded('curl')) {
	die('Extension cURL requise');
}

// Configuration de l'URL du backend
define('BACKEND_BASE_URL', 'https://app-backend-esgi-app.azurewebsites.net');

// Configuration CORS
define('CORS_CONFIG', [
	'allowed_origins' => ['*'],
	'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
	'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'Origin'],
	'exposed_headers' => ['X-Rate-Limit-Remaining', 'X-Rate-Limit-Reset'],
	'max_age' => 86400,
	'allow_credentials' => true
]);

// Configuration des timeouts
define('CURL_TIMEOUT', 30);
define('CURL_CONNECT_TIMEOUT', 10);

// Configuration du logging
define('LOG_CONFIG', [
	'enabled' => true,
	'file' => __DIR__ . '/../logs/proxy.log',
	'level' => 'debug'
]);

// Configuration de sécurité
define('SECURITY_CONFIG', [
	'rate_limit' => [
		'enabled' => true,
		'max_requests' => 1000,
		'window' => 3600
	],
	'input_validation' => [
		'enabled' => true,
		'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
		'max_length' => 1000
	],
	'headers' => [
		'X-Content-Type-Options: nosniff',
		'X-Frame-Options: DENY',
		'X-XSS-Protection: 1; mode=block',
		'Strict-Transport-Security: max-age=31536000; includeSubDomains',
		'Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\'; style-src \'self\' \'unsafe-inline\';',
		'Access-Control-Allow-Origin: *',
		'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS',
		'Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin',
		'Access-Control-Max-Age: 86400',
		'Access-Control-Allow-Credentials: true'
	]
]);

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
define('DISPLAY_ERRORS', false);  // Ne pas afficher les erreurs en production
define('LOG_ERRORS', true);
define('ERROR_REPORTING', E_ALL);

// Fonction pour obtenir les headers CORS
function getCorsHeaders()
{
	$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
	return [
		'Access-Control-Allow-Origin' => $origin,
		'Access-Control-Allow-Methods' => implode(', ', CORS_CONFIG['allowed_methods']),
		'Access-Control-Allow-Headers' => implode(', ', CORS_CONFIG['allowed_headers']),
		'Access-Control-Expose-Headers' => implode(', ', CORS_CONFIG['exposed_headers']),
		'Access-Control-Max-Age' => CORS_CONFIG['max_age'],
		'Access-Control-Allow-Credentials' => CORS_CONFIG['allow_credentials'] ? 'true' : 'false'
	];
}

// Fonction pour configurer le logging
function setupLogging()
{
	if (LOG_CONFIG['enabled']) {
		$logDir = dirname(LOG_CONFIG['file']);
		if (!is_dir($logDir)) {
			mkdir($logDir, 0755, true);
		}
		error_log("Logging enabled. Log file: " . LOG_CONFIG['file']);
	}
}

// Fonction pour valider les entrées
function validateInput($input)
{
	if (!SECURITY_CONFIG['input_validation']['enabled']) {
		return $input;
	}

	if (strlen($input) > SECURITY_CONFIG['input_validation']['max_length']) {
		error_log("Input too long: " . substr($input, 0, 100) . "...");
		return false;
	}

	// Nettoyage basique
	$input = strip_tags($input);
	$input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

	return $input;
}

// Fonction pour vérifier la limite de taux
function checkRateLimit($ip)
{
	if (!SECURITY_CONFIG['rate_limit']['enabled']) {
		return true;
	}

	$cacheDir = sys_get_temp_dir() . '/rate_limit';
	if (!is_dir($cacheDir)) {
		mkdir($cacheDir, 0755, true);
	}

	$cacheFile = $cacheDir . '/' . md5($ip) . '.json';
	$now = time();
	$window = SECURITY_CONFIG['rate_limit']['window'];
	$maxRequests = SECURITY_CONFIG['rate_limit']['max_requests'];

	if (file_exists($cacheFile)) {
		$data = json_decode(file_get_contents($cacheFile), true);
		if ($data && $data['timestamp'] > ($now - $window)) {
			if ($data['count'] >= $maxRequests) {
				error_log("Rate limit exceeded for IP: $ip");
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
