<?php
// Service d'authentification simplifié - Transmet toutes les requêtes au backend
header('Content-Type: application/json');

// Basic CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// Get authorization header
$authHeader = null;
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
	$authHeader = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
	$authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
} elseif (function_exists('apache_request_headers')) {
	$requestHeaders = apache_request_headers();
	if (isset($requestHeaders['Authorization'])) {
		$authHeader = $requestHeaders['Authorization'];
	}
}

// Log the authentication attempt
error_log("JWT Bridge: Authentication attempt");
error_log("Auth header: " . ($authHeader ? substr($authHeader, 0, 20) . '...' : 'Not provided'));

// Traiter les données de la requête POST
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// URL du backend pour l'authentification
$backendAuthUrl = 'https://app-backend-esgi-app.azurewebsites.net/api-auth.php';

// Si c'est une requête d'authentification (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['email']) && isset($input['password'])) {
	$email = $input['email'];
	$password = $input['password'];

	error_log("Tentative de connexion: $email");

	// Transmettre directement au backend
	$response = forwardAuthRequest($backendAuthUrl, $email, $password);
	echo json_encode($response);
	exit;
}

// Si on reçoit un token JWT (GET avec Authorization)
if ($authHeader && strpos($authHeader, 'Bearer ') === 0) {
	$token = substr($authHeader, 7);

	// Transmettre la vérification de token au backend
	$backendValidateUrl = 'https://app-backend-esgi-app.azurewebsites.net/api/auth/user';
	$response = forwardTokenValidation($backendValidateUrl, $token);

	echo json_encode($response);
	exit;
}

// Aucune authentification fournie
http_response_code(401);
echo json_encode([
	'success' => false,
	'message' => 'Authentication required',
	'help' => 'Send a POST request with email/password or provide a Bearer token'
]);

// Fonction pour transmettre la requête d'authentification au backend
function forwardAuthRequest($url, $email, $password)
{
	error_log("Transmission de la demande d'authentification au backend: $url");

	// Initialiser cURL
	$ch = curl_init($url);

	// Configuration de cURL
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
		'email' => $email,
		'password' => $password
	]));
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json'
	]);

	// Exécuter la requête
	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$error = curl_error($ch);

	// Fermer la connexion
	curl_close($ch);

	// Log de la réponse
	error_log("Réponse backend auth ($httpCode): " . substr($response, 0, 100) . "...");

	if ($error) {
		error_log("Erreur cURL lors de l'authentification: $error");
		return [
			'success' => false,
			'message' => 'Erreur de communication avec le serveur',
			'error' => $error
		];
	}

	// Traiter la réponse
	if ($httpCode >= 200 && $httpCode < 300) {
		$data = json_decode($response, true);
		if ($data) {
			return $data;
		}
	}

	// Format de réponse incorrecte ou erreur
	return [
		'success' => false,
		'message' => 'Échec de l\'authentification',
		'http_code' => $httpCode,
		'raw_response' => substr($response, 0, 200) // Limiter la taille de la réponse
	];
}

// Fonction pour transmettre la validation du token au backend
function forwardTokenValidation($url, $token)
{
	error_log("Transmission de la validation de token au backend: $url");

	// Initialiser cURL
	$ch = curl_init($url);

	// Configuration de cURL
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'Authorization: Bearer ' . $token
	]);

	// Exécuter la requête
	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$error = curl_error($ch);

	// Fermer la connexion
	curl_close($ch);

	// Log de la réponse
	error_log("Réponse backend validation ($httpCode): " . substr($response, 0, 100) . "...");

	if ($error) {
		error_log("Erreur cURL lors de la validation: $error");
		return [
			'success' => false,
			'message' => 'Erreur de communication avec le serveur',
			'error' => $error
		];
	}

	// Traiter la réponse
	if ($httpCode >= 200 && $httpCode < 300) {
		$data = json_decode($response, true);
		if ($data) {
			return $data;
		}
	}

	// Format de réponse incorrecte ou erreur
	return [
		'success' => false,
		'message' => 'Token invalide ou expiré',
		'http_code' => $httpCode
	];
}
