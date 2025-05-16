<?php

/**
 * Test de la solution d'authentification optimisée
 */
session_start();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Test de la solution d'authentification</title>
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

		.button-blue {
			background-color: #2196F3;
		}

		.button-blue:hover {
			background-color: #0b7dda;
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

		.info {
			color: blue;
		}

		.form-group {
			margin-bottom: 15px;
		}

		.form-group label {
			display: block;
			margin-bottom: 5px;
			font-weight: bold;
		}

		.form-group input {
			width: 100%;
			padding: 8px;
			border: 1px solid #ddd;
			border-radius: 4px;
		}

		.user-info {
			background-color: #e9f7ef;
			padding: 10px;
			border-radius: 4px;
			margin-top: 10px;
		}
	</style>
</head>

<body>
	<h1>Test de la solution d'authentification</h1>

	<div class="card">
		<h2>Authentification backend (tentative multiple)</h2>
		<p>Cette option tente de vous connecter au backend en essayant plusieurs chemins d'API possibles.</p>
		<div class="form-group">
			<label for="email1">Email:</label>
			<input type="email" id="email1" value="admin@example.com">
		</div>
		<div class="form-group">
			<label for="password1">Mot de passe:</label>
			<input type="password" id="password1" value="admin123">
		</div>
		<button onclick="testBackendAuth()">Se connecter au backend</button>
	</div>

	<div class="card">
		<h2>Authentification locale (mode développement)</h2>
		<p>Cette option utilise l'authentification locale pour le développement, sans dépendre du backend.</p>
		<div class="form-group">
			<label for="email2">Email:</label>
			<input type="email" id="email2" value="admin@example.com">
		</div>
		<div class="form-group">
			<label for="password2">Mot de passe:</label>
			<input type="password" id="password2" value="admin123">
		</div>
		<p>Utilisateurs disponibles:</p>
		<ul>
			<li><strong>admin@example.com</strong> / admin123 (rôle: admin)</li>
			<li><strong>user@example.com</strong> / user123 (rôle: user)</li>
			<li><strong>test@example.com</strong> / test123 (rôle: guest)</li>
		</ul>
		<button class="button-blue" onclick="testLocalAuth()">Se connecter localement</button>
	</div>

	<div class="card">
		<h2>Accès aux ressources protégées</h2>
		<p>Une fois authentifié, testez l'accès aux ressources protégées avec le token obtenu.</p>
		<button onclick="testProtectedResource()">Accéder aux notes (protégé)</button>
		<button onclick="checkToken()">Vérifier le token actuel</button>
		<button onclick="clearToken()">Effacer le token</button>
	</div>

	<div id="result" class="card">
		<h2>Résultat</h2>
		<pre id="output">Cliquez sur un bouton pour lancer un test...</pre>
	</div>

	<script>
		const output = document.getElementById('output');

		function displayResult(data, type = '') {
			output.className = type;

			if (typeof data === 'object') {
				output.textContent = JSON.stringify(data, null, 2);
			} else {
				output.textContent = data;
			}
		}

		async function testBackendAuth() {
			const email = document.getElementById('email1').value;
			const password = document.getElementById('password1').value;

			displayResult('Tentative d\'authentification sur le backend...', 'info');
			try {
				const response = await fetch('auth-proxy.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						email: email,
						password: password
					})
				});

				const data = await response.json();

				// Stocker le token si disponible
				if (data.success && data.data && data.data.token) {
					localStorage.setItem('auth_token', data.data.token);
					displayResult({
						message: 'Authentification réussie !',
						tokenReceived: true,
						tokenStored: true,
						data: data
					}, 'success');
				} else {
					displayResult({
						message: 'Échec de l\'authentification',
						data: data
					}, 'error');
				}
			} catch (error) {
				displayResult({
					message: 'Erreur lors de la requête',
					error: error.message
				}, 'error');
			}
		}

		async function testLocalAuth() {
			const email = document.getElementById('email2').value;
			const password = document.getElementById('password2').value;

			displayResult('Utilisation de l\'authentification locale...', 'info');
			try {
				const response = await fetch('auth-proxy.php?local=true', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						email: email,
						password: password
					})
				});

				const data = await response.json();

				// Stocker le token si disponible
				if (data.success && data.data && data.data.token) {
					localStorage.setItem('auth_token', data.data.token);
					displayResult({
						message: 'Authentification locale réussie !',
						tokenReceived: true,
						tokenStored: true,
						user: data.data.user,
						tokenExpires: new Date(data.data.expiresAt * 1000).toLocaleString()
					}, 'success');
				} else {
					displayResult({
						message: 'Échec de l\'authentification locale',
						data: data
					}, 'error');
				}
			} catch (error) {
				displayResult({
					message: 'Erreur lors de la requête',
					error: error.message
				}, 'error');
			}
		}

		async function testProtectedResource() {
			const token = localStorage.getItem('auth_token');

			if (!token) {
				displayResult('Aucun token d\'authentification trouvé. Veuillez vous connecter d\'abord.', 'error');
				return;
			}

			displayResult('Accès à une ressource protégée avec le token...', 'info');
			try {
				const response = await fetch('optimal-proxy.php?endpoint=api-notes.php', {
					headers: {
						'Authorization': 'Bearer ' + token
					}
				});

				const contentType = response.headers.get('content-type') || '';

				if (contentType.includes('application/json')) {
					const data = await response.json();
					displayResult({
						message: 'Réponse de la ressource protégée',
						status: response.status,
						data: data
					});
				} else {
					const text = await response.text();
					displayResult({
						message: 'Réponse non-JSON de la ressource protégée',
						status: response.status,
						contentType: contentType,
						data: text.substring(0, 300) + (text.length > 300 ? '...' : '')
					}, 'error');
				}
			} catch (error) {
				displayResult({
					message: 'Erreur lors de l\'accès à la ressource protégée',
					error: error.message
				}, 'error');
			}
		}

		function checkToken() {
			const token = localStorage.getItem('auth_token');

			if (!token) {
				displayResult('Aucun token d\'authentification trouvé.', 'error');
				return;
			}

			// Pour les tokens locaux, on peut décoder le payload
			if (token.startsWith('LOCAL_AUTH.')) {
				try {
					const parts = token.split('.');
					if (parts.length >= 2) {
						const payload = JSON.parse(atob(parts[1]));
						displayResult({
							message: 'Token local trouvé',
							token: token.substring(0, 20) + '...',
							payload: payload,
							expiresAt: new Date(payload.exp * 1000).toLocaleString(),
							isExpired: payload.exp < (Date.now() / 1000)
						}, payload.exp < (Date.now() / 1000) ? 'error' : 'success');
					} else {
						displayResult({
							message: 'Token local trouvé mais format invalide',
							token: token
						}, 'error');
					}
				} catch (e) {
					displayResult({
						message: 'Token local trouvé mais impossible à décoder',
						token: token,
						error: e.message
					}, 'error');
				}
			} else {
				// Pour les tokens du backend, on affiche juste le token
				displayResult({
					message: 'Token backend trouvé',
					token: token.substring(0, 20) + '...'
				}, 'success');
			}
		}

		function clearToken() {
			localStorage.removeItem('auth_token');
			displayResult('Token d\'authentification effacé.', 'info');
		}

		// Vérifier si un token existe au chargement
		document.addEventListener('DOMContentLoaded', function() {
			const token = localStorage.getItem('auth_token');
			if (token) {
				displayResult('Un token d\'authentification existe déjà. Utilisez "Vérifier le token actuel" pour voir les détails.', 'info');
			}
		});
	</script>
</body>

</html>