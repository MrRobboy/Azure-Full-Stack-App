<?php
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>

<head>
	<title>Deep Proxy Test</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			max-width: 800px;
			margin: 0 auto;
			padding: 20px;
		}

		.success {
			color: green;
			background: #e8f5e9;
			padding: 5px;
		}

		.error {
			color: red;
			background: #ffebee;
			padding: 5px;
		}

		.warning {
			color: orange;
			background: #fff8e1;
			padding: 5px;
		}

		.info {
			color: blue;
			background: #e3f2fd;
			padding: 5px;
		}

		pre {
			background: #f5f5f5;
			padding: 10px;
			overflow: auto;
		}

		table {
			border-collapse: collapse;
			width: 100%;
			margin: 20px 0;
		}

		th,
		td {
			border: 1px solid #ddd;
			padding: 8px;
			text-align: left;
		}

		th {
			background-color: #f2f2f2;
		}

		tr:nth-child(even) {
			background-color: #f9f9f9;
		}
	</style>
</head>

<body>
	<h1>Deep Proxy Test</h1>

	<?php
	// Test different proxy URLs to find what works
	$proxyUrls = [
		'unified-proxy.php',
		'/unified-proxy.php',
		'/api/unified-proxy.php',
		'/proxy/unified-proxy.php',
		'api/unified-proxy.php',
		'proxy/unified-proxy.php',
		'simple-proxy.php',
		'api-bridge.php',
		'direct-login.php',
		'azure-cors-proxy.php',
	];

	echo '<h2>Testing Proxy Access</h2>';
	echo '<table>';
	echo '<tr><th>Proxy URL</th><th>Status</th><th>Response</th></tr>';

	foreach ($proxyUrls as $proxyUrl) {
		echo '<tr>';
		echo "<td>{$proxyUrl}</td>";

		// Test if the file exists first
		$filePath = __DIR__ . '/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $proxyUrl);
		$fileExists = file_exists($filePath);

		if (!$fileExists) {
			echo '<td class="error">File Not Found</td>';
			echo '<td>File does not exist at: ' . $filePath . '</td>';
			echo '</tr>';
			continue;
		}

		// Try to access the proxy
		$testUrl = $proxyUrl . '?endpoint=status.php';
		$ch = curl_init($testUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		if ($httpCode >= 200 && $httpCode < 300) {
			echo '<td class="success">OK (' . $httpCode . ')</td>';
			echo '<td><pre>' . htmlspecialchars(substr($response, 0, 200)) . (strlen($response) > 200 ? '...' : '') . '</pre></td>';
		} else {
			echo '<td class="error">Failed (' . $httpCode . ')</td>';
			echo '<td>Error: ' . htmlspecialchars($error ?: 'No response') . '</td>';
		}

		echo '</tr>';
	}

	echo '</table>';

	// Check proxy permissions
	echo '<h2>File Permissions</h2>';
	echo '<table>';
	echo '<tr><th>File</th><th>Exists</th><th>Size</th><th>Permissions</th><th>Owner</th></tr>';

	$files = [
		'unified-proxy.php',
		'api/unified-proxy.php',
		'proxy/unified-proxy.php',
		'unified-login.php',
		'status.php',
		'web.config'
	];

	foreach ($files as $file) {
		echo '<tr>';
		$path = __DIR__ . '/' . $file;
		$exists = file_exists($path);

		echo "<td>{$file}</td>";
		echo '<td>' . ($exists ? 'Yes' : 'No') . '</td>';

		if ($exists) {
			$size = filesize($path);
			$perms = fileperms($path);
			$permissions = substr(sprintf('%o', $perms), -4);

			// Try to get owner information if possible
			$owner = function_exists('posix_getpwuid') ?
				@posix_getpwuid(fileowner($path))['name'] :
				fileowner($path);

			echo "<td>{$size} bytes</td>";
			echo "<td>{$permissions}</td>";
			echo "<td>{$owner}</td>";
		} else {
			echo '<td colspan="3" class="error">File Not Found</td>';
		}

		echo '</tr>';
	}

	echo '</table>';

	// Check if we can write to the current directory
	echo '<h2>Directory Write Test</h2>';
	$testFile = __DIR__ . '/write-test-' . time() . '.txt';
	$writeTest = @file_put_contents($testFile, 'Test write access');

	if ($writeTest !== false) {
		echo '<p class="success">Directory is writable. Test file created successfully.</p>';
		// Clean up the test file
		@unlink($testFile);
	} else {
		echo '<p class="error">Directory is not writable. Cannot create or modify files.</p>';
	}

	// Test creating a copy of the proxy directly
	echo '<h2>Direct Proxy Creation Test</h2>';

	// Try to create a new copy with a unique name
	$newProxyFile = __DIR__ . '/test-proxy-' . time() . '.php';
	$proxySource = file_exists(__DIR__ . '/unified-proxy.php') ?
		file_get_contents(__DIR__ . '/unified-proxy.php') :
		'<?php header("Content-Type: application/json"); echo json_encode(["success" => true, "message" => "Test proxy works"]);';

	$createResult = @file_put_contents($newProxyFile, $proxySource);

	if ($createResult !== false) {
		echo '<p class="success">Successfully created a new proxy file: ' . basename($newProxyFile) . '</p>';

		// Test the new proxy
		$testUrl = basename($newProxyFile) . '?endpoint=status.php';
		$ch = curl_init($testUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		echo '<p>Test result: ' . ($httpCode >= 200 && $httpCode < 300 ?
			'<span class="success">Success (' . $httpCode . ')</span>' :
			'<span class="error">Failed (' . $httpCode . ')</span>') . '</p>';

		if ($response) {
			echo '<pre>' . htmlspecialchars(substr($response, 0, 200)) . (strlen($response) > 200 ? '...' : '') . '</pre>';
		}

		// Clean up the test file
		@unlink($newProxyFile);
	} else {
		echo '<p class="error">Failed to create a new proxy file. Permission issue or disk full.</p>';
	}

	// Check web.config for proper rules
	echo '<h2>Web.Config Analysis</h2>';
	$webConfigPath = __DIR__ . '/web.config';

	if (file_exists($webConfigPath)) {
		$webConfig = file_get_contents($webConfigPath);
		echo '<p class="success">web.config file found (' . filesize($webConfigPath) . ' bytes)</p>';

		// Check for the unified proxy rule
		if (strpos($webConfig, 'unified-proxy') !== false) {
			echo '<p class="success">unified-proxy is included in web.config rules</p>';
		} else {
			echo '<p class="error">unified-proxy is NOT found in web.config rules! This may be preventing access.</p>';
			echo '<p>You need to update your web.config to include: <code>&lt;match url="^(.*/)?(?:unified-proxy|unified-login|simple-proxy|api-bridge|direct-login)\.php$" /&gt;</code></p>';
		}

		echo '<pre>' . htmlspecialchars(substr($webConfig, 0, 500)) . (strlen($webConfig) > 500 ? '...' : '') . '</pre>';
	} else {
		echo '<p class="error">web.config file not found! This is critical for URL rewriting in Azure.</p>';
	}

	// Server information
	echo '<h2>Server Information</h2>';
	echo '<pre>';
	echo 'PHP Version: ' . phpversion() . "\n";
	echo 'Server Software: ' . $_SERVER['SERVER_SOFTWARE'] . "\n";
	echo 'Document Root: ' . $_SERVER['DOCUMENT_ROOT'] . "\n";
	echo 'Script Filename: ' . $_SERVER['SCRIPT_FILENAME'] . "\n";
	echo 'Current Directory: ' . __DIR__ . "\n";
	echo 'Host: ' . $_SERVER['HTTP_HOST'] . "\n";
	echo 'User Agent: ' . $_SERVER['HTTP_USER_AGENT'] . "\n";
	echo '</pre>';

	// Create a direct inline proxy that doesn't rely on external files
	echo '<h2>Emergency Inline Proxy</h2>';
	echo '<p class="info">If all other proxies are failing, copy this emergency proxy code to a new file on your server:</p>';

	$emergencyProxy = <<<'EOT'
<?php
/**
 * Emergency Inline Proxy - For when all other proxies fail
 */

// Basic config
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE, PATCH');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// OPTIONS request handler
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Set JSON content type for API responses
header('Content-Type: application/json');

// Get the endpoint parameter
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
$backendUrl = 'https://app-backend-esgi-app.azurewebsites.net';

// Validate the endpoint
if (empty($endpoint)) {
    echo json_encode(['success' => false, 'message' => 'No endpoint specified']);
    exit;
}

// Construct the URL
$url = $backendUrl;
if (strpos($endpoint, 'http') === 0) {
    $url = $endpoint;
} else {
    if ($endpoint[0] !== '/' && substr($backendUrl, -1) !== '/') {
        $url .= '/';
    }
    $url .= $endpoint;
}

// Add query parameters
$query_string = $_SERVER['QUERY_STRING'];
$query_string = preg_replace('/(&|\?)endpoint=[^&]*/', '', $query_string);
if (!empty($query_string)) {
    $url .= (strpos($url, '?') === false ? '?' : '&') . $query_string;
}

// Make the request
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

// Forward headers
$headers = [];
if (function_exists('getallheaders')) {
    $requestHeaders = getallheaders();
    foreach ($requestHeaders as $name => $value) {
        if (strtolower($name) !== 'host' && strtolower($name) !== 'content-length') {
            $headers[] = "$name: $value";
        }
    }
}
$headers[] = 'X-Emergency-Proxy: true';
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Forward cookies
if (isset($_SERVER['HTTP_COOKIE'])) {
    curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE']);
}

// Forward request body
if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH', 'DELETE'])) {
    $input = file_get_contents('php://input');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
}

// Execute request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Handle errors
if (curl_errno($ch)) {
    $error = curl_error($ch);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error connecting to backend', 'error' => $error]);
    curl_close($ch);
    exit;
}

// Handle user profile special case
if (($http_code == 404 || $http_code >= 500) && $endpoint === 'api/user/profile') {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (isset($_SESSION['user'])) {
        echo json_encode([
            'success' => true,
            'user' => $_SESSION['user'],
            'message' => 'Profile retrieved from session (backend unavailable)',
            'is_fallback' => true
        ]);
        curl_close($ch);
        exit;
    }
}

curl_close($ch);
http_response_code($http_code);
echo $response;
EOT;

	echo '<pre>' . htmlspecialchars($emergencyProxy) . '</pre>';

	// Add a form to create the emergency proxy
	echo '<form method="post" action="">';
	echo '<input type="hidden" name="create_emergency_proxy" value="1">';
	echo '<input type="submit" value="Create Emergency Proxy" style="padding: 10px; background: #4CAF50; color: white; border: none; cursor: pointer;">';
	echo '</form>';

	// Process the form submission
	if (isset($_POST['create_emergency_proxy'])) {
		$emergencyProxyPath = __DIR__ . '/emergency-proxy.php';
		$createResult = @file_put_contents($emergencyProxyPath, $emergencyProxy);

		if ($createResult !== false) {
			echo '<p class="success">Emergency proxy created successfully at: emergency-proxy.php</p>';
			echo '<p>Use it with: <code>emergency-proxy.php?endpoint=status.php</code></p>';

			// Update API service to use the emergency proxy
			$apiServicePath = __DIR__ . '/js/api-service.js';
			if (file_exists($apiServicePath)) {
				$apiService = file_get_contents($apiServicePath);

				// Replace the proxy URL
				$apiService = preg_replace(
					'/const _corsProxy = "(.*?)";/',
					'const _corsProxy = "emergency-proxy.php";',
					$apiService
				);

				// Save the modified file
				$updateResult = @file_put_contents($apiServicePath, $apiService);

				if ($updateResult !== false) {
					echo '<p class="success">API service updated to use the emergency proxy.</p>';
				} else {
					echo '<p class="error">Failed to update API service. You need to manually change _corsProxy to "emergency-proxy.php" in js/api-service.js</p>';
				}
			}
		} else {
			echo '<p class="error">Failed to create emergency proxy. Permission denied.</p>';
		}
	}
	?>

	<h2>Troubleshooting Steps</h2>
	<ol>
		<li>Run the <a href="install-proxy.php">install-proxy.php</a> script again to reinstall the unified proxy files</li>
		<li>If that doesn't work, use the Emergency Proxy button above to create an emergency proxy</li>
		<li>Check your web.config for proper URL rewriting rules</li>
		<li>Make sure IIS has appropriate handler mappings for PHP</li>
		<li>Verify that the PHP process has permission to create and access files</li>
		<li>Try restarting the Azure Web App from the Azure Portal</li>
	</ol>
</body>

</html>