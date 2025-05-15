<?php
// Script de test du proxy
// Ce script permet de valider que le proxy fonctionne correctement

// Activer l'affichage des erreurs pour ce script de diagnostic
ini_set('display_errors', 1);
error_reporting(E_ALL);

// En-têtes pour afficher du texte brut
header('Content-Type: text/plain');

echo "=== TEST DE PROXY ESGI AZURE ===\n\n";
echo "Date du test: " . date('Y-m-d H:i:s') . "\n";
echo "Script path: " . __FILE__ . "\n";
echo "Script URL: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]\n\n";

// Test 1: Vérifier que le fichier simple-proxy.php existe
echo "=== TEST 1: Existence du fichier simple-proxy.php ===\n";
$proxyPath = __DIR__ . '/simple-proxy.php';
if (file_exists($proxyPath)) {
	echo "✓ OK - Le fichier proxy existe à: $proxyPath\n";
	echo "Taille: " . filesize($proxyPath) . " octets\n";
	echo "Dernière modification: " . date("Y-m-d H:i:s", filemtime($proxyPath)) . "\n";
} else {
	echo "✗ ERREUR - Le fichier proxy n'existe pas à: $proxyPath\n";
	// Rechercher le fichier ailleurs
	echo "Recherche dans le répertoire parent...\n";
	$parentProxyPath = dirname(__DIR__) . '/simple-proxy.php';
	if (file_exists($parentProxyPath)) {
		echo "✓ TROUVÉ - Le fichier proxy existe à: $parentProxyPath\n";
	} else {
		echo "✗ NON TROUVÉ dans le répertoire parent\n";
	}
}
echo "\n";

// Test 2: Vérifier que PHP peut exécuter le fichier proxy
echo "=== TEST 2: Tentative d'inclusion du fichier proxy ===\n";
if (file_exists($proxyPath)) {
	try {
		// Ne pas inclure réellement le fichier pour éviter les erreurs
		// mais vérifier qu'il est lisible
		if (is_readable($proxyPath)) {
			echo "✓ OK - Le fichier proxy est lisible\n";

			// Vérifier que le fichier contient du code PHP
			$content = file_get_contents($proxyPath);
			if (strpos($content, '<?php') !== false) {
				echo "✓ OK - Le fichier contient du code PHP\n";
			} else {
				echo "✗ ATTENTION - Le fichier ne commence pas par <?php\n";
			}
		} else {
			echo "✗ ERREUR - Le fichier proxy n'est pas lisible\n";
		}
	} catch (Exception $e) {
		echo "✗ ERREUR - Exception lors de la vérification du fichier: " . $e->getMessage() . "\n";
	}
} else {
	echo "✗ IGNORÉ - Le fichier proxy n'existe pas\n";
}
echo "\n";

// Test 3: Vérifier les URLs d'accès au proxy
echo "=== TEST 3: URLs d'accès possibles ===\n";
$base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$paths = [
	'/simple-proxy.php',
	rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/simple-proxy.php'
];

echo "Base URL: $base\n";
foreach ($paths as $path) {
	$url = $base . $path;
	echo "URL: $url\n";

	// On ne fait pas de requête HTTP réelle pour éviter de surcharger le serveur
	// On affiche juste les URLs possibles
}
echo "\n";

// Test 4: Configuration web.config
echo "=== TEST 4: Vérification du web.config ===\n";
$webConfigPath = __DIR__ . '/web.config';
if (file_exists($webConfigPath)) {
	echo "✓ OK - Le fichier web.config existe\n";
	$webConfigContent = file_get_contents($webConfigPath);

	// Vérifier si la règle SimpleProxyAccess existe
	if (strpos($webConfigContent, 'SimpleProxyAccess') !== false) {
		echo "✓ OK - La règle SimpleProxyAccess est configurée\n";
	} else {
		echo "✗ ATTENTION - La règle SimpleProxyAccess n'est pas configurée dans web.config\n";
	}
} else {
	echo "✗ ERREUR - Le fichier web.config n'existe pas\n";
}
echo "\n";

echo "=== RÉSUMÉ ===\n";
echo "Ce diagnostic peut aider à identifier les problèmes d'accès au proxy.\n";
echo "Vérifiez que le fichier simple-proxy.php est accessible à l'une des URLs listées ci-dessus.\n";
echo "Si le proxy est inaccessible, assurez-vous que le déploiement inclut le fichier simple-proxy.php et que web.config est correctement configuré.\n";
echo "\n";
echo "Fin du test: " . date('Y-m-d H:i:s') . "\n";
