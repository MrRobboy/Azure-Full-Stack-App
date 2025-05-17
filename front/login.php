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
		</div>
		<form id="login-form">
			<div class="form-group">
				<label for="email">Email</label>
				<div class="input-group">
					<span class="input-group-icon"><i class="fas fa-envelope"></i></span>
					<input type="email" id="email" name="email" required value="admin@example.com">
				</div>
			</div>
			<div class="form-group">
				<label for="password">Mot de passe</label>
				<div class="input-group">
					<span class="input-group-icon"><i class="fas fa-lock"></i></span>
					<input type="password" id="password" name="password" required value="admin123">
				</div>
			</div>
			<div class="alert alert-info small">
				<strong>Info:</strong> Identifiants de test pré-remplis pour faciliter l'accès.
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

<script src="js/config.js?v=5.0"></script>
<script src="js/api-service.js?v=2.0"></script>
<script src="js/notification-system.js?v=1.1"></script>
<script>
	document.addEventListener('DOMContentLoaded', function() {
		console.log('Page de connexion initialisée');

		// Éléments du formulaire
		const loginForm = document.getElementById('login-form');
		const emailInput = document.getElementById('email');
		const passwordInput = document.getElementById('password');
		const submitButton = loginForm.querySelector('button[type="submit"]');
		const btnText = submitButton.querySelector('.btn-text');
		const btnLoading = submitButton.querySelector('.btn-loading');

		// Fonction pour afficher une erreur
		function displayError(message) {
			const errorMessage = document.getElementById('error-message');
			const errorContent = errorMessage.querySelector('.error-content');

			errorContent.textContent = message;
			errorMessage.style.display = 'block';

			// Notification via le système de notification si disponible
			if (window.NotificationSystem) {
				NotificationSystem.error(message);
			}
		}

		// Fonction pour masquer les erreurs
		function hideError() {
			const errorMessage = document.getElementById('error-message');
			errorMessage.style.display = 'none';
		}

		// Fonction pour afficher le chargement
		function showLoading() {
			btnText.style.display = 'none';
			btnLoading.style.display = 'inline-block';
			submitButton.disabled = true;
		}

		// Fonction pour masquer le chargement
		function hideLoading() {
			btnText.style.display = 'inline-block';
			btnLoading.style.display = 'none';
			submitButton.disabled = false;
		}

		// Fonction pour stocker les données de session
		async function storeSessionData(userData, token) {
			console.log('Stockage des données de session...');

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

				if (!data.success) {
					console.error('Échec du stockage de session:', data.message);
					return false;
				}

				// Vérifier que la session a bien été créée
				const verificationResponse = await fetch('session-handler.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						action: 'check'
					})
				});

				const verificationData = await verificationResponse.json();
				return verificationData.loggedIn === true;
			} catch (error) {
				console.error('Erreur lors du stockage de session:', error);
				return false;
			}
		}

		// Gestionnaire de soumission du formulaire
		loginForm.addEventListener('submit', async function(e) {
			e.preventDefault();
			hideError();
			showLoading();

			// Informations d'identification
			const email = emailInput.value;
			const password = passwordInput.value;

			// Notification de tentative
			if (window.NotificationSystem) {
				NotificationSystem.info('Tentative de connexion...');
			}

			try {
				// Appel à l'API via notre service
				const result = await ApiService.login(email, password);

				console.log('Réponse de connexion:', result);

				if (result.success && result.data.success) {
					// Vérifier la présence des informations requises
					if (!result.data.user || !result.data.token) {
						throw new Error('Réponse incomplète du serveur d\'authentification');
					}

					// Stocker les données de session
					const sessionStored = await storeSessionData(result.data.user, result.data.token);

					if (!sessionStored) {
						throw new Error('Impossible de créer la session. Veuillez réessayer.');
					}

					// Notification de succès
					if (window.NotificationSystem) {
						NotificationSystem.success('Connexion réussie. Redirection...');
					}

					// Redirection vers le tableau de bord
					setTimeout(() => {
						window.location.href = 'dashboard.php';
					}, 500);
				} else {
					// Erreur d'authentification
					const errorMessage = result.data.message || 'Identifiants incorrects';
					throw new Error(errorMessage);
				}
			} catch (error) {
				console.error('Erreur de connexion:', error);
				displayError(error.message || 'Erreur lors de la connexion. Veuillez réessayer.');
				hideLoading();
			}
		});

		// Focus sur le champ email au chargement
		emailInput.focus();
	});
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>