<?php

/**
 * JSONP Test Script
 * 
 * Ce script permet de tester une connexion via JSONP
 * (une alternative à CORS pour les anciens navigateurs)
 */

// Le nom de la fonction de rappel est passé en paramètre 'callback'
$callback = isset($_GET['callback']) ? $_GET['callback'] : 'jsonpCallback';

// Validation du nom de fonction pour éviter les injections
if (!preg_match('/^[a-zA-Z0-9_]+$/', $callback)) {
	header('HTTP/1.1 400 Bad Request');
	exit('Invalid callback name');
}

// Paramètre data à renvoyer (optionnel)
$data = isset($_GET['data']) ? $_GET['data'] : '';

// Données à renvoyer
$response = [
	'success' => true,
	'message' => 'JSONP test successful',
	'timestamp' => date('Y-m-d H:i:s'),
	'method' => $_SERVER['REQUEST_METHOD'],
	'data' => $data,
	'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'Unknown'
];

// Définir le type de contenu JavaScript
header('Content-Type: application/javascript');

// Renvoyer le résultat avec la fonction de rappel
echo $callback . '(' . json_encode($response) . ');';
