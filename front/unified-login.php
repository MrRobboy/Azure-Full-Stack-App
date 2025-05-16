<?php

/**
 * Unified Login - Streamlined authentication proxy
 * 
 * This script handles authentication requests to the backend API
 * using the unified proxy approach, with specialized handling for login.
 */

// Basic configuration
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'logs/unified_login_errors.log');

// Create logs directory if it doesn't exist
if (!file_exists('logs')) {
	mkdir('logs', 0755, true);
}

// Set response type to JSON
header('Content-Type: application/json');

// Get POST data
$postData = file_get_contents('php://input');
$data = json_decode($postData, true);

// Log the request
error_log(sprintf(
	"[%s] Login attempt for: %s",
	date('Y-m-d H:i:s'),
	isset($data['email']) ? $data['email'] : 'unknown'
));

// Validate the data
if (!$data || !isset($data['email']) || !isset($data['password'])) {
	echo json_encode([
		'success' => false,
		'message' => 'Missing required fields'
	]);
	exit;
}

// Target backend URL
$backendUrl = 'https://app-backend-esgi-app.azurewebsites.net';

// List of possible login endpoints to try (in order of preference)
$loginEndpoints = [
	'/api-auth-login.php', // Direct PHP endpoint
	'/api/auth/login',     // REST API endpoint
];

// Try each endpoint until one works
foreach ($loginEndpoints as $endpoint) {
	error_log("Trying login endpoint: " . $endpoint);

	// Initialize cURL
	$ch = curl_init($backendUrl . $endpoint);

	// Set cURL options
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

	// Add headers
	$headers = [
		'Content-Type: application/json',
		'Accept: application/json',
		'X-Proxy-Forward: true',
		'User-Agent: ESGI-App-Unified-Login/1.0'
	];
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	// Execute the request
	$response = curl_exec($ch);
	$info = curl_getinfo($ch);
	$error = curl_error($ch);
	curl_close($ch);

	// Check for successful response
	if ($info['http_code'] >= 200 && $info['http_code'] < 300 && !empty($response)) {
		$responseData = json_decode($response, true);

		// If we have a valid JSON response
		if ($responseData && is_array($responseData)) {
			// Standardize the response format
			$standardized = standardizeResponse($responseData, $data);
			error_log("Login successful via endpoint: " . $endpoint);
			echo json_encode($standardized);
			exit;
		}
	}

	// Log error and try next endpoint
	error_log("Login endpoint " . $endpoint . " failed with status " . $info['http_code'] . ": " . $error);
}

// If all endpoints fail, return a generic error
echo json_encode([
	'success' => false,
	'message' => 'Impossible de se connecter au service d\'authentification',
	'details' => 'Tous les points d\'entrée d\'authentification ont échoué'
]);
exit;

/**
 * Standardize the authentication response format
 * Different API endpoints may return data in different formats
 * This ensures a consistent structure with user and token
 */
function standardizeResponse($response, $originalData)
{
	// First make sure we have a success flag
	if (!isset($response['success'])) {
		// Try to determine success from HTTP status or response structure
		$response['success'] = isset($response['user']) || isset($response['token']) ||
			isset($response['data']['user']) || isset($response['data']['token']);
	}

	// If login failed, return as is
	if (!$response['success']) {
		return $response;
	}

	// Extract and standardize user data
	if (!isset($response['user']) && isset($response['data'])) {
		if (isset($response['data']['user'])) {
			$response['user'] = $response['data']['user'];
		} else if (is_array($response['data'])) {
			// If data seems to be the user object itself
			$response['user'] = $response['data'];
		}
	}

	// Extract and standardize token
	if (!isset($response['token']) && isset($response['data'])) {
		if (isset($response['data']['token'])) {
			$response['token'] = $response['data']['token'];
		} else if (isset($response['data']['access_token'])) {
			$response['token'] = $response['data']['access_token'];
		}
	}

	// If we still don't have user data, create a minimal structure
	if (!isset($response['user']) || !is_array($response['user'])) {
		$response['user'] = [
			'id' => isset($response['data']['id']) ? $response['data']['id'] : 0,
			'email' => $originalData['email'],
			'name' => isset($response['data']['name']) ? $response['data']['name'] : $originalData['email'],
			'role' => isset($response['data']['role']) ? $response['data']['role'] : 'USER'
		];
	}

	// If we still don't have a token, create a temporary one
	if (!isset($response['token']) || empty($response['token'])) {
		$response['token'] = md5($originalData['email'] . time());
	}

	return $response;
}
