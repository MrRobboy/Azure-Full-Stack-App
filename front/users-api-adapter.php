<?php

/**
 * Adaptateur d'API pour les utilisateurs
 * 
 * Ce fichier sert d'adaptateur entre le format d'API attendu par le frontend
 * et le format réel disponible sur le backend Azure.
 */

// Configuration CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Traiter les requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

// Configuration de base
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Création du répertoire de logs si nécessaire
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
	mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/users-api.log');

// Journaliser l'accès
error_log("API Users accédée: " . $_SERVER['REQUEST_URI']);
error_log("Méthode: " . $_SERVER['REQUEST_METHOD']);

// Configuration du proxy et de l'API
$proxyEndpoint = 'optimal-proxy.php';
$apiBasePath = 'api/users';  // Chemin d'API REST du backend

// Récupérer le token d'autorisation des en-têtes
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
$token = '';

if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
	$token = $matches[1];
}

// Vérifier si un token est présent
if (empty($token)) {
	http_response_code(401);
	echo json_encode([
		'success' => false,
		'message' => 'Authentification requise'
	]);
	exit;
}

// Traiter l'ID de ressource s'il est présent dans l'URL
$resourceId = null;
if (isset($_GET['id']) && !empty($_GET['id'])) {
	$resourceId = $_GET['id'];
}

// Construire l'URL de l'API backend
$apiUrl = $apiBasePath;
if ($resourceId !== null) {
	$apiUrl .= '/' . $resourceId;
}

// Ajouter des filtres supplémentaires pour la classe si fournis
if (isset($_GET['classe']) && !empty($_GET['classe'])) {
	$apiUrl = $apiBasePath . '/classe/' . $_GET['classe'];
}

// Journaliser l'URL de l'API backend
error_log("URL API backend: " . $apiUrl);

// Fonction pour appeler le proxy
function callProxy($url, $method, $data = null)
{
	global $proxyEndpoint, $token;

	$proxyUrl = $proxyEndpoint . '?endpoint=' . urlencode($url);
	$ch = curl_init($proxyUrl);

	$headers = [
		'Authorization: Bearer ' . $token,
		'Content-Type: application/json',
		'Accept: application/json'
	];

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	// Configurer la méthode HTTP
	if ($method !== 'GET') {
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		if ($data !== null) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		}
	}

	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$error = curl_error($ch);

	curl_close($ch);

	error_log("Réponse du proxy (" . $method . " " . $url . "): Code " . $httpCode);

	if ($error) {
		error_log("Erreur cURL: " . $error);
		return [
			'success' => false,
			'message' => 'Erreur de connexion: ' . $error,
			'http_code' => $httpCode
		];
	}

	// Essayer de décoder la réponse JSON
	$decoded = json_decode($response, true);
	if (json_last_error() !== JSON_ERROR_NONE) {
		error_log("Erreur de décodage JSON: " . json_last_error_msg());
		error_log("Réponse brute: " . substr($response, 0, 200));
		return [
			'success' => false,
			'message' => 'Réponse invalide du serveur',
			'raw_response' => substr($response, 0, 500),
			'http_code' => $httpCode
		];
	}

	return $decoded;
}

// Traiter la requête en fonction de la méthode HTTP
try {
	$requestData = null;

	// Pour les méthodes POST et PUT, récupérer les données du corps
	if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
		$input = file_get_contents('php://input');
		if (!empty($input)) {
			$requestData = json_decode($input, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				throw new Exception('Données JSON invalides: ' . json_last_error_msg());
			}
		}
	}

	// Appeler le proxy avec la méthode et les données appropriées
	$result = callProxy($apiUrl, $_SERVER['REQUEST_METHOD'], $requestData);

	// Retourner le résultat
	echo json_encode($result);
} catch (Exception $e) {
	error_log("Erreur: " . $e->getMessage());
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => $e->getMessage()
	]);
}
