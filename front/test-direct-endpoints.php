<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Test Direct API Endpoints</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			line-height: 1.6;
			max-width: 800px;
			margin: 0 auto;
			padding: 20px;
		}

		h1 {
			color: #333;
		}

		h2 {
			color: #555;
			margin-top: 30px;
		}

		.endpoint {
			background: #f5f5f5;
			padding: 15px;
			border-radius: 4px;
			margin-bottom: 20px;
		}

		.success {
			color: green;
			font-weight: bold;
		}

		.error {
			color: red;
			font-weight: bold;
		}

		pre {
			background: #eee;
			padding: 10px;
			overflow: auto;
		}

		button {
			padding: 8px 15px;
			margin-top: 10px;
			cursor: pointer;
		}

		.test-group {
			border: 1px solid #ddd;
			padding: 15px;
			margin-bottom: 20px;
		}

		.hidden {
			display: none;
		}
	</style>
</head>

<body>
	<h1>Test Direct API Endpoints</h1>
	<p>Cette page teste les points d'entrée API directs qui ont été créés pour contourner les problèmes de routage.</p>

	<div id="config">
		<h2>Configuration</h2>
		<div class="test-group">
			<p>Backend URL: <input type="text" id="backendUrl" value="https://app-backend-esgi-app.azurewebsites.net" style="width: 350px;"></p>
			<p>Email: <input type="text" id="testEmail" value="user@example.com"></p>
			<p>Password: <input type="password" id="testPassword" value="password"></p>
			<button id="saveConfig">Enregistrer</button>
		</div>
	</div>

	<h2>Endpoints Disponibles</h2>

	<div class="test-group">
		<h3>1. Vérification de déploiement</h3>
		<div class="endpoint">
			<p><strong>URL:</strong> <span id="deploymentUrl"></span></p>
			<p><strong>Méthode:</strong> GET</p>
			<button onclick="testDeployment()">Tester</button>
			<div id="deploymentResult" class="hidden">
				<h4>Résultat:</h4>
				<pre id="deploymentResponse"></pre>
			</div>
		</div>
	</div>

	<div class="test-group">
		<h3>2. Nouvel endpoint d'authentification</h3>
		<div class="endpoint">
			<p><strong>URL:</strong> <span id="authLoginUrl"></span></p>
			<p><strong>Méthode:</strong> POST</p>
			<button onclick="testAuthLogin()">Tester Login (POST)</button>
			<button onclick="testAuthLoginGet()">Tester Login (GET)</button>
			<div id="authLoginResult" class="hidden">
				<h4>Résultat:</h4>
				<pre id="authLoginResponse"></pre>
			</div>
		</div>
	</div>

	<div class="test-group">
		<h3>3. Nouvel endpoint pour les notes</h3>
		<div class="endpoint">
			<p><strong>URL:</strong> <span id="notesUrl"></span></p>
			<p><strong>Méthode:</strong> GET</p>
			<button onclick="testNotes()">Tester Notes (GET)</button>
			<div id="notesResult" class="hidden">
				<h4>Résultat:</h4>
				<pre id="notesResponse"></pre>
			</div>
		</div>
	</div>

	<div class="test-group">
		<h3>4. API Router</h3>
		<div class="endpoint">
			<p><strong>URL:</strong> <span id="apiRouterUrl"></span></p>
			<p><strong>Méthode:</strong> GET</p>
			<button onclick="testApiRouter()">Tester API Router</button>
			<div id="apiRouterResult" class="hidden">
				<h4>Résultat:</h4>
				<pre id="apiRouterResponse"></pre>
			</div>
		</div>
	</div>

	<div class="test-group">
		<h3>5. Test CORS</h3>
		<div class="endpoint">
			<p><strong>URL:</strong> <span id="corsTestUrl"></span></p>
			<p><strong>Méthode:</strong> OPTIONS</p>
			<button onclick="testCorsHeaders()">Tester CORS Headers</button>
			<div id="corsHeadersResult" class="hidden">
				<h4>Résultat:</h4>
				<pre id="corsHeadersResponse"></pre>
			</div>
		</div>
	</div>

	<script>
		// Configuration
		let backendUrl = localStorage.getItem('backendUrl') || 'https://app-backend-esgi-app.azurewebsites.net';
		let testEmail = localStorage.getItem('testEmail') || 'user@example.com';
		let testPassword = localStorage.getItem('testPassword') || 'password';

		// Initialiser les champs
		document.getElementById('backendUrl').value = backendUrl;
		document.getElementById('testEmail').value = testEmail;
		document.getElementById('testPassword').value = testPassword;

		// Mettre à jour les URLs affichées
		document.getElementById('deploymentUrl').textContent = `${backendUrl}/deployment-complete.php`;
		document.getElementById('authLoginUrl').textContent = `${backendUrl}/api-auth-login.php`;
		document.getElementById('notesUrl').textContent = `${backendUrl}/api-notes.php`;
		document.getElementById('apiRouterUrl').textContent = `${backendUrl}/api-router.php`;
		document.getElementById('corsTestUrl').textContent = `${backendUrl}/api-cors.php`;

		// Enregistrer la configuration
		document.getElementById('saveConfig').addEventListener('click', function() {
			backendUrl = document.getElementById('backendUrl').value;
			testEmail = document.getElementById('testEmail').value;
			testPassword = document.getElementById('testPassword').value;

			localStorage.setItem('backendUrl', backendUrl);
			localStorage.setItem('testEmail', testEmail);
			localStorage.setItem('testPassword', testPassword);

			// Mettre à jour les URLs affichées
			document.getElementById('deploymentUrl').textContent = `${backendUrl}/deployment-complete.php`;
			document.getElementById('authLoginUrl').textContent = `${backendUrl}/api-auth-login.php`;
			document.getElementById('notesUrl').textContent = `${backendUrl}/api-notes.php`;
			document.getElementById('apiRouterUrl').textContent = `${backendUrl}/api-router.php`;
			document.getElementById('corsTestUrl').textContent = `${backendUrl}/api-cors.php`;

			alert('Configuration enregistrée');
		});

		// Test de déploiement
		async function testDeployment() {
			const resultDiv = document.getElementById('deploymentResult');
			const responseEl = document.getElementById('deploymentResponse');
			resultDiv.classList.remove('hidden');

			try {
				const response = await fetch(`${backendUrl}/deployment-complete.php`);
				const data = await response.json();

				responseEl.innerHTML = JSON.stringify(data, null, 2);
				responseEl.className = response.ok ? 'success' : 'error';
			} catch (error) {
				responseEl.innerHTML = `Erreur: ${error.message}`;
				responseEl.className = 'error';
			}
		}

		// Test d'authentification (POST)
		async function testAuthLogin() {
			const resultDiv = document.getElementById('authLoginResult');
			const responseEl = document.getElementById('authLoginResponse');
			resultDiv.classList.remove('hidden');

			try {
				const response = await fetch(`${backendUrl}/api-auth-login.php`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						email: testEmail,
						password: testPassword
					})
				});

				const data = await response.json();
				responseEl.innerHTML = JSON.stringify(data, null, 2);
				responseEl.className = response.ok ? 'success' : 'error';
			} catch (error) {
				responseEl.innerHTML = `Erreur: ${error.message}`;
				responseEl.className = 'error';
			}
		}

		// Test d'authentification (GET)
		async function testAuthLoginGet() {
			const resultDiv = document.getElementById('authLoginResult');
			const responseEl = document.getElementById('authLoginResponse');
			resultDiv.classList.remove('hidden');

			try {
				const url = `${backendUrl}/api-auth-login.php?email=${encodeURIComponent(testEmail)}&password=${encodeURIComponent(testPassword)}`;
				const response = await fetch(url);

				const data = await response.json();
				responseEl.innerHTML = JSON.stringify(data, null, 2);
				responseEl.className = response.ok ? 'success' : 'error';
			} catch (error) {
				responseEl.innerHTML = `Erreur: ${error.message}`;
				responseEl.className = 'error';
			}
		}

		// Test de notes
		async function testNotes() {
			const resultDiv = document.getElementById('notesResult');
			const responseEl = document.getElementById('notesResponse');
			resultDiv.classList.remove('hidden');

			try {
				const response = await fetch(`${backendUrl}/api-notes.php`);
				const data = await response.json();

				responseEl.innerHTML = JSON.stringify(data, null, 2);
				responseEl.className = response.ok ? 'success' : 'error';
			} catch (error) {
				responseEl.innerHTML = `Erreur: ${error.message}`;
				responseEl.className = 'error';
			}
		}

		// Test de API Router
		async function testApiRouter() {
			const resultDiv = document.getElementById('apiRouterResult');
			const responseEl = document.getElementById('apiRouterResponse');
			resultDiv.classList.remove('hidden');

			try {
				const response = await fetch(`${backendUrl}/api-router.php`);
				const data = await response.text();

				responseEl.innerHTML = data;
				responseEl.className = response.ok ? 'success' : 'error';
			} catch (error) {
				responseEl.innerHTML = `Erreur: ${error.message}`;
				responseEl.className = 'error';
			}
		}

		// Test des en-têtes CORS
		async function testCorsHeaders() {
			const resultDiv = document.getElementById('corsHeadersResult');
			const responseEl = document.getElementById('corsHeadersResponse');
			resultDiv.classList.remove('hidden');

			try {
				// Simuler une requête préflight OPTIONS pour tester CORS
				const response = await fetch(`${backendUrl}/api-cors.php`, {
					method: 'OPTIONS',
					headers: {
						'Access-Control-Request-Method': 'POST',
						'Access-Control-Request-Headers': 'Content-Type, Authorization'
					}
				});

				// Pour les requêtes OPTIONS réussies le contenu est normalement vide
				// On extrait les en-têtes pour voir les valeurs CORS
				const headers = {};
				for (const [key, value] of response.headers.entries()) {
					headers[key] = value;
				}

				const result = {
					status: response.status,
					statusText: response.statusText,
					headers: headers
				};

				// Vérifier si les en-têtes CORS importants sont présents
				const corsComplete = headers['access-control-allow-origin'] &&
					headers['access-control-allow-methods'] &&
					headers['access-control-allow-headers'];

				responseEl.innerHTML = JSON.stringify(result, null, 2);
				responseEl.className = response.ok && corsComplete ? 'success' : 'error';
			} catch (error) {
				responseEl.innerHTML = `Erreur: ${error.message}`;
				responseEl.className = 'error';
			}
		}
	</script>
</body>

</html>