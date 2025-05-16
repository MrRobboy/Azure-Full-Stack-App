<?php
// API Router - Nginx Compatibility Version
// This file is designed to be the main API entrypoint in the root directory

// IMPORTANT: Définir les en-têtes CORS avant toute autre opération
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://app-frontend-esgi-app.azurewebsites.net');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

// Traiter immédiatement les requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

// Désactivation de l'affichage des erreurs pour l'API
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Configuration des logs
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/api_errors.log');

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/logs')) {
	mkdir(__DIR__ . '/logs', 0755, true);
}

// Log request details
error_log(sprintf(
	"[%s] API Request: Method=%s, URI=%s, IP=%s, Origin=%s",
	date('Y-m-d H:i:s'),
	$_SERVER['REQUEST_METHOD'],
	$_SERVER['REQUEST_URI'],
	$_SERVER['REMOTE_ADDR'],
	isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'non défini'
));

// Get raw POST data
$raw_data = file_get_contents('php://input');
error_log("Raw POST data: " . $raw_data);

// Extract URI path
$request_uri = $_SERVER['REQUEST_URI'];
$uri = parse_url($request_uri, PHP_URL_PATH);

// Check if this is an API request
if (strpos($uri, '/api/') === 0) {
	// Remove /api/ prefix
	$api_path = substr($uri, 5);

	// Add path to $_GET for the API router
	$_GET['path'] = $api_path;

	// Use direct route inclusion
	include __DIR__ . '/routes/api.php';
	exit;
} else {
	// Not an API request, send 404
	http_response_code(404);
	echo json_encode([
		'success' => false,
		'message' => 'Not found. This endpoint only handles API requests.',
		'uri' => $uri
	]);
	exit;
}
