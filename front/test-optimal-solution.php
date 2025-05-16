<?php

/**
 * Test des solutions optimisées pour Azure
 */
session_start();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Test des solutions optimisées</title>
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

		#result {
			margin-top: 20px;
		}

		.error {
			color: red;
		}

		.success {
			color: green;
		}

		.code-block {
			background-color: #f5f5f5;
			padding: 15px;
			border-radius: 4px;
			font-family: monospace;
			white-space: pre-wrap;
		}
	</style>
</head>

<body>
	<h1>Test des solutions optimisées pour Azure</h1>

	<div class="card">
		<h2>Configuration</h2>
		<p>URL du backend: <code>https://app-backend-esgi-app.azurewebsites.net</code></p>
		<p>Endpoints confirmés via Backend Explorer:</p>
		<ul>
			<li><code>status.php</code> (200 OK) - Accessible en GET</li>
			<li><code>api-auth-login.php</code> (405 Method Not Allowed) - Requiert POST</li>
			<li><code>api-notes.php</code> (401 Unauthorized) - Requiert authentification</li>
		</ul>
	</div>

	<div class="card">
		<h2>Proxy général optimisé</h2>
		<p>Teste l'accès aux endpoints via le proxy général optimisé <code>optimal-proxy.php</code></p>
		<button onclick="testStatus()">Tester le statut</button>
		<button onclick="testNotesUnauthenticated()">Tester les notes (sans auth)</button>
	</div>

	<div class="card">
		<h2>Proxy d'authentification spécialisé</h2>
		<p>Teste l'authentification via le proxy spécialisé <code>auth-proxy.php</code></p>
		<div class="code-block">
			fetch('auth-proxy.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({
			email: 'admin@example.com',
			password: 'admin123'
			})
			})
		</div>
		<button onclick="testAuthentication()">Tester l'authentification</button>
	</div>

	<div class="card">
		<h2>Solution complète</h2>
		<p>Teste le flux complet: authentification puis accès aux ressources protégées</p>
		<button onclick="testFullFlow()">Tester le flux complet</button>
	</div>

	<div id="result" class="card">
		<h2>Résultat</h2>
		<pre id="output">Cliquez sur un bouton pour lancer un test...</pre>
	</div>

	<script>
		const output = document.getElementById('output');

		function displayResult(data, error = false) {
			if (error) {
				output.className = 'error';
			} else {
				output.className = '';
			}

			if (typeof data === 'object') {
				output.textContent = JSON.stringify(data, null, 2);
			} else {
				output.textContent = data;
			}
		}

		async function testStatus() {
			displayResult('Test du statut en cours...');
			try {
				const response = await fetch('optimal-proxy.php?endpoint=status.php');
				const contentType = response.headers.get('content-type') || '';

				if (contentType.includes('application/json')) {
					const data = await response.json();
					displayResult({
						status: response.status,
						data
					});
				} else {
					const text = await response.text();
					displayResult({
						status: response.status,
						contentType,
						data: text.substring(0, 500)
					});
				}
			} catch (error) {
				displayResult({
					error: error.message
				}, true);
			}
		}

		async function testNotesUnauthenticated() {
			displayResult('Test des notes sans authentification en cours...');
			try {
				const response = await fetch('optimal-proxy.php?endpoint=api-notes.php');
				const contentType = response.headers.get('content-type') || '';

				if (contentType.includes('application/json')) {
					const data = await response.json();
					displayResult({
						status: response.status,
						data
					});
				} else {
					const text = await response.text();
					displayResult({
						status: response.status,
						contentType,
						data: text.substring(0, 500)
					});
				}
			} catch (error) {
				displayResult({
					error: error.message
				}, true);
			}
		}

		async function testAuthentication() {
			displayResult('Test de l\'authentification en cours...');
			try {
				const credentials = {
					email: 'admin@example.com',
					password: 'admin123'
				};

				const response = await fetch('auth-proxy.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify(credentials)
				});

				const contentType = response.headers.get('content-type') || '';

				if (contentType.includes('application/json')) {
					const data = await response.json();
					// Stocker le token si disponible
					if (data.success && data.data && data.data.token) {
						localStorage.setItem('auth_token', data.data.token);
						console.log('Token stocké:', data.data.token);
					}
					displayResult({
						status: response.status,
						data
					});
				} else {
					const text = await response.text();
					displayResult({
						status: response.status,
						contentType,
						data: text.substring(0, 500)
					}, true);
				}
			} catch (error) {
				displayResult({
					error: error.message
				}, true);
			}
		}

		async function testFullFlow() {
			displayResult('Test du flux complet en cours...');
			try {
				// 1. Authentification
				displayResult('Étape 1: Authentification...');
				const credentials = {
					email: 'admin@example.com',
					password: 'admin123'
				};

				let response = await fetch('auth-proxy.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify(credentials)
				});

				let data = await response.json();

				// Vérifier si authentification réussie
				if (!data.success) {
					displayResult({
						message: 'Échec de l\'authentification',
						data
					}, true);
					return;
				}

				// Stocker le token
				let token = '';
				if (data.data && data.data.token) {
					token = data.data.token;
					localStorage.setItem('auth_token', token);
					displayResult('Token reçu et stocké. Récupération des données protégées...');
				} else {
					displayResult({
						message: 'Authentification réussie mais aucun token reçu',
						data
					}, true);
					return;
				}

				// 2. Accéder aux notes avec le token
				response = await fetch('optimal-proxy.php?endpoint=api-notes.php', {
					headers: {
						'Authorization': 'Bearer ' + token
					}
				});

				data = await response.json();

				displayResult({
					message: 'Flux complet terminé',
					authStatus: 'Succès',
					token: token.substring(0, 20) + '...',
					protectedData: data
				});

			} catch (error) {
				displayResult({
					message: 'Erreur durant le flux complet',
					error: error.message
				}, true);
			}
		}
	</script>
</body>

</html>