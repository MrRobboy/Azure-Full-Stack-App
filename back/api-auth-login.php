<?php
// Dedicated Authentication API Endpoint - For direct login access

// IMPORTANT: Définir les en-têtes CORS avant toute autre opération
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // Modification pour accepter toutes les origines en développement
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

// Forcer les en-têtes de cache pour éviter les problèmes de mise en cache
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// Désactiver le buffer de sortie pour s'assurer que les en-têtes sont envoyés immédiatement
if (ob_get_level()) ob_end_clean();

// Traiter immédiatement les requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/auth_api_errors.log');

// Create logs directory if needed
if (!is_dir(__DIR__ . '/logs')) {
	mkdir(__DIR__ . '/logs', 0755, true);
}

// Log the request for diagnostics
error_log(sprintf(
	"[%s] Auth API Request: Method=%s, URI=%s, Origin=%s, Headers=%s",
	date('Y-m-d H:i:s'),
	$_SERVER['REQUEST_METHOD'],
	$_SERVER['REQUEST_URI'],
	isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'non défini',
	json_encode(getallheaders())
));

// Information de débogage
error_log("Contenu de la requête: " . file_get_contents('php://input'));

// FALLBACK CREDENTIALS - Si l'authentification de base fonctionne lorsque AuthController échoue
$fallbackCredentials = [
	'admin@example.com' => [
		'password' => 'admin123',
		'role' => 'admin',
		'id' => 'admin_1'
	],
	'prof@example.com' => [
		'password' => 'prof123',
		'role' => 'prof',
		'id' => 'prof_1'
	],
	'user@example.com' => [
		'password' => 'user123',
		'role' => 'user',
		'id' => 'user_1'
	]
];

// Fonction pour générer un token JWT
function generateJWT($userId, $email, $role = 'user')
{
	// Header
	$header = json_encode([
		'typ' => 'JWT',
		'alg' => 'HS256'
	]);

	// Payload avec les champs spécifiques attendus par l'application
	$payload = json_encode([
		'sub' => $userId,
		'email' => $email,
		'role' => $role,
		'iat' => time(),
		'exp' => time() + (60 * 60 * 24), // 24 heures
		'azp' => 'esgi-azure-app',
		'iss' => 'esgi-auth-service'
	]);

	// Encoder header et payload en Base64Url
	$base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
	$base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

	// Signature
	$signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'esgi_azure_secret_key', true);
	$base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

	// Token complet
	return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

// Load the AuthController
$authController = null;
$useAuthController = true;

try {
	require_once __DIR__ . '/controllers/AuthController.php';
	$authController = new AuthController();
	error_log("AuthController chargé avec succès");
} catch (Exception $e) {
	error_log("Error loading AuthController: " . $e->getMessage());
	$useAuthController = false; // Utiliser les credentials de secours
}

