<?php
// Fichier de test unifié pour le proxy
header('Content-Type: application/json');

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
header('Access-Control-Max-Age: 3600');
header('Access-Control-Allow-Credentials: true');

// Headers de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\'; style-src \'self\' \'unsafe-inline\';');

// Configuration des tests
$testConfig = [
	'endpoints' => [
		'status' => 'status.php',
		'auth' => 'auth/login',
		'matieres' => 'matieres',
		'notes' => 'notes'
	],
	'security_headers' => [
		'X-Content-Type-Options',
		'X-Frame-Options',
		'X-XSS-Protection',
		'Strict-Transport-Security',
		'Content-Security-Policy'
	],
	'cors_headers' => [
		'Access-Control-Allow-Origin',
		'Access-Control-Allow-Methods',
		'Access-Control-Allow-Headers',
		'Access-Control-Max-Age',
		'Access-Control-Allow-Credentials'
	]
];

// Fonction pour exécuter les tests
function runTests($config)
{
	$results = [];

	// Test de connexion
	foreach ($config['endpoints'] as $name => $endpoint) {
		$results["connection_test_$name"] = testConnection($endpoint);
	}

	// Test CORS
	foreach ($config['endpoints'] as $name => $endpoint) {
		$results["cors_test_$name"] = testCORS($endpoint, $config['cors_headers']);
	}

	// Test de sécurité
	$results['security_test'] = testSecurity($config['security_headers']);

	// Test de performance
	$results['performance_test'] = testPerformance();

	// Test de rate limit
	$results['rate_limit_test'] = testRateLimit();

	// Test de validation des entrées
	$results['input_validation_test'] = testInputValidation();

	return $results;
}

// Test de connexion
function testConnection($endpoint)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "api-bridge.php?endpoint=$endpoint");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$error = curl_error($ch);
	curl_close($ch);

	return [
		'success' => $httpCode === 200,
		'status' => $httpCode === 200 ? '✅' : '❌',
		'message' => $httpCode === 200 ? 'Connection successful' : 'HTTP ' . $httpCode,
		'details' => [
			'http_code' => $httpCode,
			'response' => $response,
			'error' => $error
		]
	];
}

// Test CORS
function testCORS($endpoint, $requiredHeaders)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "api-bridge.php?endpoint=$endpoint");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$response = curl_exec($ch);
	$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$headers = substr($response, 0, $headerSize);
	curl_close($ch);

	$missingHeaders = [];
	foreach ($requiredHeaders as $header) {
		if (stripos($headers, $header) === false) {
			$missingHeaders[] = $header;
		}
	}

	return [
		'success' => empty($missingHeaders),
		'status' => empty($missingHeaders) ? '✅' : '❌',
		'message' => empty($missingHeaders) ? 'CORS Headers Check' : 'Missing CORS Headers',
		'details' => [
			'missing_headers' => $missingHeaders,
			'headers' => $headers
		]
	];
}

// Test de sécurité
function testSecurity($requiredHeaders)
{
	$headers = getallheaders();
	$missingHeaders = [];

	foreach ($requiredHeaders as $header) {
		if (!isset($headers[$header])) {
			$missingHeaders[] = $header;
		}
	}

	return [
		'success' => empty($missingHeaders),
		'status' => empty($missingHeaders) ? '✅' : '❌',
		'message' => empty($missingHeaders) ? 'Security Tests' : 'Missing Security Headers',
		'details' => [
			'missing_headers' => $missingHeaders,
			'headers' => $headers
		]
	];
}

// Test de performance
function testPerformance()
{
	$start = microtime(true);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "api-bridge.php?endpoint=status.php");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_exec($ch);
	$end = microtime(true);
	curl_close($ch);

	$responseTime = ($end - $start) * 1000; // en millisecondes

	return [
		'success' => $responseTime < 1000, // moins d'une seconde
		'status' => $responseTime < 1000 ? '✅' : '❌',
		'message' => 'Performance Test',
		'details' => [
			'response_time' => $responseTime,
			'threshold' => 1000
		]
	];
}

// Test de rate limit
function testRateLimit()
{
	$responses = [];
	for ($i = 0; $i < 10; $i++) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "api-bridge.php?endpoint=status.php");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$responses[] = [
			'request' => $i + 1,
			'http_code' => $httpCode,
			'response' => $response
		];

		usleep(100000); // 100ms entre chaque requête
	}

	$rateLimited = false;
	foreach ($responses as $response) {
		if ($response['http_code'] === 429) {
			$rateLimited = true;
			break;
		}
	}

	return [
		'success' => $rateLimited,
		'status' => $rateLimited ? '✅' : '❌',
		'message' => 'Rate Limit Test',
		'details' => [
			'responses' => $responses
		]
	];
}

// Test de validation des entrées
function testInputValidation()
{
	$testInputs = [
		'valid' => 'test123',
		'invalid' => str_repeat('a', 1001) // plus long que la limite
	];

	$results = [];
	foreach ($testInputs as $type => $input) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "api-bridge.php?endpoint=status.php");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['input' => $input]));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$results[$type] = [
			'input' => $input,
			'http_code' => $httpCode,
			'response' => $response
		];
	}

	return [
		'success' => $results['valid']['http_code'] === 200 && $results['invalid']['http_code'] === 400,
		'status' => ($results['valid']['http_code'] === 200 && $results['invalid']['http_code'] === 400) ? '✅' : '❌',
		'message' => 'Input Validation Test',
		'details' => [
			'results' => $results
		]
	];
}

// Exécuter les tests
$testResults = runTests($testConfig);

// Ajouter les informations de base
$testResults['info'] = [
	'timestamp' => date('Y-m-d H:i:s'),
	'server' => $_SERVER['SERVER_SOFTWARE'],
	'php_version' => PHP_VERSION,
	'request_method' => $_SERVER['REQUEST_METHOD'],
	'request_uri' => $_SERVER['REQUEST_URI'],
	'query_string' => $_SERVER['QUERY_STRING']
];

// Afficher les résultats
echo json_encode($testResults, JSON_PRETTY_PRINT);
