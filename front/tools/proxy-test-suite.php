<?php

/**
 * Proxy Test Suite
 * 
 * Ce fichier teste tous les aspects du proxy API :
 * - Connexion au backend
 * - Gestion CORS
 * - Gestion des erreurs
 * - Performance
 * - Sécurité
 */

// Charger la configuration
require_once __DIR__ . '/../config/proxy.php';

// Configuration du test
$testConfig = [
	'backend_url' => BACKEND_BASE_URL,
	'test_endpoints' => [
		'status' => 'status.php',
		'login' => 'auth/login',
		'matieres' => 'matieres',
		'notes' => 'notes'
	],
	'test_methods' => ['GET', 'POST', 'OPTIONS'],
	'timeout' => 5 // Timeout pour chaque test en secondes
];

// Fonction pour formater le résultat
function formatResult($success, $message, $data = null)
{
	return [
		'success' => $success,
		'message' => $message,
		'data' => $data,
		'timestamp' => date('Y-m-d H:i:s')
	];
}

// Fonction pour tester un endpoint
function testEndpoint($url, $method = 'GET', $data = null)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, SSL_VERIFY_PEER);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, SSL_VERIFY_HOST);

	if ($data) {
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
	}

	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$error = curl_error($ch);
	curl_close($ch);

	return [
		'http_code' => $httpCode,
		'response' => $response,
		'error' => $error
	];
}

// Fonction pour tester les headers CORS
function testCorsHeaders($url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'OPTIONS');
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_NOBODY, true);

	$response = curl_exec($ch);
	$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$headers = substr($response, 0, $headerSize);
	curl_close($ch);

	return $headers;
}

// Interface HTML pour les tests
?>
<!DOCTYPE html>
<html>

<head>
	<title>Proxy Test Suite</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			margin: 20px;
		}

		.test-section {
			margin: 20px 0;
			padding: 10px;
			border: 1px solid #ddd;
		}

		.success {
			color: green;
		}

		.error {
			color: red;
		}

		.warning {
			color: orange;
		}

		pre {
			background: #f5f5f5;
			padding: 10px;
			overflow: auto;
		}

		table {
			border-collapse: collapse;
			width: 100%;
		}

		th,
		td {
			border: 1px solid #ddd;
			padding: 8px;
			text-align: left;
		}

		th {
			background-color: #f2f2f2;
		}
	</style>
</head>

<body>
	<h1>Proxy Test Suite</h1>

	<?php
	// Section 1: Test de Connexion
	echo "<div class='test-section'>";
	echo "<h2>1. Test de Connexion</h2>";

	foreach ($testConfig['test_endpoints'] as $name => $endpoint) {
		$url = $testConfig['backend_url'] . '/' . $endpoint;
		$result = testEndpoint($url);

		echo "<h3>Test: $name</h3>";
		echo "<table>";
		echo "<tr><th>URL</th><td>$url</td></tr>";
		echo "<tr><th>Status</th><td class='" . ($result['http_code'] == 200 ? 'success' : 'error') . "'>" . $result['http_code'] . "</td></tr>";
		if ($result['error']) {
			echo "<tr><th>Error</th><td class='error'>" . $result['error'] . "</td></tr>";
		}
		echo "</table>";
	}
	echo "</div>";

	// Section 2: Test CORS
	echo "<div class='test-section'>";
	echo "<h2>2. Test CORS</h2>";

	foreach (CORS_ALLOWED_ORIGINS as $origin) {
		$headers = testCorsHeaders($testConfig['backend_url']);
		echo "<h3>Test CORS pour: $origin</h3>";
		echo "<pre>$headers</pre>";
	}
	echo "</div>";

	// Section 3: Test de Performance
	echo "<div class='test-section'>";
	echo "<h2>3. Test de Performance</h2>";

	$startTime = microtime(true);
	$iterations = 10;
	$successCount = 0;

	for ($i = 0; $i < $iterations; $i++) {
		$result = testEndpoint($testConfig['backend_url'] . '/status.php');
		if ($result['http_code'] == 200) {
			$successCount++;
		}
	}

	$endTime = microtime(true);
	$totalTime = $endTime - $startTime;
	$avgTime = $totalTime / $iterations;

	echo "<table>";
	echo "<tr><th>Total Tests</th><td>$iterations</td></tr>";
	echo "<tr><th>Succès</th><td>$successCount</td></tr>";
	echo "<tr><th>Temps Total</th><td>" . number_format($totalTime, 4) . "s</td></tr>";
	echo "<tr><th>Temps Moyen</th><td>" . number_format($avgTime, 4) . "s</td></tr>";
	echo "</table>";
	echo "</div>";

	// Section 4: Test de Sécurité
	echo "<div class='test-section'>";
	echo "<h2>4. Test de Sécurité</h2>";

	// Test SSL
	$sslResult = testEndpoint($testConfig['backend_url'] . '/status.php');
	echo "<h3>Test SSL</h3>";
	echo "<table>";
	echo "<tr><th>SSL Verifié</th><td class='" . (SSL_VERIFY_PEER ? 'success' : 'warning') . "'>" . (SSL_VERIFY_PEER ? 'Oui' : 'Non') . "</td></tr>";
	echo "<tr><th>Connexion Sécurisée</th><td class='" . ($sslResult['error'] ? 'error' : 'success') . "'>" . ($sslResult['error'] ? 'Non' : 'Oui') . "</td></tr>";
	echo "</table>";

	// Test des Headers de Sécurité
	$headers = testCorsHeaders($testConfig['backend_url']);
	echo "<h3>Headers de Sécurité</h3>";
	echo "<pre>$headers</pre>";
	echo "</div>";
	?>
</body>

</html>