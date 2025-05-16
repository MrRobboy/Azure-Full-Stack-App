<?php

/**
 * Proxy Installation/Repair Script
 * 
 * This script ensures the unified proxy files exist and are properly configured.
 * Run this script after deployment to fix any issues with proxy access.
 */

header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>

<head>
	<title>Unified Proxy Installation</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			max-width: 800px;
			margin: 0 auto;
			padding: 20px;
		}

		.success {
			color: green;
		}

		.error {
			color: red;
		}

		.info {
			color: blue;
		}

		pre {
			background: #f5f5f5;
			padding: 10px;
			overflow: auto;
		}
	</style>
</head>

<body>
	<h1>Unified Proxy Installation/Repair</h1>

	<?php
	// Define the unified proxy content
	$unifiedProxyContent = <<<'EOT'
<?php
/**
 * Unified CORS Proxy - Consolidated solution for Azure App Service CORS issues
 * 
 * This proxy is a consolidated solution that combines the best features of:
 * - azure-cors-proxy.php
 * - simple-proxy.php
 * - api-bridge.php
 * 
 * This single file handles all proxy requests from the frontend JavaScript to the backend,
 * bypassing CORS restrictions by making the request server-side.
 */

// Basic configuration
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'logs/unified_proxy_errors.log');

// Create logs directory if it doesn't exist
if (!file_exists('logs')) {
    mkdir('logs', 0755, true);
}

// Add CORS headers to handle both OPTIONS preflight and actual requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE, PATCH');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Set JSON content type for API responses
header('Content-Type: application/json');

// Get the endpoint parameter
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

// Target backend URL - can be changed to point to your specific backend
$backendUrl = 'https://app-backend-esgi-app.azurewebsites.net';

// Log request information
$logMessage = sprintf(
    "[%s] Unified Proxy Request: Method=%s, Endpoint=%s\n",
    date('Y-m-d H:i:s'),
    $_SERVER['REQUEST_METHOD'],
    $endpoint
);
error_log($logMessage);

// Validate the endpoint
if (empty($endpoint)) {
    echo json_encode([
        'success' => false,
        'message' => 'No endpoint specified'
    ]);
    exit;
}

// Construct the full URL
$url = $backendUrl;
if (strpos($endpoint, 'http') === 0) {
    // If the endpoint is a full URL, use that directly
    $url = $endpoint;
} else {
    // Ensure there's a slash between base URL and endpoint if needed
    if (!empty($endpoint)) {
        if ($endpoint[0] !== '/' && substr($backendUrl, -1) !== '/') {
            $url .= '/';
        }
        $url .= $endpoint;
    }
}

// Add any additional query parameters
$query_string = $_SERVER['QUERY_STRING'];
$query_string = preg_replace('/(&|\?)endpoint=[^&]*/', '', $query_string);
if (!empty($query_string)) {
    $url .= (strpos($url, '?') === false ? '?' : '&') . $query_string;
}

// Log the constructed URL
error_log("Unified Proxy forwarding to URL: " . $url);

// Initialize cURL
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Forward the request method
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

// Forward headers from the original request
$requestHeaders = getallheaders();
$headers = [];
foreach ($requestHeaders as $name => $value) {
    // Skip host header to avoid conflicts
    if (strtolower($name) !== 'host' && strtolower($name) !== 'content-length') {
        $headers[] = "$name: $value";
    }
}

// Add proxy identification header
$headers[] = 'X-Proxy-Forward: true';
$headers[] = 'User-Agent: ESGI-App-Unified-Proxy/1.0';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Forward cookies
if (isset($_SERVER['HTTP_COOKIE'])) {
    curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE']);
}

// Forward request body for POST, PUT, PATCH, DELETE
if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH', 'DELETE'])) {
    $input = file_get_contents('php://input');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
    error_log("Request body: " . $input);
}

// Get header info to pass back cookies
curl_setopt($ch, CURLOPT_HEADER, true);

// Execute the request
$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Split headers and body
$header_text = substr($response, 0, $header_size);
$body = substr($response, $header_size);

// Parse and forward cookies from response
$headers = explode("\r\n", $header_text);
foreach ($headers as $header) {
    if (strpos($header, 'Set-Cookie:') === 0) {
        error_log("Forwarding cookie: " . $header);
        header($header, false);
    }
}

// Check for cURL errors
if (curl_errno($ch)) {
    $error = curl_error($ch);
    error_log("cURL Error: $error");
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error connecting to backend',
        'error' => $error
    ]);
    curl_close($ch);
    exit;
}

// Enhanced error handling for specific endpoints
if ($http_code == 404 || $http_code >= 500) {
    error_log("Error response for endpoint $endpoint: HTTP $http_code");

    // Special handling for user profile API
    if ($endpoint === 'api/user/profile' && session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
        
        if (isset($_SESSION['user'])) {
            // Generate response from session data
            $fallbackResponse = [
                'success' => true,
                'user' => $_SESSION['user'],
                'message' => 'Profile retrieved from session (backend unavailable)',
                'is_fallback' => true
            ];
            http_response_code(200);
            echo json_encode($fallbackResponse);
            curl_close($ch);
            exit;
        }
    }
}

// Close the cURL handle
curl_close($ch);

// Set the response status code
http_response_code($http_code);

// Log the response
error_log("Response Status: $http_code");

