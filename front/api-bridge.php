<?php

/**
 * API Bridge Fallback
 * Un proxy de secours qui redirige simplement vers le proxy unifié
 */

// Si un endpoint est spécifié, rediriger vers le proxy unifié
if (isset($_GET['endpoint'])) {
	$endpoint = $_GET['endpoint'];

	error_log("API Bridge: redirection vers unified-proxy.php pour endpoint: $endpoint");

	// Construire l'URL de redirection
	$redirectUrl = 'unified-proxy.php?endpoint=' . urlencode($endpoint);

	// Si d'autres paramètres sont présents, les ajouter à l'URL
	$params = $_GET;
	unset($params['endpoint']);

	if (!empty($params)) {
		$redirectUrl .= '&' . http_build_query($params);
	}

	// Rediriger
	header('Location: ' . $redirectUrl);
	exit;
}

// Si pas d'endpoint, retourner une erreur
header('Content-Type: application/json');
echo json_encode([
	'success' => false,
	'message' => 'Paramètre endpoint manquant',
	'proxy' => 'api-bridge-fallback'
]);
