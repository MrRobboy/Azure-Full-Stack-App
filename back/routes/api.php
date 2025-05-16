<?php
// Désactivation de l'affichage des erreurs pour l'API
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Configuration des logs
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

// Log de l'URL et de la méthode
error_log("URL: " . $_SERVER['REQUEST_URI']);
error_log("Méthode: " . $_SERVER['REQUEST_METHOD']);

// Inclusion des fichiers nécessaires
try {
	require_once __DIR__ . '/../controllers/AuthController.php';
	require_once __DIR__ . '/../controllers/NoteController.php';
	require_once __DIR__ . '/../controllers/MatiereController.php';
	require_once __DIR__ . '/../controllers/ClasseController.php';
	require_once __DIR__ . '/../controllers/ExamenController.php';
	require_once __DIR__ . '/../controllers/ProfController.php';
	require_once __DIR__ . '/../controllers/UserController.php';
	require_once __DIR__ . '/../services/ErrorService.php';
} catch (Exception $e) {
	error_log("Erreur lors du chargement des fichiers: " . $e->getMessage());
	sendResponse(['success' => false, 'message' => 'Erreur lors du chargement des fichiers'], 500);
	exit();
}

// Configuration des headers pour les requêtes API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://app-frontend-esgi-app.azurewebsites.net');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Log des headers de la requête
error_log("Headers de la requête: " . print_r(getallheaders(), true));
error_log("Méthode HTTP: " . $_SERVER['REQUEST_METHOD']);
error_log("URI: " . $_SERVER['REQUEST_URI']);
error_log("Raw input: " . file_get_contents('php://input'));

$errorService = ErrorService::getInstance();

// Gestion des requêtes preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// Configurer les cookies pour qu'ils fonctionnent entre domaines sur Azure
$cookieParams = session_get_cookie_params();

// On vérifie si la session est déjà active avant de modifier les paramètres
if (session_status() == PHP_SESSION_NONE) {
	// Obtenir l'origine de la requête
	$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
	$domain = '';

	// Configurer le domaine en fonction de l'environnement
	if (strpos($origin, 'azurewebsites.net') !== false) {
		// En production sur Azure, utiliser .azurewebsites.net pour partager les cookies
		$domain = '.azurewebsites.net';
	} elseif (strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false) {
		// En développement local, laisser le domaine vide pour que ça fonctionne sur localhost
		$domain = '';
	}

	error_log("Configuration session cookie - Origin: $origin, Domain: " . ($domain ?: 'Default'));

	session_set_cookie_params([
		'lifetime' => $cookieParams['lifetime'],
		'path' => '/',
		'domain' => $domain,
		'secure' => $domain !== '', // Secure uniquement si pas en local
		'httponly' => true,
		'samesite' => $domain !== '' ? 'None' : 'Lax' // None pour cross-domain, Lax pour local
	]);

	// Démarrer la session après avoir configuré les paramètres
	session_start();
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
	header('Content-Type: application/json; charset=utf-8');
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => "Erreur serveur: $errstr",
		'debug' => [
			'file' => $errfile,
			'line' => $errline,
			'type' => $errno
		]
	], JSON_UNESCAPED_UNICODE);
	exit();
});

// Gestion des exceptions non capturées
set_exception_handler(function ($e) {
	error_log("Exception non gérée: " . $e->getMessage());
	error_log("Trace: " . $e->getTraceAsString());
	header('Content-Type: application/json; charset=utf-8');
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => $e->getMessage(),
		'debug' => [
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'trace' => $e->getTraceAsString()
		]
	], JSON_UNESCAPED_UNICODE);
	exit();
});

// Gestion des erreurs fatales
register_shutdown_function(function () {
	$error = error_get_last();
	if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
		error_log("Erreur fatale: " . print_r($error, true));
		header('Content-Type: application/json; charset=utf-8');
		http_response_code(500);
		echo json_encode([
			'success' => false,
			'message' => "Erreur fatale: " . $error['message'],
			'debug' => $error
		], JSON_UNESCAPED_UNICODE);
		exit();
	}
});

