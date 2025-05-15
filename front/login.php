<?php
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user']) && !empty($_SESSION['token'])) {
	header('Location: dashboard.php');
	exit;
}

// Vérification automatique des fichiers proxy pour Azure
if (strpos($_SERVER['HTTP_HOST'], 'azurewebsites.net') !== false) {
	// Sur Azure, vérifier si les fichiers proxy existent
	$proxy_files = [
		'simple-proxy.php' => '<?php
// Simple proxy for Azure - minimal version
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("log_errors", 1);
ini_set("error_log", "proxy_errors.log");

// Log basic information
error_log("Simple proxy accessed at " . date("Y-m-d H:i:s") . " from " . ($_SERVER["REMOTE_ADDR"] ?? "unknown"));
error_log("Request method: " . ($_SERVER["REQUEST_METHOD"] ?? "unknown"));
error_log("Query string: " . ($_SERVER["QUERY_STRING"] ?? "none"));

// Add CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle OPTIONS requests
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

// Set content type
header("Content-Type: application/json");

// Get the endpoint parameter
$endpoint = isset($_GET["endpoint"]) ? $_GET["endpoint"] : "";
if (empty($endpoint)) {
    echo json_encode([
        "error" => "No endpoint specified",
        "timestamp" => date("Y-m-d H:i:s")
    ]);
    exit;
}

// Basic configuration
$backend_url = "https://app-backend-esgi-app.azurewebsites.net";

// Log the target
error_log("Proxying to: " . $backend_url . " endpoint: " . $endpoint);

// Construct the API URL
$url = $backend_url;
if (strpos($endpoint, "http") === 0) {
    $url = $endpoint;
} else {
    if (!empty($endpoint)) {
        if ($endpoint[0] !== "/" && substr($backend_url, -1) !== "/") {
            $url .= "/";
        }
        $url .= $endpoint;
    }
}

error_log("Full URL: " . $url);

// Initialize cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER["REQUEST_METHOD"]);

// Forward headers
$headers = [];
foreach (getallheaders() as $name => $value) {
    if (strtolower($name) !== "host") {
        $headers[] = $name . ": " . $value;
    }
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Forward request body for POST, PUT, PATCH
if ($_SERVER["REQUEST_METHOD"] === "POST" || $_SERVER["REQUEST_METHOD"] === "PUT" || $_SERVER["REQUEST_METHOD"] === "PATCH") {
    $input = file_get_contents("php://input");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
    error_log("Request body: " . substr($input, 0, 100) . (strlen($input) > 100 ? "..." : ""));
}

// Execute the request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for cURL errors
if ($response === false) {
    $error = curl_error($ch);
    curl_close($ch);
    error_log("cURL error: " . $error);
    http_response_code(500);
    echo json_encode([
        "error" => "Proxy error: " . $error,
        "url" => $url,
        "timestamp" => date("Y-m-d H:i:s")
    ]);
    exit;
}

// Close cURL
curl_close($ch);

// Log the response code
error_log("Response code: " . $http_code);

// Set the HTTP response code
http_response_code($http_code);

// Output the response
echo $response;',
		
		'api-bridge.php' => '<?php
// API Bridge - Last Resort Fallback
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("log_errors", 1);
ini_set("error_log", "api_bridge_errors.log");

// Log access
error_log("API Bridge accessed at " . date("Y-m-d H:i:s") . " from " . ($_SERVER["REMOTE_ADDR"] ?? "unknown"));
error_log("Request method: " . ($_SERVER["REQUEST_METHOD"] ?? "unknown"));
error_log("Query string: " . ($_SERVER["QUERY_STRING"] ?? "none"));

// CORS headers
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight OPTIONS
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

// Get endpoint from query string
$endpoint = isset($_GET["endpoint"]) ? $_GET["endpoint"] : "";
if (empty($endpoint)) {
    http_response_code(400);
    echo json_encode([
        "error" => "No endpoint specified",
        "timestamp" => date("Y-m-d H:i:s")
    ]);
    exit;
}

// Log the target endpoint
error_log("API Bridge endpoint: " . $endpoint);

// Configuration
$backend_url = "https://app-backend-esgi-app.azurewebsites.net";

// Construct API URL
$url = $backend_url;
if (strpos($endpoint, "http") === 0) {
    $url = $endpoint;
} else {
    if (!empty($endpoint)) {
        if ($endpoint[0] !== "/" && substr($backend_url, -1) !== "/") {
            $url .= "/";
        }
        $url .= $endpoint;
    }
}

error_log("Full URL: " . $url);

// Set up cURL request
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER["REQUEST_METHOD"]);

// Forward request body
if ($_SERVER["REQUEST_METHOD"] === "POST" || $_SERVER["REQUEST_METHOD"] === "PUT" || $_SERVER["REQUEST_METHOD"] === "PATCH") {
    $input = file_get_contents("php://input");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
    error_log("Request body: " . substr($input, 0, 100) . (strlen($input) > 100 ? "..." : ""));
}

// Execute the request
$response = curl_exec($ch);

// Check for cURL errors
if ($response === false) {
    $error = curl_error($ch);
    curl_close($ch);
    error_log("cURL error: " . $error);
    http_response_code(500);
    echo json_encode([
        "error" => "API Bridge error: " . $error,
        "url" => $url,
        "timestamp" => date("Y-m-d H:i:s")
    ]);
    exit;
}

// Get HTTP status code
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Log response
error_log("Response code: " . $http_code);
error_log("Response length: " . strlen($response));

// Set the HTTP response code
http_response_code($http_code);

// Output the response
echo $response;',
		
		'direct-login.php' => '<?php
// Direct Login - Se connecter sans proxy
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("log_errors", 1);
ini_set("error_log", "direct_login_errors.log");

error_log("Direct login accessed at " . date("Y-m-d H:i:s") . " from " . ($_SERVER["REMOTE_ADDR"] ?? "unknown"));

// Set headers
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle OPTIONS requests
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

// Vérifier la méthode HTTP
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "error" => "Method not allowed",
        "timestamp" => date("Y-m-d H:i:s")
    ]);
    exit;
}

