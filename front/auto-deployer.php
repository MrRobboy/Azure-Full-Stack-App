<?php

/**
 * Auto-Deployer de proxies pour Azure
 * Ce script vérifie si les fichiers nécessaires existent et les crée si nécessaire
 */

// Activer le reporting d'erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'auto_deployer.log');

// Répertoire courant
$current_dir = dirname(__FILE__);

// Journal des opérations
function log_operation($message)
{
	error_log("[" . date('Y-m-d H:i:s') . "] " . $message);
	return $message;
}

// Réponse JSON pour les appels AJAX
function json_response($success, $message, $data = null)
{
	header('Content-Type: application/json');
	echo json_encode([
		'success' => $success,
		'message' => $message,
		'data' => $data
	]);
	exit;
}

// Vérifier si c'est un appel AJAX
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
	strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Fichiers à vérifier
$required_files = [
	'simple-proxy.php' => [
		'path' => $current_dir . '/simple-proxy.php',
		'content' => <<<'EOT'
<?php
// Simple proxy for Azure - minimal version to test deployment
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'proxy_errors.log');

// Log the file path and existence
$self_path = __FILE__;
$parent_dir = dirname($self_path);
error_log("Simple proxy file path: $self_path");
error_log("Parent directory: $parent_dir");

// Add CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only set content type if not OPTIONS request
if ($_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
    header('Content-Type: application/json');
}

// Get the endpoint parameter
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
if (empty($endpoint)) {
    echo json_encode([
        'success' => false,
        'message' => 'No endpoint specified',
        'debug' => $_GET
    ]);
    exit;
}

// Log proxy request
error_log("Proxy request to endpoint: " . $endpoint);
error_log("Method: " . $_SERVER['REQUEST_METHOD']);

// Basic configuration
$api_base_url = 'https://app-backend-esgi-app.azurewebsites.net';

// Construct the API URL
$api_url = $api_base_url;
if (strpos($endpoint, 'http') === 0) {
    // If the endpoint is a full URL, use that directly
    $api_url = $endpoint;
} else {
    // Otherwise, append it to the base URL
    if (!empty($endpoint)) {
        if ($endpoint[0] !== '/' && substr($api_base_url, -1) !== '/') {
            $api_url .= '/';
        }
        $api_url .= $endpoint;
    }
}

// Initialize cURL
$ch = curl_init($api_url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

// Forward headers
$headers = [];
$request_headers = getallheaders();
foreach ($request_headers as $name => $value) {
    if (strtolower($name) !== 'host') {
        $headers[] = $name . ': ' . $value;
    }
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Forward cookies
$cookie_string = '';
foreach ($_COOKIE as $name => $value) {
    $cookie_string .= $name . '=' . $value . '; ';
}
if (!empty($cookie_string)) {
    curl_setopt($ch, CURLOPT_COOKIE, $cookie_string);
}

// Forward request body for POST, PUT, PATCH
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH') {
    $input = file_get_contents('php://input');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
}

// Execute the request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for cURL errors
if ($response === false) {
    $error = curl_error($ch);
    curl_close($ch);
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Proxy error: ' . $error,
        'endpoint' => $endpoint,
        'url' => $api_url
    ]);
    exit;
}

// Close cURL
curl_close($ch);

// Set the HTTP response code
http_response_code($http_code);

// Output the response
echo $response;
EOT
	],
	'api-bridge.php' => [
		'path' => $current_dir . '/api-bridge.php',
		'content' => <<<'EOT'
<?php
/**
 * API Bridge - Last Resort Fallback for Azure Connectivity Issues
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'api_bridge_errors.log');

// Log access
$log_message = sprintf(
    "[%s] API Bridge accessed from %s - URI: %s, Method: %s",
    date('Y-m-d H:i:s'),
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['REQUEST_URI'],
    $_SERVER['REQUEST_METHOD']
);
error_log($log_message);

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Set JSON content type
header('Content-Type: application/json');

// Configuration
$api_base_url = 'https://app-backend-esgi-app.azurewebsites.net';

// Get endpoint from query string
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
$query_string = $_SERVER['QUERY_STRING'];

// Remove endpoint parameter from query string
$query_string = preg_replace('/(&|\?)endpoint=[^&]*/', '', $query_string);

// Check if endpoint is provided
if (empty($endpoint)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'No endpoint specified',
        'debug' => $_GET
    ]);
    exit;
}

