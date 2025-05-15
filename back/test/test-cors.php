<?php

/**
 * Test CORS - Script de test simple pour vérifier les en-têtes CORS
 */

// Activer les en-têtes CORS directement dans ce script
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://app-frontend-esgi-app.azurewebsites.net');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400'); // 24 hours cache pour preflight

// Pour les requêtes OPTIONS (preflight), retourner 200 OK immédiatement
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// Collecter les informations sur la requête
$requestInfo = [
	'method' => $_SERVER['REQUEST_METHOD'],
	'headers' => getRequestHeaders(),
	'time' => date('Y-m-d H:i:s'),
	'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
];

// Collecter également les en-têtes de réponse qui seront envoyés
$responseHeaders = [];
foreach (headers_list() as $header) {
	$responseHeaders[] = $header;
}

// Retourner les informations en JSON
echo json_encode([
	'success' => true,
	'message' => 'Test CORS réussi',
	'request' => $requestInfo,
	'response_headers' => $responseHeaders
]);

// Fonction pour récupérer les en-têtes de la requête
function getRequestHeaders()
{
	$headers = [];
	foreach ($_SERVER as $key => $value) {
		if (substr($key, 0, 5) === 'HTTP_') {
			$headerName = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
			$headers[$headerName] = $value;
		}
	}
	return $headers;
}
