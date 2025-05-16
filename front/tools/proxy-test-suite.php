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
$backendUrl = BACKEND_BASE_URL;
$endpoints = [
	'status.php',
	'auth/login',
	'matieres',
	'notes'
];

// Fonction pour formater les résultats
function formatResult($test, $result)
{
	$status = $result['success'] ? '✅' : '❌';
	$output = [
		'test' => $test,
		'status' => $status,
		'success' => $result['success'],
		'message' => $result['message'],
		'details' => $result['details'] ?? null
	];

	// Log dans la console
	echo "<script>console.log(" . json_encode($output, JSON_PRETTY_PRINT) . ");</script>";

	return $output;
}

// Fonction pour tester un endpoint
function testEndpoint($url, $method = 'GET', $data = null)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, SSL_VERIFY_PEER);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, SSL_VERIFY_HOST);
	curl_setopt($ch, CURLOPT_TIMEOUT, CURL_TIMEOUT);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, CURL_CONNECT_TIMEOUT);

	if ($method === 'POST') {
		curl_setopt($ch, CURLOPT_POST, true);
		if ($data) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		}
	}

	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$error = curl_error($ch);
	curl_close($ch);

	return [
		'success' => $httpCode >= 200 && $httpCode < 300,
		'message' => $error ?: "HTTP $httpCode",
		'details' => [
			'http_code' => $httpCode,
			'response' => $response,
			'error' => $error
		]
	];
}

// Fonction pour tester les headers CORS
function testCorsHeaders($url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, SSL_VERIFY_PEER);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, SSL_VERIFY_HOST);

	$response = curl_exec($ch);
	$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$headers = substr($response, 0, $headerSize);
	curl_close($ch);

	$corsHeaders = [
		'Access-Control-Allow-Origin' => false,
		'Access-Control-Allow-Methods' => false,
		'Access-Control-Allow-Headers' => false
	];

	foreach (explode("\n", $headers) as $line) {
		foreach ($corsHeaders as $header => $value) {
			if (stripos($line, $header) !== false) {
				$corsHeaders[$header] = true;
			}
		}
	}

	return [
		'success' => !in_array(false, $corsHeaders),
		'message' => 'CORS Headers Check',
		'details' => $corsHeaders
	];
}

// Fonction pour tester la performance
function testPerformance($url, $iterations = 5)
{
	$times = [];
	for ($i = 0; $i < $iterations; $i++) {
		$start = microtime(true);
		testEndpoint($url);
		$times[] = microtime(true) - $start;
	}

	$avg = array_sum($times) / count($times);
	$max = max($times);
	$min = min($times);

	return [
		'success' => $avg < 1.0, // Considérer comme succès si la moyenne est < 1 seconde
		'message' => 'Performance Test',
		'details' => [
			'average_time' => round($avg * 1000, 2) . 'ms',
			'min_time' => round($min * 1000, 2) . 'ms',
			'max_time' => round($max * 1000, 2) . 'ms',
			'iterations' => $iterations
		]
	];
}

// Fonction pour tester la sécurité
function testSecurity($url)
{
	$securityTests = [
		'SSL' => [
			'success' => strpos($url, 'https://') === 0,
			'message' => 'SSL Check',
			'details' => ['protocol' => parse_url($url, PHP_URL_SCHEME)]
		],
		'Headers' => testCorsHeaders($url),
		'RateLimit' => testRateLimit(),
		'InputValidation' => testInputValidation(),
		'SecurityHeaders' => testSecurityHeaders($url)
	];

	$overallSuccess = true;
	foreach ($securityTests as $test) {
		if (!$test['success']) {
			$overallSuccess = false;
			break;
		}
	}

	return [
		'success' => $overallSuccess,
		'message' => 'Security Tests',
		'details' => $securityTests
	];
}

// Fonction pour tester la limitation de taux
function testRateLimit()
{
	$testResults = [];
	$ip = '127.0.0.1';

	// Test de la limite de taux
	for ($i = 0; $i < SECURITY_CONFIG['rate_limit']['max_requests'] + 1; $i++) {
		$result = checkRateLimit($ip);
		if ($i < SECURITY_CONFIG['rate_limit']['max_requests']) {
			$testResults[] = $result;
		} else {
			$testResults[] = !$result; // Le dernier devrait être bloqué
		}
	}

	return [
		'success' => !in_array(false, array_slice($testResults, 0, -1)) && !end($testResults),
		'message' => 'Rate Limit Test',
		'details' => [
			'max_requests' => SECURITY_CONFIG['rate_limit']['max_requests'],
			'time_window' => SECURITY_CONFIG['rate_limit']['time_window'],
			'test_results' => $testResults
		]
	];
}

// Fonction pour tester la validation des entrées
function testInputValidation()
{
	$testCases = [
		'valid' => 'test123',
		'too_long' => str_repeat('a', SECURITY_CONFIG['input_validation']['max_length'] + 1),
		'empty' => '',
		'xss' => '<script>alert("xss")</script>',
		'sql_injection' => "' OR '1'='1"
	];

	$results = [];
	foreach ($testCases as $case => $input) {
		$results[$case] = [
			'input' => $input,
			'validated' => validateInput($input)
		];
	}

	return [
		'success' => $results['valid']['validated'] &&
			!$results['too_long']['validated'] &&
			!$results['empty']['validated'] &&
			$results['xss']['validated'] !== $testCases['xss'] &&
			$results['sql_injection']['validated'] !== $testCases['sql_injection'],
		'message' => 'Input Validation Test',
		'details' => $results
	];
}

