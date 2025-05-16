<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Test du JWT Amélioré</title>
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
	</style>
</head>

<body>
	<h1>Test du JWT Amélioré</h1>

	<div class="card">
		<h2>Description</h2>
		<p>
			Cette page permet de tester le JWT Amélioré qui génère des tokens JWT parfaitement compatibles avec le backend Azure.
			Cette version utilise exactement la même méthode de génération que le backend pour une compatibilité maximale.
		</p>
	</div>

	<div class="card" id="environmentInfo" style="border-color: #007bff; margin-bottom: 20px;">
		<h2>Informations d'environnement</h2>
		<div id="envDetails">Vérification de l'environnement...</div>
		<div id="deploymentStatus" style="margin-top: 10px;"></div>
	</div>

	<div class="card">
		<h2>Authentification avec JWT Amélioré</h2>
		<div class="form-group">
			<label for="email">Email:</label>
			<input type="email" id="email" value="admin@example.com">
		</div>
		<div class="form-group">
			<label for="password">Mot de passe:</label>
			<input type="password" id="password" value="admin123">
		</div>
		<button onclick="testImprovedJwt()">Obtenir un token JWT</button>
	</div>

	<div class="card">
		<h2>Tester l'accès aux ressources protégées</h2>
		<p>Une fois authentifié, testez l'accès aux ressources protégées du backend avec le token JWT:</p>
		<button onclick="testProtectedResource('notes')">Accéder aux notes</button>
		<button onclick="testProtectedResource('users')">Accéder aux utilisateurs</button>
		<button onclick="checkToken()">Vérifier le token actuel</button>
		<button onclick="clearToken()">Effacer le token</button>
	</div>

	<div id="result" class="card">
		<h2>Résultat</h2>
		<pre id="output">Cliquez sur un bouton pour lancer un test...</pre>
	</div>

	<script>
		const output = document.getElementById('output');
		const isAzureEnvironment = window.location.hostname.includes('azurewebsites.net');

		// Afficher les informations d'environnement
		document.addEventListener('DOMContentLoaded', async function() {
			const envDetails = document.getElementById('envDetails');
			const deploymentStatus = document.getElementById('deploymentStatus');

			// Déterminer l'environnement
			if (isAzureEnvironment) {
				envDetails.innerHTML = `
					<p><strong>Environnement détecté:</strong> Azure Web App</p>
					<p><strong>Hostname:</strong> ${window.location.hostname}</p>
					<p><strong>URL de base:</strong> ${window.location.origin}</p>
				`;
			} else {
				envDetails.innerHTML = `
					<p><strong>Environnement détecté:</strong> Développement local</p>
					<p><strong>Hostname:</strong> ${window.location.hostname}</p>
					<p><strong>URL de base:</strong> ${window.location.origin}</p>
				`;
			}

			// Vérifier les composants déployés
			try {
				// Tester si le proxy amélioré est accessible
				const proxyTest = await fetch('enhanced-proxy.php?endpoint=status.php')
					.then(resp => {
						if (!resp.ok) throw new Error(`Statut: ${resp.status}`);
						return resp.json();
					})
					.then(data => {
						return {
							success: true,
							data
						};
					})
					.catch(err => {
						return {
							success: false,
							error: err.message
						};
					});

				// Vérifier si le JWT bridge est accessible (pas exécuté, juste vérifier la réponse 405 indiquant qu'il existe)
				const bridgeTest = await fetch('improved-jwt-bridge.php')
					.then(resp => {
						// 405 Method Not Allowed est attendu et signifie que le fichier existe
						if (resp.status === 405) {
							return {
								success: true,
								message: "Bridge accessible (405 - Méthode GET non autorisée)"
							};
						}
						if (!resp.ok) throw new Error(`Statut: ${resp.status}`);
						return resp.json();
					})
					.then(data => {
						return {
							success: true,
							data
						};
					})
					.catch(err => {
						return {
							success: false,
							error: err.message
						};
					});

				// Afficher l'état de déploiement
				let statusHTML = '<h3>État des composants:</h3><ul>';
				statusHTML += `<li>Proxy amélioré: ${proxyTest.success ? '<span style="color:green">✓ Accessible</span>' : '<span style="color:red">✗ Non accessible</span> - ' + proxyTest.error}</li>`;
				statusHTML += `<li>JWT Bridge: ${bridgeTest.success ? '<span style="color:green">✓ Accessible</span>' : '<span style="color:red">✗ Non accessible</span> - ' + bridgeTest.error}</li>`;

				// Si les tests indiquent que les composants sont en place
				if (proxyTest.success && bridgeTest.success) {
					statusHTML += '</ul><p><strong style="color:green">✓ Tous les composants sont correctement déployés.</strong></p>';

					// Si on est sur Azure, ajouter un message sur la redirection
					if (isAzureEnvironment) {
						statusHTML += `<p><strong>Backend cible:</strong> ${proxyTest.data?.api_base_url || 'Non détecté'}</p>`;
					}
				} else {
					statusHTML += '</ul><p><strong style="color:red">✗ Certains composants ne sont pas accessibles.</strong></p>';
					statusHTML += '<p>Vérifiez que tous les fichiers ont été déployés correctement.</p>';
				}

				deploymentStatus.innerHTML = statusHTML;
			} catch (error) {
				deploymentStatus.innerHTML = `
					<h3>Erreur lors de la vérification des composants:</h3>
					<p style="color:red">${error.message}</p>
				`;
			}
		});

		function displayResult(data, status = '') {
			if (status === 'error') {
				output.className = 'error';
			} else if (status === 'success') {
				output.className = 'success';
			} else {
				output.className = '';
			}

			if (typeof data === 'object') {
				output.textContent = JSON.stringify(data, null, 2);
			} else {
				output.textContent = data;
			}
		}

		async function testImprovedJwt() {
			const email = document.getElementById('email').value;
			const password = document.getElementById('password').value;

			displayResult('Tentative d\'authentification avec le JWT Amélioré...');

			// Ajouter des logs supplémentaires
			console.log(`Tentative d'authentification sur: ${window.location.origin}/improved-jwt-bridge.php`);

			try {
				const response = await fetch('improved-jwt-bridge.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						email: email,
						password: password
					})
				});

				// Logging détaillé pour aider au diagnostic
				console.log(`Réponse reçue: Status ${response.status}`);
				if (!response.ok) {
					console.warn(`Attention: La réponse n'est pas OK (${response.status})`);
				}

				const data = await response.json();
				console.log("Structure de la réponse:", Object.keys(data));

				// Stocker le token si disponible
				if (data.success && data.data && data.data.token) {
					localStorage.setItem('jwt_token', data.data.token);
					displayResult({
						message: 'JWT obtenu avec succès !',
						tokenReceived: true,
						tokenStored: true,
						isLocallyGenerated: data.isLocallyGenerated || false,
						data: data
					}, 'success');

					// Afficher info additionnelle sur le type de token
					if (data.isLocallyGenerated) {
						console.info("Token généré localement - Le backend n'a pas pu être contacté");
					} else {
						console.info("Token obtenu directement du backend");
					}
				} else {
					displayResult({
						message: 'Échec de la génération du JWT',
						data: data
					}, 'error');
				}
			} catch (error) {
				// Logging d'erreur amélioré
				console.error("Erreur complète:", error);
				displayResult({
					message: 'Erreur lors de la requête',
					error: error.message,
					help: isAzureEnvironment ?
						"Vérifiez que le fichier improved-jwt-bridge.php est bien déployé sur Azure" : "Vérifiez que le serveur local est en cours d'exécution"
				}, 'error');
			}
		}

		async function testProtectedResource(resource) {
			const token = localStorage.getItem('jwt_token');

			if (!token) {
				displayResult('Aucun token JWT trouvé. Veuillez vous authentifier d\'abord.', 'error');
				return;
			}

			displayResult(`Accès à la ressource "${resource}" avec le token JWT...`);

			let endpoint;
			// Utiliser le nouveau format REST pour les ressources
			switch (resource) {
				case 'notes':
					endpoint = 'api/notes';
					break;
				case 'users':
					endpoint = 'api/users';
					break;
				case 'classes':
					endpoint = 'api/classes';
					break;
				case 'profs':
					endpoint = 'api/profs';
					break;
				default:
					endpoint = resource;
			}

			try {
				// Utiliser le proxy amélioré pour tous les endpoints
				const url = `enhanced-proxy.php?endpoint=${endpoint}`;

				const response = await fetch(url, {
					headers: {
						'Authorization': 'Bearer ' + token
					}
				});

				const contentType = response.headers.get('content-type') || '';

				if (contentType.includes('application/json')) {
					const data = await response.json();
					displayResult({
						message: `Réponse de la ressource "${resource}"`,
						status: response.status,
						data: data
					}, response.status >= 200 && response.status < 300 ? 'success' : '');
				} else {
					const text = await response.text();
					displayResult({
						message: `Réponse non-JSON de la ressource "${resource}"`,
						status: response.status,
						contentType: contentType,
						data: text.substring(0, 300) + (text.length > 300 ? '...' : '')
					}, 'error');
				}
			} catch (error) {
				displayResult({
					message: `Erreur lors de l'accès à la ressource "${resource}"`,
					error: error.message
				}, 'error');
			}
		}

		function checkToken() {
			const token = localStorage.getItem('jwt_token');

			if (!token) {
				displayResult('Aucun token JWT trouvé.', 'error');
				return;
			}

			try {
				// Analyser le token JWT
				const parts = token.split('.');
				if (parts.length === 3) {
					// Décoder le payload (deuxième partie)
					const decodedPayload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));

					const now = Math.floor(Date.now() / 1000);
					const isExpired = decodedPayload.exp && decodedPayload.exp < now;

					displayResult({
						message: 'Analyse du token JWT',
						token: token.substring(0, 20) + '...',
						payload: decodedPayload,
						expiresAt: decodedPayload.exp ? new Date(decodedPayload.exp * 1000).toLocaleString() : 'Non spécifié',
						isExpired: isExpired
					}, isExpired ? 'error' : 'success');
				} else {
					displayResult({
						message: 'Format de token JWT invalide',
						token: token
					}, 'error');
				}
			} catch (e) {
				displayResult({
					message: 'Erreur lors de l\'analyse du token JWT',
					error: e.message,
					token: token.substring(0, 30) + '...'
				}, 'error');
			}
		}

		function clearToken() {
			localStorage.removeItem('jwt_token');
			displayResult('Token JWT effacé.');
		}

		// Vérifier si un token existe au chargement
		document.addEventListener('DOMContentLoaded', function() {
			const token = localStorage.getItem('jwt_token');
			if (token) {
				displayResult('Un token JWT existe déjà. Utilisez "Vérifier le token actuel" pour voir les détails.');
			}
		});
	</script>
</body>

</html>