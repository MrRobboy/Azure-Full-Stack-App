<?php
// Fichier pour gérer les requêtes CORS OPTIONS préflight
// Ce fichier sera utilisé comme point d'entrée unique pour toutes les requêtes OPTIONS

// Configuration CORS complète
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://app-frontend-esgi-app.azurewebsites.net');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

// Log de la requête OPTIONS
error_log(sprintf(
	"[%s] CORS préflight OPTIONS: URI=%s, Origin=%s",
	date('Y-m-d H:i:s'),
	$_SERVER['REQUEST_URI'],
	isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'non défini'
));

// Si c'est une requête OPTIONS, renvoyer 204 No Content
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

// Si ce n'est pas une requête OPTIONS, renvoyer des informations sur la configuration CORS
$response = [
	'success' => true,
	'message' => 'Configuration CORS du serveur',
	'timestamp' => date('Y-m-d H:i:s'),
	'cors' => [
		'allowed_origins' => ['https://app-frontend-esgi-app.azurewebsites.net'],
		'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
		'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'Origin'],
		'allow_credentials' => true,
		'max_age' => 86400
	],
	'request' => [
		'method' => $_SERVER['REQUEST_METHOD'],
		'uri' => $_SERVER['REQUEST_URI'],
		'origin' => isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : null,
		'headers' => getallheaders()
	]
];

echo json_encode($response, JSON_PRETTY_PRINT);
exit;
