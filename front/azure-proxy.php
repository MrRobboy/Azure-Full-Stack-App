<?php
// Redirection vers simple-proxy.php
// Ce fichier est recherché par le frontend
error_log("Azure proxy called - redirecting to simple-proxy.php");

// Préserver tous les paramètres
$queryString = $_SERVER['QUERY_STRING'];
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

// Inclure le simple proxy avec la même requête
include __DIR__ . '/simple-proxy.php';
