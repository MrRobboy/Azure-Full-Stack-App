<?php

/**
 * URL Debug Tool
 * Cet outil aide à diagnostiquer les problèmes de construction d'URL dans le proxy unifié
 */

// Configuration identique au proxy
define('API_BASE_URL', 'https://app-backend-esgi-app.azurewebsites.net');
define('LOG_DIR', __DIR__ . '/logs');

// Création du répertoire de logs si nécessaire
if (!is_dir(LOG_DIR)) {
	mkdir(LOG_DIR, 0755, true);
}

// Headers pour réponse JSON
header('Content-Type: application/json; charset=utf-8');

// Fonction de journalisation
function logMessage($message, $data = null)
{
	$logFile = LOG_DIR . '/url-debug-' . date('Y-m-d') . '.log';
	$timestamp = date('[Y-m-d H:i:s]');
	$logMessage = $timestamp . ' ' . $message;

	if ($data !== null) {
		$logMessage .= ' ' . json_encode($data);
	}

	file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
}

// Tester différentes constructions d'URL
$endpoints = [
	'status',
	'auth/login',
	'auth/user',
	'matieres',
	'classes',
	'examens',
	'profs',
	'users',
	'notes',
	'privileges'
];

$results = [];

foreach ($endpoints as $endpoint) {
	// Méthode utilisée dans le proxy unifié
	$mainEndpoints = ['matieres', 'classes', 'examens', 'profs', 'users', 'notes', 'privileges'];
	$isMainEndpoint = false;

	foreach ($mainEndpoints as $mainEndpoint) {
		if (strpos($endpoint, $mainEndpoint) === 0) {
			$isMainEndpoint = true;
			break;
		}
	}

	// Construction d'URL comme dans unified-proxy.php
	$requestUrl = API_BASE_URL;

	if ($endpoint === 'auth/login') {
		$requestUrl .= '/api-auth-login.php';
	} elseif ($endpoint === 'auth/user') {
		$requestUrl .= '/api/auth/user';
	} elseif ($endpoint === 'status') {
		$requestUrl .= '/status.php';
	} else if ($isMainEndpoint) {
		$requestUrl .= '/api/' . $endpoint;
	} else {
		$requestUrl .= '/' . $endpoint;
	}

	// Construction alternative d'URL - ajout direct de /api/
	$altRequestUrl = API_BASE_URL . '/api/' . $endpoint;

	// Test des deux URLs avec cURL
	$results[$endpoint] = [
		'standard_url' => $requestUrl,
		'alternative_url' => $altRequestUrl,
		'is_main_endpoint' => $isMainEndpoint,
		'tests' => []
	];

	// Test standard URL
	$ch = curl_init($requestUrl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	$results[$endpoint]['tests']['standard'] = [
		'url' => $requestUrl,
		'status' => $httpCode
	];

	// Test alternative URL
	$ch = curl_init($altRequestUrl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	$results[$endpoint]['tests']['alternative'] = [
		'url' => $altRequestUrl,
		'status' => $httpCode
	];

	logMessage("Test URL pour endpoint: " . $endpoint, $results[$endpoint]);
}

// Afficher les résultats
echo json_encode([
	'api_base_url' => API_BASE_URL,
	'timestamp' => date('Y-m-d H:i:s'),
	'results' => $results,
	'recommendation' => 'Comparez les codes HTTP pour déterminer quelle construction d\'URL fonctionne le mieux'
], JSON_PRETTY_PRINT);
