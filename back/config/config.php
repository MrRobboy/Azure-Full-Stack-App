<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gestion_notes');

// Connexion à la base de données
try {
	$pdo = new PDO(
		"mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
		DB_USER,
		DB_PASS,
		array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
	);
} catch (PDOException $e) {
	die("Erreur de connexion : " . $e->getMessage());
}

// Démarrage de la session
session_start();
