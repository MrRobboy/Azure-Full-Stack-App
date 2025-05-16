<?php

/**
 * Backend Explorer - Outil pour tester différents chemins d'accès au backend
 */

// Configuration de base
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Création du répertoire de logs si nécessaire
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
	mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/backend-explorer.log');

// Différentes bases d'URL à tester
$baseUrls = [
	'standard' => 'https://app-backend-esgi-app.azurewebsites.net',
	'api_path' => 'https://app-backend-esgi-app.azurewebsites.net/api',
	'index_php' => 'https://app-backend-esgi-app.azurewebsites.net/index.php',
	'api_router' => 'https://app-backend-esgi-app.azurewebsites.net/api-router.php'
];

// Différents chemins d'API à tester
$endpointPaths = [
	'/api-auth-login.php',
	'/api/auth/login',
	'/auth/login',
	'/api-auth-login',
	'/status.php',
	'/api-notes.php',
	'/api-router.php'
];

// Récupérer l'action demandée
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Fonction pour tester une URL
function testUrl($url)
{
	error_log("Testing URL: " . $url);

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_HEADER, true);

	// Désactiver la vérification SSL pour le test
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

	$response = curl_exec($ch);
	$error = curl_error($ch);
	$info = curl_getinfo($ch);

	curl_close($ch);

	return [
		'url' => $url,
		'status' => $info['http_code'],
		'content_type' => isset($info['content_type']) ? $info['content_type'] : '',
		'response_size' => $info['size_download'],
		'error' => $error,
		'response_preview' => $response ? substr($response, 0, 500) . '...' : ''
	];
}

// Traiter une action de test
if ($action === 'test_all') {
	$results = [];

	foreach ($baseUrls as $baseKey => $baseUrl) {
		foreach ($endpointPaths as $path) {
			$url = $baseUrl . $path;
			$results[] = testUrl($url);
		}
	}

	// Renvoyer les résultats en JSON
	header('Content-Type: application/json');
	echo json_encode(['results' => $results]);
	exit;
}

// Interface HTML
?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Backend Explorer</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			max-width: 1200px;
			margin: 0 auto;
			padding: 20px;
		}

		.card {
			border: 1px solid #ddd;
			border-radius: 8px;
			padding: 20px;
			margin-bottom: 20px;
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
		}

		h1,
		h2 {
			color: #333;
		}

		button {
			background-color: #4CAF50;
			color: white;
			border: none;
			padding: 10px 15px;
			border-radius: 4px;
			cursor: pointer;
			font-size: 16px;
			margin-bottom: 20px;
		}

		button:hover {
			background-color: #45a049;
		}

		table {
			width: 100%;
			border-collapse: collapse;
			margin-top: 20px;
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

		.success {
			background-color: #d4edda;
		}

		.error {
			background-color: #f8d7da;
		}

		.warning {
			background-color: #fff3cd;
		}

		.response-preview {
			font-family: monospace;
			font-size: 12px;
			overflow-x: auto;
			white-space: pre-wrap;
			max-height: 100px;
			overflow-y: auto;
			padding: 8px;
			background-color: #f8f9fa;
			border-radius: 4px;
		}

		#results {
			margin-top: 20px;
		}

		.loading {
			text-align: center;
			font-size: 18px;
			margin: 20px 0;
		}
	</style>
</head>

<body>
	<h1>Explorateur du Backend</h1>

	<div class="card">
		<h2>Tester les chemins d'API</h2>
		<p>Cet outil teste différentes combinaisons d'URLs et de chemins pour identifier les points d'entrée valides du backend.</p>
		<button id="testAll">Tester tous les chemins</button>
		<div id="loading" class="loading" style="display: none;">Test en cours, veuillez patienter...</div>
	</div>

	<div id="results" class="card" style="display: none;">
		<h2>Résultats</h2>
		<table id="resultsTable">
			<thead>
				<tr>
					<th>URL</th>
					<th>Statut</th>
					<th>Type de contenu</th>
					<th>Erreur</th>
					<th>Aperçu de la réponse</th>
				</tr>
			</thead>
			<tbody>
				<!-- Les résultats seront insérés ici -->
			</tbody>
		</table>
	</div>

	<div class="card">
		<h2>Notes importantes</h2>
		<ul>
			<li>Les routes qui renvoient un statut 200 sont probablement les bonnes à utiliser.</li>
			<li>Les routes qui renvoient un statut 404 n'existent pas sur le serveur.</li>
			<li>Les routes qui renvoient un statut 405 existent mais la méthode GET n'est pas autorisée.</li>
			<li>Les routes qui renvoient un statut 401 existent mais nécessitent une authentification.</li>
		</ul>
	</div>

	<script>
		// Fonction pour tester tous les chemins
		document.getElementById('testAll').addEventListener('click', async function() {
			const loadingDiv = document.getElementById('loading');
			const resultsDiv = document.getElementById('results');
			const resultsTable = document.getElementById('resultsTable').getElementsByTagName('tbody')[0];

			loadingDiv.style.display = 'block';
			resultsDiv.style.display = 'none';
			resultsTable.innerHTML = '';

			try {
				const response = await fetch('backend-explorer.php?action=test_all');
				const data = await response.json();

				// Afficher les résultats
				data.results.forEach(result => {
					const row = document.createElement('tr');

					// Ajouter une classe basée sur le statut
					if (result.status >= 200 && result.status < 300) {
						row.className = 'success';
					} else if (result.status >= 400 && result.status < 500) {
						row.className = 'error';
					} else {
						row.className = 'warning';
					}

					row.innerHTML = `
                        <td>${result.url}</td>
                        <td>${result.status}</td>
                        <td>${result.content_type}</td>
                        <td>${result.error || 'Aucune'}</td>
                        <td>
                            <div class="response-preview">${result.response_preview}</div>
                        </td>
                    `;

					resultsTable.appendChild(row);
				});

				loadingDiv.style.display = 'none';
				resultsDiv.style.display = 'block';
			} catch (error) {
				console.error('Erreur lors du test:', error);
				loadingDiv.style.display = 'none';
				alert('Une erreur est survenue lors du test. Veuillez consulter la console pour plus de détails.');
			}
		});
	</script>
</body>

</html>