// Construct API URL
$api_url = $api_base_url;
if (strpos($endpoint, 'http') === 0) {
    // If endpoint is a full URL, use that directly
    $api_url = $endpoint;
} else {
    // Append endpoint to base URL
    if (!empty($endpoint)) {
        if ($endpoint[0] !== '/' && substr($api_base_url, -1) !== '/') {
            $api_url .= '/';
        }
        $api_url .= $endpoint;
    }
}

// Set up cURL request
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

// Forward request body for POST, PUT, PATCH
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH') {
    $input = file_get_contents('php://input');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
}

// Execute the request
$response = curl_exec($ch);

// Check for cURL errors
if ($response === false) {
    $error = curl_error($ch);
    curl_close($ch);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'API Bridge error: ' . $error,
        'endpoint' => $endpoint,
        'url' => $api_url
    ]);
    exit;
}

// Get HTTP status code
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Set the HTTP response code
http_response_code($http_code);

// Output the response
echo $response;
EOT
	],
	'direct-login.php' => [
		'path' => $current_dir . '/direct-login.php',
		'content' => <<<'EOT'
<?php
// Ce script permet de se connecter directement au backend sans passer par le proxy

// Affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'direct_login_errors.log');

// Définir l'en-tête de réponse
header('Content-Type: application/json');

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données de connexion
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données de connexion incomplètes']);
    exit;
}

// Log des informations (sans le mot de passe)
error_log("Tentative de connexion pour: " . $data['email']);

// URL de l'API de login
$loginUrl = 'https://app-backend-esgi-app.azurewebsites.net/api/auth/login';

// Initialiser cURL
$ch = curl_init($loginUrl);

// Configurer la requête
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => $data['email'],
    'password' => $data['password']
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Désactiver la vérification SSL pour éviter les problèmes

// Exécuter la requête
$response = curl_exec($ch);

// Vérifier les erreurs
if ($response === false) {
    $error = curl_error($ch);
    error_log("Erreur cURL: " . $error);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de connexion au serveur',
        'error' => $error
    ]);
    curl_close($ch);
    exit;
}

// Traiter la réponse
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Définir le code HTTP de réponse
http_response_code($httpCode);

// Retourner la réponse
echo $response;
EOT
	],
	'local-proxy.php' => [
		'path' => $current_dir . '/local-proxy.php',
		'content' => <<<'EOT'
<?php
/**
 * Simple Proxy PHP minimal 
 * Version minimale pour essayer de résoudre les problèmes de connexion
 */

// Activer les logs d'erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'proxy_errors.log');

// Récupérer l'endpoint demandé
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
if (empty($endpoint)) {
    http_response_code(400);
    echo json_encode(['error' => 'Endpoint non spécifié']);
    exit;
}

// URL backend de base
$backend_url = 'https://app-backend-esgi-app.azurewebsites.net';

// Construire l'URL complète
$url = $backend_url;
if (strpos($endpoint, 'http') === 0) {
    // Endpoint complet
    $url = $endpoint;
} else {
    // Endpoint relatif
    if ($endpoint[0] !== '/' && substr($backend_url, -1) !== '/') {
        $url .= '/';
    }
    $url .= $endpoint;
}

// Récupérer la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Log des informations de requête
error_log("Proxying request to: $url");
error_log("Method: $method");

// Initialiser cURL
$ch = curl_init($url);

// Configurer les options de base
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Pour POST, PUT ou PATCH, copier le body
if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
    $input = file_get_contents("php://input");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
}

// Exécuter la requête
$response = curl_exec($ch);

// Vérifier les erreurs
if ($response === false) {
    $error = curl_error($ch);
    error_log("cURL error: $error");
    http_response_code(500);
    echo json_encode(['error' => "Proxy error: $error"]);
    curl_close($ch);
    exit;
}

// Traiter la réponse
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Envoyer le code de statut
http_response_code($statusCode);

// Ajouter les headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Envoyer le body
echo $response;
EOT
	]
];

// Vérifier et créer les fichiers manquants
$created_files = [];
$existing_files = [];
$failed_creation = [];

