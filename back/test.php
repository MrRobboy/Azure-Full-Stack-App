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

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/services/DatabaseService.php';

try {
	// Test de la connexion à la base de données
	$db = DatabaseService::getInstance()->getConnection();
	echo "Connexion à la base de données réussie\n";

	// Test de la table CLASSE
	$stmt = $db->query("SELECT * FROM CLASSE");
	$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo "Nombre de classes trouvées : " . count($classes) . "\n";
	echo "Contenu de la table CLASSE :\n";
	print_r($classes);
} catch (Exception $e) {
	echo "Erreur : " . $e->getMessage() . "\n";
	echo "Trace : " . $e->getTraceAsString() . "\n";
}

// Test des modules Apache
echo "--------------------------------\n";
echo "Modules Apache chargés:\n";
if (function_exists('apache_get_modules')) {
	print_r(apache_get_modules());
} else {
	echo "La fonction apache_get_modules n'est pas disponible\n";
}
