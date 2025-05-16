<?php

/**
 * Direct Login - Version améliorée pour Azure
 * 
 * Ce script fait office de solution de contournement pour les erreurs 404 avec les proxies sur Azure.
 * Il effectue une requête directe au backend depuis le serveur, évitant les problèmes CORS et 404.
 */

// Activer les rapports d'erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'direct_login_errors.log');

// En-têtes HTTP
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Traiter les requêtes OPTIONS (CORS pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// URL de base de l'API backend
$backendBaseUrl = 'https://app-backend-esgi-app.azurewebsites.net';
$apiBaseUrl = $backendBaseUrl . '/api';

// Loguer l'accès
error_log('Direct Login accédé le ' . date('Y-m-d H:i:s') . ' depuis ' . ($_SERVER['REMOTE_ADDR'] ?? 'inconnu'));
error_log('Méthode: ' . ($_SERVER['REQUEST_METHOD'] ?? 'inconnue'));

// Récupérer les données POST
$requestBody = file_get_contents('php://input');
$requestData = json_decode($requestBody, true);

// Vérifier si c'est une requête de login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($requestData)) {
	// Endpoint
	$endpoint = 'api/auth/login';
	$url = $backendBaseUrl . '/' . $endpoint;

	error_log('Tentative de connexion directe à ' . $url);

	// Construire la requête cURL
	$ch = curl_init($url);

	// Configuration cURL
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'Accept: application/json',
		'User-Agent: ESGI-App-DirectLogin/1.0',
		'X-Request-Source: DirectLogin'
	]);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

	// Exécuter la requête
	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	// Vérifier les erreurs
	if ($response === false) {
		$error = curl_error($ch);
		error_log('Erreur cURL: ' . $error);

		echo json_encode([
			'success' => false,
			'message' => 'Erreur de connexion au backend: ' . $error,
			'timestamp' => date('Y-m-d H:i:s'),
			'error_code' => 'CURL_ERROR'
		]);
	} else {
		// Transmettre le code HTTP
		http_response_code($httpCode);

		// Logguer la réponse
		error_log('Réponse du backend: ' . $httpCode);

		// Relayer la réponse au client
		echo $response;
	}

	curl_close($ch);
} else {
	// Requête non supportée
	http_response_code(400);
	echo json_encode([
		'success' => false,
		'message' => 'Méthode non supportée ou données manquantes',
		'timestamp' => date('Y-m-d H:i:s'),
		'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
		'has_body' => !empty($requestBody) ? 'yes' : 'no'
	]);
}
