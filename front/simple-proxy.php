<?php
// Simple proxy for Azure - minimal version to test deployment
error_reporting(E_ALL);
ini_set('display_errors', 1); // Enable error display for debugging
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Log the file path and existence
$self_path = __FILE__;
$parent_dir = dirname($self_path);
error_log("Simple proxy file path: $self_path");
error_log("Parent directory: $parent_dir");
error_log("File exists: " . (file_exists($self_path) ? 'yes' : 'no'));

// Add CORS headers to avoid issues
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Only set content type if not OPTIONS request
if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
	header('Content-Type: application/json');
}

// Log request information
error_log("Simple proxy accessed: " . $_SERVER['REQUEST_URI']);
error_log("Query string: " . $_SERVER['QUERY_STRING']);
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);

// Handle OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// Basic configuration
$api_base_url = 'https://app-backend-esgi-app.azurewebsites.net';

// Get the endpoint parameter
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
if (empty($endpoint)) {
	echo json_encode([
		'success' => false,
		'message' => 'No endpoint specified',
		'debug' => $_GET,
		'request_uri' => $_SERVER['REQUEST_URI'],
		'query_string' => $_SERVER['QUERY_STRING']
	]);
	exit;
}

// If the backend is down or unreachable, we can mock the response for testing
$mock_data = true; // Set to true to enable mocking

