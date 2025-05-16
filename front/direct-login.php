<?php

/**
 * Direct Login - Script de communication directe serveur-à-serveur
 * 
 * Ce script contourne les limitations CORS en effectuant les requêtes
 * directement du serveur frontend vers le backend sans passer par le navigateur.
 */

// Enable error reporting for troubleshooting
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'logs/direct_login_errors.log');

// Set response content type to JSON
header('Content-Type: application/json');

// Create logs directory if it doesn't exist
if (!file_exists('logs')) {
	mkdir('logs', 0755, true);
}

// Target backend URL
$backendBaseUrl = 'https://app-backend-esgi-app.azurewebsites.net';

// Log the request
$logMessage = sprintf(
	"[%s] Direct Login Request\n",
	date('Y-m-d H:i:s')
);
error_log($logMessage);

// Get POST data
$postData = file_get_contents('php://input');
$data = json_decode($postData, true);

// Validate the data
if (!$data || !isset($data['email']) || !isset($data['password'])) {
	echo json_encode([
		'success' => false,
		'message' => 'Missing required fields'
	]);
	exit;
}

// Try multiple methods to authenticate with the backend
$methods = [
	[
		'description' => 'API Direct Endpoint',
		'url' => $backendBaseUrl . '/api-auth-login.php',
		'method' => 'POST',
		'data' => $postData,
		'headers' => [
			'Content-Type: application/json',
			'Accept: application/json',
			'X-Proxy-Forward: true',
			'User-Agent: ESGI-App-Proxy/1.0'
		]
	],
	[
		'description' => 'Legacy API Route',
		'url' => $backendBaseUrl . '/api/auth/login',
		'method' => 'POST',
		'data' => $postData,
		'headers' => [
			'Content-Type: application/json',
			'Accept: application/json',
			'X-Proxy-Forward: true',
			'User-Agent: ESGI-App-Proxy/1.0'
		]
	],
	[
		'description' => 'GET with parameters',
		'url' => $backendBaseUrl . '/api-auth-login.php?email=' . urlencode($data['email']) . '&password=' . urlencode($data['password']),
		'method' => 'GET',
		'data' => null,
		'headers' => [
			'Accept: application/json',
			'X-Proxy-Forward: true',
			'User-Agent: ESGI-App-Proxy/1.0'
		]
	]
];

// Results tracking
$results = [];
$finalResponse = null;

// Try each method
foreach ($methods as $method) {
	try {
		// Log the current attempt
		error_log("Trying: " . $method['description'] . " (" . $method['url'] . ")");

		// Initialize cURL
		$ch = curl_init();

		// Set cURL options
		curl_setopt($ch, CURLOPT_URL, $method['url']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $method['headers']);

		// Set cookies from the current request
		if (isset($_SERVER['HTTP_COOKIE'])) {
			curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE']);
		}

		// Method-specific options
		if ($method['method'] === 'POST') {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $method['data']);
		} else if ($method['method'] !== 'GET') {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method['method']);
			if ($method['data']) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $method['data']);
			}
		}

		// Execute the request
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		$error = curl_error($ch);
		curl_close($ch);

		// Track the result
		$results[$method['description']] = [
			'status' => $info['http_code'],
			'error' => $error
		];

		// If successful, parse the response
		if ($info['http_code'] >= 200 && $info['http_code'] < 300 && $response) {
			$responseData = json_decode($response, true);

			// If we got a valid JSON response with success status
			if ($responseData && isset($responseData['success'])) {
				error_log("Success with: " . $method['description']);
				$finalResponse = $responseData;

				// If authentication was successful, we can stop here
				if ($responseData['success'] === true) {
					break;
				}
			}
		}
	} catch (Exception $e) {
		error_log("Exception with " . $method['description'] . ": " . $e->getMessage());
		$results[$method['description']] = [
			'status' => 'error',
			'error' => $e->getMessage()
		];
	}
}

// If we have a successful response, return it
if ($finalResponse) {
	echo json_encode($finalResponse);
} else {
	// Otherwise, return an error with details
	echo json_encode([
		'success' => false,
		'message' => 'Erreur de connexion au serveur backend',
		'details' => array_map(function ($key, $value) {
			return $key . ": HTTP " . $value['status'] . ", Erreur: " . $value['error'];
		}, array_keys($results), $results)
	]);
}
exit;