// Fonction pour tester les headers de sécurité
function testSecurityHeaders($url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_NOBODY, true);

	$response = curl_exec($ch);
	$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$headers = substr($response, 0, $headerSize);
	curl_close($ch);

	$requiredHeaders = [
		'X-Content-Type-Options',
		'X-Frame-Options',
		'X-XSS-Protection',
		'Strict-Transport-Security',
		'Content-Security-Policy'
	];

	$foundHeaders = [];
	foreach ($requiredHeaders as $header) {
		$foundHeaders[$header] = stripos($headers, $header) !== false;
	}

	return [
		'success' => !in_array(false, $foundHeaders),
		'message' => 'Security Headers Test',
		'details' => $foundHeaders
	];
}

// Exécution des tests
$results = [
	'connection' => [],
	'cors' => [],
	'performance' => [],
	'security' => []
];

// Tests de connexion
foreach ($endpoints as $endpoint) {
	$url = $backendUrl . '/' . $endpoint;
	$results['connection'][$endpoint] = formatResult("Connection Test: $endpoint", testEndpoint($url));
}

// Tests CORS
foreach ($endpoints as $endpoint) {
	$url = $backendUrl . '/' . $endpoint;
	$results['cors'][$endpoint] = formatResult("CORS Test: $endpoint", testCorsHeaders($url));
}

// Tests de performance
$results['performance']['status'] = formatResult("Performance Test: status.php", testPerformance($backendUrl . '/status.php'));

// Tests de sécurité
$results['security']['overall'] = formatResult("Security Test", testSecurity($backendUrl));
$results['security']['rate_limit'] = formatResult("Rate Limit Test", testRateLimit());
$results['security']['input_validation'] = formatResult("Input Validation Test", testInputValidation());
$results['security']['security_headers'] = formatResult("Security Headers Test", testSecurityHeaders($backendUrl));

// Affichage des résultats
?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Proxy Test Suite</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			line-height: 1.6;
			margin: 20px;
			background-color: #f5f5f5;
		}

		.container {
			max-width: 1200px;
			margin: 0 auto;
			background-color: white;
			padding: 20px;
			border-radius: 8px;
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
		}

		h1 {
			color: #333;
			border-bottom: 2px solid #eee;
			padding-bottom: 10px;
		}

		h2 {
			color: #444;
			margin-top: 20px;
		}

		.test-section {
			margin-bottom: 30px;
			padding: 15px;
			background-color: #f9f9f9;
			border-radius: 4px;
		}

		.test-result {
			margin: 10px 0;
			padding: 10px;
			border-left: 4px solid #ddd;
		}

		.success {
			border-left-color: #4CAF50;
		}

		.error {
			border-left-color: #f44336;
		}

		.details {
			margin-top: 5px;
			padding: 10px;
			background-color: #fff;
			border-radius: 4px;
			font-family: monospace;
			white-space: pre-wrap;
		}

		.timestamp {
			color: #666;
			font-size: 0.9em;
			margin-top: 20px;
		}
	</style>
</head>

<body>
	<div class="container">
		<h1>Proxy Test Suite</h1>
		<div class="timestamp">Test exécuté le: <?php echo date('Y-m-d H:i:s'); ?></div>

		<div class="test-section">
			<h2>Tests de Connexion</h2>
			<?php foreach ($results['connection'] as $endpoint => $result): ?>
				<div class="test-result <?php echo $result['success'] ? 'success' : 'error'; ?>">
					<strong><?php echo $result['test']; ?></strong>
					<div><?php echo $result['message']; ?></div>
					<?php if ($result['details']): ?>
						<div class="details"><?php echo json_encode($result['details'], JSON_PRETTY_PRINT); ?></div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="test-section">
			<h2>Tests CORS</h2>
			<?php foreach ($results['cors'] as $endpoint => $result): ?>
				<div class="test-result <?php echo $result['success'] ? 'success' : 'error'; ?>">
					<strong><?php echo $result['test']; ?></strong>
					<div><?php echo $result['message']; ?></div>
					<?php if ($result['details']): ?>
						<div class="details"><?php echo json_encode($result['details'], JSON_PRETTY_PRINT); ?></div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="test-section">
			<h2>Tests de Performance</h2>
			<?php foreach ($results['performance'] as $test => $result): ?>
				<div class="test-result <?php echo $result['success'] ? 'success' : 'error'; ?>">
					<strong><?php echo $result['test']; ?></strong>
					<div><?php echo $result['message']; ?></div>
					<?php if ($result['details']): ?>
						<div class="details"><?php echo json_encode($result['details'], JSON_PRETTY_PRINT); ?></div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="test-section">
			<h2>Tests de Sécurité</h2>
			<?php foreach ($results['security'] as $test => $result): ?>
				<div class="test-result <?php echo $result['success'] ? 'success' : 'error'; ?>">
					<strong><?php echo $result['test']; ?></strong>
					<div><?php echo $result['message']; ?></div>
					<?php if ($result['details']): ?>
						<div class="details"><?php echo json_encode($result['details'], JSON_PRETTY_PRINT); ?></div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<script>
		// Fonction pour exporter les résultats
		function exportResults() {
			const results = <?php echo json_encode($results); ?>;
			console.log('Test Results:', results);

			// Créer un fichier de log
			const log = {
				timestamp: new Date().toISOString(),
				results: results
			};

			// Sauvegarder dans le localStorage
			localStorage.setItem('lastTestResults', JSON.stringify(log));

			// Afficher un message de confirmation
			alert('Les résultats ont été enregistrés dans la console et le localStorage');
		}

		// Exporter les résultats au chargement
		window.onload = exportResults;
	</script>
</body>

</html>