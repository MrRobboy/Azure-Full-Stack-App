<?php

/**
 * Direct Matières Provider
 * 
 * This is a specialized endpoint that directly returns matières data
 * without requiring any proxy or backend connection.
 * 
 * It can be used as a direct fallback when all other methods fail.
 */

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// Static matières data
$matieres = [
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
];

// Handle POST requests to add a new matière
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$data = json_decode(file_get_contents('php://input'), true);

	if (isset($data['nom']) && !empty($data['nom'])) {
		// Find the highest ID
		$maxId = 0;
		foreach ($matieres as $matiere) {
			if ($matiere['id_matiere'] > $maxId) {
				$maxId = $matiere['id_matiere'];
			}
		}

		// Create new matière with incremented ID
		$newMatiere = [
			'id_matiere' => $maxId + 1,
			'nom' => $data['nom']
		];

		// Simulate successful addition
		echo json_encode([
			'success' => true,
			'message' => 'Matière ajoutée avec succès (simulation)',
			'data' => $newMatiere,
			'is_direct' => true
		]);
		exit;
	} else {
		http_response_code(400);
		echo json_encode([
			'success' => false,
			'message' => 'Le nom de la matière est requis',
			'is_direct' => true
		]);
		exit;
	}
}

// Handle PUT requests to update a matière
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
	// Extract ID from URL path
	$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	$segments = explode('/', $path);
	$id = end($segments);

	if (is_numeric($id)) {
		$data = json_decode(file_get_contents('php://input'), true);

		if (isset($data['nom']) && !empty($data['nom'])) {
			// Simulate successful update
			echo json_encode([
				'success' => true,
				'message' => 'Matière modifiée avec succès (simulation)',
				'data' => [
					'id_matiere' => (int)$id,
					'nom' => $data['nom']
				],
				'is_direct' => true
			]);
			exit;
		} else {
			http_response_code(400);
			echo json_encode([
				'success' => false,
				'message' => 'Le nom de la matière est requis',
				'is_direct' => true
			]);
			exit;
		}
	}
}

// Handle DELETE requests to remove a matière
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
	// Extract ID from URL path
	$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	$segments = explode('/', $path);
	$id = end($segments);

	if (is_numeric($id)) {
		// Simulate successful deletion
		echo json_encode([
			'success' => true,
			'message' => 'Matière supprimée avec succès (simulation)',
			'id' => (int)$id,
			'is_direct' => true
		]);
		exit;
	}
}

// Default: Return all matières for GET request
echo json_encode([
	'success' => true,
	'data' => $matieres,
	'message' => 'Données fournies par direct-matieres.php (sans proxy)',
	'is_direct' => true
]);