// Get login data
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!is_array($data) || !isset($data["email"]) || !isset($data["password"])) {
    http_response_code(400);
    echo json_encode([
        "error" => "Invalid login data",
        "timestamp" => date("Y-m-d H:i:s")
    ]);
    exit;
}

// Log login attempt (without password)
error_log("Login attempt for: " . ($data["email"] ?? "unknown"));

// API endpoint for login
$login_url = "https://app-backend-esgi-app.azurewebsites.net/api/auth/login";

// Initialize cURL
$ch = curl_init($login_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Accept: application/json"
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

// Execute request
$response = curl_exec($ch);

// Check for errors
if ($response === false) {
    $error = curl_error($ch);
    error_log("Login cURL error: " . $error);
    http_response_code(500);
    echo json_encode([
        "error" => "Connection error: " . $error,
        "timestamp" => date("Y-m-d H:i:s")
    ]);
    curl_close($ch);
    exit;
}

// Get response code
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Log response
error_log("Login response code: " . $http_code);
error_log("Login response length: " . strlen($response));

// Set response code and output
http_response_code($http_code);
echo $response;',
		
		'local-proxy.php' => '<?php
// Ultra-simplified proxy
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("log_errors", 1);
ini_set("error_log", "local_proxy_errors.log");

error_log("Local proxy accessed at " . date("Y-m-d H:i:s") . " from " . ($_SERVER["REMOTE_ADDR"] ?? "unknown"));
error_log("Request method: " . ($_SERVER["REQUEST_METHOD"] ?? "unknown"));
error_log("Query string: " . ($_SERVER["QUERY_STRING"] ?? "none"));

// Headers
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle OPTIONS
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

// Get endpoint
$endpoint = isset($_GET["endpoint"]) ? $_GET["endpoint"] : "";
if (empty($endpoint)) {
    echo json_encode([
        "error" => "No endpoint specified",
        "timestamp" => date("Y-m-d H:i:s")
    ]);
    exit;
}

// Construct URL
$backend_url = "https://app-backend-esgi-app.azurewebsites.net";
$url = $backend_url;
if ($endpoint[0] !== "/" && substr($backend_url, -1) !== "/") {
    $url .= "/";
}
$url .= $endpoint;

error_log("Requesting: " . $url);

// Get content with proper method
$context = stream_context_create([
    "http" => [
        "method" => $_SERVER["REQUEST_METHOD"],
        "header" => "Content-Type: application/json\r\n" .
                   "Accept: application/json\r\n" .
                   "User-Agent: ESGI-App-Proxy/1.0\r\n",
        "content" => file_get_contents("php://input"),
        "timeout" => 30
    ],
    "ssl" => [
        "verify_peer" => false,
        "verify_peer_name" => false
    ]
]);

$response = @file_get_contents($url, false, $context);

if ($response === false) {
    error_log("Local proxy error: " . error_get_last()["message"] ?? "Unknown error");
    http_response_code(500);
    echo json_encode([
        "error" => "Failed to connect to backend",
        "url" => $url,
        "timestamp" => date("Y-m-d H:i:s")
    ]);
    exit;
}

// Get response code
$status_line = $http_response_header[0];
preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);
$status = $match[1] ?? 200;
http_response_code($status);

