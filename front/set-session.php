<?php
session_start();

// Récupérer les données JSON envoyées
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data) {
	// Stocker les informations dans la session
	$_SESSION['prof_id'] = $data['id_prof'];
	$_SESSION['prof_nom'] = $data['nom'];
	$_SESSION['prof_prenom'] = $data['prenom'];
	$_SESSION['prof_role'] = $data['role'] ?? 'Enseignant';

	// Répondre avec succès
	http_response_code(200);
	echo json_encode(['success' => true]);
} else {
	// Répondre avec une erreur
	http_response_code(400);
	echo json_encode(['success' => false, 'message' => 'Données invalides']);
}
