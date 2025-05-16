<?php
// Redirection vers simplified-jwt-bridge.php
// Ce fichier est recherché par le frontend pour l'authentification
error_log("API bridge called - redirecting to simplified-jwt-bridge.php");

// Préserver tous les paramètres
$queryString = $_SERVER['QUERY_STRING'];
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

// Si l'endpoint concerne l'authentification, utiliser simplified-jwt-bridge.php
if (strpos($endpoint, 'auth') !== false) {
	include __DIR__ . '/simplified-jwt-bridge.php';
} else {
	// Pour tous les autres endpoints, utiliser simple-proxy.php
	include __DIR__ . '/simple-proxy.php';
}
