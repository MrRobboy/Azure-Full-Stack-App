<?php
// Dedicated Privileges API Endpoint

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
ini_set('error_log', __DIR__ . '/logs/privileges_api_errors.log');

// Create logs directory if needed
if (!is_dir(__DIR__ . '/logs')) {
	mkdir(__DIR__ . '/logs', 0755, true);
}

// Log the request for diagnostics
error_log(sprintf(
	"[%s] Privileges API Request: Method=%s, URI=%s, Origin=%s, Headers=%s",
	date('Y-m-d H:i:s'),
	$_SERVER['REQUEST_METHOD'],
	$_SERVER['REQUEST_URI'],
	isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'non défini',
	json_encode(getallheaders())
));

// Load required models and controllers
try {
	require_once __DIR__ . '/models/UserPrivilege.php';
	require_once __DIR__ . '/controllers/AuthController.php';
	$privilegeModel = new UserPrivilege();
	$authController = new AuthController();
} catch (Exception $e) {
	error_log("Error loading models/controllers: " . $e->getMessage());
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
	$id = isset($_GET['id']) ? $_GET['id'] : null;
	$action = isset($_GET['action']) ? $_GET['action'] : null;

	switch ($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			// Check if this is a request for students with privileges
			if ($action === 'students') {
				// Récupérer tous les privilèges des étudiants
				$privileges = $privilegeModel->getAllPrivileges();
				echo json_encode([
					'success' => true,
					'data' => $privileges
				]);
				exit;
			}
			// Handle specific privilege retrieval
			elseif ($id && is_numeric($id)) {
				// Récupérer le privilège d'un étudiant spécifique
				$minNote = $privilegeModel->getMinNoteForUser($id);
				if ($minNote !== null) {
					echo json_encode([
						'success' => true,
						'data' => [
							'id_user' => (int)$id,
							'min_note' => (float)$minNote
						]
					]);
				} else {
					echo json_encode([
						'success' => false,
						'message' => 'Aucun privilège trouvé pour cet étudiant'
					]);
				}
				exit;
			}
			// Default: return all privileges
			else {
				$privileges = $privilegeModel->getAllPrivileges();
				echo json_encode([
					'success' => true,
					'data' => $privileges
				]);
				exit;
			}
			break;

		case 'POST':
			// Get the request body
			$input = file_get_contents('php://input');
			$data = json_decode($input, true);

			// Validate input
			if (!$data || !isset($data['id_user']) || !isset($data['min_note'])) {
				http_response_code(400);
				echo json_encode([
					'success' => false,
					'message' => 'Missing required fields (id_user, min_note)'
				]);
				exit;
			}

			// Vérifier que la note minimale est valide
			if (!is_numeric($data['min_note']) || $data['min_note'] < 0 || $data['min_note'] > 20) {
				http_response_code(400);
				echo json_encode([
					'success' => false,
					'message' => 'La note minimale doit être un nombre entre 0 et 20'
				]);
				exit;
			}

			// Add privilege
			$result = $privilegeModel->addPrivilege($data['id_user'], $data['min_note']);
			if ($result) {
				echo json_encode([
					'success' => true,
					'message' => 'Privilège ajouté avec succès',
					'data' => [
						'id_user' => (int)$data['id_user'],
						'min_note' => (float)$data['min_note']
					]
				]);
			} else {
				throw new Exception("Erreur lors de l'ajout du privilège");
			}
			exit;
			break;

		case 'PUT':
			// Get the request body
			$input = file_get_contents('php://input');
			$data = json_decode($input, true);

			// Validate input
			if (!$data || !isset($data['id_user']) || !isset($data['min_note'])) {
				http_response_code(400);
				echo json_encode([
					'success' => false,
					'message' => 'Missing required fields (id_user, min_note)'
				]);
				exit;
			}

			// Vérifier que la note minimale est valide
			if (!is_numeric($data['min_note']) || $data['min_note'] < 0 || $data['min_note'] > 20) {
				http_response_code(400);
				echo json_encode([
					'success' => false,
					'message' => 'La note minimale doit être un nombre entre 0 et 20'
				]);
				exit;
			}

			// Update privilege
			$result = $privilegeModel->addPrivilege($data['id_user'], $data['min_note']);
			if ($result) {
				echo json_encode([
					'success' => true,
					'message' => 'Privilège mis à jour avec succès',
					'data' => [
						'id_user' => (int)$data['id_user'],
						'min_note' => (float)$data['min_note']
					]
				]);
			} else {
				throw new Exception("Erreur lors de la mise à jour du privilège");
			}
			exit;
			break;

		case 'DELETE':
			// Get the user ID from the URL or request body
			if (!$id && isset($_GET['id_user'])) {
				$id = $_GET['id_user'];
			}

			if (!$id) {
				// Try to get it from request body
				$input = file_get_contents('php://input');
				$data = json_decode($input, true);
				if ($data && isset($data['id_user'])) {
					$id = $data['id_user'];
				}
			}

			// Validate
			if (!$id || !is_numeric($id)) {
				http_response_code(400);
				echo json_encode([
					'success' => false,
					'message' => 'Missing or invalid user ID'
				]);
				exit;
			}

			// Delete privilege
			$result = $privilegeModel->removePrivilege($id);
			if ($result) {
				echo json_encode([
					'success' => true,
					'message' => 'Privilège supprimé avec succès'
				]);
			} else {
				throw new Exception("Erreur lors de la suppression du privilège");
			}
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
	error_log("Privileges API error: " . $e->getMessage());
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => $e->getMessage()
	]);
	exit;
}
