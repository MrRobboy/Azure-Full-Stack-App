<?php
// Ce script permet de se connecter directement au backend sans passer par le proxy

// Affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'direct_login_errors.log');

// Définir l'en-tête de réponse
header('Content-Type: application/json');

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
	exit;
}

// Récupérer les données de connexion
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['email']) || !isset($data['password'])) {
	http_response_code(400);
	echo json_encode(['success' => false, 'message' => 'Données de connexion incomplètes']);
	exit;
}

// Log des informations (sans le mot de passe)
error_log("Tentative de connexion pour: " . $data['email']);

// URL de l'API de login
$loginUrl = 'https://app-backend-esgi-app.azurewebsites.net/api/auth/login';

// Initialiser cURL
$ch = curl_init($loginUrl);

// Configurer la requête
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
	'email' => $data['email'],
	'password' => $data['password']
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
	'Content-Type: application/json',
	'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Désactiver la vérification SSL pour éviter les problèmes

// Exécuter la requête
$response = curl_exec($ch);

// Vérifier les erreurs
if ($response === false) {
	$error = curl_error($ch);
	error_log("Erreur cURL: " . $error);
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => 'Erreur de connexion au serveur',
		'error' => $error
	]);
	curl_close($ch);
	exit;
}

// Traiter la réponse
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$body = substr($response, $headerSize);
$headers = substr($response, 0, $headerSize);

// Fermer la connexion
curl_close($ch);

// Traiter les cookies de la réponse
$cookies = [];
preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headers, $matches);
foreach ($matches[1] as $cookie) {
	error_log("Cookie trouvé: " . $cookie);
	$parts = explode('=', $cookie, 2);
	if (count($parts) == 2) {
		$name = $parts[0];
		$value = $parts[1];
		$cookies[$name] = $value;
		// Définir le cookie côté client
		setcookie($name, $value, 0, '/', '', true, true);
	}
}

// Vérifier le corps de la réponse
try {
	$result = json_decode($body, true);

	if ($result === null && json_last_error() !== JSON_ERROR_NONE) {
		error_log("Erreur de décodage JSON: " . json_last_error_msg());
		error_log("Corps de réponse brut: " . $body);
		throw new Exception("Erreur de décodage de la réponse: " . json_last_error_msg());
	}

	// Si la réponse est un succès, enregistrer les données en session
	if ($httpCode === 200 && isset($result['success']) && $result['success']) {
		// Démarrer la session pour stocker les données
		session_start();

		// Stocker les informations de l'utilisateur en session
		$_SESSION['user'] = $result['user'] ?? null;
		$_SESSION['token'] = $result['token'] ?? null;
		$_SESSION['loggedIn'] = true;
		$_SESSION['loginTime'] = time();

		error_log("Session créée pour l'utilisateur: " . ($result['user']['email'] ?? 'inconnu'));
	}

	// Envoyer la réponse au client
	http_response_code($httpCode);
	echo $body;
} catch (Exception $e) {
	error_log("Exception: " . $e->getMessage());
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => 'Erreur lors du traitement de la réponse',
		'error' => $e->getMessage()
	]);
}
