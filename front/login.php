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
	// Use appConfig's proxy path directly - no need to redefine isAzure
	const proxyPath = appConfig.proxyUrl;
	console.log('Environment:', isAzure ? 'Azure' : 'Local');
	console.log('Using proxy path:', proxyPath);

	// Log additional debug info about the environment
	console.log('Full hostname:', window.location.hostname);
	console.log('Full window.location:', window.location.href);
	console.log('API base URL from config:', appConfig.backendBaseUrl);

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

			// Using the direct proxy URL for reliability
			const loginEndpoint = 'api/auth/login';
			const proxyUrl = `${proxyPath}?endpoint=${encodeURIComponent(loginEndpoint)}`;

			console.log('URL de connexion:', proxyUrl);
			console.log('Testing if proxy file exists...');

			// Test if the proxy file actually exists before trying login
			try {
				const testResponse = await fetch(proxyPath + '?endpoint=status.php');
				console.log('Proxy status test:', testResponse.status, testResponse.ok ? 'OK' : 'Failed');
			} catch (testErr) {
				console.error('Proxy test failed:', testErr);
			}

			const response = await fetch(proxyUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					email,
					password
				}),
				credentials: 'include'
			});

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