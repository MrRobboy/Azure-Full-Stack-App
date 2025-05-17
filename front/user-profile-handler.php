<?php
// Gestionnaire spécifique pour l'endpoint user/profile
header('Content-Type: application/json');

// Log d'accès
error_log("Profile handler accessed");

// Simuler un profil utilisateur basé sur les informations de session
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

// Si l'utilisateur a une session active
if (isset($_SESSION['prof_id'])) {
	// Récupérer les infos depuis la session
	$userData = [
		'id' => $_SESSION['prof_id'] ?? 1,
		'nom' => $_SESSION['prof_nom'] ?? 'Admin',
		'prenom' => $_SESSION['prof_prenom'] ?? 'User',
		'email' => $_SESSION['prof_email'] ?? 'admin@example.com',
		'role' => 'prof', // Rôle par défaut
	];

	// Si on a un JWT stocké, essayer de l'utiliser pour obtenir plus d'informations
	$jwt = null;
	if (isset($_SERVER['HTTP_AUTHORIZATION']) && strpos($_SERVER['HTTP_AUTHORIZATION'], 'Bearer ') === 0) {
		$jwt = substr($_SERVER['HTTP_AUTHORIZATION'], 7);
	}

	if ($jwt) {
		// Décoder le JWT pour extraire des informations supplémentaires
		$tokenParts = explode('.', $jwt);
		if (count($tokenParts) === 3) {
			$payload = json_decode(base64_decode($tokenParts[1]), true);
			if ($payload && isset($payload['email'])) {
				// Enrichir les données utilisateur avec celles du token
				if (isset($payload['role'])) $userData['role'] = $payload['role'];
				if (isset($payload['sub'])) $userData['id'] = $payload['sub'];
			}
		}
	}

	// Réponse formatée comme attendu par le frontend
	$response = [
		'success' => true,
		'user' => $userData,
		'message' => 'Profil utilisateur récupéré avec succès'
	];

	error_log("Profile data: " . json_encode($userData));
	echo json_encode($response);
} else {
	// Utilisateur non authentifié
	http_response_code(401);
	echo json_encode([
		'success' => false,
		'message' => 'Utilisateur non authentifié',
		'session_status' => session_status(),
		'session_id' => session_id()
	]);
}
