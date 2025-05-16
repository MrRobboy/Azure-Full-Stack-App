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

	<!-- Nouveau: Tableau de bord de débogage -->
	<div id="debugPanel" class="card" style="display:none; border-color:#ff9800;">
		<h2>Informations de débogage</h2>
		<div id="debugContent"></div>
	</div>

	<script>
		const output = document.getElementById('output');
		const isAzureEnvironment = window.location.hostname.includes('azurewebsites.net');
		const debugPanel = document.getElementById('debugPanel');
		const debugContent = document.getElementById('debugContent');

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

			// Masquer le panneau de débogage jusqu'à ce que nous ayons des données
			debugPanel.style.display = 'none';

			// Ajouter des logs supplémentaires
			console.log(`Tentative d'authentification sur: ${window.location.origin}/improved-jwt-bridge.php`);

			// Afficher les données qui seront envoyées
			const requestData = {
				email,
				password
			};
			console.log("Données à envoyer:", requestData);

			try {
				// 1. Ajout d'un timeout plus long pour les environnements Azure qui peuvent être plus lents
				const controller = new AbortController();
				const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 secondes de timeout

				const response = await fetch('improved-jwt-bridge.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'Accept': 'application/json',
						'X-Requested-With': 'XMLHttpRequest'
					},
					body: JSON.stringify(requestData),
					signal: controller.signal
				});

				clearTimeout(timeoutId);

				// Logging détaillé pour aider au diagnostic
				console.log(`Réponse reçue: Status ${response.status}`);
				console.log(`Headers:`, Object.fromEntries([...response.headers.entries()]));

				if (!response.ok) {
					console.warn(`Attention: La réponse n'est pas OK (${response.status})`);
					// Essayer de lire le corps même en cas d'erreur
					try {
						const errorText = await response.text();
						console.warn(`Corps de l'erreur:`, errorText);
						try {
							const errorJson = JSON.parse(errorText);
							console.warn(`Corps de l'erreur (JSON):`, errorJson);
						} catch (e) {
							// Ce n'est pas du JSON, on a déjà affiché le texte
						}
					} catch (e) {
						console.warn(`Impossible de lire le corps de l'erreur:`, e);
					}
				}

				// Récupérer le corps de la réponse comme texte d'abord
				const responseText = await response.text();
				console.log("Réponse brute:", responseText);

				// Essayer de parser comme JSON
				let data;
				try {
					data = JSON.parse(responseText);
					console.log("Structure de la réponse:", data);

					// Afficher les informations de débogage si disponibles
					if (data.debug_info || data.isLocallyGenerated) {
						showDebugInfo(data);
					}
				} catch (jsonError) {
					console.error("Erreur de parsing JSON:", jsonError);
					// Si ce n'est pas du JSON mais que ça ressemble à un token JWT
					if (responseText.trim().startsWith('ey') && responseText.trim().includes('.')) {
						data = {
							token: responseText.trim()
						};
						console.log("Réponse traitée comme un token JWT brut");
					} else {
						throw new Error(`Réponse non-JSON: ${responseText.substring(0, 100)}${responseText.length > 100 ? '...' : ''}`);
					}
				}

				// Amélioration de la gestion du token - vérifier les différentes structures possibles
				let token = null;
				let tokenSource = "inconnu";

				// Option 1: Structure { success: true, data: { token: '...' } }
				if (data.success && data.data && data.data.token) {
					token = data.data.token;
					tokenSource = "structure success.data.token";
				}
				// Option 2: Structure { token: '...' }
				else if (data.token) {
					token = data.token;
					tokenSource = "structure directe token";
				}
				// Option 3: Structure { data: { token: '...' } }
				else if (data.data && data.data.token) {
					token = data.data.token;
					tokenSource = "structure data.token";
				}
				// Option 4: Structure { data: '...' } (token direct)
				else if (data.data && typeof data.data === 'string' && data.data.startsWith('ey')) {
					token = data.data;
					tokenSource = "token JWT direct dans data";
				}
				// Option 5: Réponse en texte brut qui est un JWT (commence par ey)
				else if (typeof responseText === 'string' && responseText.trim().startsWith('ey') && responseText.trim().includes('.')) {
					token = responseText.trim();
					tokenSource = "token JWT sous forme de texte brut";
				}

				if (token) {
					localStorage.setItem('jwt_token', token);
					displayResult({
						message: 'JWT obtenu avec succès !',
						tokenReceived: true,
						tokenStored: true,
						tokenSource: tokenSource,
						isLocallyGenerated: data.isLocallyGenerated || false,
						tokenPreview: `${token.substring(0, 15)}...${token.substring(token.length - 10)}`,
						dataPreview: typeof data === 'object' ? JSON.stringify(data).substring(0, 100) + '...' : 'Non disponible'
					}, 'success');

					// Afficher info additionnelle sur le type de token
					if (data.isLocallyGenerated) {
						console.info("Token généré localement - Le backend n'a pas pu être contacté");
					} else {
						console.info("Token obtenu directement du backend");
					}

					// Vérification rapide de la validité du token
					try {
						const parts = token.split('.');
						if (parts.length === 3) {
							const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
							console.log("Contenu décodé du token:", payload);

							// Afficher aussi le contenu décodé du token
							if (!data.debug_info) {
								showTokenDebugInfo(token, payload);
							}
						}
					} catch (e) {
						console.warn("Impossible de décoder le contenu du token:", e);
					}
				} else {
					console.error("Aucun token trouvé dans la réponse:", data);

					// Approche alternative: appel direct vers le backend - en dernier recours
					if (isAzureEnvironment && !window.hasTriedDirectBackend) {
						window.hasTriedDirectBackend = true;
						displayResult({
							message: 'Échec de la récupération du JWT via le bridge. Tentative d\'accès direct au backend...',
							data: data
						});

						try {
							const backendUrl = 'https://app-backend-esgi-app.azurewebsites.net/api/auth/login';
							console.log(`Tentative alternative via: ${backendUrl}`);

							const directResponse = await fetch(backendUrl, {
								method: 'POST',
								headers: {
									'Content-Type': 'application/json',
									'Accept': 'application/json',
									'X-Requested-With': 'XMLHttpRequest'
								},
								body: JSON.stringify(requestData)
							});

							console.log(`Réponse backend directe: Status ${directResponse.status}`);
							const directData = await directResponse.json();
							console.log("Réponse du backend:", directData);

							// Extraire le token du backend
							let directToken = null;
							if (directData.token) {
								directToken = directData.token;
							} else if (directData.data && directData.data.token) {
								directToken = directData.data.token;
							}

							if (directToken) {
								localStorage.setItem('jwt_token', directToken);
								displayResult({
									message: 'JWT obtenu avec succès directement du backend!',
									tokenReceived: true,
									tokenStored: true,
									tokenSource: "appel direct au backend",
									tokenPreview: `${directToken.substring(0, 15)}...${directToken.substring(directToken.length - 10)}`
								}, 'success');
							} else {
								throw new Error("Aucun token trouvé dans la réponse directe du backend");
							}
						} catch (backendError) {
							console.error("Erreur lors de l'accès direct au backend:", backendError);
							displayResult({
								message: 'Échec de la récupération du JWT (méthodes bridge et directe)',
								error: `Bridge: ${JSON.stringify(data)}, Backend direct: ${backendError.message}`,
								help: "Vérifiez les identifiants et l'état du backend"
							}, 'error');
						}
					} else {
						displayResult({
							message: 'Échec de la récupération du JWT',
							data: data,
							help: "Structure de réponse non reconnue. Vérifiez la console pour plus de détails."
						}, 'error');
					}
				}
			} catch (error) {
				// Logging d'erreur amélioré
				console.error("Erreur complète:", error);
				displayResult({
					message: 'Erreur lors de la requête',
					error: error.message,
					help: isAzureEnvironment ?
						"Vérifiez que le fichier improved-jwt-bridge.php est bien déployé sur Azure et que le backend est accessible" : "Vérifiez que le serveur local est en cours d'exécution"
				}, 'error');
			}
		}

		// Fonction pour afficher les infos de débogage retournées par le bridge
		function showDebugInfo(data) {
			debugPanel.style.display = 'block';
			let debugHtml = '';

			// Informations sur le token généré localement
			if (data.isLocallyGenerated) {
				debugHtml += `<div style="margin-bottom:15px; padding:10px; background:#fff9c4; border-radius:4px;">
					<h3 style="margin-top:0; color:#ff6f00;">⚠️ Token généré localement</h3>
					<p>Le backend n'a pas pu être contacté ou n'a pas fourni de token valide. Un token compatible a été généré localement.</p>
				</div>`;
			}

			// Afficher les informations de debug si disponibles
			if (data.debug_info) {
				debugHtml += `<h3>Environnement</h3>
				<p>${data.debug_info.environment}</p>`;

				if (data.debug_info.backend_attempts && data.debug_info.backend_attempts.length > 0) {
					debugHtml += `<h3>Tentatives de connexion au backend</h3>
					<table style="width:100%; border-collapse:collapse;">
						<tr style="background:#f5f5f5;">
							<th style="text-align:left; padding:8px; border:1px solid #ddd;">Endpoint</th>
							<th style="text-align:left; padding:8px; border:1px solid #ddd;">URL</th>
							<th style="text-align:left; padding:8px; border:1px solid #ddd;">Statut</th>
							<th style="text-align:left; padding:8px; border:1px solid #ddd;">Erreur</th>
						</tr>`;

					data.debug_info.backend_attempts.forEach(attempt => {
						const statusColor = attempt.status >= 200 && attempt.status < 300 ? 'green' : 'red';
						debugHtml += `<tr>
							<td style="padding:8px; border:1px solid #ddd;">${attempt.endpoint}</td>
							<td style="padding:8px; border:1px solid #ddd; font-size:0.9em;">${attempt.url}</td>
							<td style="padding:8px; border:1px solid #ddd; color:${statusColor};">${attempt.status}</td>
							<td style="padding:8px; border:1px solid #ddd;">${attempt.error || '-'}</td>
						</tr>`;
					});

					debugHtml += `</table>`;
				}

				if (data.debug_info.last_error) {
					debugHtml += `<h3>Dernière erreur</h3>
					<div style="padding:8px; background:#ffebee; border-radius:4px;">
						${data.debug_info.last_error}
					</div>`;
				}
			}

			debugContent.innerHTML = debugHtml;
		}

		// Fonction pour afficher les infos de débogage du token
		function showTokenDebugInfo(token, payload) {
			debugPanel.style.display = 'block';

			// Analyser les parties du token
			const parts = token.split('.');

			let debugHtml = `<h3>Informations sur le token JWT</h3>`;

			// Afficher les parties du token
			debugHtml += `<div style="margin-bottom:15px;">
				<strong>Header:</strong> ${parts[0].substring(0, 10)}...
				<br><strong>Payload:</strong> ${parts[1].substring(0, 10)}...
				<br><strong>Signature:</strong> ${parts[2].substring(0, 10)}...
			</div>`;

			// Afficher le contenu décodé
			debugHtml += `<h3>Contenu du payload</h3>
			<table style="width:100%; border-collapse:collapse;">
				<tr style="background:#f5f5f5;">
					<th style="text-align:left; padding:8px; border:1px solid #ddd;">Champ</th>
					<th style="text-align:left; padding:8px; border:1px solid #ddd;">Valeur</th>
				</tr>`;

			// Ajouter chaque champ du payload
			Object.keys(payload).forEach(key => {
				let value = payload[key];

				// Formater les dates pour les timestamps
				if (key === 'exp' || key === 'iat') {
					const date = new Date(value * 1000);
					value = `${value} (${date.toLocaleString()})`;
				}

				debugHtml += `<tr>
					<td style="padding:8px; border:1px solid #ddd;"><strong>${key}</strong></td>
					<td style="padding:8px; border:1px solid #ddd;">${value}</td>
				</tr>`;
			});

			debugHtml += `</table>`;

			// Vérification de l'expiration
			const now = Math.floor(Date.now() / 1000);
			if (payload.exp && payload.exp < now) {
				debugHtml += `<div style="margin-top:15px; padding:10px; background:#ffebee; border-radius:4px; color:#d32f2f;">
					<strong>⚠️ ATTENTION:</strong> Ce token est expiré!
				</div>`;
			} else if (payload.exp) {
				const expiresIn = payload.exp - now;
				const hours = Math.floor(expiresIn / 3600);
				const minutes = Math.floor((expiresIn % 3600) / 60);

				debugHtml += `<div style="margin-top:15px; padding:10px; background:#e8f5e9; border-radius:4px; color:#2e7d32;">
					<strong>✓ Valide:</strong> Ce token expire dans ${hours}h ${minutes}m
				</div>`;
			}

			debugContent.innerHTML = debugHtml;
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