// Output response
echo $response;',
		
		'status.php' => '<?php
// Status and health check file
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("log_errors", 1);
ini_set("error_log", "status_check.log");

// Log access
error_log("Status check accessed at " . date("Y-m-d H:i:s") . " from " . ($_SERVER["REMOTE_ADDR"] ?? "unknown"));

// Headers
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Check if filesystem is writable
$temp_file = __DIR__ . '/temp_test.txt';
$fs_writable = @file_put_contents($temp_file, "Test") !== false;
if ($fs_writable) {
    @unlink($temp_file);
}

// Check files existence
$proxy_files = [
    'simple-proxy.php',
    'api-bridge.php',
    'direct-login.php',
    'local-proxy.php'
];
$file_status = [];
foreach ($proxy_files as $file) {
    $file_path = __DIR__ . '/' . $file;
    $file_status[$file] = [
        'exists' => file_exists($file_path),
        'size' => file_exists($file_path) ? filesize($file_path) : 0,
        'permissions' => file_exists($file_path) ? substr(sprintf("%o", fileperms($file_path)), -4) : "none",
        'readable' => file_exists($file_path) ? is_readable($file_path) : false,
        'path' => $file_path
    ];
}

echo json_encode([
    "success" => true,
    "status" => "ok",
    "server" => $_SERVER["SERVER_NAME"] ?? 'unknown',
    "message" => "System status check",
    "timestamp" => date("Y-m-d H:i:s"),
    "environment" => strpos($_SERVER["HTTP_HOST"] ?? "", "azurewebsites.net") !== false ? "azure" : "local",
    "php_version" => PHP_VERSION,
    "filesystem_writable" => $fs_writable,
    "proxy_files" => $file_status,
    "server_info" => [
        "software" => $_SERVER["SERVER_SOFTWARE"] ?? "unknown",
        "document_root" => $_SERVER["DOCUMENT_ROOT"] ?? "unknown",
        "script_filename" => $_SERVER["SCRIPT_FILENAME"] ?? "unknown"
    ]
]);'
	];
	
	// Diagnostiquer l'accès au fichier système
	$diagnostics = [];
	$diagnostics['document_root'] = $_SERVER['DOCUMENT_ROOT'] ?? 'unknown';
	$diagnostics['script_filename'] = $_SERVER['SCRIPT_FILENAME'] ?? 'unknown'; 
	$diagnostics['current_dir'] = __DIR__;
	$diagnostics['can_write_current'] = is_writable(__DIR__);
	
	error_log('Azure deployment diagnostics: ' . json_encode($diagnostics));
	
	// Créer les fichiers manquants avec contenu plus robuste
	foreach ($proxy_files as $filename => $content) {
		$filepath = __DIR__ . '/' . $filename;
		$file_exists = file_exists($filepath);
		
		if (!$file_exists || filesize($filepath) < 100) {
			$success = @file_put_contents($filepath, $content);
			if ($success) {
				// Rendre le fichier accessible
				@chmod($filepath, 0755);
				error_log("Created/Updated proxy file: $filename with " . strlen($content) . " bytes");
			} else {
				error_log("Failed to create $filename, error: " . error_get_last()['message'] ?? 'unknown');
			}
		}
		
		// Aussi créer les copies dans d'autres dossiers pour plus de fiabilité
		$extra_dirs = ['/api', '/proxy'];
		foreach ($extra_dirs as $dir) {
			$dir_path = __DIR__ . $dir;
			
			// Créer le dossier s'il n'existe pas
			if (!is_dir($dir_path)) {
				$dir_created = @mkdir($dir_path, 0755, true);
				error_log("Directory creation " . ($dir_created ? "succeeded" : "failed") . " for " . $dir);
			}
			
			if (is_dir($dir_path)) {
				$extra_filepath = $dir_path . '/' . $filename;
				if (!file_exists($extra_filepath) || filesize($extra_filepath) < 100) {
					$success = @file_put_contents($extra_filepath, $content);
					if ($success) {
						@chmod($extra_filepath, 0755);
						error_log("Created/Updated proxy file in $dir: $filename");
					}
				}
			}
		}
	}
	
	// Vérifier si web.config existe et le mettre à jour si nécessaire
	$web_config_file = __DIR__ . '/web.config';
	$create_web_config = !file_exists($web_config_file) || filesize($web_config_file) < 100;
	
	if ($create_web_config) {
				$web_config_content = '<?xml version="1.0" encoding="UTF-8"?><configuration>    <system.webServer>        <rewrite>            <rules>                <rule name="PHP Files" stopProcessing="true">                    <match url="^.*\.php$" />                    <action type="None" />                </rule>                                <rule name="SimpleProxyAccess" stopProcessing="true">                    <match url="^(.*/)?simple-proxy\.php$" />                    <action type="Rewrite" url="simple-proxy.php" />                </rule>                                <rule name="ApiBridgeAccess" stopProcessing="true">                    <match url="^(.*/)?api-bridge\.php$" />                    <action type="Rewrite" url="api-bridge.php" />                </rule>                                <rule name="LocalProxyAccess" stopProcessing="true">                    <match url="^(.*/)?local-proxy\.php$" />                    <action type="Rewrite" url="local-proxy.php" />                </rule>                                <rule name="DirectLoginAccess" stopProcessing="true">                    <match url="^(.*/)?direct-login\.php$" />                    <action type="Rewrite" url="direct-login.php" />                </rule>                                <rule name="StatusAccess" stopProcessing="true">                    <match url="^(.*/)?status\.php$" />                    <action type="Rewrite" url="status.php" />                </rule>                                <rule name="LoginRedirect" stopProcessing="true">                    <match url="^login$" />                    <action type="Rewrite" url="login.php" />                </rule>                                <rule name="DashboardRedirect" stopProcessing="true">                    <match url="^dashboard$" />                    <action type="Rewrite" url="dashboard.php" />                </rule>            </rules>        </rewrite>                <directoryBrowse enabled="true" />                <httpProtocol>            <customHeaders>                <add name="Access-Control-Allow-Origin" value="*" />                <add name="Access-Control-Allow-Methods" value="GET, POST, OPTIONS, PUT, DELETE" />                <add name="Access-Control-Allow-Headers" value="Content-Type, Authorization, X-Requested-With" />            </customHeaders>        </httpProtocol>    </system.webServer></configuration>';
		$web_config_result = @file_put_contents($web_config_file, $web_config_content);
		error_log("Web.config " . ($web_config_result ? "created/updated successfully" : "creation failed"));
	}
	
	// Créer un test.html pour diagnostiquer les problèmes
	$test_html_file = __DIR__ . '/proxy-test.html';
	if (!file_exists($test_html_file)) {
		$test_html_content = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Proxy Test Page</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .test { margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>Proxy Test Page</h1>
    <div id="status">Testing proxy files...</div>
    <div id="results"></div>
    
    <script>
        // Files to test
        const filesToTest = [
            "simple-proxy.php",
            "api-bridge.php",
            "direct-login.php",
            "local-proxy.php",
            "status.php",
            "/simple-proxy.php",
            "/api-bridge.php",
            "/api/simple-proxy.php",
            "/proxy/simple-proxy.php"
        ];
        
        // Test results
        const results = {};
        let testsCompleted = 0;
        
        // Test each file
        filesToTest.forEach(file => {
            const testDiv = document.createElement("div");
            testDiv.className = "test";
            testDiv.innerHTML = `<h3>Testing: ${file}</h3><div class="status">In progress...</div>`;
            document.getElementById("results").appendChild(testDiv);
            
            // Try to fetch the file with status.php endpoint
            fetch(`${file}?endpoint=status.php`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    testDiv.querySelector(".status").innerHTML = 
                        `<p class="success">SUCCESS</p><pre>${text}</pre>`;
                    results[file] = { success: true, text };
                })
                .catch(error => {
                    testDiv.querySelector(".status").innerHTML = 
                        `<p class="error">FAILED: ${error.message}</p>`;
                    results[file] = { success: false, error: error.message };
                })
                .finally(() => {
                    testsCompleted++;
                    document.getElementById("status").textContent = 
                        `Completed ${testsCompleted} of ${filesToTest.length} tests`;
                    
                    if (testsCompleted === filesToTest.length) {
                        // All tests completed, show summary
                        const workingProxies = Object.keys(results)
                            .filter(file => results[file].success);
                        
                        if (workingProxies.length > 0) {
                            document.getElementById("status").innerHTML = 
                                `<h2 class="success">Found ${workingProxies.length} working proxies!</h2>
                                <p>Working proxies: ${workingProxies.join(", ")}</p>`;
                        } else {
                            document.getElementById("status").innerHTML = 
                                `<h2 class="error">No working proxies found!</h2>`;
                        }
                    }
                });
        });
    </script>
</body>
</html>';
		@file_put_contents($test_html_file, $test_html_content);
		error_log("Created proxy test HTML file");
	}
}