// Process the request
try {
	// Handle POST login request
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		// Get the request body
		$input = file_get_contents('php://input');
		$data = json_decode($input, true);

		// Log the input (without password)
		$safe_data = $data;
		if (isset($safe_data['password'])) {
			$safe_data['password'] = '******';
		}
		error_log("Login attempt with data: " . json_encode($safe_data));

		// Validate input
		if (!$data || !isset($data['email']) || !isset($data['password'])) {
			http_response_code(400);
			echo json_encode([
				'success' => false,
				'message' => 'Missing required fields (email, password)'
			]);
			exit;
		}

		$email = $data['email'];
		$password = $data['password'];

		// Attempt login with AuthController
		if ($useAuthController) {
			try {
				error_log("Tentative d'authentification via AuthController");
				$result = $authController->login($email, $password);
				error_log("Authentification réussie via AuthController");

				// Return the result
				echo json_encode($result);
				exit;
			} catch (Exception $e) {
				error_log("Échec de l'authentification via AuthController: " . $e->getMessage());
				// Si AuthController échoue, on essaie avec les credentials de secours
			}
		}

		// Si on arrive ici, soit AuthController a échoué, soit il n'est pas disponible
		// On essaie avec les credentials de secours
		error_log("Tentative d'authentification via les credentials de secours");
		if (array_key_exists($email, $fallbackCredentials) && $fallbackCredentials[$email]['password'] === $password) {
			error_log("Authentification réussie via les credentials de secours");

			// Générer un token JWT
			$token = generateJWT($fallbackCredentials[$email]['id'], $email, $fallbackCredentials[$email]['role']);

			// Retourner le résultat
			echo json_encode([
				'success' => true,
				'message' => 'Connexion réussie (credentials de secours)',
				'user' => [
					'id' => $fallbackCredentials[$email]['id'],
					'email' => $email,
					'role' => $fallbackCredentials[$email]['role']
				],
				'token' => $token
			]);
			exit;
		} else {
			error_log("Échec de l'authentification via les credentials de secours");
			http_response_code(401);
			echo json_encode([
				'success' => false,
				'message' => 'Email ou mot de passe incorrect',
				'fallback_used' => true
			]);
			exit;
		}
	}
	// Handle GET credential check (alternative method)
	else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['email']) && isset($_GET['password'])) {
		$email = $_GET['email'];
		$password = $_GET['password'];

		// Log the attempt (without password)
		error_log("GET credential check for: " . $email);

		// Attempt login with AuthController
		if ($useAuthController) {
			try {
				$result = $authController->login($email, $password);
				echo json_encode($result);
				exit;
			} catch (Exception $e) {
				error_log("Échec de l'authentification via AuthController (GET): " . $e->getMessage());
				// Si AuthController échoue, on essaie avec les credentials de secours
			}
		}

		// Essai avec les credentials de secours
		if (array_key_exists($email, $fallbackCredentials) && $fallbackCredentials[$email]['password'] === $password) {
			$token = generateJWT($fallbackCredentials[$email]['id'], $email, $fallbackCredentials[$email]['role']);

			echo json_encode([
				'success' => true,
				'message' => 'Connexion réussie (credentials de secours)',
				'user' => [
					'id' => $fallbackCredentials[$email]['id'],
					'email' => $email,
					'role' => $fallbackCredentials[$email]['role']
				],
				'token' => $token
			]);
			exit;
		} else {
			http_response_code(401);
			echo json_encode([
				'success' => false,
				'message' => 'Email ou mot de passe incorrect',
				'fallback_used' => true
			]);
			exit;
		}
	}
	// Check if this is a test credentials request
	else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'test-credentials') {
		// Get a random professor or use a default one for testing
		try {
			$database = new Database();
			$db = $database->getConnection();
			$query = "SELECT TOP 1 email, 'prof123' AS default_password FROM prof ORDER BY id_prof";
			$stmt = $db->prepare($query);
			$stmt->execute();

			$result = $stmt->fetch(PDO::FETCH_ASSOC);

			if ($result) {
				echo json_encode([
					'success' => true,
					'credentials' => [
						'email' => $result['email'],
						'password' => $result['default_password']
					]
				]);
			} else {
				// Fallback to default if no professors in DB
				echo json_encode([
					'success' => true,
					'credentials' => [
						'email' => 'admin@example.com',
						'password' => 'admin123'
					]
				]);
			}
		} catch (PDOException $e) {
			error_log('Database error: ' . $e->getMessage());
			// Fallback to default credentials if DB error
			echo json_encode([
				'success' => true,
				'credentials' => [
					'email' => 'admin@example.com',
					'password' => 'admin123'
				]
			]);
		}
		exit;
	}
	// Invalid request method
	else {
		http_response_code(405);
		echo json_encode([
			'success' => false,
			'message' => 'Method not allowed. Use POST for login or GET with credentials.'
		]);
		exit;
	}
} catch (Exception $e) {
	error_log("Auth error: " . $e->getMessage());
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => $e->getMessage()
	]);
	exit;
}
