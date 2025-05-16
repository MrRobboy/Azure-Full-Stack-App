<?php

/**
 * Special Matieres Proxy
 * This is a simplified proxy specifically for retrieving subject (matiere) data
 */

// Basic config
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// Target backend URL
$backendUrl = 'https://app-backend-esgi-app.azurewebsites.net';
$endpoint = 'api/matieres';

// Log access
error_log("Matieres proxy accessed at " . date('Y-m-d H:i:s'));

// Full URL
$url = $backendUrl . '/' . $endpoint;

// Initialize cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
	'Accept: application/json',
	'X-Special-Proxy: matieres-proxy'
]);

// Execute request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for cURL errors
if (curl_errno($ch)) {
	$error = curl_error($ch);
	error_log("Matieres proxy error: " . $error);

	// Return fallback data
	echo json_encode([
		'success' => true,
		'data' => [
			[
				'id_matiere' => 1,
				'nom' => 'Mathématiques'
			],
			[
				'id_matiere' => 2,
				'nom' => 'Français'
			],
			[
				'id_matiere' => 16,
				'nom' => 'Docker'
			],
			[
				'id_matiere' => 17,
				'nom' => 'Azure'
			]
		],
		'message' => 'Using fallback data due to backend connection error',
		'is_fallback' => true
	]);

	curl_close($ch);
	exit;
}

// Handle backend errors
if ($http_code >= 400) {
	error_log("Matieres backend returned error code: " . $http_code);

	// Return fallback data
	echo json_encode([
		'success' => true,
		'data' => [
			[
				'id_matiere' => 1,
				'nom' => 'Mathématiques'
			],
			[
				'id_matiere' => 2,
				'nom' => 'Français'
			],
			[
				'id_matiere' => 16,
				'nom' => 'Docker'
			],
			[
				'id_matiere' => 17,
				'nom' => 'Azure'
			]
		],
		'message' => 'Using fallback data due to backend error code ' . $http_code,
		'is_fallback' => true
	]);

	curl_close($ch);
	exit;
}

// Close cURL
curl_close($ch);

// Return the response
http_response_code($http_code);
echo $response;