foreach ($required_files as $name => $file_info) {
	if (!file_exists($file_info['path'])) {
		// Le fichier n'existe pas, essayez de le créer
		$success = @file_put_contents($file_info['path'], $file_info['content']);
		if ($success !== false) {
			$created_files[] = $name;
			log_operation("Created file: " . $name);
		} else {
			$failed_creation[] = $name;
			log_operation("Failed to create file: " . $name);
		}
	} else {
		$existing_files[] = $name;
		log_operation("File already exists: " . $name);
	}
}

// Vérifier le web.config pour les règles de routage
$web_config_path = $current_dir . '/web.config';
if (!file_exists($web_config_path)) {
	$web_config_content = <<<'EOT'
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <rule name="PHPHandler" stopProcessing="true">
                    <match url="^.*\.php$" />
                    <action type="None" />
                </rule>
                
                <rule name="SimpleProxyAccess" stopProcessing="true">
                    <match url="^(.*/)?simple-proxy\.php$" />
                    <action type="Rewrite" url="simple-proxy.php" />
                </rule>
                
                <rule name="LoginRedirect" stopProcessing="true">
                    <match url="^login$" />
                    <action type="Rewrite" url="login.php" />
                </rule>
            </rules>
        </rewrite>
        
        <httpProtocol>
            <customHeaders>
                <add name="Access-Control-Allow-Origin" value="*" />
                <add name="Access-Control-Allow-Methods" value="GET, POST, OPTIONS, PUT, DELETE" />
                <add name="Access-Control-Allow-Headers" value="Content-Type, Authorization, X-Requested-With" />
            </customHeaders>
        </httpProtocol>
    </system.webServer>
</configuration>
EOT;
	$web_config_result = @file_put_contents($web_config_path, $web_config_content);
	if ($web_config_result !== false) {
		$created_files[] = 'web.config';
		log_operation("Created web.config file");
	} else {
		$failed_creation[] = 'web.config';
		log_operation("Failed to create web.config file");
	}
} else {
	$existing_files[] = 'web.config';
	log_operation("web.config already exists");
}

// Préparer le résultat
$result = [
	'created' => $created_files,
	'existing' => $existing_files,
	'failed' => $failed_creation,
	'success' => count($failed_creation) === 0,
	'timestamp' => date('Y-m-d H:i:s')
];

// Répondre en fonction du type de requête
if ($is_ajax) {
	json_response($result['success'], "Vérification des fichiers terminée", $result);
} else {
	if (!headers_sent()) {
		header('Content-Type: text/html');
	}
	echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Auto-Deployer de Proxy</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        h1 { color: #333; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
        .card { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Auto-Deployer de Proxy</h1>
    <div class='card'>
        <h2>Résultat</h2>
        <p class='" . ($result['success'] ? 'success' : 'error') . "'>
            " . ($result['success'] ? 'Tous les fichiers sont prêts!' : 'Certains fichiers n\'ont pas pu être créés.') . "
        </p>
    </div>";

	if (!empty($created_files)) {
		echo "<div class='card'>
            <h3>Fichiers créés (" . count($created_files) . ")</h3>
            <ul>";
		foreach ($created_files as $file) {
			echo "<li>$file</li>";
		}
		echo "</ul>
        </div>";
	}

	if (!empty($existing_files)) {
		echo "<div class='card'>
            <h3>Fichiers existants (" . count($existing_files) . ")</h3>
            <ul>";
		foreach ($existing_files as $file) {
			echo "<li>$file</li>";
		}
		echo "</ul>
        </div>";
	}

	if (!empty($failed_creation)) {
		echo "<div class='card'>
            <h3 class='error'>Échecs de création (" . count($failed_creation) . ")</h3>
            <ul>";
		foreach ($failed_creation as $file) {
			echo "<li>$file</li>";
		}
		echo "</ul>
        </div>";
	}

	echo "<div class='card'>
        <h3>Tests rapides</h3>
        <ul>
            <li><a href='simple-proxy.php?endpoint=status.php' target='_blank'>Test simple-proxy.php</a></li>
            <li><a href='api-bridge.php?endpoint=status.php' target='_blank'>Test api-bridge.php</a></li>
            <li><a href='local-proxy.php?endpoint=status.php' target='_blank'>Test local-proxy.php</a></li>
        </ul>
    </div>
    
    <div class='card'>
        <h3>Retour à l'application</h3>
        <p><a href='login.php'>Retourner à la page de connexion</a></p>
    </div>
    
    <script>
        console.log('Auto-Deployer results:', " . json_encode($result) . ");
    </script>
</body>
</html>";
}
