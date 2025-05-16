<?php

/**
 * Direct API Data Provider
 * 
 * This file provides direct API response data without requiring backend connection.
 * It serves as a fallback when proxies fail or backend is unreachable.
 * 
 * Supports multiple endpoints:
 * - /matieres (GET, POST, PUT, DELETE)
 * - /classes (GET)
 * - /examens (GET)
 * - /professeurs (GET)
 * - /admin/users (GET)
 */

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// Parse the request URL to determine which endpoint is being requested
$requestUri = $_SERVER['REQUEST_URI'];
$queryParams = [];

// Parse query parameters if present
if (strpos($requestUri, '?') !== false) {
	parse_str(parse_url($requestUri, PHP_URL_QUERY), $queryParams);
}

// Get the endpoint from query parameter or from the path
$endpoint = isset($queryParams['endpoint']) ? $queryParams['endpoint'] : $requestUri;

// Extract the resource type from the endpoint
$resourceType = '';
if (strpos($endpoint, 'matieres') !== false) {
	$resourceType = 'matieres';
} elseif (strpos($endpoint, 'classes') !== false) {
	$resourceType = 'classes';
} elseif (strpos($endpoint, 'examens') !== false) {
	$resourceType = 'examens';
} elseif (strpos($endpoint, 'professeurs') !== false) {
	$resourceType = 'professeurs';
} elseif (strpos($endpoint, 'admin/users') !== false) {
	$resourceType = 'users';
}

// Static data for different resources
$data = [
	'matieres' => [
		[
			'id_matiere' => 1,
			'nom' => 'Mathématiques'
		],
		[
			'id_matiere' => 2,
			'nom' => 'Français'
		],
		[
			'id_matiere' => 3,
			'nom' => 'Anglais'
		],
		[
			'id_matiere' => 4,
			'nom' => 'Histoire-Géographie'
		],
		[
			'id_matiere' => 5,
			'nom' => 'Physique-Chimie'
		],
		[
			'id_matiere' => 6,
			'nom' => 'SVT'
		],
		[
			'id_matiere' => 7,
			'nom' => 'Philosophie'
		],
		[
			'id_matiere' => 8,
			'nom' => 'Sport'
		],
		[
			'id_matiere' => 9,
			'nom' => 'Musique'
		],
		[
			'id_matiere' => 10,
			'nom' => 'Arts Plastiques'
		],
		[
			'id_matiere' => 11,
			'nom' => 'Informatique'
		],
		[
			'id_matiere' => 12,
			'nom' => 'Programmation'
		],
		[
			'id_matiere' => 13,
			'nom' => 'Base de données'
		],
		[
			'id_matiere' => 14,
			'nom' => 'Réseaux'
		],
		[
			'id_matiere' => 15,
			'nom' => 'Systèmes'
		],
		[
			'id_matiere' => 16,
			'nom' => 'Docker'
		],
		[
			'id_matiere' => 17,
			'nom' => 'Azure'
		],
		[
			'id_matiere' => 18,
			'nom' => 'Droits'
		]
	],
	'classes' => [
		[
			'id_classe' => 1,
			'nom' => 'Terminale S',
			'annee' => '2023-2024'
		],
		[
			'id_classe' => 2,
			'nom' => 'Terminale ES',
			'annee' => '2023-2024'
		],
		[
			'id_classe' => 3,
			'nom' => 'Terminale L',
			'annee' => '2023-2024'
		],
		[
			'id_classe' => 4,
			'nom' => 'Première S',
			'annee' => '2023-2024'
		],
		[
			'id_classe' => 5,
			'nom' => 'Seconde A',
			'annee' => '2023-2024'
		]
	],
	'examens' => [
		[
			'id_examen' => 1,
			'titre' => 'Bac blanc Mathématiques',
			'date' => '2023-12-15',
			'coefficient' => 2,
			'id_matiere' => 1,
			'id_classe' => 1
		],
		[
			'id_examen' => 2,
			'titre' => 'Contrôle Français',
			'date' => '2023-11-20',
			'coefficient' => 1,
			'id_matiere' => 2,
			'id_classe' => 2
		],
		[
			'id_examen' => 3,
			'titre' => 'Examen final Anglais',
			'date' => '2024-01-10',
			'coefficient' => 1.5,
			'id_matiere' => 3,
			'id_classe' => 3
		],
		[
			'id_examen' => 4,
			'titre' => 'Contrôle Azure',
			'date' => '2023-12-05',
			'coefficient' => 2,
			'id_matiere' => 17,
			'id_classe' => 4
		]
	],
	'professeurs' => [
		[
			'id_professeur' => 1,
			'nom' => 'Dupont',
			'prenom' => 'Jean',
			'email' => 'jean.dupont@example.com'
		],
		[
			'id_professeur' => 2,
			'nom' => 'Martin',
			'prenom' => 'Sophie',
			'email' => 'sophie.martin@example.com'
		],
		[
			'id_professeur' => 3,
			'nom' => 'Bernard',
			'prenom' => 'Pierre',
			'email' => 'pierre.bernard@example.com'
		],
		[
			'id_professeur' => 4,
			'nom' => 'Petit',
			'prenom' => 'Marie',
			'email' => 'marie.petit@example.com'
		]
	],
	'users' => [
		[
			'id' => 1,
			'nom' => 'Admin',
			'prenom' => 'System',
			'email' => 'admin@example.com',
			'role' => 'admin'
		],
		[
			'id' => 2,
			'nom' => 'Dupont',
			'prenom' => 'Jean',
			'email' => 'jean.dupont@example.com',
			'role' => 'professeur'
		],
		[
			'id' => 3,
			'nom' => 'Martin',
			'prenom' => 'Sophie',
			'email' => 'sophie.martin@example.com',
			'role' => 'professeur'
		]
	]
];