$pageTitle = "Connexion";
ob_start();
?>

<div class="login-container">
	<div class="login-card">
		<h2>Connexion</h2>
		<div id="error-message" class="alert alert-danger" style="display: none;">
			<div class="error-header">
				<i class="fas fa-exclamation-circle"></i>
				<span class="error-title">Erreur</span>
			</div>
			<div class="error-content"></div>
			<div class="error-details" style="display: none;">
				<div class="error-type"></div>
				<div class="error-timestamp"></div>
				<div class="error-trace"></div>
			</div>
			<button class="btn btn-link btn-sm toggle-details" onclick="toggleErrorDetails()">
				Afficher les détails
			</button>
		</div>
		<form id="login-form">
			<div class="form-group">
				<label for="email">Email</label>
				<div class="input-group">
					<span class="input-group-icon"><i class="fas fa-envelope"></i></span>
					<input type="email" id="email" name="email" required>
				</div>
			</div>
			<div class="form-group">
				<label for="password">Mot de passe</label>
				<div class="input-group">
					<span class="input-group-icon"><i class="fas fa-lock"></i></span>
					<input type="password" id="password" name="password" required>
				</div>
			</div>
			<button type="submit" class="btn btn-primary">
				<span class="btn-text">Se connecter</span>
				<span class="btn-loading" style="display: none;">
					<i class="fas fa-spinner fa-spin"></i> Connexion en cours...
				</span>
			</button>
		</form>
	</div>
