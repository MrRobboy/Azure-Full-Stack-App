<?php
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user']) && !empty($_SESSION['token'])) {
	header('Location: dashboard.php');
	exit;
}

$pageTitle = "Connexion";
ob_start();
?>

<div class="login-container">
	<div class="login-card">
		<h2>Connexion</h2>
		<div id="error-message" class="alert alert-danger" style="display: none;">
			<div class="error-header">
				<i class="fas fa-exclamation-circle"></i>
				<span class="error-title">Erreur</span>
			</div>
			<div class="error-content"></div>
			<div class="error-details" style="display: none;">
				<div class="error-type"></div>
				<div class="error-timestamp"></div>
				<div class="error-trace"></div>
			</div>
			<button class="btn btn-link btn-sm toggle-details" onclick="toggleErrorDetails()">
				Afficher les détails
			</button>
		</div>
		<form id="login-form">
			<div class="form-group">
				<label for="email">Email</label>
				<div class="input-group">
					<span class="input-group-icon"><i class="fas fa-envelope"></i></span>
					<input type="email" id="email" name="email" required>
				</div>
			</div>
			<div class="form-group">
				<label for="password">Mot de passe</label>
				<div class="input-group">
					<span class="input-group-icon"><i class="fas fa-lock"></i></span>
					<input type="password" id="password" name="password" required>
				</div>
			</div>
			<button type="submit" class="btn btn-primary">
				<span class="btn-text">Se connecter</span>
				<span class="btn-loading" style="display: none;">
					<i class="fas fa-spinner fa-spin"></i> Connexion en cours...
				</span>
			</button>
		</form>
	</div>
</div>

