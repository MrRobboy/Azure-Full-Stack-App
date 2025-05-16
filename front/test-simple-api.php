<?php
// Test des endpoints directs avec le proxy simplifié
session_start();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Test API Simplifié</title>
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
	<h1>Test API avec Proxy Simplifié (sans JWT)</h1>

	<div class="card">
		<h2>Description</h2>
		<p>Cette page teste les endpoints API en utilisant un proxy simplifié sans authentification JWT.</p>
	</div>

	<div class="card">
		<h2>Endpoints fonctionnels découverts</h2>
		<div>
			<button onclick="testEndpoint('status.php')">status.php</button>
			<button onclick="testEndpoint('api-test.php')">api-test.php</button>
			<button onclick="testEndpoint('test-api.php')">test-api.php</button>
			<button onclick="testEndpoint('notes.php')">notes.php</button>
			<button onclick="testEndpoint('api-notes.php')">api-notes.php</button>
			<button onclick="testPostEndpoint('api-auth.php')">api-auth.php (POST)</button>
		</div>
		<div id="endpointResults"></div>
	</div>

	<div class="card">
		<h2>Test d'authentification manuel</h2>
		<div>
			<h3>Connexion</h3>
			<form id="loginForm">
				<div>
					<label for="email">Email:</label>
					<input type="email" id="email" value="admin@example.com" style="width: 250px; margin: 5px;">
				</div>
				<div>
					<label for="password">Mot de passe:</label>
					<input type="password" id="password" value="admin123" style="width: 250px; margin: 5px;">
				</div>
				<button type="submit">Se connecter</button>
			</form>
			<div id="loginResult" class="result"></div>
		</div>
	</div>

	<div class="card">
		<h2>Recherche d'endpoints</h2>
		<p>Essayons de détecter automatiquement des endpoints PHP valides:</p>
		<button onclick="detectEndpoints()">Détecter automatiquement</button>
		<div id="detectionResults"></div>
	</div>

	<script>
		// Tester un endpoint en GET
		async function testEndpoint(endpoint) {
			const resultDiv = document.getElementById('endpointResults');
			resultDiv.innerHTML = `<div>Test de ${endpoint} en cours...</div>`;

			try {
				const response = await fetch(`simple-proxy.php?endpoint=${endpoint}`);
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

		// Tester un endpoint en POST
		async function testPostEndpoint(endpoint) {
			const resultDiv = document.getElementById('endpointResults');
			resultDiv.innerHTML = `<div>Test de ${endpoint} (POST) en cours...</div>`;

			try {
				const response = await fetch(`simple-proxy.php?endpoint=${endpoint}`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						email: 'admin@example.com',
						password: 'admin123'
					})
				});

				const isSuccess = response.status >= 200 && response.status < 300;
				const resultClass = isSuccess ? 'success' : 'error';

				let responseText = '';
				try {
					const data = await response.json();
					responseText = JSON.stringify(data, null, 2);

					// Si on a un token, on le sauvegarde
					if (data.token) {
						localStorage.setItem('jwt_token', data.token);
					}
				} catch (e) {
					responseText = await response.text();
				}

				resultDiv.innerHTML = `
                    <div class="result ${resultClass}">
                        <h3>${endpoint} (POST): ${response.status} ${response.statusText}</h3>
                        <pre>${responseText}</pre>
                    </div>
                `;
			} catch (error) {
				resultDiv.innerHTML = `
                    <div class="result error">
                        <h3>${endpoint} (POST): Erreur</h3>
                        <pre>${error.message}</pre>
                    </div>
                `;
			}
		}

		// Login manuel
		document.getElementById('loginForm').addEventListener('submit', async function(e) {
			e.preventDefault();
			const email = document.getElementById('email').value;
			const password = document.getElementById('password').value;
			const resultDiv = document.getElementById('loginResult');

			resultDiv.innerHTML = '<div>Connexion en cours...</div>';

			try {
				const response = await fetch('simple-proxy.php?endpoint=api-auth.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						email,
						password
					})
				});

				const isSuccess = response.status >= 200 && response.status < 300;
				const resultClass = isSuccess ? 'success' : 'error';

				const data = await response.json();

				if (data.token) {
					localStorage.setItem('jwt_token', data.token);
					resultDiv.innerHTML = `
                        <div class="result success">
                            <h3>Connexion réussie!</h3>
                            <p>Token JWT sauvegardé dans le localStorage</p>
                            <p>Aperçu: ${data.token.substring(0, 20)}...</p>
                        </div>
                    `;
				} else {
					resultDiv.innerHTML = `
                        <div class="result error">
                            <h3>Échec de connexion</h3>
                            <pre>${JSON.stringify(data, null, 2)}</pre>
                        </div>
                    `;
				}
			} catch (error) {
				resultDiv.innerHTML = `
                    <div class="result error">
                        <h3>Erreur de connexion</h3>
                        <pre>${error.message}</pre>
                    </div>
                `;
			}
		});

		// Essayer de détecter automatiquement des endpoints valides
		async function detectEndpoints() {
			const resultsDiv = document.getElementById('detectionResults');
			resultsDiv.innerHTML = `<div>Détection en cours, veuillez patienter...</div>`;

			// Liste des endpoints potentiels à tester
			const potentialEndpoints = [
				'status.php',
				'api-test.php',
				'test-api.php',
				'notes.php',
				'users.php',
				'classes.php',
				'auth.php',
				'login.php',
				'students.php',
				'examens.php',
				'matieres.php',
				'profs.php',
				'admin.php',
				'me.php'
			];

			const results = [];

			for (const endpoint of potentialEndpoints) {
				try {
					const response = await fetch(`simple-proxy.php?endpoint=${endpoint}`);
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