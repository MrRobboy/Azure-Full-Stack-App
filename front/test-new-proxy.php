<?php

/**
 * Test du nouveau proxy
 */
session_start();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Test du nouveau proxy</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			max-width: 800px;
			margin: 0 auto;
			padding: 20px;
		}

		.card {
			border: 1px solid #ddd;
			border-radius: 4px;
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
	</style>
</head>

<body>
	<h1>Test du nouveau proxy</h1>

	<div class="card">
		<h2>Configuration</h2>
		<p>URL du backend: <code>https://app-backend-esgi-app.azurewebsites.net</code></p>
		<p>Proxy utilisé: <code>new-proxy.php</code></p>
	</div>

	<div class="card">
		<h2>Tests disponibles</h2>
		<button onclick="testStatus()">Tester le statut</button>
		<button onclick="testMatieres()">Tester les matières</button>
		<button onclick="testAuth()">Tester l'authentification</button>
		<button onclick="testCORS()">Tester les en-têtes CORS</button>
		<button onclick="testDirectAuth()">Tester l'auth directe</button>
		<button onclick="testLocalAuth()">Tester l'auth locale</button>
	</div>

	<div id="result" class="card">
		<h2>Résultats</h2>
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

		async function fetchWithProxy(endpoint, options = {}) {
			try {
				const url = `new-proxy.php?endpoint=${encodeURIComponent(endpoint)}`;

				// Define default options
				const defaultOptions = {
					credentials: 'include', // Important pour les cookies d'authentification
					headers: {
						'Content-Type': 'application/json'
					}
				};

				const response = await fetch(url, {
					...defaultOptions,
					...options
				});
				const contentType = response.headers.get('content-type') || '';

				if (contentType.includes('application/json')) {
					const data = await response.json();
					return {
						status: response.status,
						data
					};
				} else {
					const text = await response.text();
					return {
						status: response.status,
						data: text
					};
				}
			} catch (error) {
				console.error('Erreur lors de la requête:', error);
				return {
					status: 0,
					error: error.message
				};
			}
		}

		async function testStatus() {
			displayResult('Test du statut en cours...');
			const result = await fetchWithProxy('status.php');
			displayResult(result);
		}

		async function testMatieres() {
			displayResult('Test des matières en cours...');
			const result = await fetchWithProxy('api-notes.php?action=matieres');
			displayResult(result);
		}

		async function testAuth() {
			displayResult('Test de l\'authentification en cours...');
			const credentials = {
				email: 'admin@example.com',
				password: 'admin123'
			};

			const result = await fetchWithProxy('/api-auth-login.php', {
				method: 'POST',
				body: JSON.stringify(credentials)
			});

			if (result.status === 404) {
				console.error('Erreur 404: URL de l\'API d\'authentification non trouvée');
				result.debug = {
					urlUsed: '/api-auth-login.php',
					message: 'Vérifier que ce fichier existe sur le serveur backend'
				};
			}

			displayResult(result);
		}

		async function testCORS() {
			displayResult('Test des en-têtes CORS en cours...');

			try {
				// Effectuer une requête OPTIONS pour voir les en-têtes CORS
				const response = await fetch('new-proxy.php?endpoint=status.php', {
					method: 'OPTIONS',
					credentials: 'include'
				});

				// Récupérer tous les en-têtes
				const headers = {};
				response.headers.forEach((value, name) => {
					headers[name] = value;
				});

				displayResult({
					status: response.status,
					statusText: response.statusText,
					headers: headers
				});
			} catch (error) {
				displayResult('Erreur lors du test CORS: ' + error.message, true);
			}
		}

		async function testDirectAuth() {
			displayResult('Test de l\'authentification directe en cours...');
			const credentials = {
				email: 'admin@example.com',
				password: 'admin123'
			};

			try {
				const response = await fetch('direct-auth.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					credentials: 'include',
					body: JSON.stringify(credentials)
				});

				const contentType = response.headers.get('content-type') || '';

				if (contentType.includes('application/json')) {
					const data = await response.json();
					displayResult({
						status: response.status,
						data: data
					});
				} else {
					const text = await response.text();
					displayResult({
						status: response.status,
						error: 'Réponse non-JSON',
						data: text.substring(0, 500) + '...'
					}, true);
				}
			} catch (error) {
				displayResult({
					status: 0,
					error: error.message
				}, true);
			}
		}

		async function testLocalAuth() {
			displayResult('Test de l\'authentification locale en cours...');
			const credentials = {
				email: 'admin@example.com',
				password: 'admin123'
			};

			try {
				const response = await fetch('auth-api-fix.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					credentials: 'include',
					body: JSON.stringify(credentials)
				});

				const contentType = response.headers.get('content-type') || '';

				if (contentType.includes('application/json')) {
					const data = await response.json();
					displayResult({
						status: response.status,
						data: data
					});

					// Si l'authentification réussit, stocker le token
					if (data.success && data.data && data.data.token) {
						localStorage.setItem('auth_token', data.data.token);
						console.log('Token stocké:', data.data.token);

						// Test pour voir si on peut maintenant accéder aux matières
						testAuthenticatedMatieres();
					}
				} else {
					const text = await response.text();
					displayResult({
						status: response.status,
						error: 'Réponse non-JSON',
						data: text.substring(0, 500) + '...'
					}, true);
				}
			} catch (error) {
				displayResult({
					status: 0,
					error: error.message
				}, true);
			}
		}

		// Ajouter une fonction pour tester l'accès aux matières avec authentification
		async function testAuthenticatedMatieres() {
			const token = localStorage.getItem('auth_token');
			if (!token) {
				console.error('Aucun token trouvé, veuillez vous authentifier d\'abord');
				return;
			}

			setTimeout(async () => {
				displayResult('Test des matières après authentification...');

				try {
					// Utiliser le proxy
					const result = await fetchWithProxy('api-notes.php?action=matieres', {
						headers: {
							'Authorization': 'Bearer ' + token
						}
					});

					displayResult({
						type: 'Matières authentifiées',
						...result
					});
				} catch (error) {
					displayResult({
						status: 0,
						error: error.message
					}, true);
				}
			}, 1000); // Petit délai pour s'assurer que la session est bien établie
		}
	</script>
</body>

</html>