if ($mock_data) {
	// Map of mock data endpoints
	$mock_endpoints = [
		'api/auth/login' => function () {
			$raw_post = file_get_contents("php://input");
			$user_data = json_decode($raw_post, true);

			// Simple mock login response for testing
			if ($user_data && isset($user_data['email'])) {
				// Accept any credentials for testing, but show email in the response
				echo json_encode([
					'success' => true,
					'message' => 'Login successful (mocked)',
					'user' => [
						'id' => 1,
						'email' => $user_data['email'],
						'name' => 'Test User (' . $user_data['email'] . ')',
						'role' => 'admin'
					],
					'token' => 'mock_jwt_token_' . base64_encode(json_encode([
						'user_id' => 1,
						'email' => $user_data['email'],
						'exp' => time() + 3600
					]))
				]);

				// Log successful mock login
				error_log("Mock login successful for user: " . $user_data['email']);
			} else {
				http_response_code(401);
				echo json_encode([
					'success' => false,
					'message' => 'Invalid credentials (mocked)'
				]);

				// Log failed mock login
				error_log("Mock login failed - invalid credentials");
			}
		},

		'classes' => function () {
			// Mock data for classes based on SQL structure
			echo json_encode([
				'success' => true,
				'message' => 'Classes retrieved successfully (mocked)',
				'data' => [
					[
						'id_classe' => 1,
						'nom_classe' => '2A1',
						'niveau' => '2ème Année',
						'rythme' => 'Alternance',
						'numero' => '1'
					],
					[
						'id_classe' => 3,
						'nom_classe' => '2A2',
						'niveau' => '2ème Année',
						'rythme' => 'Alternance',
						'numero' => '2'
					],
					[
						'id_classe' => 4,
						'nom_classe' => '2A3',
						'niveau' => '2ème Année',
						'rythme' => 'Alternance',
						'numero' => '3'
					],
					[
						'id_classe' => 6,
						'nom_classe' => '2A5 (aka la classe bien guez)',
						'niveau' => '2ème Année',
						'rythme' => 'Alternance',
						'numero' => '5'
					],
					[
						'id_classe' => 7,
						'nom_classe' => '1A2',
						'niveau' => '1ère Année',
						'rythme' => 'Alternance',
						'numero' => '2'
					]
				]
			]);
		},

		'matieres' => function () {
			// Mock data for subjects based on SQL structure
			echo json_encode([
				'success' => true,
				'message' => 'Matières retrieved successfully (mocked)',
				'data' => [
					[
						'id_matiere' => 1,
						'nom' => 'Mathématiques'
					],
					[
						'id_matiere' => 2,
						'nom' => 'Français'
					],
					[
						'id_matiere' => 16,
						'nom' => 'Docker'
					],
					[
						'id_matiere' => 17,
						'nom' => 'Azure'
					]
				]
			]);
		},

		'examens' => function () {
			// Mock data for exams based on SQL structure
			echo json_encode([
				'success' => true,
				'message' => 'Examens retrieved successfully (mocked)',
				'data' => [
					[
						'id_exam' => 1,
						'titre' => 'Analyse de texte',
						'matiere' => 2,
						'classe' => 3,
						'date' => '2025-05-10',
						'matiere_nom' => 'Français',
						'classe_nom' => '2A2'
					],
					[
						'id_exam' => 10,
						'titre' => 'TEST POSITIONNEMENT',
						'matiere' => 1,
						'classe' => 3,
						'date' => '2025-05-20',
						'matiere_nom' => 'Mathématiques',
						'classe_nom' => '2A2'
					],
					[
						'id_exam' => 12,
						'titre' => 'Examen Docker',
						'matiere' => 16,
						'classe' => 3,
						'date' => '2025-05-16',
						'matiere_nom' => 'Docker',
						'classe_nom' => '2A2'
					]
				]
			]);
		},

		'profs' => function () {
			// Mock data for professors based on SQL structure
			echo json_encode([
				'success' => true,
				'message' => 'Professeurs retrieved successfully (mocked)',
				'data' => [
					[
						'id_prof' => 1,
						'nom' => 'El Attar',
						'prenom' => 'Ahmed',
						'email' => 'mr.ahmed.elattar.pro@gmail.com',
						'matiere' => 1,
						'matiere_nom' => 'Mathématiques'
					],
					[
						'id_prof' => 2,
						'nom' => 'Ngo',
						'prenom' => 'Mathis',
						'email' => 'mathis.ngoo@gmail.com',
						'matiere' => null,
						'matiere_nom' => null
					]
				]
			]);
		},

		'users' => function () {
			// Mock data for users based on SQL structure
			echo json_encode([
				'success' => true,
				'message' => 'Users retrieved successfully (mocked)',
				'data' => [
					[
						'id_user' => 1,
						'nom' => 'Pelcat',
						'prenom' => 'Arthur',
						'email' => 'apelcat@myges.fr',
						'classe' => 3,
						'classe_nom' => '2A2'
					],
					[
						'id_user' => 2,
						'nom' => 'Sage',
						'prenom' => 'William',
						'email' => 'wsage@myges.fr',
						'classe' => 3,
						'classe_nom' => '2A2'
					],
					[
						'id_user' => 3,
						'nom' => 'Theo',
						'prenom' => 'Przybylski',
						'email' => 'tprzybylski@myges.fr',
						'classe' => 4,
						'classe_nom' => '2A3'
					],
					[
						'id_user' => 4,
						'nom' => 'El Attar',
						'prenom' => 'Ahmed',
						'email' => 'aelattar@myges.fr',
						'classe' => 3,
						'classe_nom' => '2A2'
					],
					[
						'id_user' => 5,
						'nom' => 'Ngo',
						'prenom' => 'Mathis',
						'email' => 'mngo4@myges.fr',
						'classe' => 3,
						'classe_nom' => '2A2'
					]
				]
			]);
		}
	];

	// Check if we have a mock response for this endpoint
	$clean_endpoint = trim($endpoint, '/');

	// Try exact match first
	if (isset($mock_endpoints[$clean_endpoint])) {
		error_log("Using mock data for endpoint: " . $clean_endpoint);
		$mock_endpoints[$clean_endpoint]();
		exit;
	}

	// Check if it's an API endpoint that we should mock
	if (strpos($clean_endpoint, 'api/') === 0) {
		$api_endpoint = substr($clean_endpoint, 4); // Remove 'api/' prefix
		if (isset($mock_endpoints[$api_endpoint])) {
			error_log("Using mock data for API endpoint: " . $api_endpoint);
			$mock_endpoints[$api_endpoint]();
			exit;
		}
	}

	// For status.php, we'll let it pass through to the real backend
	if ($clean_endpoint === 'status.php') {
		error_log("Passing through status.php to real backend");
		// Continue processing
	} else {
		error_log("No mock data defined for endpoint: " . $clean_endpoint . " - trying real backend");
	}
}

