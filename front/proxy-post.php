<?php

/**
 * Proxy POST - Proxy simplifié pour les requêtes POST
 */

// Configuration de base
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Création du répertoire de logs si nécessaire
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
	mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/proxy-post.log');

// Journalisation de base
error_log("Proxy POST accessed: " . $_SERVER['REQUEST_URI']);
error_log("Method: " . $_SERVER['REQUEST_METHOD']);

// Configuration CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Content-Type: application/json');

// Traiter OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

// Vérifier si c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode([
		'success' => false,
		'message' => 'Seule la méthode POST est autorisée'
	]);
	exit;
}

// Récupérer l'URL cible et le corps de la requête
$target = isset($_GET['target']) ? $_GET['target'] : '';
$input = file_get_contents('php://input');

// Vérifier si la cible est spécifiée
if (empty($target)) {
	http_response_code(400);
	echo json_encode([
		'success' => false,
		'message' => 'Paramètre "target" manquant'
	]);
	exit;
}

// Valider l'URL cible
$baseUrl = 'https://app-backend-esgi-app.azurewebsites.net';
$url = $baseUrl;

// Construire l'URL complète
if ($target[0] !== '/') {
	$url .= '/';
}
$url .= $target;

error_log("Target URL: " . $url);
error_log("Input data: " . substr($input, 0, 100) . (strlen($input) > 100 ? '...' : ''));

// Initialiser cURL
$ch = curl_init($url);

// Configuration des options cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
curl_setopt($ch, CURLOPT_ENCODING, '');

// En-têtes de la requête
$headers = [
	'Content-Type: application/json',
	'Accept: application/json',
	'X-Requested-With: XMLHttpRequest',
	'User-Agent: ESGI-App-Proxy-POST/1.0'
];

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLINFO_HEADER_OUT, true);

// Exécuter la requête
$response = curl_exec($ch);
$error = curl_error($ch);
$info = curl_getinfo($ch);

error_log("Sent headers: " . $info['request_header']);
error_log("Response status: " . $info['http_code']);

// Traiter les erreurs cURL
if ($response === false) {
	error_log("cURL error: " . $error);
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => 'Erreur de connexion au serveur: ' . $error
	]);
	exit;
}

// Séparer les en-têtes et le corps de la réponse
$headerSize = $info['header_size'];
$headerText = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

error_log("Response headers: " . substr(str_replace("\r\n", " | ", $headerText), 0, 200));
error_log("Response body preview: " . substr($body, 0, 200));

// Essayer de décoder le corps comme du JSON
$jsonData = json_decode($body);
$isJson = json_last_error() === JSON_ERROR_NONE;

// Définir le code de statut HTTP
http_response_code($info['http_code']);

// Si ce n'est pas du JSON mais que nous attendons du JSON, renvoyer une erreur
if (!$isJson && strpos($info['content_type'], 'json') !== false) {
	error_log("Expected JSON but got: " . substr($body, 0, 500));
	echo json_encode([
		'success' => false,
		'message' => 'Le serveur a renvoyé une réponse non-JSON',
		'raw_response_preview' => substr($body, 0, 200) . '...',
		'status' => $info['http_code']
	]);
	exit;
}

// Renvoyer le corps tel quel
echo $body;
