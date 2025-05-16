<?php

/**
 * Mock d'API local - Simule les ressources protégées pour le développement
 * Date de génération: 2025-05-16
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
ini_set('error_log', $logDir . '/local-api-mock.log');

// Journaliser l'accès
error_log("Mock API accédé: " . $_SERVER['REQUEST_URI']);
error_log("Méthode: " . $_SERVER['REQUEST_METHOD']);

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

// Obtenir l'endpoint demandé
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
if (empty($endpoint)) {
	http_response_code(400);
	echo json_encode([
		'success' => false,
		'message' => 'Paramètre endpoint manquant'
	]);
	exit;
}

// Extraire le token d'autorisation
$token = null;
$headers = getallheaders();
foreach ($headers as $name => $value) {
	if (strtolower($name) === 'authorization') {
		if (preg_match('/^Bearer\s+(.*)$/i', $value, $matches)) {
			$token = $matches[1];
		}
		break;
	}
}

// Vérifier si le token est un token local
$isLocalToken = strpos($token, 'LOCAL_AUTH.') === 0;

// Fonction pour décoder le token local
function decodeLocalToken($token)
{
	$parts = explode('.', $token);
	if (count($parts) < 2) {
		return null;
	}

	try {
		$payload = json_decode(base64_decode($parts[1]), true);
		if (!$payload) {
			return null;
		}

		// Vérifier si le token est expiré
		if (isset($payload['exp']) && $payload['exp'] < time()) {
			return null;
		}

		return $payload;
	} catch (Exception $e) {
		return null;
	}
}

// Vérifier l'authentification
if (!$token) {
	http_response_code(401);
	echo json_encode([
		'success' => false,
		'message' => 'Authentication required'
	]);
	exit;
}

// Si c'est un token local, le décoder
if ($isLocalToken) {
	$userData = decodeLocalToken($token);
	if (!$userData) {
		http_response_code(401);
		echo json_encode([
			'success' => false,
			'message' => 'Invalid or expired token'
		]);
		exit;
	}
} else {
	// Si ce n'est pas un token local, on redirige vers le backend réel
	http_response_code(302);
	echo json_encode([
		'success' => false,
		'message' => 'This is a mock API. For real backend access, use the optimal-proxy.php',
		'redirectTo' => 'optimal-proxy.php?endpoint=' . urlencode($endpoint)
	]);
	exit;
}

// Données mockées pour le développement
$mockData = [
	'api-notes.php' => [
		'success' => true,
		'message' => 'Données mockées pour le développement',
		'data' => [
			'matieres' => [
				[
					'id' => 1,
					'nom' => 'Mathématiques',
					'notes' => [
						['id' => 1, 'valeur' => 15, 'date' => '2025-05-12'],
						['id' => 2, 'valeur' => 17, 'date' => '2025-05-14']
					]
				],
				[
					'id' => 2,
					'nom' => 'Physique',
					'notes' => [
						['id' => 3, 'valeur' => 13, 'date' => '2025-05-13'],
						['id' => 4, 'valeur' => 18, 'date' => '2025-05-15']
					]
				],
				[
					'id' => 3,
					'nom' => 'Informatique',
					'notes' => [
						['id' => 5, 'valeur' => 19, 'date' => '2025-05-11'],
						['id' => 6, 'valeur' => 20, 'date' => '2025-05-16']
					]
				]
			]
		],
		'user' => $userData
	],
	'user-profile.php' => [
		'success' => true,
		'message' => 'Profil utilisateur',
		'data' => [
			'profile' => array_merge([
				'createdAt' => '2025-01-01',
				'lastLogin' => date('Y-m-d H:i:s'),
				'status' => 'active',
				'preferences' => [
					'theme' => 'light',
					'notifications' => true
				]
			], $userData)
		]
	],
	'default' => [
		'success' => true,
		'message' => 'Endpoint mocké générique',
		'endpoint' => $endpoint,
		'data' => [
			'info' => 'Ceci est une donnée mockée pour le développement',
			'timestamp' => time(),
			'user' => $userData
		]
	]
];

// Déterminer quelle donnée mockée renvoyer
$response = null;

if (strpos($endpoint, 'notes') !== false) {
	$response = $mockData['api-notes.php'];
} elseif (strpos($endpoint, 'profile') !== false || strpos($endpoint, 'user') !== false) {
	$response = $mockData['user-profile.php'];
} else {
	$response = $mockData['default'];
}

// Ajouter un délai artificiel pour simuler la latence réseau (optionnel)
// usleep(200000); // 200ms

// Renvoyer la réponse
http_response_code(200);
echo json_encode($response);
