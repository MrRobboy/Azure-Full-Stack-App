<?php
// Activation de l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuration des logs
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/apache2/php_errors.log');

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../../back/controllers/AuthController.php';
require_once __DIR__ . '/../../back/controllers/NoteController.php';
require_once __DIR__ . '/../../back/controllers/MatiereController.php';
require_once __DIR__ . '/../../back/controllers/ClasseController.php';
require_once __DIR__ . '/../../back/controllers/ExamenController.php';
require_once __DIR__ . '/../../back/services/ErrorService.php';

// Configuration des headers pour les requêtes API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$errorService = ErrorService::getInstance();

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
	header('Content-Type: application/json; charset=utf-8');

	// Vérification que les données sont valides
	if ($data === null) {
		$data = ['success' => false, 'message' => 'Données invalides'];
	}

	// Conversion des données en tableau si nécessaire
	if (!is_array($data)) {
		$data = ['success' => true, 'data' => $data];
	}

	// Si c'est une réponse d'erreur, on s'assure qu'elle a le bon format
	if ($status >= 400 || (isset($data['success']) && $data['success'] === false)) {
		if (!isset($data['message'])) {
			$data['message'] = $data['error'] ?? 'Une erreur est survenue';
			unset($data['error']);
		}
		$data['success'] = false;
	} else {
		$data['success'] = true;
	}

	$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

	if ($json === false) {
		error_log("Erreur d'encodage JSON: " . json_last_error_msg());
		error_log("Données à encoder: " . print_r($data, true));
		$json = json_encode([
			'success' => false,
			'message' => 'Erreur d\'encodage JSON'
		], JSON_UNESCAPED_UNICODE);
	}

	error_log("Réponse envoyée: " . $json);
	echo $json;
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

// Fonction pour gérer les erreurs
function handleError($e)
{
	error_log("Erreur dans l'API: " . $e->getMessage());
	error_log("Trace: " . $e->getTraceAsString());

	$status = $e->getCode() ?: 500;
	$message = $e->getMessage();

	// Vérification si le message est déjà un JSON
	$error_data = json_decode($message, true);
	if (json_last_error() === JSON_ERROR_NONE) {
		sendResponse($error_data, $status);
	} else {
		sendResponse(['error' => 'Une erreur est survenue', 'message' => $message], $status);
	}
}

try {
	error_log("Début de la requête API");
	error_log("Méthode HTTP: " . $method);
	error_log("URI: " . $request_uri);
	error_log("Chemin: " . $path);
	error_log("Segments: " . print_r($segments, true));

	// Routes d'authentification
	if ($segments[0] === 'auth') {
		if ($method === 'POST' && $segments[1] === 'login') {
			error_log("Traitement de la requête de login");

			$data = json_decode(file_get_contents('php://input'), true);
			error_log("Données reçues: " . print_r($data, true));

			if (!$data) {
				error_log("Données JSON invalides");
				throw new Exception(json_encode(
					$errorService->logError('api', 'Données JSON invalides', ['input' => file_get_contents('php://input')])
				), 400);
			}

			$result = $authController->login($data['email'], $data['password']);
			error_log("Réponse du contrôleur: " . print_r($result, true));

			if ($result['success']) {
				sendResponse(['message' => $result['message']]);
			} else {
				sendResponse(['error' => $result['error']], $result['code']);
			}
		} elseif ($method === 'POST' && $segments[1] === 'logout') {
			error_log("Traitement de la requête de logout");

			$result = $authController->logout();
			if ($result['success']) {
				sendResponse(['message' => $result['message']]);
			} else {
				sendResponse(['error' => $result['error']], $result['code']);
			}
		} else {
			error_log("Route d'authentification non trouvée");
			throw new Exception(json_encode(
				$errorService->logError('api', 'Route d\'authentification non trouvée', ['uri' => $segments])
			), 404);
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
		error_log("Traitement de la route matieres");

		if ($method === 'GET') {
			if (isset($segments[1])) {
				error_log("Récupération de la matière avec l'ID: " . $segments[1]);
				$result = $matiereController->getMatiereById($segments[1]);
				error_log("Résultat brut: " . print_r($result, true));

				if (!$result['success']) {
					sendResponse(['message' => $result['error']], 404);
				}
				sendResponse($result['data']);
			} else {
				error_log("Récupération de toutes les matières");
				$result = $matiereController->getAllMatieres();
				error_log("Résultat brut: " . print_r($result, true));

				if (!$result['success']) {
					sendResponse(['message' => $result['error']], 500);
				}
				sendResponse($result['data']);
			}
		} elseif ($method === 'POST') {
			error_log("Création d'une nouvelle matière");
			$data = json_decode(file_get_contents('php://input'), true);
			error_log("Données reçues: " . print_r($data, true));

			if (!$data || !isset($data['nom'])) {
				sendResponse(['message' => 'Le nom de la matière est requis'], 400);
			}

			$result = $matiereController->createMatiere($data);
			error_log("Résultat: " . print_r($result, true));

			if (!$result['success']) {
				sendResponse(['message' => $result['error']], 400);
			}
			sendResponse(['message' => 'Matière créée avec succès', 'data' => $result['data']], 201);
		} elseif ($method === 'PUT' && isset($segments[1])) {
			error_log("Mise à jour de la matière avec l'ID: " . $segments[1]);
			$data = json_decode(file_get_contents('php://input'), true);
			error_log("Données reçues: " . print_r($data, true));

			if (!$data || !isset($data['nom'])) {
				sendResponse(['message' => 'Le nom de la matière est requis'], 400);
			}

			$result = $matiereController->updateMatiere($segments[1], $data);
			error_log("Résultat: " . print_r($result, true));

			if (!$result['success']) {
				sendResponse(['message' => $result['error']], 400);
			}
			sendResponse(['message' => 'Matière mise à jour avec succès', 'data' => $result['data']]);
		} elseif ($method === 'DELETE' && isset($segments[1])) {
			error_log("Suppression de la matière avec l'ID: " . $segments[1]);
			$result = $matiereController->deleteMatiere($segments[1]);
			error_log("Résultat: " . print_r($result, true));

			if (!$result['success']) {
				sendResponse(['message' => $result['error']], 400);
			}
			sendResponse(['message' => 'Matière supprimée avec succès']);
		} else {
			sendResponse(['message' => 'Méthode non autorisée'], 405);
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
	throw new Exception("Route non trouvée", 404);
} catch (Exception $e) {
	handleError($e);
}