// Handle POST requests to add a new resource
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $resourceType === 'matieres') {
	$inputData = json_decode(file_get_contents('php://input'), true);

	if (isset($inputData['nom']) && !empty($inputData['nom'])) {
		// Find the highest ID
		$maxId = 0;
		foreach ($data['matieres'] as $matiere) {
			if ($matiere['id_matiere'] > $maxId) {
				$maxId = $matiere['id_matiere'];
			}
		}

		// Create new matière with incremented ID
		$newMatiere = [
			'id_matiere' => $maxId + 1,
			'nom' => $inputData['nom']
		];

		// Simulate successful addition
		echo json_encode([
			'success' => true,
			'message' => 'Ressource ajoutée avec succès (simulation)',
			'data' => $newMatiere,
			'is_direct' => true
		]);
		exit;
	} else {
		http_response_code(400);
		echo json_encode([
			'success' => false,
			'message' => 'Données requises manquantes',
			'is_direct' => true
		]);
		exit;
	}
}

// Handle PUT requests to update a resource
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && $resourceType === 'matieres') {
	// Extract ID from URL path
	$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	$segments = explode('/', $path);
	$id = end($segments);

	if (is_numeric($id)) {
		$inputData = json_decode(file_get_contents('php://input'), true);

		if (isset($inputData['nom']) && !empty($inputData['nom'])) {
			// Simulate successful update
			echo json_encode([
				'success' => true,
				'message' => 'Ressource modifiée avec succès (simulation)',
				'data' => [
					'id_matiere' => (int)$id,
					'nom' => $inputData['nom']
				],
				'is_direct' => true
			]);
			exit;
		} else {
			http_response_code(400);
			echo json_encode([
				'success' => false,
				'message' => 'Données requises manquantes',
				'is_direct' => true
			]);
			exit;
		}
	}
}

// Handle DELETE requests to remove a resource
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $resourceType === 'matieres') {
	// Extract ID from URL path
	$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	$segments = explode('/', $path);
	$id = end($segments);

	if (is_numeric($id)) {
		// Simulate successful deletion
		echo json_encode([
			'success' => true,
			'message' => 'Ressource supprimée avec succès (simulation)',
			'id' => (int)$id,
			'is_direct' => true
		]);
		exit;
	}
}

// Default: Return data for the requested resource type
if ($resourceType && isset($data[$resourceType])) {
	echo json_encode([
		'success' => true,
		'data' => $data[$resourceType],
		'message' => "Données fournies par direct-matieres.php pour endpoint: $endpoint",
		'is_direct' => true
	]);
} else {
	// Unknown resource type
	echo json_encode([
		'success' => false,
		'message' => 'Endpoint non pris en charge par direct-matieres.php',
		'requested_endpoint' => $endpoint,
		'is_direct' => true
	]);
}
