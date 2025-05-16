<?php

/**
 * Proxy optimisé pour Azure - Créé selon les résultats du Backend Explorer
 * Date de génération: 2025-05-16
 */

// Configuration des endpoints confirmés
$API_BASE = 'https://app-backend-esgi-app.azurewebsites.net';
$AUTH_PATH = 'api-auth-login.php';  // 405 = existe mais méthode GET non autorisée
$STATUS_PATH = 'status.php';        // 200 = existe et fonctionne
$NOTES_PATH = 'api-notes.php';      // 401 = existe mais requiert authentification

// Journalisation
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Création du répertoire de logs si nécessaire
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
	mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/optimal-proxy.log');

// Journaliser l'accès
error_log("Proxy optimisé accédé: " . $_SERVER['REQUEST_URI']);
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

// Mapper l'endpoint à la bonne URL
$targetUrl = $API_BASE;

// Construire l'URL cible basée sur l'analyse des résultats du Backend Explorer
if (strpos($endpoint, 'auth') !== false || strpos($endpoint, 'login') !== false) {
	// Endpoint d'authentification
	$targetUrl .= '/' . $AUTH_PATH;
} else if (strpos($endpoint, 'status') !== false) {
	// Endpoint de statut
	$targetUrl .= '/' . $STATUS_PATH;
} else if (strpos($endpoint, 'note') !== false || strpos($endpoint, 'matiere') !== false) {
	// Endpoint des notes/matières
	$targetUrl .= '/' . $NOTES_PATH;

	// Ajouter les paramètres de requête
	$query = '';
	foreach ($_GET as $key => $value) {
		if ($key !== 'endpoint') {
			$query .= ($query === '' ? '?' : '&') . $key . '=' . urlencode($value);
		}
	}
	$targetUrl .= $query;
} else {
	// Pour les autres cas, essayer le chemin direct
	if ($endpoint[0] !== '/') {
		$targetUrl .= '/';
	}
	$targetUrl .= $endpoint;
}

error_log("URL cible: " . $targetUrl);

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
$headers[] = 'X-Optimal-Proxy: true';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Transmettre le corps pour POST/PUT
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
	$input = file_get_contents('php://input');
	curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
	error_log("Corps de requête: " . substr($input, 0, 200) . (strlen($input) > 200 ? '...' : ''));
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
		'message' => 'Erreur de connexion: ' . $error
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