</div>

<script src="js/cache-buster.js"></script>
<script src="js/config.js?v=1.9"></script>
<script src="js/notification-system.js?v=1.1"></script>
<script>
	// Vérificateur en ligne pour détecter si les fichiers proxy sont accessibles
	(function() {
		const isAzure = window.location.hostname.includes('azurewebsites.net');
		if (!isAzure) return; // Seulement sur Azure

		console.log("Vérification de l'accessibilité des proxies sur Azure...");

		// Essayer plusieurs chemins de proxy possibles
		const pathsToCheck = [
			'simple-proxy.php',
			'api-bridge.php',
			'local-proxy.php',
			'/simple-proxy.php',
			'/api/simple-proxy.php',
			'/proxy/simple-proxy.php'
		];

		let proxyFound = false;

		// Fonction pour vérifier un fichier proxy
		async function checkProxy(path) {
			try {
				const response = await fetch(`${path}?endpoint=status.php`);
				if (response.ok) {
					console.log(`Proxy trouvé et fonctionnel: ${path}`);
					proxyFound = true;
					return true;
				}
				return false;
			} catch (e) {
				console.log(`Erreur lors de l'accès à ${path}:`, e.message);
				return false;
			}
		}

		// Vérifier tous les chemins
		Promise.all(pathsToCheck.map(path => checkProxy(path)))
			.then(results => {
				// Si aucun proxy n'est trouvé, recharger la page pour déclencher la création des fichiers
				if (!proxyFound) {
					console.warn("Aucun proxy accessible trouvé. Tentative de création automatique...");
					// Forcer le rechargement de la page pour déclencher la création de fichiers côté serveur
					if (!window.localStorage.getItem('proxy_creation_attempted')) {
						window.localStorage.setItem('proxy_creation_attempted', 'true');
						console.log("Rechargement de la page pour créer les proxies...");
						setTimeout(() => window.location.reload(), 1000);
					} else {
						console.warn("Une tentative de création a déjà été effectuée, vérifiez les permissions du serveur.");
						// Montrer une notification
						if (typeof NotificationSystem !== 'undefined') {
							NotificationSystem.warning("Impossible d'accéder aux proxies. Contactez l'administrateur.");
						}
					}
				} else {
					// Réinitialiser le flag si un proxy est trouvé
					window.localStorage.removeItem('proxy_creation_attempted');
				}
			});
	})();
