<?php
// Dedicated Notes API Endpoint
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://app-frontend-esgi-app.azurewebsites.net');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/notes_api_errors.log');

// Log the request for diagnostics
error_log(sprintf(
	"[%s] Notes API Request: Method=%s, URI=%s",
	date('Y-m-d H:i:s'),
	$_SERVER['REQUEST_METHOD'],
	$_SERVER['REQUEST_URI']
));

// Handle OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

// Load required controllers
try {
	require_once __DIR__ . '/controllers/NoteController.php';
	require_once __DIR__ . '/controllers/AuthController.php';
	$noteController = new NoteController();
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
	if (!$authController->isLoggedIn()) {
		http_response_code(401);
		echo json_encode([
			'success' => false,
			'message' => 'Authentication required'
		]);
		exit;
	}
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
			$eleve_id = isset($_GET['eleve_id']) ? $_GET['eleve_id'] : null;

			// Handle specific note retrieval
			if ($id) {
				$result = $noteController->getNoteById($id);
				echo json_encode($result);
				exit;
			}
			// Handle notes by student
			else if ($eleve_id) {
				$result = $noteController->getNotesByEleve($eleve_id);
				echo json_encode($result);
				exit;
			}
			// Handle all notes
			else {
				$result = $noteController->getAllNotes();
				echo json_encode($result);
				exit;
			}
			break;

		case 'POST':
			// Get the request body
			$input = file_get_contents('php://input');
			$data = json_decode($input, true);

			// Validate input
			if (!$data || !isset($data['valeur']) || !isset($data['eleve_id']) || !isset($data['matiere_id'])) {
				http_response_code(400);
				echo json_encode([
					'success' => false,
					'message' => 'Missing required fields (valeur, eleve_id, matiere_id)'
				]);
				exit;
			}

			// Add default examen_id if not provided
			if (!isset($data['examen_id'])) {
				$data['examen_id'] = 1; // Default value if not provided
			}

			// Create the note
			$result = $noteController->createNote(
				$data['eleve_id'],
				$data['matiere_id'],
				$data['examen_id'],
				$data['valeur']
			);
			echo json_encode($result);
			exit;
			break;

		case 'PUT':
			// Get the request body
			$input = file_get_contents('php://input');
			$data = json_decode($input, true);

			// Validate input
			if (!$data || !isset($data['id']) || !isset($data['valeur'])) {
				http_response_code(400);
				echo json_encode([
					'success' => false,
					'message' => 'Missing required fields (id, valeur)'
				]);
				exit;
			}

			// Update the note
			$result = $noteController->updateNote($data['id'], $data);
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

			// Delete the note
			$result = $noteController->deleteNote($data['id']);
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
	error_log("Notes API error: " . $e->getMessage());
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => $e->getMessage()
	]);
	exit;
}
