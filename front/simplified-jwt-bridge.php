<?php
// Simplified JWT Bridge: Simple authentication with hard-coded credentials
header('Content-Type: application/json');

// Basic CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// Get authorization header
$authHeader = null;
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
	$authHeader = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
	$authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
} elseif (function_exists('apache_request_headers')) {
	$requestHeaders = apache_request_headers();
	if (isset($requestHeaders['Authorization'])) {
		$authHeader = $requestHeaders['Authorization'];
	}
}

// Log the authentication attempt
error_log("Simplified JWT Bridge: Authentication attempt");
error_log("Auth header: " . ($authHeader ? substr($authHeader, 0, 20) . '...' : 'Not provided'));

// Hard-coded user responses
$users = [
	'admin@example.com' => [
		'id' => 1,
		'nom' => 'Admin',
		'prenom' => 'User',
		'email' => 'admin@example.com',
		'role' => 'admin'
	],
	'prof@example.com' => [
		'id' => 2,
		'nom' => 'Prof',
		'prenom' => 'Example',
		'email' => 'prof@example.com',
		'role' => 'enseignant'
	],
	'student@example.com' => [
		'id' => 3,
		'nom' => 'Student',
		'prenom' => 'Test',
		'email' => 'student@example.com',
		'role' => 'etudiant'
	]
];

// Process input data (for POST requests)
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// If this is an authentication request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['email']) && isset($input['password'])) {
	$email = $input['email'];
	$password = $input['password'];

	error_log("Login attempt: $email / $password");

	// Simple authentication - accept any password for known users
	if (isset($users[$email])) {
		$userData = $users[$email];
		$token = generateSimpleJwt($userData);

		echo json_encode([
			'success' => true,
			'message' => 'Authentication successful',
			'token' => $token,
			'user' => $userData
		]);
		exit;
	} else {
		// Return generic success for any email to facilitate testing
		$mockUser = [
			'id' => 999,
			'nom' => 'Generic',
			'prenom' => 'User',
			'email' => $email,
			'role' => 'user'
		];

		echo json_encode([
			'success' => true,
			'message' => 'Generic authentication successful',
			'token' => generateSimpleJwt($mockUser),
			'user' => $mockUser
		]);
		exit;
	}
}

// If we receive a JWT token, validate it (simplified version that accepts any token)
if ($authHeader && strpos($authHeader, 'Bearer ') === 0) {
	$token = substr($authHeader, 7);

	// Parse the token to get the user email
	$tokenParts = explode('.', $token);
	if (count($tokenParts) === 3) {
		try {
			$payload = json_decode(base64_decode($tokenParts[1]), true);

			if ($payload && isset($payload['email'])) {
				$email = $payload['email'];

				// Provide user data based on email
				if (isset($users[$email])) {
					echo json_encode([
						'success' => true,
						'message' => 'Token validated (simplified)',
						'user' => $users[$email]
					]);
				} else {
					// Return a generic user
					echo json_encode([
						'success' => true,
						'message' => 'Token accepted with generic user',
						'user' => [
							'id' => 888,
							'nom' => 'Generic',
							'prenom' => 'Token User',
							'email' => $email,
							'role' => 'user'
						]
					]);
				}
				exit;
			}
		} catch (Exception $e) {
			error_log("Token parsing error: " . $e->getMessage());
		}
	}

	// Accept any token for simplicity
	echo json_encode([
		'success' => true,
		'message' => 'Any token accepted (simplified)',
		'user' => [
			'id' => 777,
			'nom' => 'Any',
			'prenom' => 'User',
			'email' => 'any@example.com',
			'role' => 'user'
		]
	]);
	exit;
}

// No authentication provided
echo json_encode([
	'success' => false,
	'message' => 'Authentication required',
	'help' => 'Send a POST request with email/password or provide a Bearer token'
]);

// Function to generate a simple JWT
function generateSimpleJwt($userData)
{
	// Header
	$header = [
		'alg' => 'HS256',
		'typ' => 'JWT'
	];

	// Payload
	$payload = [
		'sub' => $userData['id'],
		'name' => $userData['prenom'] . ' ' . $userData['nom'],
		'email' => $userData['email'],
		'role' => $userData['role'],
		'iat' => time(),
		'exp' => time() + 3600 // 1 hour
	];

	// Encode header and payload
	$base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
	$base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

	// Create signature - in a real app this would use a secret key
	$signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, 'simplified_secret_key');
	$base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(pack('H*', $signature)));

	// Create JWT
	return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
}
