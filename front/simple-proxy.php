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

// Try multiple possible API base URLs
function tryMultipleUrls($endpoint, $method, $body = null)
{
	global $api_base_url;

	// Define the possible URL patterns to try
	$patterns = [
		// Original pattern
		"$api_base_url/$endpoint",

		// With api/ prefix if not present
		(strpos($endpoint, 'api/') === 0) ? "$api_base_url/$endpoint" : "$api_base_url/api/$endpoint",

		// Without api/ prefix if present
		(strpos($endpoint, 'api/') === 0) ? "$api_base_url/" . substr($endpoint, 4) : "$api_base_url/$endpoint",

		// Special case for login
		(strpos($endpoint, 'login') !== false) ? "$api_base_url/api/auth/login" : null,

		// Alternative API path - some backends use /v1/ pattern
		"$api_base_url/v1/$endpoint",

		// Some backends use root paths for API
		(strpos($endpoint, 'api/') === 0) ? "$api_base_url/" . substr($endpoint, 4) : null,
	];

	// Filter out null entries
	$patterns = array_filter($patterns);

	// Make them unique
	$patterns = array_unique($patterns);

	error_log("Trying multiple URL patterns for endpoint: $endpoint");
	foreach ($patterns as $idx => $url) {
		error_log("Attempt #" . ($idx + 1) . ": $url");
	}

	// For now, return the URL we would normally use
	if (strpos($endpoint, 'status.php') !== false) {
		return "$api_base_url/status.php";
	} else if (strpos($endpoint, 'api/auth/login') !== false || strpos($endpoint, 'auth/login') !== false) {
		return "$api_base_url/api/auth/login";
	} else if (strpos($endpoint, 'api/') === 0) {
		return "$api_base_url/$endpoint";
	} else {
		return "$api_base_url/api/$endpoint";
	}
}

try {
	// Simple check if URL is valid
	$endpoint = ltrim($endpoint, '/');

	// Special handling for login endpoint
	$target_url = '';

	if ($endpoint == 'api/auth/login' || $endpoint == 'auth/login') {
		// Explicitly use the correct login endpoint
		$target_url = $api_base_url . '/api/auth/login';
		error_log("Using explicit login endpoint: " . $target_url);
	} else if ($endpoint == 'status.php') {
		// For the status endpoint, keep it at the root
		$target_url = $api_base_url . '/status.php';
		error_log("Using status endpoint: " . $target_url);
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

		// If we got a 404, let's try other URL patterns
		if ($status == 404 && $endpoint != 'status.php') {
			error_log("404 error for URL: " . $target_url . " - Attempting alternate paths");

			// Store the original response in case we need to fall back
			$original_response = $response;
			$original_status = $status;

			// Try the alternative URL patterns
			$alternate_patterns = [
				"$api_base_url/index.php/$endpoint",
				"$api_base_url/public/$endpoint",
				"$api_base_url/public/index.php/$endpoint",
				(strpos($endpoint, 'api/') === 0) ? "$api_base_url/" . substr($endpoint, 4) : "$api_base_url/$endpoint"
			];

			foreach ($alternate_patterns as $alt_url) {
				error_log("Trying alternate URL: " . $alt_url);

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
				$alt_error = curl_error($ch_alt);
				curl_close($ch_alt);

				error_log("Alternate response: HTTP $alt_status, Response length: " . strlen($alt_response));

				// If we get a success response (200-299), use this response
				if ($alt_status >= 200 && $alt_status < 300) {
					error_log("Found working alternate URL: " . $alt_url);
					$response = $alt_response;
					$status = $alt_status;
					break;
				}
			}

			// If all alternates failed, stick with original
			if ($status == 404) {
				error_log("All alternate URLs failed. Using original response.");
				$response = $original_response;
				$status = $original_status;
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
