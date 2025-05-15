<?php
// Script de test pour vérifier la connexion à SQL Server
require_once __DIR__ . '/config/config.php';

// Vérifier que l'extension SQL Server est installée
if (!extension_loaded('sqlsrv') && !extension_loaded('pdo_sqlsrv')) {
	die("Extension PHP pour SQL Server non installée. Veuillez installer les extensions sqlsrv et pdo_sqlsrv.");
}

echo "<h1>Test de connexion à SQL Server</h1>";
echo "<h2>Informations de configuration</h2>";
echo "<pre>";
echo "DB_TYPE: " . (defined('DB_TYPE') ? DB_TYPE : 'non défini (utilise MySQL par défaut)') . "\n";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_PORT: " . (defined('DB_PORT') ? DB_PORT : '1433 (port par défaut)') . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_USER: " . DB_USER . "\n";
echo "DB_PASS: " . (empty(DB_PASS) ? "vide" : "****") . "\n";
echo "</pre>";

// Test de connexion avec PDO
echo "<h2>Test de connexion avec PDO</h2>";
try {
	$port = defined('DB_PORT') ? DB_PORT : '1433';
	$dsn = "sqlsrv:Server=" . DB_HOST . "," . $port . ";Database=" . DB_NAME;

	$options = [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
	];

	$conn = new PDO($dsn, DB_USER, DB_PASS, $options);
	echo "<p style='color: green;'>✅ Connexion réussie !</p>";

	// Test d'une requête simple
	echo "<h3>Test de requête</h3>";
	$query = "SELECT 1 AS test";
	$stmt = $conn->query($query);
	$result = $stmt->fetch(PDO::FETCH_ASSOC);

	echo "<p>Résultat de 'SELECT 1 AS test': " . json_encode($result) . "</p>";

	// Test des tables
	echo "<h3>Tables disponibles</h3>";
	$query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
	$stmt = $conn->query($query);
	$tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo "<ul>";
	foreach ($tables as $table) {
		echo "<li>" . htmlspecialchars($table['TABLE_NAME']) . "</li>";
	}
	echo "</ul>";

	// Test sur les données
	if (!empty($tables)) {
		$testTable = $tables[0]['TABLE_NAME'];
		echo "<h3>Échantillon de données de la table '{$testTable}'</h3>";

		try {
			$query = "SELECT TOP 5 * FROM [{$testTable}]";
			$stmt = $conn->query($query);
			$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

			echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
		} catch (PDOException $e) {
			echo "<p style='color: red;'>Erreur lors de la récupération des données: " . htmlspecialchars($e->getMessage()) . "</p>";
		}
	}
} catch (PDOException $e) {
	echo "<p style='color: red;'>❌ Erreur de connexion: " . htmlspecialchars($e->getMessage()) . "</p>";

	// Afficher des conseils de dépannage
	echo "<h3>Conseils de dépannage</h3>";
	echo "<ul>";
	echo "<li>Vérifiez que SQL Server est en cours d'exécution</li>";
	echo "<li>Vérifiez que les identifiants (utilisateur/mot de passe) sont corrects</li>";
	echo "<li>Vérifiez que l'authentification SQL est activée sur le serveur</li>";
	echo "<li>Vérifiez que le pare-feu autorise les connexions sur le port SQL Server</li>";
	echo "<li>Si vous utilisez une instance nommée, assurez-vous que le format est correct: SERVER\INSTANCE</li>";
	echo "</ul>";
}
