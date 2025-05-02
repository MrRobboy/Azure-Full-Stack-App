<?php
// Activation de l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Test de la configuration
echo "Test de la configuration PHP/Apache\n";
echo "--------------------------------\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "--------------------------------\n";

// Test de la connexion à la base de données
try {
	require_once __DIR__ . '/../config/config.php';
	$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;
	$pdo = new PDO($dsn, DB_USER, DB_PASS);
	echo "Connexion à la base de données réussie\n";
} catch (PDOException $e) {
	echo "Erreur de connexion à la base de données: " . $e->getMessage() . "\n";
}

// Test des modules Apache
echo "--------------------------------\n";
echo "Modules Apache chargés:\n";
if (function_exists('apache_get_modules')) {
	print_r(apache_get_modules());
} else {
	echo "La fonction apache_get_modules n'est pas disponible\n";
}
