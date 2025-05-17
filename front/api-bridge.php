<?php
// API Bridge - Pont entre le frontend et le backend avec mappings d'endpoints
error_log("API bridge called - endpoint: " . ($_GET['endpoint'] ?? 'non spécifié'));

// Préserver tous les paramètres
$queryString = $_SERVER['QUERY_STRING'];
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

// Mappings d'endpoints frontend vers backend
$endpointMappings = [
	'auth/login' => 'api-auth.php',
	'auth/logout' => 'api/auth/logout',
	'user/profile' => 'api/auth/user',  // Mapper user/profile vers auth/user qui existe dans le backend
	'notes' => 'api-notes.php',
	'matieres' => 'api/matieres',
	'classes' => 'api/classes',
	'examens' => 'api/examens',
	'profs' => 'api/profs'
];

// Si l'endpoint existe dans nos mappings, remplacer par sa valeur
if (isset($endpointMappings[$endpoint])) {
	$_GET['endpoint'] = $endpointMappings[$endpoint];
	error_log("Endpoint mappé: $endpoint -> " . $_GET['endpoint']);
}

// Si l'endpoint concerne l'authentification, utiliser simplified-jwt-bridge.php
if (strpos($endpoint, 'auth') !== false) {
	error_log("Redirection vers simplified-jwt-bridge.php pour endpoint: $endpoint");
	include __DIR__ . '/simplified-jwt-bridge.php';
}
// Si l'endpoint concerne le profil utilisateur, utiliser la fonctionnalité "me" qui existe probablement
elseif ($endpoint === 'user/profile') {
	error_log("Redirection vers user-profile-handler.php pour endpoint: $endpoint");
	include __DIR__ . '/user-profile-handler.php';
} else {
	// Pour tous les autres endpoints, utiliser simple-proxy.php
	error_log("Redirection vers simple-proxy.php pour endpoint: $endpoint");
	include __DIR__ . '/simple-proxy.php';
}