// Output the response
echo $body;
exit;
EOT;

	// Define the unified login content
	$unifiedLoginContent = <<<'EOT'
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
function standardizeResponse($response, $originalData) {
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
EOT;

	// Define the proxy configuration file
	$simpleStatusContent = <<<'EOT'
<?php
// Simple status file to test proxy operation
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Status check successful',
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['SERVER_NAME']
]);
EOT;

	// Create logs directory if it doesn't exist
	if (!file_exists('logs')) {
		$logDirCreated = mkdir('logs', 0755, true);
		echo "<p class='" . ($logDirCreated ? "success" : "error") . "'>";
		echo $logDirCreated ? "✓ Logs directory created successfully" : "✗ Failed to create logs directory";
		echo "</p>";
	} else {
		echo "<p class='info'>ℹ Logs directory already exists</p>";
	}

	// Write the unified proxy file
	$proxyWriteResult = file_put_contents(__DIR__ . '/unified-proxy.php', $unifiedProxyContent);
	echo "<p class='" . ($proxyWriteResult !== false ? "success" : "error") . "'>";
	echo $proxyWriteResult !== false ? "✓ unified-proxy.php created successfully (" . $proxyWriteResult . " bytes)" : "✗ Failed to create unified-proxy.php";
	echo "</p>";

	// Write the unified login file
	$loginWriteResult = file_put_contents(__DIR__ . '/unified-login.php', $unifiedLoginContent);
	echo "<p class='" . ($loginWriteResult !== false ? "success" : "error") . "'>";
	echo $loginWriteResult !== false ? "✓ unified-login.php created successfully (" . $loginWriteResult . " bytes)" : "✗ Failed to create unified-login.php";
	echo "</p>";

	// Write the status file for testing
	$statusWriteResult = file_put_contents(__DIR__ . '/status.php', $simpleStatusContent);
	echo "<p class='" . ($statusWriteResult !== false ? "success" : "error") . "'>";
	echo $statusWriteResult !== false ? "✓ status.php created successfully (" . $statusWriteResult . " bytes)" : "✗ Failed to create status.php";
	echo "</p>";

	// Create copies in subfolders if needed
	$folders = ['api', 'proxy'];
	foreach ($folders as $folder) {
		$folderPath = __DIR__ . '/' . $folder;
		if (!is_dir($folderPath)) {
			$folderCreated = mkdir($folderPath, 0755, true);
			echo "<p class='" . ($folderCreated ? "success" : "info") . "'>";
			echo $folderCreated ? "✓ Created directory: " . $folder : "ℹ Failed to create directory: " . $folder;
			echo "</p>";
		}

		if (is_dir($folderPath)) {
			// Copy proxy files to subfolder
			$proxyInSubFolder = copy(__DIR__ . '/unified-proxy.php', $folderPath . '/unified-proxy.php');
			$loginInSubFolder = copy(__DIR__ . '/unified-login.php', $folderPath . '/unified-login.php');
			$statusInSubFolder = copy(__DIR__ . '/status.php', $folderPath . '/status.php');

			echo "<p class='" . ($proxyInSubFolder ? "success" : "error") . "'>";
			echo $proxyInSubFolder ? "✓ Copied unified-proxy.php to " . $folder . " folder" : "✗ Failed to copy unified-proxy.php to " . $folder . " folder";
			echo "</p>";
		}
	}

	// Test the proxy installation
	echo "<h2>Testing Proxy Installation</h2>";

	$testUrl = 'unified-proxy.php?endpoint=status.php';
	echo "<p class='info'>Testing proxy with: " . $testUrl . "</p>";

	$ch = curl_init($testUrl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	echo "<p class='" . ($httpCode >= 200 && $httpCode < 300 ? "success" : "error") . "'>";
	echo "HTTP Status: " . $httpCode;
	echo "</p>";

	if ($response) {
		echo "<pre>" . htmlspecialchars($response) . "</pre>";
	} else {
		echo "<p class='error'>No response from proxy test</p>";
	}

	// Check file permissions
	echo "<h2>File Permissions</h2>";
	$files = ['unified-proxy.php', 'unified-login.php', 'status.php'];
	foreach ($files as $file) {
		$path = __DIR__ . '/' . $file;
		if (file_exists($path)) {
			$perms = fileperms($path);
			$permissions = substr(sprintf('%o', $perms), -4);
			echo "<p>";
			echo "File: " . $file . " - Permissions: " . $permissions;
			echo " - Size: " . filesize($path) . " bytes";
			echo "</p>";
		} else {
			echo "<p class='error'>File not found: " . $file . "</p>";
		}
	}

	echo "<h2>Server Information</h2>";
	echo "<pre>";
	echo "PHP Version: " . phpversion() . "\n";
	echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
	echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
	echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
	echo "Current Directory: " . __DIR__ . "\n";
	echo "</pre>";
	?>

	<h2>Next Steps</h2>
	<p>The unified proxy files have been installed. You should now be able to access:</p>
	<ul>
		<li><a href="unified-proxy.php?endpoint=status.php" target="_blank">Test the proxy</a></li>
		<li><a href="proxy-test.php" target="_blank">Check proxy file status</a></li>
		<li><a href="login.php" target="_blank">Try logging in</a></li>
	</ul>

	<p>If the unified proxy still doesn't work, check the following:</p>
	<ol>
		<li>Verify file permissions (should be readable and executable by the web server)</li>
		<li>Verify error logs in the logs directory</li>
		<li>Check your web.config file to ensure it's properly configured</li>
		<li>Try deploying the application again with the new files</li>
	</ol>
</body>

</html>