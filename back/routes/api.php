<?php
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/NoteController.php';
require_once __DIR__ . '/../controllers/MatiereController.php';
require_once __DIR__ . '/../controllers/ClasseController.php';
require_once __DIR__ . '/../controllers/ExamenController.php';

// Configuration des headers pour les requêtes API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gestion des requêtes OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit();
}

// Récupération de l'URL et de la méthode HTTP
$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Extraction du chemin de l'API
$path = parse_url($request_uri, PHP_URL_PATH);
$path = str_replace('/api', '', $path);
$path = trim($path, '/');
$segments = explode('/', $path);

// Initialisation des contrôleurs
$authController = new AuthController();
$noteController = new NoteController();
$matiereController = new MatiereController();
$classeController = new ClasseController();
$examenController = new ExamenController();

// Fonction pour envoyer une réponse JSON
function sendResponse($data, $status = 200)
{
	http_response_code($status);
	echo json_encode($data);
	exit();
}

// Fonction pour vérifier l'authentification
function checkAuth()
{
	global $authController;
	if (!$authController->isLoggedIn()) {
		sendResponse(['error' => 'Non authentifié'], 401);
	}
}

try {
	// Routes d'authentification
	if ($segments[0] === 'auth') {
		if ($method === 'POST' && $segments[1] === 'login') {
			$data = json_decode(file_get_contents('php://input'), true);
			$result = $authController->login($data['email'], $data['password']);

			if ($result['success']) {
				sendResponse(['message' => $result['message']]);
			} else {
				sendResponse(['error' => $result['error']], $result['code']);
			}
		} elseif ($method === 'POST' && $segments[1] === 'logout') {
			$result = $authController->logout();
			if ($result['success']) {
				sendResponse(['message' => $result['message']]);
			} else {
				sendResponse(['error' => $result['error']], $result['code']);
			}
		}
	}

	// Vérification de l'authentification pour les autres routes
	checkAuth();

	// Routes des notes
	if ($segments[0] === 'notes') {
		if ($method === 'GET') {
			if (isset($segments[1])) {
				if ($segments[1] === 'eleve' && isset($segments[2])) {
					sendResponse($noteController->getNotesByEleve($segments[2]));
				} else {
					sendResponse($noteController->getNoteById($segments[1]));
				}
			} else {
				sendResponse($noteController->getAllNotes());
			}
		} elseif ($method === 'POST') {
			$data = json_decode(file_get_contents('php://input'), true);
			$result = $noteController->createNote(
				$data['id_eleve'],
				$data['id_matiere'],
				$data['id_examen'],
				$data['valeur']
			);
			sendResponse(['id' => $result], 201);
		} elseif ($method === 'PUT' && isset($segments[1])) {
			$data = json_decode(file_get_contents('php://input'), true);
			$noteController->updateNote(
				$segments[1],
				$data['id_eleve'],
				$data['id_matiere'],
				$data['id_examen'],
				$data['valeur']
			);
			sendResponse(['message' => 'Note mise à jour']);
		} elseif ($method === 'DELETE' && isset($segments[1])) {
			$noteController->deleteNote($segments[1]);
			sendResponse(['message' => 'Note supprimée']);
		}
	}

	// Routes des matières
	if ($segments[0] === 'matieres') {
		if ($method === 'GET') {
			if (isset($segments[1])) {
				sendResponse($matiereController->getMatiereById($segments[1]));
			} else {
				sendResponse($matiereController->getAllMatieres());
			}
		} elseif ($method === 'POST') {
			$data = json_decode(file_get_contents('php://input'), true);
			$result = $matiereController->createMatiere($data['nom']);
			sendResponse(['id' => $result], 201);
		} elseif ($method === 'PUT' && isset($segments[1])) {
			$data = json_decode(file_get_contents('php://input'), true);
			$matiereController->updateMatiere($segments[1], $data['nom']);
			sendResponse(['message' => 'Matière mise à jour']);
		} elseif ($method === 'DELETE' && isset($segments[1])) {
			$matiereController->deleteMatiere($segments[1]);
			sendResponse(['message' => 'Matière supprimée']);
		}
	}

	// Routes des classes
	if ($segments[0] === 'classes') {
		if ($method === 'GET') {
			if (isset($segments[1])) {
				if ($segments[1] === 'eleves' && isset($segments[2])) {
					sendResponse($classeController->getElevesByClasse($segments[2]));
				} else {
					sendResponse($classeController->getClasseById($segments[1]));
				}
			} else {
				sendResponse($classeController->getAllClasses());
			}
		} elseif ($method === 'POST') {
			$data = json_decode(file_get_contents('php://input'), true);
			$result = $classeController->createClasse(
				$data['nom_classe'],
				$data['niveau'],
				$data['numero']
			);
			sendResponse(['id' => $result], 201);
		} elseif ($method === 'PUT' && isset($segments[1])) {
			$data = json_decode(file_get_contents('php://input'), true);
			$classeController->updateClasse(
				$segments[1],
				$data['nom_classe'],
				$data['niveau'],
				$data['numero']
			);
			sendResponse(['message' => 'Classe mise à jour']);
		} elseif ($method === 'DELETE' && isset($segments[1])) {
			$classeController->deleteClasse($segments[1]);
			sendResponse(['message' => 'Classe supprimée']);
		}
	}

	// Routes des examens
	if ($segments[0] === 'examens') {
		if ($method === 'GET') {
			if (isset($segments[1])) {
				sendResponse($examenController->getExamenById($segments[1]));
			} else {
				sendResponse($examenController->getAllExamens());
			}
		} elseif ($method === 'POST') {
			$data = json_decode(file_get_contents('php://input'), true);
			$result = $examenController->createExamen(
				$data['nom_examen'],
				$data['date_examen'],
				$data['coefficient']
			);
			sendResponse(['id' => $result], 201);
		} elseif ($method === 'PUT' && isset($segments[1])) {
			$data = json_decode(file_get_contents('php://input'), true);
			$examenController->updateExamen(
				$segments[1],
				$data['nom_examen'],
				$data['date_examen'],
				$data['coefficient']
			);
			sendResponse(['message' => 'Examen mis à jour']);
		} elseif ($method === 'DELETE' && isset($segments[1])) {
			$examenController->deleteExamen($segments[1]);
			sendResponse(['message' => 'Examen supprimé']);
		}
	}

	// Route non trouvée
	sendResponse(['error' => 'Route non trouvée'], 404);
} catch (Exception $e) {
	sendResponse(['error' => $e->getMessage()], 400);
}
