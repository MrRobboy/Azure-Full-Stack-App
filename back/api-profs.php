<?php
// Dedicated Profs API Endpoint

// IMPORTANT: Définir les en-têtes CORS avant toute autre opération
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://app-frontend-esgi-app.azurewebsites.net');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
ini_set('error_log', __DIR__ . '/logs/profs_api_errors.log');

// Create logs directory if needed
if (!is_dir(__DIR__ . '/logs')) {
	mkdir(__DIR__ . '/logs', 0755, true);
}

// Log the request for diagnostics
error_log(sprintf(
	"[%s] Profs API Request: Method=%s, URI=%s, Origin=%s, Headers=%s",
	date('Y-m-d H:i:s'),
	$_SERVER['REQUEST_METHOD'],
	$_SERVER['REQUEST_URI'],
	isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'non défini',
	json_encode(getallheaders())
));

// Load required controllers
try {
	require_once __DIR__ . '/controllers/ProfController.php';
	require_once __DIR__ . '/controllers/AuthController.php';
	$profController = new ProfController();
	$authController = new AuthController();
} catch (Exception $e) {
	error_log("Error loading controllers: " . $e->getMessage());
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => 'Server configuration error',
		'error' => $e->getMessage()
	]);
	exit;
}

// Check authentication
try {
	// Authentication check removed for simplified access
	error_log("Authentication check bypassed for simplified access");

	/* Original code commented out
	if (!$authController->isLoggedIn()) {
		http_response_code(401);
		echo json_encode([
			'success' => false,
			'message' => 'Authentication required'
		]);
		exit;
	}
	*/
} catch (Exception $e) {
	error_log("Auth error: " . $e->getMessage());
	http_response_code(401);
	echo json_encode([
		'success' => false,
		'message' => 'Authentication error: ' . $e->getMessage()
	]);
	exit;
}

// Process the request based on method
try {
	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			// Get query parameters
			$id = isset($_GET['id']) ? $_GET['id'] : null;

			// Handle specific prof retrieval
			if ($id) {
				$result = $profController->getProfById($id);
				echo json_encode($result);
				exit;
			}
			// Handle all profs
			else {
				$result = $profController->getAllProfs();
				echo json_encode($result);
				exit;
			}
			break;

		case 'POST':
			// Get the request body
			$input = file_get_contents('php://input');
			$data = json_decode($input, true);

			// Validate input
			if (!$data || !isset($data['nom']) || !isset($data['prenom']) || !isset($data['email']) || !isset($data['password'])) {
				http_response_code(400);
				echo json_encode([
					'success' => false,
					'message' => 'Missing required fields (nom, prenom, email, password)'
				]);
				exit;
			}

			// Create the prof
			$result = $profController->createProf($data);
			echo json_encode($result);
			exit;
			break;

		case 'PUT':
			// Get the request body
			$input = file_get_contents('php://input');
			$data = json_decode($input, true);

			// Validate input
			if (!$data || !isset($data['id']) || !isset($data['nom']) || !isset($data['prenom']) || !isset($data['email'])) {
				http_response_code(400);
				echo json_encode([
					'success' => false,
					'message' => 'Missing required fields (id, nom, prenom, email)'
				]);
				exit;
			}

			// Update the prof
			$result = $profController->updateProf($data['id'], $data);
			echo json_encode($result);
			exit;
			break;

		case 'DELETE':
			// Get the request body
			$input = file_get_contents('php://input');
			$data = json_decode($input, true);

			// Validate input
			if (!$data || !isset($data['id'])) {
				http_response_code(400);
				echo json_encode([
					'success' => false,
					'message' => 'Missing required field (id)'
				]);
				exit;
			}

			// Delete the prof
			$result = $profController->deleteProf($data['id']);
			echo json_encode($result);
			exit;
			break;

		default:
			http_response_code(405);
			echo json_encode([
				'success' => false,
				'message' => 'Method not allowed'
			]);
			exit;
	}
} catch (Exception $e) {
	error_log("Profs API error: " . $e->getMessage());
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => $e->getMessage()
	]);
	exit;
}
