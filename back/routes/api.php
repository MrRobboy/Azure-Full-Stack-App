<?php
// Désactivation de l'affichage des erreurs pour l'API
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Configuration des logs
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/apache2/php_errors.log');

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/NoteController.php';
require_once __DIR__ . '/../controllers/MatiereController.php';
require_once __DIR__ . '/../controllers/ClasseController.php';
require_once __DIR__ . '/../controllers/ExamenController.php';
require_once __DIR__ . '/../controllers/ProfController.php';
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../services/ErrorService.php';

// Configuration des headers pour les requêtes API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:727');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept');

// Log des headers de la requête
error_log("Headers de la requête: " . print_r(getallheaders(), true));
error_log("Méthode HTTP: " . $_SERVER['REQUEST_METHOD']);
error_log("URI: " . $_SERVER['REQUEST_URI']);
error_log("Raw input: " . file_get_contents('php://input'));

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
$profController = new ProfController();
$userController = new UserController();

// Fonction pour envoyer une réponse JSON
function sendResponse($data, $status = 200)
{
	http_response_code($status);
	header('Content-Type: application/json; charset=utf-8');

	// Vérification que les données sont valides
	if ($data === null) {
		$data = ['success' => false, 'message' => 'Données invalides'];
	}

	// Assure que la réponse a toujours un format cohérent
	if (!isset($data['success'])) {
		$data = [
			'success' => $status >= 200 && $status < 300,
			'data' => $data
		];
	}

	// Log de la réponse avant l'encodage
	error_log("Données à encoder: " . print_r($data, true));

	// Tentative d'encodage JSON
	$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

	// Vérification des erreurs d'encodage
	if ($json === false) {
		error_log("Erreur d'encodage JSON: " . json_last_error_msg());
		$data = [
			'success' => false,
			'message' => 'Erreur d\'encodage JSON: ' . json_last_error_msg()
		];
		$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	// Log de la réponse encodée
	error_log("Réponse JSON: " . $json);

	// Envoi de la réponse
	echo $json;
	exit();
}

// Fonction pour vérifier l'authentification
function checkAuth()
{
	global $authController;
	try {
		if (!$authController->isLoggedIn()) {
			throw new Exception("Non authentifié", 401);
		}
		return true;
	} catch (Exception $e) {
		throw $e;
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
		sendResponse([
			'success' => false,
			'message' => $message
		], $status);
	}
}

// Gestion des erreurs PHP
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
	error_log("Erreur PHP [$errno]: $errstr dans $errfile à la ligne $errline");
	sendResponse([
		'success' => false,
		'message' => "Erreur serveur: $errstr",
		'debug' => [
			'file' => $errfile,
			'line' => $errline,
			'type' => $errno
		]
	], 500);
	return true;
});

// Gestion des exceptions non capturées
set_exception_handler(function ($e) {
	error_log("Exception non gérée: " . $e->getMessage());
	error_log("Trace: " . $e->getTraceAsString());
	sendResponse([
		'success' => false,
		'message' => $e->getMessage(),
		'debug' => [
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'trace' => $e->getTraceAsString()
		]
	], 500);
});

