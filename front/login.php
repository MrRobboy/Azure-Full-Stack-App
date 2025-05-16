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

<head>
	<!-- Load our API service -->
	<script src="js/api-service.js"></script>
</head>

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
		<div class="links">
			<a href="login.php">Connexion standard</a>
			<a href="index.php">Accueil</a>
		</div>
	</div>
</div>

<script src="js/config.js?v=4.0"></script>
<script src="js/notification-system.js?v=1.1"></script>
<script>
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
		console.log('Storing session data...', {
			userData,
			token
		});

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

			const data = await response.json();
			console.log('Session handler response:', data);

			if (!data.success) {
				console.error('Session storage failed:', data.message);
				return false;
			}

			// Verify the session was actually set
			const sessionCheckResponse = await fetch('session-handler.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					action: 'check'
				})
			});

			const sessionStatus = await sessionCheckResponse.json();
			console.log('Session verification:', sessionStatus);

			return sessionStatus.loggedIn === true;
		} catch (error) {
			console.error('Session storage error:', error);
			return false;
		}
	}

	document.addEventListener('DOMContentLoaded', function() {
		// Get the login form
		const loginForm = document.getElementById('login-form');

		// Add submit handler
		loginForm.addEventListener('submit', async function(e) {
			e.preventDefault();

			// Show loading notification
			NotificationSystem.info('Tentative de connexion...');
			console.log('Tentative de connexion...');

			// Get form data
			const email = document.getElementById('email').value;
			const password = document.getElementById('password').value;

			try {
				// Use our API Service for login
				const result = await ApiService.login(email, password);

				console.log('Login response:', result);

				if (result.success && result.data.success) {
					// Success - check for user data and token in the response
					if (!result.data.user || !result.data.token) {
						console.error('Missing user data or token in login response:', result.data);
						NotificationSystem.error('Réponse de connexion incomplète. Contactez l\'administrateur.');
						return;
					}

					console.log('Login successful, storing session data...');

					// Store session data on server side before redirect
					const sessionStored = await storeSessionData(result.data.user, result.data.token);
					console.log('Session stored:', sessionStored);

					if (!sessionStored) {
						console.error('Failed to store session data');
						NotificationSystem.error('Erreur lors de la création de la session. Veuillez réessayer.');
						return;
					}

					NotificationSystem.success('Connexion réussie. Redirection...');

					// Short delay before redirect to ensure session is fully established
					setTimeout(() => {
						window.location.href = 'dashboard.php';
					}, 500);
				} else {
					// Login failed
					const errorMessage = result.data.message || 'Erreur de connexion';
					console.error('Login failed:', errorMessage);
					NotificationSystem.error(errorMessage);
					console.log('Erreur de connexion:', result.data);
				}
			} catch (error) {
				// Exception occurred
				NotificationSystem.error('Erreur technique lors de la connexion');
				console.error('Exception:', error);
			}
		});
	});
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>