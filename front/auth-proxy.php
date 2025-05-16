<?php

/**
 * Proxy d'authentification optimisé - Spécialement conçu pour l'endpoint d'authentification
 * Date de génération: 2025-05-16
 */

// Configuration
$API_BASE = 'https://app-backend-esgi-app.azurewebsites.net';
$AUTH_ENDPOINT = 'api-auth-login.php';  // Confirmé via Backend Explorer (405 Method Not Allowed)

// Journalisation
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Création du répertoire de logs si nécessaire
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
	mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/auth-proxy.log');

// Journaliser l'accès
error_log("Proxy auth accédé: " . $_SERVER['REQUEST_URI']);
error_log("Méthode: " . $_SERVER['REQUEST_METHOD']);

// Configuration CORS complète
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Traiter les requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

// Vérifier si c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode([
		'success' => false,
		'message' => 'Seule la méthode POST est autorisée pour l\'authentification'
	]);
	exit;
}

// Construire l'URL cible
$targetUrl = $API_BASE . '/' . $AUTH_ENDPOINT;
error_log("URL cible d'authentification: " . $targetUrl);

// Obtenir le corps de la requête
$input = file_get_contents('php://input');
error_log("Corps de requête: " . substr($input, 0, 100) . (strlen($input) > 100 ? '...' : ''));

// Vérifier la syntaxe JSON
$jsonData = json_decode($input);
if (json_last_error() !== JSON_ERROR_NONE) {
	http_response_code(400);
	echo json_encode([
		'success' => false,
		'message' => 'JSON invalide: ' . json_last_error_msg()
	]);
	exit;
}

// Initialiser cURL
$ch = curl_init($targetUrl);

// Configuration optimisée pour l'authentification
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
curl_setopt($ch, CURLOPT_ENCODING, '');  // Accepter toute compression

// En-têtes de la requête
$headers = [
	'Content-Type: application/json',
	'Accept: application/json',
	'X-Requested-With: XMLHttpRequest',
	'X-Auth-Proxy: true'
];

// Ajouter l'en-tête d'autorisation s'il existe
foreach (getallheaders() as $name => $value) {
	if (strtolower($name) === 'authorization') {
		$headers[] = 'Authorization: ' . $value;
		break;
	}
}

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_HEADER, true);

// Exécuter la requête
$response = curl_exec($ch);
$error = curl_error($ch);
$info = curl_getinfo($ch);

// Journaliser les informations importantes
error_log("Statut réponse: " . $info['http_code']);
error_log("Temps de réponse: " . $info['total_time'] . " secondes");

// Gérer les erreurs
if ($response === false) {
	error_log("Erreur cURL: " . $error);
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => 'Erreur de connexion au serveur d\'authentification: ' . $error
	]);
	exit;
}

// Séparer les en-têtes et le corps
$headerSize = $info['header_size'];
$headerText = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

// Définir le statut HTTP
http_response_code($info['http_code']);

// Journaliser un aperçu de la réponse
error_log("En-têtes réponse: " . substr(str_replace("\r\n", " | ", $headerText), 0, 200));
error_log("Corps réponse: " . substr($body, 0, 200) . (strlen($body) > 200 ? '...' : ''));

// Si c'est un JSON invalide, formatter une réponse d'erreur
$jsonData = json_decode($body);
if (json_last_error() !== JSON_ERROR_NONE) {
	error_log("Réponse non-JSON du serveur: " . substr($body, 0, 500));
	echo json_encode([
		'success' => false,
		'message' => 'Le serveur d\'authentification a renvoyé une réponse non-JSON',
		'status' => $info['http_code']
	]);
	exit;
}

// Renvoyer le corps tel quel
echo $body;