try {
	error_log("Début de la requête API");
	error_log("Méthode HTTP: " . $method);
	error_log("URI: " . $request_uri);
	error_log("Chemin: " . $path);
	error_log("Segments: " . print_r($segments, true));

	// Routage des requêtes
	if ($path === 'status') {
		$status = [
			'success' => true,
			'message' => 'Le serveur backend est opérationnel',
			'timestamp' => date('Y-m-d H:i:s'),
			'environment' => 'development',
			'api_base_url' => 'https://app-backend-esgi-app.azurewebsites.net/api',
			'php_version' => PHP_VERSION,
			'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
			'request' => [
				'method' => $_SERVER['REQUEST_METHOD'],
				'uri' => $_SERVER['REQUEST_URI'],
				'origin' => $_SERVER['HTTP_ORIGIN'] ?? '*',
				'headers' => getallheaders(),
				'cookies' => $_COOKIE,
			],
			'session' => [
				'status' => session_status(),
				'id' => session_id(),
				'cookie_params' => session_get_cookie_params()
			],
			'cors' => [
				'allowed_origins' => ['https://app-frontend-esgi-app.azurewebsites.net', 'http://localhost', 'http://127.0.0.1'],
				'current_origin' => $_SERVER['HTTP_ORIGIN'] ?? '*',
				'is_allowed' => in_array($_SERVER['HTTP_ORIGIN'] ?? '*', ['https://app-frontend-esgi-app.azurewebsites.net', 'http://localhost', 'http://127.0.0.1']) || strpos($_SERVER['HTTP_ORIGIN'] ?? '', 'azurewebsites.net') !== false
			],
			'database' => [
				'type' => 'sqlsrv',
				'host' => 'sql-esgi-app.database.windows.net',
				'name' => 'sqldb-esgi-app',
				'connected' => true
			]
		];
		sendResponse($status);
	}

	// Routes pour l'authentification
	else if ($segments[0] === 'auth') {
		if ($method === 'POST' && $segments[1] === 'login') {
			// Récupérer les données JSON
			$data = json_decode(file_get_contents('php://input'), true);

			// Vérifier les données
			if (!$data || !isset($data['email']) || !isset($data['password'])) {
				throw new Exception("Données invalides", 400);
			}

			$login = $authController->login($data['email'], $data['password']);
			sendResponse($login);
		}

		// NOUVELLE ROUTE: Vérification des identifiants par GET (uniquement pour l'exercice)
		else if ($method === 'GET' && $segments[1] === 'check-credentials') {
			// Récupérer les paramètres GET
			$email = $_GET['email'] ?? '';
			$password = $_GET['password'] ?? '';

			// Log de la tentative
			error_log("Tentative de vérification d'identifiants via GET pour: $email (NON SÉCURISÉ!)");

			// Vérifier les données
			if (empty($email) || empty($password)) {
				error_log("Données manquantes pour check-credentials");
				sendResponse([
					'success' => false,
					'message' => 'Email et mot de passe requis'
				], 400);
			}

			try {
				// Utiliser le même contrôleur que pour le login normal
				$result = $authController->login($email, $password);
				sendResponse($result);
			} catch (Exception $e) {
				sendResponse([
					'success' => false,
					'message' => $e->getMessage()
				], 401);
			}
		} else if ($method === 'POST' && $segments[1] === 'logout') {
			$logout = $authController->logout();
			sendResponse($logout);
		} else if ($method === 'GET' && $segments[1] === 'user') {
			try {
				checkAuth();
				$user = $authController->getCurrentUser();
				sendResponse($user);
			} catch (Exception $e) {
				handleError($e);
			}
		} else {
			throw new Exception("Route d'authentification non trouvée", 404);
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
				error_log("Erreur dans la route matieres: " . $e->getMessage());
				error_log("Trace: " . $e->getTraceAsString());
				sendResponse(['success' => false, 'message' => $e->getMessage()], 500);
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
				sendResponse($result);
			} catch (Exception $e) {
				error_log("Erreur: " . $e->getMessage());
				sendResponse(['success' => false, 'error' => $e->getMessage()], 400);
			}
		} else {
			error_log("Méthode non autorisée: " . $method);
			sendResponse(['message' => 'Méthode non autorisée'], 405);
		}
	}

	// Routes des classes
	if ($segments[0] === 'classes') {
		try {
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
		} catch (PDOException $e) {
			error_log("Erreur de base de données dans la route classes: " . $e->getMessage());
			sendResponse([
				'success' => false,
				'message' => 'Erreur de connexion à la base de données',
				'error' => $e->getMessage()
			], 500);
		} catch (Exception $e) {
			error_log("Erreur dans la route classes: " . $e->getMessage());
			sendResponse([
				'success' => false,
				'message' => $e->getMessage()
			], $e->getCode() ?: 500);
		}
	}

	// Routes des examens
	if ($segments[0] === 'examens') {
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
		try {
			if ($method === 'GET') {
				if (isset($segments[1])) {
					error_log("Récupération du professeur: " . $segments[1]);
					$result = $profController->getProfById($segments[1]);
					error_log("Résultat: " . print_r($result, true));
					sendResponse($result);
				} else {
					error_log("Récupération de tous les professeurs");
					$result = $profController->getAllProfs();
					error_log("Résultat: " . print_r($result, true));
					sendResponse($result);
				}
			} elseif ($method === 'POST') {
				$data = json_decode(file_get_contents('php://input'), true);
				if (!$data || !isset($data['nom']) || !isset($data['prenom']) || !isset($data['email']) || !isset($data['password'])) {
					throw new Exception("Tous les champs sont obligatoires");
				}
				$result = $profController->createProf($data);
				sendResponse($result);
			} elseif ($method === 'PUT' && isset($segments[1])) {
				$data = json_decode(file_get_contents('php://input'), true);
				if (!$data || !isset($data['nom']) || !isset($data['prenom']) || !isset($data['email'])) {
					throw new Exception("Tous les champs sont obligatoires");
				}
				$result = $profController->updateProf($segments[1], $data);
				sendResponse($result);
			} elseif ($method === 'DELETE' && isset($segments[1])) {
				$result = $profController->deleteProf($segments[1]);
				sendResponse($result);
			} else {
				throw new Exception("Méthode non autorisée");
			}
		} catch (Exception $e) {
			error_log("Erreur dans la route profs: " . $e->getMessage());
			sendResponse(['success' => false, 'message' => $e->getMessage()], 500);
		}
	}

	// Routes pour les utilisateurs
	if ($segments[0] === 'users') {
		checkAuth();

		if ($method === 'GET') {
			if (isset($segments[1]) && $segments[1] === 'classe' && isset($segments[2])) {
				$classeId = $segments[2];
				$users = $userController->getUsersByClasse($classeId);
				sendResponse($users);
			} else if (isset($segments[1])) {
				$user = $userController->getUserById($segments[1]);
				sendResponse($user);
			} else {
				$users = $userController->getAllUsers();
				sendResponse($users);
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

	// Routes pour les privilèges des étudiants
	if ($segments[0] === 'privileges') {
		checkAuth();

		try {
			// Créer une instance du modèle de privilèges
			require_once __DIR__ . '/../models/UserPrivilege.php';
			$privilegeModel = new UserPrivilege();

			if ($method === 'GET') {
				if (isset($segments[1]) && $segments[1] === 'students') {
					// Récupérer tous les privilèges des étudiants
					$privileges = $privilegeModel->getAllPrivileges();
					sendResponse([
						'success' => true,
						'data' => $privileges
					]);
				} elseif (isset($segments[1]) && is_numeric($segments[1])) {
					// Récupérer le privilège d'un étudiant spécifique
					$minNote = $privilegeModel->getMinNoteForUser($segments[1]);
					if ($minNote !== null) {
						sendResponse([
							'success' => true,
							'data' => [
								'id_user' => (int)$segments[1],
								'min_note' => (float)$minNote
							]
						]);
					} else {
						sendResponse([
							'success' => false,
							'message' => 'Aucun privilège trouvé pour cet étudiant'
						], 404);
					}
				} else {
					throw new Exception("Route de privilège invalide", 400);
				}
			} elseif ($method === 'POST') {
				// Ajouter un nouveau privilège
				$data = json_decode(file_get_contents('php://input'), true);
				if (!$data || !isset($data['id_user']) || !isset($data['min_note'])) {
					throw new Exception("Les champs id_user et min_note sont obligatoires");
				}

				// Vérifier que la note minimale est valide
				if (!is_numeric($data['min_note']) || $data['min_note'] < 0 || $data['min_note'] > 20) {
					throw new Exception("La note minimale doit être un nombre entre 0 et 20");
				}

				$result = $privilegeModel->addPrivilege($data['id_user'], $data['min_note']);
				if ($result) {
					sendResponse([
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
			} elseif ($method === 'PUT' && isset($segments[1]) && is_numeric($segments[1])) {
				// Mettre à jour un privilège existant
				$data = json_decode(file_get_contents('php://input'), true);
				if (!$data || !isset($data['min_note'])) {
					throw new Exception("Le champ min_note est obligatoire");
				}

				// Vérifier que la note minimale est valide
				if (!is_numeric($data['min_note']) || $data['min_note'] < 0 || $data['min_note'] > 20) {
					throw new Exception("La note minimale doit être un nombre entre 0 et 20");
				}

				$result = $privilegeModel->addPrivilege($segments[1], $data['min_note']);
				if ($result) {
					sendResponse([
						'success' => true,
						'message' => 'Privilège mis à jour avec succès',
						'data' => [
							'id_user' => (int)$segments[1],
							'min_note' => (float)$data['min_note']
						]
					]);
				} else {
					throw new Exception("Erreur lors de la mise à jour du privilège");
				}
			} elseif ($method === 'DELETE' && isset($segments[1]) && is_numeric($segments[1])) {
				// Supprimer un privilège
				$result = $privilegeModel->removePrivilege($segments[1]);
				if ($result) {
					sendResponse([
						'success' => true,
						'message' => 'Privilège supprimé avec succès'
					]);
				} else {
					throw new Exception("Erreur lors de la suppression du privilège");
				}
			} else {
				throw new Exception("Méthode non autorisée", 405);
			}
		} catch (Exception $e) {
			error_log("Erreur dans la route privileges: " . $e->getMessage());
			sendResponse([
				'success' => false,
				'message' => $e->getMessage()
			], $e->getCode() ?: 500);
		}
	}

	// Route non trouvée
	throw new Exception("Route non trouvée", 404);
} catch (Exception $e) {
	handleError($e);
}
