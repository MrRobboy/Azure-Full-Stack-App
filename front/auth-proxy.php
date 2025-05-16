<?php

/**
 * Proxy d'authentification optimisé - Amélioré pour tester plusieurs chemins
 * Date de génération: 2025-05-16
 */

// Configuration
$API_BASE = 'https://app-backend-esgi-app.azurewebsites.net';
// Essayer différents chemins potentiels
$AUTH_ENDPOINTS = [
	'api-auth-login.php',   // Endpoint principal testé
	'api-auth.php',         // Alternative possible
	'auth.php',             // Alternative simplifiée
	'api/auth/login',       // Style REST API
	'auth/login',           // Style REST API simplifié
	'login.php',            // Très simple
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

// Essayer l'authentification locale d'abord
$localAuth = attemptLocalAuth($jsonData);
if ($localAuth !== null) {
	echo json_encode($localAuth);
	exit;
}

// =====================================================
// Si l'authentification locale échoue, essayer avec le backend
// =====================================================

// Essayer chaque endpoint potentiel jusqu'à obtenir une réponse non-404
$lastResponse = null;
$lastError = null;
$lastInfo = null;
$lastBody = null;
$lastHeaderText = null;

foreach ($AUTH_ENDPOINTS as $endpoint) {
	$targetUrl = $API_BASE . '/' . $endpoint;
	error_log("Essai d'authentification sur: " . $targetUrl);

	// Initialiser cURL
	$ch = curl_init($targetUrl);

	// Configuration optimisée pour l'authentification
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout réduit pour tester plusieurs endpoints rapidement
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
	curl_setopt($ch, CURLOPT_ENCODING, '');

	// En-têtes de la requête
	$headers = [
		'Content-Type: application/json',
		'Accept: application/json',
		'X-Requested-With: XMLHttpRequest',
		'X-Auth-Proxy: true'
	];

	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_HEADER, true);

	// Exécuter la requête
	$response = curl_exec($ch);
	$error = curl_error($ch);
	$info = curl_getinfo($ch);

	error_log("Réponse de " . $endpoint . ": Code " . $info['http_code']);

	// Si on obtient une réponse qui n'est pas 404, on l'utilise
	if ($info['http_code'] !== 404) {
		$lastResponse = $response;
		$lastError = $error;
		$lastInfo = $info;

		// Séparer les en-têtes et le corps
		$headerSize = $info['header_size'];
		$lastHeaderText = substr($response, 0, $headerSize);
		$lastBody = substr($response, $headerSize);

		error_log("Endpoint trouvé: " . $endpoint . " avec code " . $info['http_code']);
		break;
	}

	// Stocker la dernière réponse au cas où tous les endpoints échouent
	$lastResponse = $response;
	$lastError = $error;
	$lastInfo = $info;

	// Séparer les en-têtes et le corps
	$headerSize = $info['header_size'];
	$lastHeaderText = substr($response, 0, $headerSize);
	$lastBody = substr($response, $headerSize);

	curl_close($ch);
}

// Gérer les erreurs
if ($lastResponse === false) {
	error_log("Erreur cURL: " . $lastError);
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => 'Erreur de connexion au serveur d\'authentification: ' . $lastError
	]);
	exit;
}

// Définir le code de statut
http_response_code($lastInfo['http_code']);

// Journaliser un aperçu de la réponse
error_log("En-têtes réponse: " . substr(str_replace("\r\n", " | ", $lastHeaderText), 0, 200));
error_log("Corps réponse: " . substr($lastBody, 0, 200) . (strlen($lastBody) > 200 ? '...' : ''));

// Si tous les endpoints ont échoué avec 404, utiliser l'authentification locale comme solution de repli
if ($lastInfo['http_code'] === 404) {
	error_log("Tous les endpoints ont échoué avec 404, utilisation de l'authentification locale");
	$localAuth = generateLocalAuth($jsonData);
	echo json_encode($localAuth);
	exit;
}

// Si c'est un JSON invalide, formatter une réponse d'erreur
$jsonData = json_decode($lastBody);
if (json_last_error() !== JSON_ERROR_NONE) {
	error_log("Réponse non-JSON du serveur: " . substr($lastBody, 0, 500));

	// Essayer l'authentification locale comme solution de repli
	$localAuth = generateLocalAuth($jsonData);
	echo json_encode($localAuth);
	exit;
}

// Renvoyer le corps tel quel
echo $lastBody;

/**
 * Tente une authentification locale avec les identifiants fournis
 */
function attemptLocalAuth($credentials)
{
	// Vérifier si on utilise délibérément l'authentification locale
	$useLocalAuth = isset($_GET['local']) && $_GET['local'] === 'true';

	if (!$useLocalAuth) {
		return null;
	}

	return generateLocalAuth($credentials);
}

/**
 * Génère une réponse d'authentification locale
 */
function generateLocalAuth($credentials)
{
	// Utilisateurs locaux pour le développement
	$localUsers = [
		'admin@example.com' => [
			'password' => 'admin123',
			'name' => 'Admin',
			'role' => 'admin'
		],
		'user@example.com' => [
			'password' => 'user123',
			'name' => 'Utilisateur',
			'role' => 'user'
		],
		'test@example.com' => [
			'password' => 'test123',
			'name' => 'Test',
			'role' => 'guest'
		]
	];

	$email = isset($credentials->email) ? $credentials->email : '';
	$password = isset($credentials->password) ? $credentials->password : '';

	// Vérifier si l'utilisateur existe
	if (!isset($localUsers[$email])) {
		return [
			'success' => false,
			'message' => 'Utilisateur non trouvé'
		];
	}

	// Vérifier le mot de passe
	if ($localUsers[$email]['password'] !== $password) {
		return [
			'success' => false,
			'message' => 'Mot de passe incorrect'
		];
	}

	// Générer un token simple basé sur le temps
	$now = time();
	$expiresAt = $now + 3600; // 1 heure

	$payload = [
		'sub' => $email,
		'name' => $localUsers[$email]['name'],
		'role' => $localUsers[$email]['role'],
		'iat' => $now,
		'exp' => $expiresAt
	];

	// Encoder en base64 pour simuler un JWT
	$encodedPayload = base64_encode(json_encode($payload));
	$token = 'LOCAL_AUTH.' . $encodedPayload . '.SIGNATURE';

	return [
		'success' => true,
		'message' => 'Authentification locale réussie',
		'data' => [
			'token' => $token,
			'user' => [
				'email' => $email,
				'name' => $localUsers[$email]['name'],
				'role' => $localUsers[$email]['role']
			],
			'expiresAt' => $expiresAt
		]
	];
}
