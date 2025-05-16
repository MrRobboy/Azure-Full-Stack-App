<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Direct API Data Provider
 * 
 * This file provides direct API response data without requiring backend connection.
 * It serves as a fallback when proxies fail or backend is unreachable.
 * 
 * Supports multiple endpoints:
 * - /matieres (GET, POST, PUT, DELETE)
 * - /classes (GET)
 * - /examens (GET)
 * - /professeurs (GET)
 * - /admin/users (GET)
 */

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit();
}

// Get the endpoint from the query string
$endpoint = $_GET['endpoint'] ?? '';
if (empty($endpoint)) {
	http_response_code(400);
	echo json_encode(['error' => 'No endpoint specified']);
	exit();
}

// Check if this is a supported endpoint
$supportedEndpoints = [
	'api/matieres',
	'api/classes',
	'api/examens',
	'api/professeurs'
];

if (!in_array($endpoint, $supportedEndpoints)) {
	http_response_code(400);
	echo json_encode([
		'success' => false,
		'message' => 'Endpoint non pris en charge par direct-matieres.php',
		'requested_endpoint' => $endpoint,
		'is_direct' => true
	]);
	exit();
}

// Return fallback data based on the endpoint
$fallbackData = [
	'api/matieres' => [
		['id' => 1, 'nom' => 'Mathématiques'],
		['id' => 2, 'nom' => 'Français'],
		['id' => 3, 'nom' => 'Anglais'],
		['id' => 4, 'nom' => 'Histoire']
	],
	'api/classes' => [
		['id' => 1, 'nom' => '6ème A'],
		['id' => 2, 'nom' => '6ème B'],
		['id' => 3, 'nom' => '5ème A'],
		['id' => 4, 'nom' => '5ème B']
	],
	'api/examens' => [
		['id' => 1, 'nom' => 'Contrôle de Mathématiques'],
		['id' => 2, 'nom' => 'Devoir de Français'],
		['id' => 3, 'nom' => 'Test d\'Anglais']
	],
	'api/professeurs' => [
		['id' => 1, 'nom' => 'Dupont', 'prenom' => 'Jean'],
		['id' => 2, 'nom' => 'Martin', 'prenom' => 'Marie'],
		['id' => 3, 'nom' => 'Bernard', 'prenom' => 'Pierre']
	]
];

// Return the fallback data
http_response_code(200);
echo json_encode([
	'success' => true,
	'data' => $fallbackData[$endpoint],
	'isFallback' => true
]);
