<?php

/**
 * Fallback User Profile API
 * Returns the user profile data from the session when the backend is unavailable
 */

// Start or resume session
session_start();

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// Check if user is logged in
if (isset($_SESSION['user']) && isset($_SESSION['token'])) {
	// Return session data as API response
	echo json_encode([
		'success' => true,
		'user' => $_SESSION['user'],
		'message' => 'User profile data retrieved from session',
		'is_fallback' => true,
		'timestamp' => date('Y-m-d H:i:s')
	]);
	exit;
} else {
	// Not logged in
	http_response_code(401);
	echo json_encode([
		'success' => false,
		'message' => 'Not authenticated',
		'is_fallback' => true,
		'timestamp' => date('Y-m-d H:i:s')
	]);
	exit;
}
