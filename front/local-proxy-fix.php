<?php
// Ultra-simplified proxy - Fixed version
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
if (!empty($endpoint)) {
	// Ensure we have a slash between base URL and endpoint
	if ($endpoint[0] !== "/" && substr($backend_url, -1) !== "/") {
		$url .= "/";
	}
	$url .= $endpoint;
}

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
	error_log("Local proxy error: " . (error_get_last()["message"] ?? "Unknown error"));
	http_response_code(500);
	echo json_encode([
		"error" => "Failed to connect to backend",
		"url" => $url,
		"timestamp" => date("Y-m-d H:i:s")
	]);
	exit;
}

// Get response code using a safer method
$status = 200; // Default status
if (!empty($http_response_header[0])) {
	$status_line = $http_response_header[0];
	if (preg_match("#HTTP/[0-9.]+\s+([0-9]+)#", $status_line, $match)) {
		$status = intval($match[1]);
	}
}
http_response_code($status);

// Output response
echo $response;
