<?php

/**
 * Post Handler pour Azure
 * 
 * Ce fichier est conçu pour intercepter et traiter toutes les requêtes POST
 * reçues par le backend, afin d'éviter les problèmes 404.
 */

// Activer tous les en-têtes CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
header('Access-Control-Max-Age: 86400');

// Log des requêtes pour diagnostic
error_log('[POST-HANDLER] Requête reçue: ' . $_SERVER['REQUEST_URI']);
error_log('[POST-HANDLER] Méthode: ' . $_SERVER['REQUEST_METHOD']);
error_log('[POST-HANDLER] Origine: ' . ($_SERVER['HTTP_ORIGIN'] ?? 'Inconnue'));

// Gestion spéciale des requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(204);
	exit;
}

// Si la requête n'est pas une requête POST, passer au script suivant
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	error_log('[POST-HANDLER] Méthode non POST, acheminement vers index.php');
	include 'index.php';
	exit;
}

// Récupérer l'URI demandée et les données POST
$request_uri = $_SERVER['REQUEST_URI'];
$input = file_get_contents('php://input');
$post_data = json_decode($input, true);

error_log('[POST-HANDLER] Données reçues: ' . $input);

// Définir le type de contenu comme JSON par défaut
header('Content-Type: application/json');

// Parser l'URI pour déterminer l'endpoint
$path = parse_url($request_uri, PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));
$endpoint = $segments[0] ?? '';

error_log('[POST-HANDLER] Endpoint détecté: ' . $endpoint);

// Routes POST spécifiques
$valid_endpoints = [
	'login',
	'users',
	'matieres',
	'classes',
	'profs',
	'exams',
	'notes',
	'auth',
	'register',
	'status'
];

// Vérifier si l'endpoint est valide ou passer à index.php
if (in_array($endpoint, $valid_endpoints)) {
	error_log('[POST-HANDLER] Endpoint valide, traitement...');

	// Vérifier s'il existe un contrôleur pour cet endpoint
	$controller_file = __DIR__ . '/controllers/' . $endpoint . '_controller.php';

	if (file_exists($controller_file)) {
		error_log('[POST-HANDLER] Contrôleur trouvé, inclusion...');
		include $controller_file;

		// Après l'inclusion, vérifier si une fonction de traitement POST existe
		$function_name = 'handle_' . $endpoint . '_post';

		if (function_exists($function_name)) {
			error_log('[POST-HANDLER] Fonction de traitement trouvée, exécution...');
			$result = $function_name($post_data, $segments);
			echo json_encode($result);
			exit;
		}
	}

	// Si aucun contrôleur spécifique n'est trouvé, utiliser le contrôleur par défaut
	error_log('[POST-HANDLER] Pas de contrôleur spécifique, utilisation de index.php');
	include 'index.php';
	exit;
} else {
	// Endpoint non reconnu, passer à index.php
	error_log('[POST-HANDLER] Endpoint non reconnu, utilisation de index.php');
	include 'index.php';
	exit;
}

// Si l'exécution arrive ici, c'est qu'aucune route n'a été trouvée
error_log('[POST-HANDLER] Aucune route trouvée, réponse 404');
http_response_code(404);
echo json_encode([
	'success' => false,
	'message' => 'Endpoint not found: ' . $endpoint,
	'request_uri' => $request_uri
]);
exit;
