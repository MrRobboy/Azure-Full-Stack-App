<?php
// Service d'authentification simplifié - Vérifie les identifiants
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

// Hard-coded user database (à utiliser si la base de données n'est pas accessible)
$users = [
	'admin@example.com' => [
		'id' => 1,
		'nom' => 'Admin',
		'prenom' => 'User',
		'email' => 'admin@example.com',
		'role' => 'admin',
		'password' => 'admin123'
	],
	'prof@example.com' => [
		'id' => 2,
		'nom' => 'Prof',
		'prenom' => 'Example',
		'email' => 'prof@example.com',
		'role' => 'enseignant',
		'password' => 'prof123'
	],
	'student@example.com' => [
		'id' => 3,
		'nom' => 'Student',
		'prenom' => 'Test',
		'email' => 'student@example.com',
		'role' => 'etudiant',
		'password' => 'student123'
	]
];

// Traiter les données de la requête POST
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Si c'est une requête d'authentification (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['email']) && isset($input['password'])) {
	$email = $input['email'];
	$password = $input['password'];

	error_log("Tentative de connexion: $email / [HIDDEN]");

	// Vérifier si l'utilisateur existe dans notre base de données simulée
	if (isset($users[$email]) && $users[$email]['password'] === $password) {
		$userData = $users[$email];
		$token = generateSimpleJwt($userData);

		error_log("Authentification réussie pour: $email");

		echo json_encode([
			'success' => true,
			'message' => 'Authentication successful',
			'token' => $token,
			'user' => [
				'id' => $userData['id'],
				'nom' => $userData['nom'],
				'prenom' => $userData['prenom'],
				'email' => $userData['email'],
				'role' => $userData['role']
			]
		]);
		exit;
	} else {
		// Envoyer la requête au backend pour vérifier les identifiants
		$result = forwardAuthRequest($email, $password);

		if ($result['success']) {
			echo json_encode($result);
			exit;
		}

		// Échec de l'authentification
		error_log("Échec de l'authentification pour: $email");
		http_response_code(401);
		echo json_encode([
			'success' => false,
			'message' => 'Email ou mot de passe incorrect'
		]);
		exit;
	}
}

// Si on reçoit un token JWT (GET avec Authorization)
if ($authHeader && strpos($authHeader, 'Bearer ') === 0) {
	$token = substr($authHeader, 7);

	// Vérifier la validité du token
	$isValid = validateToken($token);

	if ($isValid) {
		// Extraire les informations du token
		$tokenData = decodeToken($token);

		if ($tokenData && isset($tokenData['email'])) {
			$email = $tokenData['email'];

			// Vérifier si l'utilisateur existe dans notre base de données
			if (isset($users[$email])) {
				echo json_encode([
					'success' => true,
					'message' => 'Token validated',
					'user' => [
						'id' => $users[$email]['id'],
						'nom' => $users[$email]['nom'],
						'prenom' => $users[$email]['prenom'],
						'email' => $users[$email]['email'],
						'role' => $users[$email]['role']
					]
				]);
				exit;
			}
		}
	}

	// Token invalide
	http_response_code(401);
	echo json_encode([
		'success' => false,
		'message' => 'Token invalide ou expiré'
	]);
	exit;
}

// Aucune authentification fournie
http_response_code(401);
echo json_encode([
	'success' => false,
	'message' => 'Authentication required',
	'help' => 'Send a POST request with email/password or provide a Bearer token'
]);

// Fonction pour générer un JWT simple
function generateSimpleJwt($userData)
{
	// Header
	$header = [
		'alg' => 'HS256',
		'typ' => 'JWT'
	];

	// Payload
	$payload = [
		'sub' => $userData['id'],
		'name' => $userData['prenom'] . ' ' . $userData['nom'],
		'email' => $userData['email'],
		'role' => $userData['role'],
		'iat' => time(),
		'exp' => time() + 3600 // 1 heure
	];

	// Encoder header et payload
	$base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
	$base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

	// Créer signature - dans une vraie application, utiliser une clé secrète
	$signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, 'esgi_azure_secret_key', true);
	$base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

	// Créer JWT
	return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
}

// Fonction pour valider un token
function validateToken($token)
{
	$tokenParts = explode('.', $token);
	if (count($tokenParts) !== 3) return false;

	list($header, $payload, $signature) = $tokenParts;

	// Décoder le payload
	$decodedPayload = json_decode(base64_decode($payload), true);
	if (!$decodedPayload) return false;

	// Vérifier l'expiration
	if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
		return false;
	}

	// Vérifier la signature (simplifié)
	$expectedSignature = hash_hmac('sha256', $header . '.' . $payload, 'esgi_azure_secret_key', true);
	$expectedSignatureEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expectedSignature));

	return hash_equals($expectedSignatureEncoded, $signature);
}

// Fonction pour décoder un token
function decodeToken($token)
{
	$tokenParts = explode('.', $token);
	if (count($tokenParts) !== 3) return null;

	$payload = base64_decode(strtr($tokenParts[1], '-_', '+/'));
	return json_decode($payload, true);
}

// Fonction pour transmettre la requête d'authentification au backend
function forwardAuthRequest($email, $password)
{
	// URL du backend pour l'authentification
	$backendUrl = 'https://app-backend-esgi-app.azurewebsites.net/api-auth.php';

	// Initialiser cURL
	$ch = curl_init($backendUrl);

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

	// Fermer la connexion
	curl_close($ch);

	// Traiter la réponse
	if ($httpCode === 200) {
		$data = json_decode($response, true);
		if ($data && isset($data['success']) && $data['success']) {
			return $data;
		}
	}

	// Échec de l'authentification
	return [
		'success' => false,
		'message' => 'Échec de l\'authentification sur le backend',
		'http_code' => $httpCode
	];
}
