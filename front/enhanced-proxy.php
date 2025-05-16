<?php

/**
 * Proxy Azure Amélioré - Compatible avec les endpoints REST et traditionnels
 * Date de génération: 2023-06-10
 */

// Configuration des endpoints
$API_BASE = 'https://app-backend-esgi-app.azurewebsites.net';

// Mappage des endpoints
$ENDPOINT_MAP = [
	// Endpoints traditionnels
	'api-auth-login.php' => '/api-auth-login.php',
	'status.php' => '/status.php',
	'api-notes.php' => '/api-notes.php',

	// Endpoints REST
	'api/notes' => '/api/notes',
	'api/users' => '/api/users',
	'api/classes' => '/api/classes',
	'api/profs' => '/api/profs',
	'api/matieres' => '/api/matieres',
	'api/examens' => '/api/examens',
	'api/privileges' => '/api/privileges'
];

// Journalisation
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Création du répertoire de logs si nécessaire
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
	mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/enhanced-proxy.log');

// Journaliser l'accès
error_log("Proxy amélioré accédé: " . $_SERVER['REQUEST_URI']);
error_log("Méthode: " . $_SERVER['REQUEST_METHOD']);

// Configuration CORS complète
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Access-Control-Allow-Credentials: true');

// Traiter les requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

// Obtenir l'endpoint demandé
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
if (empty($endpoint)) {
	http_response_code(400);
	header('Content-Type: application/json');
	echo json_encode([
		'success' => false,
		'message' => 'Paramètre endpoint manquant'
	]);
	exit;
}

// Extraire les paramètres d'ID si présents (format: resource/id ou resource?id=X)
$resourceId = null;
$resourcePath = $endpoint;

// Méthode 1: Format REST - resource/id
if (preg_match('#^([^/]+)/([^/]+)$#', $endpoint, $matches)) {
	$resourcePath = $matches[1];
	$resourceId = $matches[2];
	error_log("Format REST détecté: resource=$resourcePath, id=$resourceId");
}
// Méthode 2: Format query string - resource?id=X
else if (isset($_GET['id'])) {
	$resourceId = $_GET['id'];
	error_log("Format query string détecté: resource=$resourcePath, id=$resourceId");
}

// Construire l'URL cible
$targetUrl = $API_BASE;

// Vérifier si l'endpoint est mappé directement
if (isset($ENDPOINT_MAP[$endpoint])) {
	$targetUrl .= $ENDPOINT_MAP[$endpoint];
	error_log("Endpoint mappé directement: " . $endpoint . " -> " . $ENDPOINT_MAP[$endpoint]);
}
// Vérifier si la ressource de base est mappée (pour les formats REST avec ID)
else if ($resourceId && isset($ENDPOINT_MAP[$resourcePath])) {
	$targetUrl .= $ENDPOINT_MAP[$resourcePath] . '/' . $resourceId;
	error_log("Endpoint REST mappé: " . $resourcePath . "/" . $resourceId);
}
// Essayer de mapper automatiquement selon les patrons connus
else if (strpos($endpoint, 'auth') !== false || strpos($endpoint, 'login') !== false) {
	$targetUrl .= '/api-auth-login.php';
	error_log("Endpoint d'auth mappé automatiquement: " . $endpoint . " -> /api-auth-login.php");
} else if (strpos($endpoint, 'status') !== false) {
	$targetUrl .= '/status.php';
	error_log("Endpoint de statut mappé automatiquement: " . $endpoint . " -> /status.php");
} else if (strpos($endpoint, 'note') !== false) {
	$targetUrl .= '/api-notes.php';
	error_log("Endpoint de notes mappé automatiquement: " . $endpoint . " -> /api-notes.php");
}
// Format API REST standard
else if (strpos($endpoint, 'api/') === 0) {
	$targetUrl .= '/' . $endpoint;
	error_log("Endpoint REST standard: " . $endpoint);
}
// Dernier recours: transmission directe
else {
	if ($endpoint[0] !== '/') {
		$targetUrl .= '/';
	}
	$targetUrl .= $endpoint;
	error_log("Endpoint transmis directement: " . $endpoint);
}

// Ajouter les paramètres supplémentaires (sauf endpoint et id déjà traité)
$query = '';
foreach ($_GET as $key => $value) {
	if ($key !== 'endpoint' && $key !== 'id') {
		$query .= ($query === '' ? '?' : '&') . $key . '=' . urlencode($value);
	}
}
$targetUrl .= $query;

error_log("URL cible finale: " . $targetUrl);

// Initialiser cURL
$ch = curl_init($targetUrl);

// Configuration de base
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);
curl_setopt($ch, CURLOPT_ENCODING, '');  // Accepter toute compression

// Transmettre les en-têtes pertinents
$headers = [];
foreach (getallheaders() as $name => $value) {
	if (strtolower($name) !== 'host') {
		$headers[] = $name . ': ' . $value;
	}
}
$headers[] = 'X-Enhanced-Proxy: true';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Transmettre le corps pour POST/PUT/DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
	$input = file_get_contents('php://input');
	if (!empty($input)) {
		curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
		error_log("Corps de requête: " . substr($input, 0, 200) . (strlen($input) > 200 ? '...' : ''));
	}
}

// Récupérer l'en-tête et le corps
curl_setopt($ch, CURLOPT_HEADER, true);

// Exécuter la requête
$response = curl_exec($ch);
$error = curl_error($ch);

// Gérer les erreurs
if ($response === false) {
	error_log("Erreur cURL: " . $error);
	http_response_code(500);
	header('Content-Type: application/json');
	echo json_encode([
		'success' => false,
		'message' => 'Erreur de connexion: ' . $error,
		'target_url' => $targetUrl
	]);
	exit;
}

// Récupérer les informations
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

// Fermer cURL
curl_close($ch);

// Séparer l'en-tête et le corps
$headerText = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

// Journaliser le résultat
error_log("Réponse: Code=" . $httpCode . ", Type=" . $contentType);
error_log("Début du corps: " . substr($body, 0, 100) . (strlen($body) > 100 ? '...' : ''));

// Définir le code de statut
http_response_code($httpCode);

// Ajouter le type de contenu si présent
if ($contentType) {
	header('Content-Type: ' . $contentType);
} else {
	header('Content-Type: application/json');
}

// Vérifier si le corps est du JSON valide et si une erreur 404 pourrait être à cause d'un mauvais chemin
if ($httpCode === 404) {
	error_log("Erreur 404 - URL incorrecte? " . $targetUrl);
	// Si pas de JSON valide, formatter une réponse JSON pour le client
	if (!json_decode($body)) {
		echo json_encode([
			'success' => false,
			'message' => 'Endpoint non trouvé: ' . $endpoint,
			'debug_url' => $targetUrl,
			'http_code' => $httpCode
		]);
		exit;
	}
}

// Renvoyer la réponse
echo $body;