<script src="js/cache-buster.js"></script>
<script src="js/config.js?v=1.9"></script>
<script src="js/notification-system.js?v=1.1"></script>
<script>
	// Utiliser le chemin du proxy depuis la configuration
	let proxyPath = appConfig.proxyUrl;
	console.log('Environment:', isAzure ? 'Azure' : 'Local');
	console.log('Using proxy path:', proxyPath);

	// Log de diagnostic étendu sur l'environnement
	console.log('Full hostname:', window.location.hostname);
	console.log('Full window.location:', window.location.href);
	console.log('Current pathname:', window.location.pathname);
	console.log('API base URL from config:', appConfig.backendBaseUrl);

	// Fonction pour tester un chemin de proxy
	async function testProxyPath(path) {
		try {
			console.log('Testing proxy path:', path);
			const response = await fetch(`${path}?endpoint=status.php`);
			const success = response.ok;
			console.log(`Proxy test ${path}: ${success ? 'SUCCESS' : 'FAILED'} (${response.status})`);
			return success;
		} catch (err) {
			console.error(`Proxy test ${path} error:`, err);
			return false;
		}
	}

	// Tester différents chemins pour le proxy
	(async function() {
		// Essayer plusieurs chemins possibles pour trouver le proxy
		const pathsToTry = [
			proxyPath,
			"simple-proxy.php",
			"/simple-proxy.php",
			"local-proxy.php", // Essayer notre nouveau proxy simplifié
			window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + "/simple-proxy.php",
			window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + "/local-proxy.php"
		];

		let foundWorkingPath = false;

		for (const path of pathsToTry) {
			if (await testProxyPath(path)) {
				console.log('Found working proxy path:', path);
				proxyPath = path;
				foundWorkingPath = true;
				break;
			}
		}

		if (!foundWorkingPath) {
			console.error('No working proxy path found! Login may fail.');
			// Définir un fallback
			proxyPath = "local-proxy.php";
			console.log('Using fallback proxy path:', proxyPath);
		}
	})();

	function toggleErrorDetails() {
		const details = document.querySelector('.error-details');
		const button = document.querySelector('.toggle-details');
		if (details.style.display === 'none') {
			details.style.display = 'block';
			button.textContent = 'Masquer les détails';
		} else {
			details.style.display = 'none';
			button.textContent = 'Afficher les détails';
		}
	}

	function displayError(error) {
		const errorMessage = document.getElementById('error-message');
		const errorContent = errorMessage.querySelector('.error-content');
		const errorType = errorMessage.querySelector('.error-type');
		const errorTimestamp = errorMessage.querySelector('.error-timestamp');
		const errorTrace = errorMessage.querySelector('.error-trace');

		errorContent.textContent = error.message || 'Une erreur est survenue';
		errorType.textContent = 'Type: ' + (error.type || 'Inconnu');
		errorTimestamp.textContent = 'Date: ' + (error.timestamp || new Date().toISOString());
		errorTrace.textContent = 'Détails: ' + JSON.stringify(error.details || {}, null, 2);

		errorMessage.style.display = 'block';

		// Ajouter la notification via le système de notification
		NotificationSystem.error(error.message || 'Erreur de connexion');
	}

	// Function to store session data via AJAX
	async function storeSessionData(userData, token) {
		try {
			const response = await fetch('session-handler.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					action: 'login',
					user: userData,
					token: token
				})
			});

			return response.ok;
		} catch (error) {
			console.error('Session storage error:', error);
			return false;
		}
	}

	document.getElementById('login-form').addEventListener('submit', async function(e) {
		e.preventDefault();

		const email = document.getElementById('email').value;
		const password = document.getElementById('password').value;
		const errorMessage = document.getElementById('error-message');
		const submitButton = this.querySelector('button[type="submit"]');
		const btnText = submitButton.querySelector('.btn-text');
		const btnLoading = submitButton.querySelector('.btn-loading');

		// Afficher le loader
		btnText.style.display = 'none';
		btnLoading.style.display = 'inline-block';
		errorMessage.style.display = 'none';

		// Force proxy to true for reliability
		appConfig.useProxy = true;

		// Notification pour informer l'utilisateur
		NotificationSystem.info('Tentative de connexion...');

		try {
			console.log('Tentative de connexion...');

			// Endpoint d'authentification
			const loginEndpoint = 'api/auth/login';

			// Tenter une connexion directe au backend comme solution de contournement
			console.log('Tentative de connexion directe au backend (contournement du proxy)');

			// URL du backend
			const directBackendUrl = `${appConfig.backendBaseUrl}/${loginEndpoint}`;
			console.log('URL directe au backend:', directBackendUrl);

			// Options de requête pour la connexion directe
			const fetchOptions = {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					email,
					password
				}),
				credentials: 'include',
				mode: 'cors' // Explicitement demander le mode CORS
			};

			// Essayer la connexion directe
			let response;
			try {
				console.log('Envoi de la requête au backend...');
				NotificationSystem.info("Tentative de connexion directe au backend...");

				response = await fetch(directBackendUrl, fetchOptions);
				console.log('Réponse directe reçue:', response);

				// Si la réponse directe échoue à cause de CORS, alors on essaie un fallback
				if (!response.ok && (response.status === 0 || response.type === 'opaque')) {
					console.log('Erreur CORS détectée, tentative avec iframe ou formdata...');
					// On pourrait implémenter une solution de fallback ici
					// Mais pour l'instant, on affiche juste l'erreur
					throw new Error('Erreur CORS - connexion directe impossible');
				}
			} catch (fetchErr) {
				console.error("Erreur de connexion directe:", fetchErr);

				// Comme solution de dernier recours, on peut utiliser une iframe cachée
				try {
					console.log('Tentative avec solution alternative...');
					NotificationSystem.info("Tentative avec méthode alternative...");

					// Créer une forme de proxy côté client avec un iframe caché
					const iframe = document.createElement('iframe');
					iframe.style.display = 'none';
					document.body.appendChild(iframe);

					// On utilise une promesse pour attendre la réponse
					response = await new Promise((resolve, reject) => {
						// Timeout de sécurité après 10 secondes
						const timeout = setTimeout(() => {
							reject(new Error('Timeout de la requête'));
						}, 10000);

						// Créer un ID unique pour cette requête
						const requestId = 'req_' + Math.random().toString(36).substring(2, 15);

						// Écouter les messages de l'iframe
						window.addEventListener('message', function responseHandler(event) {
							if (event.data && event.data.requestId === requestId) {
								clearTimeout(timeout);
								window.removeEventListener('message', responseHandler);
								resolve(event.data.response);
							}
						});

						// Naviguer vers le backend dans l'iframe avec les credentials
						iframe.src = `${appConfig.backendBaseUrl}/auth-bridge.html?requestId=${requestId}&endpoint=${loginEndpoint}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}`;
					});

					// Supprimer l'iframe une fois terminé
					document.body.removeChild(iframe);

					console.log('Réponse alternative reçue:', response);
				} catch (altErr) {
					console.error("Toutes les tentatives ont échoué:", altErr);
					throw new Error("Impossible de communiquer avec le backend: " + altErr.message);
				}
			}

			console.log('Réponse reçue:', response);
			const responseText = await response.text();
			console.log('Contenu de la réponse:', responseText);

			let data;
			try {
				data = JSON.parse(responseText);
			} catch (e) {
				console.error('Erreur de parsing JSON:', e);
				throw new Error('Réponse invalide du serveur: ' + responseText);
			}

			if (response.ok && data.success) {
				// Store user data in a session (using PHP session)
				const sessionSaved = await storeSessionData(data.user, data.token);

				if (sessionSaved) {
					NotificationSystem.success('Connexion réussie! Redirection vers le tableau de bord...');
					console.log('Login successful, redirecting to dashboard...');

					// Short delay for notification to be visible
					setTimeout(() => {
						window.location.href = 'dashboard.php';
					}, 1000);
				} else {
					throw new Error('Erreur de sauvegarde de session');
				}
			} else {
				displayError({
					type: 'Erreur d\'authentification',
					message: data.message || 'Identifiants incorrects',
					details: data
				});
				console.error('Erreur de connexion:', data);
			}
		} catch (error) {
			displayError({
				type: 'Erreur réseau',
				message: 'Erreur de connexion au serveur: ' + error.message,
				details: {
					errorMessage: error.message,
					useProxy: appConfig.useProxy,
					proxyUrl: proxyPath,
					isAzure: isAzure
				}
			});
			console.error('Erreur:', error);
		} finally {
			// Réinitialiser le bouton
			btnText.style.display = 'inline-block';
			btnLoading.style.display = 'none';
		}
	});
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>