// Gestion des erreurs fatales
register_shutdown_function(function () {
	$error = error_get_last();
	if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
		error_log("Erreur fatale: " . print_r($error, true));
		sendResponse([
			'success' => false,
			'message' => "Erreur fatale: " . $error['message'],
			'debug' => $error
		], 500);
	}
});

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
				} else if ($segments[1] === 'exam' && isset($segments[2])) {
					sendResponse($noteController->getNotesByExamen($segments[2]));
				} else {
					sendResponse($noteController->getNoteById($segments[1]));
				}
			} else {
				sendResponse($noteController->getAllNotes());
			}
		} elseif ($method === 'POST') {
			$data = json_decode(file_get_contents('php://input'), true);
			if (!$data || !isset($data['id_eleve']) || !isset($data['id_matiere']) || !isset($data['id_examen']) || !isset($data['valeur'])) {
				throw new Exception("Tous les champs sont obligatoires");
			}
			$result = $noteController->createNote(
				$data['id_eleve'],
				$data['id_matiere'],
				$data['id_examen'],
				$data['valeur']
			);
			sendResponse($result);
		} elseif ($method === 'PUT' && isset($segments[1])) {
			$data = json_decode(file_get_contents('php://input'), true);
			if (!$data || !isset($data['valeur'])) {
				throw new Exception("La valeur de la note est obligatoire");
			}
			$result = $noteController->updateNote($segments[1], $data['valeur']);
			sendResponse($result);
		} elseif ($method === 'DELETE' && isset($segments[1])) {
			$result = $noteController->deleteNote($segments[1]);
			sendResponse($result);
		} else {
			throw new Exception(json_encode(
				$errorService->logError('api', 'Route de notes non trouvée', ['uri' => $segments])
			), 404);
		}
	}

	// Routes des matières
	if ($segments[0] === 'matieres') {
		error_log("Traitement de la route matieres - " . date('Y-m-d H:i:s'));

		if ($method === 'GET') {
			try {
				if (isset($segments[1])) {
					error_log("Récupération de la matière avec l'ID: " . $segments[1]);
					$result = $matiereController->getMatiereById($segments[1]);
					error_log("Données de la matière: " . print_r($result, true));
					sendResponse($result);
				} else {
					error_log("Récupération de toutes les matières");
					$result = $matiereController->getAllMatieres();
					error_log("Données des matières: " . print_r($result, true));
					sendResponse($result);
				}
			} catch (Exception $e) {
				error_log("Erreur: " . $e->getMessage());
				sendResponse(['message' => $e->getMessage()], 500);
			}
		} elseif ($method === 'POST') {
			try {
				error_log("Création d'une nouvelle matière");
				$data = json_decode(file_get_contents('php://input'), true);
				error_log("Données reçues: " . print_r($data, true));

				if (!$data || !isset($data['nom'])) {
					throw new Exception("Le nom de la matière est requis");
				}

				$result = $matiereController->createMatiere($data);
				error_log("Matière créée avec succès: " . print_r($result, true));
				sendResponse($result, 201);
			} catch (Exception $e) {
				error_log("Erreur: " . $e->getMessage());
				sendResponse(['message' => $e->getMessage()], 400);
			}
		} elseif ($method === 'PUT' && isset($segments[1])) {
			try {
				error_log("Mise à jour de la matière avec l'ID: " . $segments[1]);
				$data = json_decode(file_get_contents('php://input'), true);
				error_log("Données reçues: " . print_r($data, true));

				if (!$data || !isset($data['nom'])) {
					throw new Exception("Le nom de la matière est requis");
				}

				$result = $matiereController->updateMatiere($segments[1], $data);
				error_log("Matière mise à jour avec succès: " . print_r($result, true));
				sendResponse($result);
			} catch (Exception $e) {
				error_log("Erreur: " . $e->getMessage());
				sendResponse(['message' => $e->getMessage()], 400);
			}
		} elseif ($method === 'DELETE' && isset($segments[1])) {
			try {
				error_log("Suppression de la matière avec l'ID: " . $segments[1]);
				$result = $matiereController->deleteMatiere($segments[1]);
				error_log("Matière supprimée avec succès");
				sendResponse(['message' => 'Matière supprimée avec succès']);
			} catch (Exception $e) {
				error_log("Erreur: " . $e->getMessage());
				sendResponse(['message' => $e->getMessage()], 400);
			}
		} else {
			error_log("Méthode non autorisée: " . $method);
			sendResponse(['message' => 'Méthode non autorisée'], 405);
		}
	}

	// Routes des classes
	if ($segments[0] === 'classes') {
		if ($method === 'GET') {
			if (isset($segments[1])) {
				if ($segments[1] === 'eleves' && isset($segments[2])) {
					error_log("Récupération des élèves de la classe: " . $segments[2]);
					$result = $classeController->getElevesByClasse($segments[2]);
					error_log("Résultat de getElevesByClasse: " . print_r($result, true));
					sendResponse($result);
				} else if ($segments[1] === 'etudiants' && isset($segments[2])) {
					error_log("Récupération des étudiants de la classe: " . $segments[2]);
					$result = $classeController->getElevesByClasse($segments[2]);
					error_log("Résultat de getElevesByClasse: " . print_r($result, true));
					sendResponse($result);
				} else {
					error_log("Récupération de la classe: " . $segments[1]);
					$result = $classeController->getClasseById($segments[1]);
					error_log("Résultat: " . print_r($result, true));
					sendResponse($result);
				}
			} else {
				error_log("Récupération de toutes les classes");
				$result = $classeController->getAllClasses();
				error_log("Résultat: " . print_r($result, true));
				sendResponse($result);
			}
		} elseif ($method === 'POST') {
			$data = json_decode(file_get_contents('php://input'), true);
			if (!$data || !isset($data['nom_classe']) || !isset($data['niveau']) || !isset($data['numero']) || !isset($data['rythme'])) {
				throw new Exception("Tous les champs sont obligatoires");
			}
			$result = $classeController->createClasse(
				$data['nom_classe'],
				$data['niveau'],
				$data['numero'],
				$data['rythme']
			);
			sendResponse($result);
		} elseif ($method === 'PUT' && isset($segments[1])) {
			$data = json_decode(file_get_contents('php://input'), true);
			if (!$data || !isset($data['nom_classe']) || !isset($data['niveau']) || !isset($data['numero']) || !isset($data['rythme'])) {
				throw new Exception("Tous les champs sont obligatoires");
			}
			$result = $classeController->updateClasse(
				$segments[1],
				$data['nom_classe'],
				$data['niveau'],
				$data['numero'],
				$data['rythme']
			);
			sendResponse($result);
		} elseif ($method === 'DELETE' && isset($segments[1])) {
			$result = $classeController->deleteClasse($segments[1]);
			sendResponse($result);
		} else {
			throw new Exception("Méthode non autorisée", 405);
		}
	}

	// Routes des examens
	if ($segments[0] === 'exams') {
		error_log("Traitement de la requête d'examens");
		if ($method === 'GET') {
			if (isset($segments[1])) {
				sendResponse($examenController->getExamenById($segments[1]));
			} else {
				sendResponse($examenController->getAllExamens());
			}
		} elseif ($method === 'POST') {
			$data = json_decode(file_get_contents('php://input'), true);
			if (!$data || !isset($data['titre']) || !isset($data['matiere']) || !isset($data['classe']) || !isset($data['date'])) {
				throw new Exception("Tous les champs sont obligatoires (titre, matiere, classe, date)");
			}
			$result = $examenController->createExamen(
				$data['titre'],
				$data['matiere'],
				$data['classe'],
				$data['date']
			);
			sendResponse($result);
		} elseif ($method === 'PUT' && isset($segments[1])) {
			$data = json_decode(file_get_contents('php://input'), true);
			if (!$data || !isset($data['titre']) || !isset($data['matiere']) || !isset($data['classe']) || !isset($data['date'])) {
				throw new Exception("Tous les champs sont obligatoires (titre, matiere, classe, date)");
			}
			$result = $examenController->updateExamen(
				$segments[1],
				$data['titre'],
				$data['matiere'],
				$data['classe'],
				$data['date']
			);
			sendResponse($result);
		} elseif ($method === 'DELETE' && isset($segments[1])) {
			sendResponse($examenController->deleteExamen($segments[1]));
		} else {
			throw new Exception(json_encode(
				$errorService->logError('api', 'Route d\'examens non trouvée', ['uri' => $segments])
			), 404);
		}
	}

	// Routes des professeurs
	if ($segments[0] === 'profs') {
		error_log("Traitement de la route profs - " . date('Y-m-d H:i:s'));

		if ($method === 'GET') {
			try {
				if (isset($segments[1])) {
					error_log("Récupération du professeur avec l'ID: " . $segments[1]);
					$result = $profController->getProfById($segments[1]);
					error_log("Données du professeur: " . print_r($result, true));
					sendResponse($result);
				} else {
					error_log("Récupération de tous les professeurs");
					$result = $profController->getAllProfs();
					error_log("Données des professeurs: " . print_r($result, true));
					sendResponse($result);
				}
			} catch (Exception $e) {
				error_log("Erreur: " . $e->getMessage());
				sendResponse(['message' => $e->getMessage()], 500);
			}
		} elseif ($method === 'POST') {
			try {
				error_log("Création d'un nouveau professeur");
				$data = json_decode(file_get_contents('php://input'), true);
				error_log("Données reçues: " . print_r($data, true));

				if (!$data || !isset($data['nom']) || !isset($data['prenom']) || !isset($data['email']) || !isset($data['password'])) {
					throw new Exception("Tous les champs sont obligatoires");
				}

				$result = $profController->createProf($data);
				error_log("Professeur créé avec succès: " . print_r($result, true));
				sendResponse($result, 201);
			} catch (Exception $e) {
				error_log("Erreur: " . $e->getMessage());
				sendResponse(['message' => $e->getMessage()], 400);
			}
		} elseif ($method === 'PUT' && isset($segments[1])) {
			try {
				error_log("Mise à jour du professeur avec l'ID: " . $segments[1]);
				$data = json_decode(file_get_contents('php://input'), true);
				error_log("Données reçues: " . print_r($data, true));

				if (!$data || !isset($data['nom']) || !isset($data['prenom']) || !isset($data['email'])) {
					throw new Exception("Tous les champs sont obligatoires");
				}

				$result = $profController->updateProf($segments[1], $data);
				error_log("Professeur mis à jour avec succès: " . print_r($result, true));
				sendResponse($result);
			} catch (Exception $e) {
				error_log("Erreur: " . $e->getMessage());
				sendResponse(['message' => $e->getMessage()], 400);
			}
		} elseif ($method === 'DELETE' && isset($segments[1])) {
			try {
				error_log("Suppression du professeur avec l'ID: " . $segments[1]);
				$result = $profController->deleteProf($segments[1]);
				error_log("Professeur supprimé avec succès");
				sendResponse(['message' => 'Professeur supprimé avec succès']);
			} catch (Exception $e) {
				error_log("Erreur: " . $e->getMessage());
				sendResponse(['message' => $e->getMessage()], 400);
			}
		} else {
			error_log("Méthode non autorisée: " . $method);
			sendResponse(['message' => 'Méthode non autorisée'], 405);
		}
	}

	// Routes pour les utilisateurs
	if ($segments[0] === 'users') {
		if ($method === 'GET') {
			try {
				$result = $userController->getAllUsers();
				sendResponse($result);
			} catch (Exception $e) {
				sendResponse(['success' => false, 'message' => $e->getMessage()], 500);
			}
		} elseif ($method === 'POST') {
			try {
				$data = json_decode(file_get_contents('php://input'), true);
				$result = $userController->createUser($data);
				sendResponse($result, 201);
			} catch (Exception $e) {
				sendResponse(['success' => false, 'message' => $e->getMessage()], 400);
			}
		} elseif ($method === 'PUT' && isset($segments[1])) {
			try {
				$data = json_decode(file_get_contents('php://input'), true);
				$result = $userController->updateUser($segments[1], $data);
				sendResponse($result);
			} catch (Exception $e) {
				sendResponse(['success' => false, 'message' => $e->getMessage()], 400);
			}
		} elseif ($method === 'DELETE' && isset($segments[1])) {
			try {
				$result = $userController->deleteUser($segments[1]);
				sendResponse($result);
			} catch (Exception $e) {
				sendResponse(['success' => false, 'message' => $e->getMessage()], 400);
			}
		}
	}

	// Route non trouvée
	throw new Exception("Route non trouvée", 404);
} catch (Exception $e) {
	handleError($e);
}
