<?php

/**
 * Test d'endpoints d'API
 * 
 * Ce script teste différents endpoints d'API pour trouver ceux qui fonctionnent
 */

// En-têtes pour la sortie HTML et CORS
header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Configuration du backend
$api_base_url = 'https://app-backend-esgi-app.azurewebsites.net';

// Liste des endpoints à tester
$endpoints_to_test = [
	// Endpoints standards d'authentification
	'api/auth/login',
	'api/login',
	'auth/login',
	'login',
	'user/login',
	'api/user/login',
	'api/v1/auth/login',
	'api/authenticate',

	// Endpoints standards d'API
	'api/status',
	'status',
	'api/health',
	'health',
	'api/version',
	'version',

	// Endpoints divers
	'api/users',
	'users',
	'api/products',
	'products'
];

// Fonction pour tester un endpoint avec différentes méthodes HTTP
function test_endpoint($base_url, $endpoint)
{
	echo "<h3>Test de $base_url/$endpoint</h3>";
	echo "<ul>";

	// Test avec GET
	$url = "$base_url/$endpoint";
	$result_get = test_http_method($url, 'GET');
	echo "<li>GET: " . ($result_get['success'] ?
		"<span style='color:green'>✓ " . $result_get['status'] . "</span>" :
		"<span style='color:red'>✗ " . $result_get['status'] . "</span>") . "</li>";

	// Test avec OPTIONS (pré-vol CORS)
	$result_options = test_http_method($url, 'OPTIONS');
	echo "<li>OPTIONS: " . ($result_options['success'] ?
		"<span style='color:green'>✓ " . $result_options['status'] . "</span>" :
		"<span style='color:red'>✗ " . $result_options['status'] . "</span>") . "</li>";

	// Test avec POST (simulé)
	$result_post = test_http_method($url, 'POST');
	echo "<li>POST: " . ($result_post['success'] ?
		"<span style='color:green'>✓ " . $result_post['status'] . "</span>" :
		"<span style='color:red'>✗ " . $result_post['status'] . "</span>") . "</li>";

	echo "</ul>";

	// Résultats détaillés si disponibles
	if ($result_get['success']) {
		echo "<div style='background-color:#e6ffe6;padding:10px;border-radius:5px;margin-bottom:15px'>";
		echo "<strong>Réponse GET:</strong><br>";
		echo "<pre>" . htmlspecialchars(json_encode(json_decode($result_get['response']), JSON_PRETTY_PRINT)) . "</pre>";
		echo "</div>";
	}

	// Retourner un résultat indiquant si au moins une méthode a fonctionné
	return [
		'endpoint' => $endpoint,
		'get_works' => $result_get['success'],
		'options_works' => $result_options['success'],
		'post_works' => $result_post['success'],
		'any_works' => $result_get['success'] || $result_options['success'] || $result_post['success']
	];
}

// Fonction pour tester une méthode HTTP spécifique
function test_http_method($url, $method)
{
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

	if ($method === 'POST') {
		// Simuler une requête POST avec des données simples
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['test' => true]));
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
	}

	// Timeout court pour ne pas attendre trop longtemps
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

	$response = curl_exec($ch);
	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$error = curl_error($ch);
	curl_close($ch);

	return [
		'success' => ($status >= 200 && $status < 500), // Considérer même 4xx comme "trouvé" pour diagnostic
		'status' => $status,
		'response' => $response,
		'error' => $error
	];
}

?>
<!DOCTYPE html>
<html>

<head>
	<title>Test d'endpoints d'API</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			max-width: 1200px;
			margin: 0 auto;
			padding: 20px;
		}

		h1 {
			color: #0078D4;
			text-align: center;
		}

		h2 {
			color: #0078D4;
			border-bottom: 1px solid #ddd;
			padding-bottom: 10px;
			margin-top: 30px;
		}

		.working-endpoints {
			background-color: #f0fff0;
			border: 1px solid #d0f0d0;
			border-radius: 5px;
			padding: 15px;
			margin: 20px 0;
		}

		.endpoint-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
			grid-gap: 15px;
			margin-top: 20px;
		}

		.endpoint-card {
			border: 1px solid #ddd;
			border-radius: 5px;
			padding: 15px;
			background-color: #f9f9f9;
		}

		.success {
			color: green;
			font-weight: bold;
		}

		.failure {
			color: red;
		}
	</style>
</head>

<body>
	<h1>Test d'endpoints d'API</h1>
	<p>Ce diagnostic teste différents endpoints d'API sur <code><?php echo $api_base_url; ?></code> pour identifier ceux qui sont accessibles.</p>

	<?php
	// Tester tous les endpoints
	$working_endpoints = [];
	$results = [];

	foreach ($endpoints_to_test as $endpoint) {
		$result = test_endpoint($api_base_url, $endpoint);
		$results[] = $result;

		if ($result['any_works']) {
			$working_endpoints[] = $endpoint;
		}
	}
	?>

	<h2>Résumé des endpoints fonctionnels</h2>
	<div class="working-endpoints">
		<?php if (count($working_endpoints) > 0): ?>
			<p><strong>Endpoints accessibles:</strong></p>
			<ul>
				<?php foreach ($working_endpoints as $endpoint): ?>
					<li><code><?php echo $api_base_url . '/' . $endpoint; ?></code></li>
				<?php endforeach; ?>
			</ul>
		<?php else: ?>
			<p><strong>Aucun endpoint accessible trouvé.</strong> Vérifiez que le backend est correctement configuré et en ligne.</p>
		<?php endif; ?>
	</div>

	<h2>Recommandations pour l'authentification</h2>
	<div class="endpoint-grid">
		<?php
		// Identifier les endpoints d'authentification potentiels
		$auth_endpoints = array_filter($results, function ($result) {
			return strpos($result['endpoint'], 'login') !== false ||
				strpos($result['endpoint'], 'auth') !== false;
		});

		if (count($auth_endpoints) > 0) {
			foreach ($auth_endpoints as $result) {
				if ($result['any_works']) {
					echo '<div class="endpoint-card">';
					echo '<h3 class="success">✓ ' . $result['endpoint'] . '</h3>';
					echo '<p>Cet endpoint est accessible et pourrait être utilisé pour l\'authentification.</p>';
					echo '<p>Méthodes supportées:</p>';
					echo '<ul>';
					echo '<li>GET: ' . ($result['get_works'] ? '<span class="success">Oui</span>' : '<span class="failure">Non</span>') . '</li>';
					echo '<li>OPTIONS: ' . ($result['options_works'] ? '<span class="success">Oui</span>' : '<span class="failure">Non</span>') . '</li>';
					echo '<li>POST: ' . ($result['post_works'] ? '<span class="success">Oui</span>' : '<span class="failure">Non</span>') . '</li>';
					echo '</ul>';
					echo '</div>';
				} else {
					echo '<div class="endpoint-card">';
					echo '<h3 class="failure">✗ ' . $result['endpoint'] . '</h3>';
					echo '<p>Cet endpoint n\'est pas accessible.</p>';
					echo '</div>';
				}
			}
		} else {
			echo '<p>Aucun endpoint d\'authentification potentiel n\'a été trouvé.</p>';
		}
		?>
	</div>
</body>

</html>