<?php
// Test des endpoints directs (fichiers PHP) du backend
session_start();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Test des endpoints PHP directs</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			max-width: 800px;
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

		pre {
			background-color: #f5f5f5;
			padding: 10px;
			border-radius: 4px;
			overflow-x: auto;
		}

		button {
			background-color: #4CAF50;
			color: white;
			border: none;
			padding: 10px 15px;
			border-radius: 4px;
			cursor: pointer;
			margin-right: 10px;
			margin-bottom: 10px;
		}

		button:hover {
			background-color: #45a049;
		}

		.result {
			margin-top: 10px;
			padding: 10px;
			border: 1px solid #ddd;
			border-radius: 4px;
		}

		.success {
			background-color: #d4edda;
		}

		.error {
			background-color: #f8d7da;
		}
	</style>
</head>

<body>
	<h1>Test des endpoints PHP directs</h1>

	<div class="card">
		<h2>Description</h2>
		<p>Cet outil teste les endpoints PHP directs du backend, plutôt que les endpoints REST API.</p>
		<p>Puisque status.php fonctionne, essayons des endpoints similaires qui utilisent le même format.</p>
	</div>

	<div class="card">
		<h2>Authentification</h2>
		<p>Un token JWT valide est nécessaire pour les endpoints protégés.</p>
		<div id="tokenInfo"></div>
	</div>

	<div class="card">
		<h2>Endpoints PHP à tester</h2>
		<div>
			<button onclick="testEndpoint('status.php')">status.php</button>
			<button onclick="testEndpoint('api-notes.php')">api-notes.php</button>
			<button onclick="testEndpoint('api-auth.php')">api-auth.php</button>
			<button onclick="testEndpoint('api-classes.php')">api-classes.php</button>
			<button onclick="testEndpoint('api-profs.php')">api-profs.php</button>
			<button onclick="testEndpoint('api-users.php')">api-users.php</button>
			<button onclick="testEndpoint('api-matieres.php')">api-matieres.php</button>
			<button onclick="testEndpoint('api-examens.php')">api-examens.php</button>
		</div>
		<div id="endpointResults"></div>
	</div>

	<div class="card">
		<h2>Recherche d'endpoints</h2>
		<p>Essayons de détecter automatiquement des endpoints PHP valides:</p>
		<button onclick="detectEndpoints()">Détecter automatiquement</button>
		<div id="detectionResults"></div>
	</div>

	<script>
		// Vérifier si un token JWT existe
		document.addEventListener('DOMContentLoaded', function() {
			const token = localStorage.getItem('jwt_token');
			if (token) {
				document.getElementById('tokenInfo').innerHTML =
					`<p>Token JWT trouvé dans localStorage.</p>
                     <p>Aperçu: ${token.substring(0, 20)}...</p>`;
			} else {
				document.getElementById('tokenInfo').innerHTML =
					`<p style="color:red">Aucun token JWT trouvé!</p>
                     <p>Retournez à test-improved-jwt.php pour générer un token.</p>`;
			}
		});

		// Tester un endpoint
		async function testEndpoint(endpoint) {
			const token = localStorage.getItem('jwt_token');
			const resultDiv = document.getElementById('endpointResults');

			resultDiv.innerHTML = `<div>Test de ${endpoint} en cours...</div>`;

			try {
				const response = await fetch(`enhanced-proxy.php?endpoint=${endpoint}`, {
					headers: token ? {
						'Authorization': 'Bearer ' + token
					} : {}
				});

				const isSuccess = response.status >= 200 && response.status < 300;
				const resultClass = isSuccess ? 'success' : 'error';

				let responseText = '';
				try {
					const data = await response.json();
					responseText = JSON.stringify(data, null, 2);
				} catch (e) {
					responseText = await response.text();
				}

				resultDiv.innerHTML = `
                    <div class="result ${resultClass}">
                        <h3>${endpoint}: ${response.status} ${response.statusText}</h3>
                        <pre>${responseText}</pre>
                    </div>
                `;
			} catch (error) {
				resultDiv.innerHTML = `
                    <div class="result error">
                        <h3>${endpoint}: Erreur</h3>
                        <pre>${error.message}</pre>
                    </div>
                `;
			}
		}

		// Essayer de détecter automatiquement des endpoints valides
		async function detectEndpoints() {
			const token = localStorage.getItem('jwt_token');
			const resultsDiv = document.getElementById('detectionResults');

			// Liste des endpoints potentiels à tester
			const potentialEndpoints = [
				'api-test.php',
				'test-api.php',
				'api.php',
				'api-router.php',
				'index.php',
				'router.php',
				'api-data.php',
				'data.php',
				'api-service.php',
				'notes.php',
				'users.php',
				'classes.php',
				'profs.php'
			];

			resultsDiv.innerHTML = `<div>Détection en cours, veuillez patienter...</div>`;

			const results = [];

			for (const endpoint of potentialEndpoints) {
				try {
					const response = await fetch(`enhanced-proxy.php?endpoint=${endpoint}`, {
						headers: token ? {
							'Authorization': 'Bearer ' + token
						} : {}
					});

					results.push({
						endpoint,
						status: response.status,
						success: response.status >= 200 && response.status < 300,
						exists: response.status !== 404
					});
				} catch (error) {
					results.push({
						endpoint,
						error: error.message,
						success: false,
						exists: false
					});
				}
			}

			// Afficher les résultats
			let html = '<h3>Résultats de la détection:</h3>';
			html += '<table style="width:100%; border-collapse:collapse; margin-top:10px;">';
			html += '<tr style="background-color:#f2f2f2;"><th>Endpoint</th><th>Statut</th><th>Action</th></tr>';

			results.forEach(result => {
				const rowClass = result.success ? 'success' : (result.exists ? 'warning' : '');
				html += `<tr style="${rowClass ? 'background-color:' + (result.success ? '#d4edda' : '#fff3cd') : ''}">`;
				html += `<td>${result.endpoint}</td>`;
				html += `<td>${result.status || 'Erreur'}</td>`;
				html += `<td><button onclick="testEndpoint('${result.endpoint}')">Tester</button></td>`;
				html += '</tr>';
			});

			html += '</table>';
			resultsDiv.innerHTML = html;
		}
	</script>
</body>

</html>