</script>

<script>
	// Utiliser le chemin du proxy depuis la configuration
	let proxyPath = appConfig.proxyUrl;
	console.log('Environment:', isAzure ? 'Azure' : 'Local');
	console.log('Using proxy path:', proxyPath);

	// Log de diagnostic étendu sur l'environnement
	console.log('Full hostname:', window.location.hostname);
	console.log('Full window.location:', window.location.href);
	console.log('Current pathname:', window.location.pathname);
	console.log('API base URL from config:', appConfig.backendBaseUrl);

	// Fonction pour tester un chemin de proxy
	async function testProxyPath(path) {
		try {
			console.log('Testing proxy path:', path);

			// Tester avec différents endpoints pour plus de fiabilité
			const endpointsToTry = [
				'status.php', // Notre fichier de statut local
				'api/status', // Endpoint API de statut
				'api/ping', // Autre endpoint potentiel
				'health' // Endpoint de santé standard
			];

			// Essayer chaque endpoint jusqu'à ce qu'un fonctionne
			for (const endpoint of endpointsToTry) {
				try {
					console.log(`Testing ${path} with endpoint: ${endpoint}`);
					const response = await fetch(`${path}?endpoint=${encodeURIComponent(endpoint)}`);

					if (response.ok) {
						console.log(`Proxy test ${path} with ${endpoint}: SUCCESS (${response.status})`);

						// Vérifier que la réponse est du JSON valide
						try {
							const text = await response.text();
							console.log(`Proxy ${path} response:`, text);
							JSON.parse(text);
						} catch (jsonErr) {
							console.warn(`Proxy ${path} returned invalid JSON, but connection works:`, jsonErr);
							// On ignore cette erreur, tant que la connexion est établie
						}

						return true;
					}

					console.log(`Proxy test ${path} with ${endpoint}: FAILED (${response.status})`);
				} catch (endpointErr) {
					console.log(`Error testing ${path} with ${endpoint}:`, endpointErr.message);
				}
			}

			// Si on arrive ici, aucun endpoint n'a fonctionné
			console.error(`All endpoints failed for proxy path: ${path}`);
			return false;
		} catch (err) {
			console.error(`Proxy test ${path} error:`, err);
			return false;
		}
	}

	// Tester différents chemins pour le proxy
	(async function() {
		// Essayer plusieurs chemins possibles pour trouver le proxy
		const pathsToTry = [
			proxyPath,
			// Chemins relatifs
			"simple-proxy.php",
			"api-bridge.php",
			"local-proxy.php",
			"proxy/simple-proxy.php",
			"api/simple-proxy.php",
			// Chemins absolus
			"/simple-proxy.php",
			"/api-bridge.php",
			"/local-proxy.php",
			"/proxy/simple-proxy.php",
			"/api/simple-proxy.php",
			// Chemins basés sur le chemin actuel
			window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + "/simple-proxy.php",
			window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + "/api-bridge.php",
			window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + "/local-proxy.php"
		];

		console.log('Testing proxy paths in this order:', pathsToTry);
		let foundWorkingPath = false;

		for (const path of pathsToTry) {
			if (await testProxyPath(path)) {
				console.log('Found working proxy path:', path);
				proxyPath = path;
				foundWorkingPath = true;
				break;
			}
		}

		if (!foundWorkingPath) {
			console.error('No working proxy path found! Login may fail.');
			// Définir un fallback
			proxyPath = "local-proxy.php";
			console.log('Using fallback proxy path:', proxyPath);
		}
	})();

	function toggleErrorDetails() {
		const details = document.querySelector('.error-details');
		const button = document.querySelector('.toggle-details');
		if (details.style.display === 'none') {
			details.style.display = 'block';
			button.textContent = 'Masquer les détails';
		} else {
			details.style.display = 'none';
			button.textContent = 'Afficher les détails';
		}
	}

	function displayError(error) {
		const errorMessage = document.getElementById('error-message');
		const errorContent = errorMessage.querySelector('.error-content');
		const errorType = errorMessage.querySelector('.error-type');
		const errorTimestamp = errorMessage.querySelector('.error-timestamp');
		const errorTrace = errorMessage.querySelector('.error-trace');

		errorContent.textContent = error.message || 'Une erreur est survenue';
		errorType.textContent = 'Type: ' + (error.type || 'Inconnu');
		errorTimestamp.textContent = 'Date: ' + (error.timestamp || new Date().toISOString());
		errorTrace.textContent = 'Détails: ' + JSON.stringify(error.details || {}, null, 2);

		errorMessage.style.display = 'block';

		// Ajouter la notification via le système de notification
		NotificationSystem.error(error.message || 'Erreur de connexion');
	}

	// Function to store session data via AJAX
	async function storeSessionData(userData, token) {
		try {
			const response = await fetch('session-handler.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					action: 'login',
					user: userData,
					token: token
				})
			});

			return response.ok;
		} catch (error) {
			console.error('Session storage error:', error);
			return false;
		}
	}

	document.getElementById('login-form').addEventListener('submit', async function(e) {
		e.preventDefault();

		const email = document.getElementById('email').value;
		const password = document.getElementById('password').value;
		const errorMessage = document.getElementById('error-message');
		const submitButton = this.querySelector('button[type="submit"]');
		const btnText = submitButton.querySelector('.btn-text');
		const btnLoading = submitButton.querySelector('.btn-loading');

		// Afficher le loader
		btnText.style.display = 'none';
		btnLoading.style.display = 'inline-block';
		errorMessage.style.display = 'none';

		// Force proxy to true for reliability
		appConfig.useProxy = true;

		// Notification pour informer l'utilisateur
		NotificationSystem.info('Tentative de connexion...');

		try {
			console.log('Tentative de connexion...');

			// Endpoint d'authentification
			const loginEndpoint = 'api/auth/login';

			// Utiliser le proxy qui est déjà testé et fonctionne
			console.log('Utilisation du proxy pour la connexion: ' + proxyPath);
			NotificationSystem.info("Connexion via proxy...");

			// Construire l'URL d'API en utilisant le proxy
			const loginUrl = `${proxyPath}?endpoint=${encodeURIComponent(loginEndpoint)}`;
			console.log('URL de connexion via proxy:', loginUrl);

			// Options de requête
			const fetchOptions = {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					email,
					password
				}),
				credentials: 'include'
			};

			// Faire la requête via le proxy
			let response;
			try {
				console.log('Envoi de la requête via proxy...');

				response = await fetch(loginUrl, fetchOptions);
				console.log('Réponse du proxy reçue:', response);

				if (!response.ok) {
					// Si le proxy principal échoue, essayer l'API bridge comme solution de secours
					throw new Error('Échec de la requête via proxy: ' + response.status);
				}
			} catch (proxyErr) {
				console.error("Erreur de connexion via proxy:", proxyErr);

				// Essayer avec l'api-bridge comme solution de dernier recours
				try {
					console.log('Tentative avec api-bridge...');
					NotificationSystem.info("Tentative avec api-bridge...");

					// Utiliser le api-bridge qui fait une requête côté serveur
					const bridgeUrl = 'api-bridge.php?endpoint=' + encodeURIComponent(loginEndpoint);
					console.log('URL bridge:', bridgeUrl);

					response = await fetch(bridgeUrl, fetchOptions);
					console.log('Réponse api-bridge reçue:', response);

					if (!response.ok) {
						throw new Error('Échec de la requête api-bridge: ' + response.status);
					}
				} catch (bridgeErr) {
					console.error("Erreur avec api-bridge:", bridgeErr);

					// Solution de dernier recours - utiliser direct-login.php
					try {
						console.log('Tentative avec direct-login.php...');
						NotificationSystem.info("Tentative avec solution de secours...");

						// direct-login.php fait la requête côté serveur avec PHP
						response = await fetch('direct-login.php', fetchOptions);
						console.log('Réponse direct-login reçue:', response);

						if (!response.ok) {
							throw new Error('Toutes les tentatives ont échoué');
						}
					} catch (directErr) {
						console.error("Toutes les tentatives ont échoué:", directErr);
						throw new Error("Impossible de communiquer avec le backend: " + directErr.message);
					}
				}
			}

			console.log('Réponse reçue:', response);
			const responseText = await response.text();
			console.log('Contenu de la réponse:', responseText);

			let data;
			try {
				data = JSON.parse(responseText);
			} catch (e) {
				console.error('Erreur de parsing JSON:', e);
				throw new Error('Réponse invalide du serveur: ' + responseText);
			}

			if (response.ok && data.success) {
				// Store user data in a session (using PHP session)
				const sessionSaved = await storeSessionData(data.user, data.token);

				if (sessionSaved) {
					NotificationSystem.success('Connexion réussie! Redirection vers le tableau de bord...');
					console.log('Login successful, redirecting to dashboard...');

					// Short delay for notification to be visible
					setTimeout(() => {
						window.location.href = 'dashboard.php';
					}, 1000);
				} else {
					throw new Error('Erreur de sauvegarde de session');
				}
			} else {
				displayError({
					type: 'Erreur d\'authentification',
					message: data.message || 'Identifiants incorrects',
					details: data
				});
				console.error('Erreur de connexion:', data);
			}
		} catch (error) {
			displayError({
				type: 'Erreur réseau',
				message: 'Erreur de connexion au serveur: ' + error.message,
				details: {
					errorMessage: error.message,
					useProxy: appConfig.useProxy,
					proxyUrl: proxyPath,
					isAzure: isAzure
				}
			});
			console.error('Erreur:', error);
		} finally {
			// Réinitialiser le bouton
			btnText.style.display = 'inline-block';
			btnLoading.style.display = 'none';
		}
	});
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>