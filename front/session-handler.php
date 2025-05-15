<?php

/**
 * Session Handler
 * Handles session management for the application
 */
session_start();
header('Content-Type: application/json');

// Log for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Allow CORS for local development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// Get JSON data from request
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['action'])) {
	http_response_code(400);
	echo json_encode(['success' => false, 'message' => 'Invalid request']);
	exit;
}

// Log the request for debugging
error_log("Session handler request: " . json_encode($data['action']));

switch ($data['action']) {
	case 'login':
		// Store user data and token in session
		if (!isset($data['user']) || !isset($data['token'])) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'Missing user data or token']);
			exit;
		}

		$_SESSION['user'] = $data['user'];
		$_SESSION['token'] = $data['token'];
		$_SESSION['loggedIn'] = true;
		$_SESSION['loginTime'] = time();

		// Log successful login
		error_log("User logged in: " . $data['user']['email']);

		echo json_encode(['success' => true, 'message' => 'Session created']);
		break;

	case 'logout':
		// Clear session data
		$_SESSION = [];

		// Destroy the session
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(
				session_name(),
				'',
				time() - 42000,
				$params["path"],
				$params["domain"],
				$params["secure"],
				$params["httponly"]
			);
		}

		session_destroy();
		echo json_encode(['success' => true, 'message' => 'Logged out']);
		break;

	case 'check':
		// Check if user is logged in
		if (isset($_SESSION['user']) && isset($_SESSION['token'])) {
			echo json_encode([
				'success' => true,
				'loggedIn' => true,
				'user' => $_SESSION['user']
			]);
		} else {
			echo json_encode(['success' => true, 'loggedIn' => false]);
		}
		break;

	default:
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