try {
	// Simple check if URL is valid
	$endpoint = ltrim($endpoint, '/');

	// Based on our diagnostics, we need special handling
	if ($endpoint == 'status.php') {
		// Status.php works at the root
		$target_url = $api_base_url . '/status.php';
		error_log("Using known working status endpoint: " . $target_url);
	} else if (strpos($endpoint, 'api/auth/login') !== false || strpos($endpoint, 'auth/login') !== false) {
		// For login, we know the real structure is problematic
		// We'll try with the 'api/' prefix as this is the most common structure
		$target_url = $api_base_url . '/api/auth/login';
		error_log("Using login endpoint: " . $target_url);
	} else if (strpos($endpoint, 'api/') === 0) {
		// If it already begins with api/, use as-is
		$target_url = $api_base_url . '/' . $endpoint;
		error_log("Using API endpoint as-is: " . $target_url);
	} else {
		// Otherwise, add api/ prefix for API endpoints
		$target_url = $api_base_url . '/api/' . $endpoint;
		error_log("Adding API prefix to endpoint: " . $target_url);
	}

	// Log the target URL and raw post data
	error_log("Proxying to: " . $target_url);
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$raw_post = file_get_contents('php://input');
		error_log("Raw POST data: " . $raw_post);
	}

	// Attempt to use curl if available
	if (function_exists('curl_init')) {
		$ch = curl_init($target_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		// Set proper method
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

		// Set headers - important for backend authentication
		$headers = ['Content-Type: application/json'];

		// Forward any Authorization header
		if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
			$headers[] = 'Authorization: ' . $_SERVER['HTTP_AUTHORIZATION'];
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		// If it's a POST or PUT, forward the body
		if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
			$body = file_get_contents("php://input");
			if (!empty($body)) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
			}
		}

		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		// Log and return the response
		error_log("Proxy response: HTTP $status, Response length: " . strlen($response));
		if ($error) {
			error_log("Curl error: " . $error);
		}

		// If we got a 404, try a different endpoint format
		if ($status == 404 && $endpoint != 'status.php') {
			error_log("404 error for URL: " . $target_url . " - Attempting alternate paths");

			// Store the original response in case we need to fall back
			$original_response = $response;
			$original_status = $status;

			// Try the alternative URL patterns - since status.php works at root, other endpoints might too
			if (strpos($endpoint, 'api/') === 0) {
				// Try without api/ prefix
				$alt_url = $api_base_url . '/' . substr($endpoint, 4);
				error_log("Trying without api/ prefix: " . $alt_url);

				$ch_alt = curl_init($alt_url);
				curl_setopt($ch_alt, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch_alt, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch_alt, CURLOPT_TIMEOUT, 30);
				curl_setopt($ch_alt, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch_alt, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);
				curl_setopt($ch_alt, CURLOPT_HTTPHEADER, $headers);

				if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
					if (!empty($body)) {
						curl_setopt($ch_alt, CURLOPT_POSTFIELDS, $body);
					}
				}

				$alt_response = curl_exec($ch_alt);
				$alt_status = curl_getinfo($ch_alt, CURLINFO_HTTP_CODE);
				curl_close($ch_alt);

				if ($alt_status < 400) {
					$response = $alt_response;
					$status = $alt_status;
					error_log("Alt URL succeeded: " . $alt_url . " with status " . $alt_status);
				}
			}

			// If API endpoints are still failing but status.php works, we might need to mock
			if ($status >= 400 && $endpoint != 'status.php' && $mock_data) {
				error_log("Mocking response for endpoint: " . $endpoint);
				if (strpos($endpoint, 'auth/login') !== false) {
					$status = 200;
					$response = json_encode([
						'success' => true,
						'message' => 'Mock login successful',
						'user' => [
							'id' => 1,
							'email' => 'admin@test.com',
							'name' => 'Admin User',
							'role' => 'admin'
						],
						'token' => 'mock_token_' . time()
					]);
				}
			}
		}

		// Set the status code
		http_response_code($status);

		echo $response;
	} else {
		// Fallback to file_get_contents if curl is not available
		$context = stream_context_create([
			'http' => [
				'method' => $_SERVER['REQUEST_METHOD'],
				'ignore_errors' => true,
				'header' => 'Content-Type: application/json'
			],
			'ssl' => [
				'verify_peer' => false,
				'verify_peer_name' => false
			]
		]);

		if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
			$body = file_get_contents("php://input");
			if (!empty($body)) {
				$context['http']['content'] = $body;
			}
		}

		$response = @file_get_contents($target_url, false, $context);
		$status = $http_response_header[0] ?? 'HTTP/1.1 500 Internal Server Error';
		preg_match('#HTTP/\d+\.\d+ (\d+)#', $status, $matches);
		$status_code = $matches[1] ?? 500;

		// Set the status code
		http_response_code($status_code);

		// Log and return the response
		error_log("Proxy response (file_get_contents): $status, Response length: " . strlen($response ?? ''));

		echo $response;
	}
} catch (Exception $e) {
	// Return a proper error response
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => 'Proxy error: ' . $e->getMessage(),
		'endpoint' => $endpoint,
		'target_url' => $target_url ?? null
	]);

	error_log("Proxy exception: " . $e->getMessage());
}
