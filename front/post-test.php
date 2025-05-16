<?php
// Test POST requests with various methods
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Log de diagnostic
error_log("POST-TEST: Démarrage du test de POST à " . date('Y-m-d H:i:s'));
error_log("POST-TEST: Méthode reçue: " . $_SERVER['REQUEST_METHOD']);
error_log("POST-TEST: Query: " . $_SERVER['QUERY_STRING']);

// Handler for OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// Handle GET requests (just for testing if the script is accessible)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	echo json_encode([
		'success' => true,
		'message' => 'Script accessible en GET avec succès',
		'method' => 'GET',
		'server' => $_SERVER['SERVER_NAME'],
		'time' => date('Y-m-d H:i:s')
	]);
	exit;
}

// Init results array
$results = [
	'success' => false,
	'tests' => [],
	'method' => $_SERVER['REQUEST_METHOD'],
	'time' => date('Y-m-d H:i:s')
];

// Get endpoint from query or use default status endpoint
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : 'status.php';
$api_base_url = 'https://app-backend-esgi-app.azurewebsites.net';
$api_url = $api_base_url . '/' . $endpoint;

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true) ?: [];

error_log("POST-TEST: Données reçues: " . $input);
error_log("POST-TEST: Endpoint cible: " . $api_url);

// Test 1: cURL direct
try {
	$ch = curl_init($api_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'User-Agent: PostTest/1.0'
	]);

	$response = curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$error = curl_error($ch);
	curl_close($ch);

	$results['tests']['curl_direct'] = [
		'success' => ($http_code >= 200 && $http_code < 300),
		'status' => $http_code,
		'response' => $response ? json_decode($response, true) : null,
		'error' => $error ?: null
	];

	error_log("POST-TEST: Test cURL direct - Statut: $http_code");
} catch (Exception $e) {
	$results['tests']['curl_direct'] = [
		'success' => false,
		'error' => $e->getMessage()
	];
	error_log("POST-TEST: Erreur cURL direct: " . $e->getMessage());
}

// Test 2: file_get_contents avec contexte
try {
	$context = stream_context_create([
		'http' => [
			'method' => 'POST',
			'header' => "Content-Type: application/json\r\n",
			'content' => $input,
			'timeout' => 15
		]
	]);

	$response = @file_get_contents($api_url, false, $context);
	$status_line = $http_response_header[0] ?? '';
	preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);
	$http_code = $match[1] ?? 0;

	$results['tests']['file_get_contents'] = [
		'success' => ($http_code >= 200 && $http_code < 300),
		'status' => $http_code,
		'response' => $response ? json_decode($response, true) : null
	];

	error_log("POST-TEST: Test file_get_contents - Statut: $http_code");
} catch (Exception $e) {
	$results['tests']['file_get_contents'] = [
		'success' => false,
		'error' => $e->getMessage()
	];
	error_log("POST-TEST: Erreur file_get_contents: " . $e->getMessage());
}

// Test 3: cURL avec contournements d'en-têtes
try {
	$ch = curl_init($api_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $input);

	// En-têtes alternatifs sans Content-Type spécifique
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'X-HTTP-Method-Override: POST',
		'User-Agent: AzureWebApp/1.0',
		'Accept: application/json'
	]);

	$response = curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$error = curl_error($ch);
	curl_close($ch);

	$results['tests']['curl_alt_headers'] = [
		'success' => ($http_code >= 200 && $http_code < 300),
		'status' => $http_code,
		'response' => $response ? json_decode($response, true) : null,
		'error' => $error ?: null
	];

	error_log("POST-TEST: Test cURL alt headers - Statut: $http_code");
} catch (Exception $e) {
	$results['tests']['curl_alt_headers'] = [
		'success' => false,
		'error' => $e->getMessage()
	];
	error_log("POST-TEST: Erreur cURL alt headers: " . $e->getMessage());
}

// Test 4: curl avec en-têtes Azure spécifiques
try {
	$ch = curl_init($api_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $input);

	// En-têtes plus spécifiques à Azure et IIS
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'X-MS-SITE-RESTRICTED-TOKEN: true',
		'X-ARR-SSL: true',
		'X-MS-REQUEST-ID: ' . uniqid(),
		'X-Original-URL: /' . $endpoint
	]);

	$response = curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$error = curl_error($ch);
	curl_close($ch);

	$results['tests']['curl_azure_headers'] = [
		'success' => ($http_code >= 200 && $http_code < 300),
		'status' => $http_code,
		'response' => $response ? json_decode($response, true) : null,
		'error' => $error ?: null
	];

	error_log("POST-TEST: Test cURL Azure headers - Statut: $http_code");
} catch (Exception $e) {
	$results['tests']['curl_azure_headers'] = [
		'success' => false,
		'error' => $e->getMessage()
	];
	error_log("POST-TEST: Erreur cURL Azure headers: " . $e->getMessage());
}

// Déterminer le résultat global
$results['success'] = false;
foreach ($results['tests'] as $test) {
	if ($test['success']) {
		$results['success'] = true;
		break;
	}
}

// Ajouter les infos d'environnement
$results['environment'] = [
	'server' => $_SERVER['SERVER_NAME'],
	'php_version' => phpversion(),
	'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
	'request_time' => $_SERVER['REQUEST_TIME']
];

// Retourner les résultats
echo json_encode($results, JSON_PRETTY_PRINT);
