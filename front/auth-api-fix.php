<?php

/**
 * Auth API Fix - Solution alternative pour l'authentification
 */

// Configuration des erreurs et de la journalisation
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Création du répertoire de logs si nécessaire
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
	mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/auth-api-fix.log');

// Journalisation de base
error_log("Auth API Fix accessed: " . $_SERVER['REQUEST_URI']);
error_log("Method: " . $_SERVER['REQUEST_METHOD']);

// Configuration CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Traiter OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode([
		'success' => false,
		'message' => 'Method not allowed'
	]);
	exit;
}

// Récupérer les données d'authentification
$input = file_get_contents('php://input');
$data = json_decode($input, true);

error_log("Données reçues: " . json_encode(array_keys($data)));

// Valider les données
if (!$data || !isset($data['email']) || !isset($data['password'])) {
	http_response_code(400);
	echo json_encode([
		'success' => false,
		'message' => 'Missing email or password'
	]);
	exit;
}

// Tester avec des identifiants de test fixes
$testUsers = [
	'admin@example.com' => [
		'password' => 'admin123',
		'id' => 1,
		'nom' => 'Admin',
		'prenom' => 'Test',
		'role' => 'admin'
	],
	'prof@example.com' => [
		'password' => 'prof123',
		'id' => 2,
		'nom' => 'Professeur',
		'prenom' => 'Test',
		'role' => 'prof'
	],
	'user@example.com' => [
		'password' => 'user123',
		'id' => 3,
		'nom' => 'Utilisateur',
		'prenom' => 'Test',
		'role' => 'user'
	]
];

// Vérifier les identifiants
$email = $data['email'];
$password = $data['password'];

if (isset($testUsers[$email]) && $testUsers[$email]['password'] === $password) {
	// Authentification réussie
	$user = $testUsers[$email];

	// Générer un token simple pour les tests
	$token = base64_encode(json_encode([
		'user_id' => $user['id'],
		'email' => $email,
		'exp' => time() + 3600
	]));

	// Démarrer la session
	session_start();
	$_SESSION['user_id'] = $user['id'];
	$_SESSION['user_email'] = $email;
	$_SESSION['user_role'] = $user['role'];

	// Retourner la réponse de succès
	echo json_encode([
		'success' => true,
		'message' => 'Authentification réussie',
		'data' => [
			'user' => [
				'id' => $user['id'],
				'email' => $email,
				'nom' => $user['nom'],
				'prenom' => $user['prenom'],
				'role' => $user['role']
			],
			'token' => $token,
			'expires_in' => 3600
		]
	]);
} else {
	// Authentification échouée
	http_response_code(401);
	echo json_encode([
		'success' => false,
		'message' => 'Identifiants invalides'
	]);
}
