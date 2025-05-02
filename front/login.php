<?php
session_start();

$pageTitle = "Connexion";
ob_start();
?>

<div class="card" style="max-width: 400px; margin: 0 auto;">
	<div class="card-header">
		<h2 class="card-title">
			<i class="fas fa-user-graduate"></i> Connexion
		</h2>
	</div>
	<div class="card-body">
		<form id="loginForm">
			<div class="form-group">
				<label for="email" class="form-label">
					<i class="fas fa-envelope"></i> Email
				</label>
				<input type="email" id="email" name="email" class="form-control" required>
			</div>
			<div class="form-group">
				<label for="password" class="form-label">
					<i class="fas fa-lock"></i> Mot de passe
				</label>
				<input type="password" id="password" name="password" class="form-control" required>
			</div>
			<div class="form-group">
				<button type="submit" class="btn btn-primary" style="width: 100%;" id="submitButton">
					<i class="fas fa-sign-in-alt"></i> Se connecter
				</button>
			</div>
		</form>
		<div id="errorMessage" class="alert alert-danger" style="display: none;"></div>
	</div>
</div>

<script>
	document.getElementById('loginForm').addEventListener('submit', async function(e) {
		e.preventDefault();

		// Désactiver le bouton et afficher l'indicateur de chargement
		const submitButton = document.getElementById('submitButton');
		const originalButtonText = submitButton.innerHTML;
		submitButton.disabled = true;
		submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connexion en cours...';

		// Cacher les messages d'erreur précédents
		const errorMessage = document.getElementById('errorMessage');
		errorMessage.style.display = 'none';

		const formData = {
			email: document.getElementById('email').value,
			password: document.getElementById('password').value
		};

		try {
			const response = await fetch('api/auth/login', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify(formData)
			});

			const data = await response.json();

			if (response.ok) {
				window.location.href = 'dashboard.php';
			} else {
				// Afficher le message d'erreur spécifique
				errorMessage.textContent = data.error || 'Erreur de connexion';
				errorMessage.style.display = 'block';

				// Si c'est une erreur d'authentification, vider le champ mot de passe
				if (response.status === 401) {
					document.getElementById('password').value = '';
				}
			}
		} catch (error) {
			console.error('Erreur détaillée:', error);

			// Afficher un message d'erreur plus spécifique
			if (error.name === 'TypeError' && error.message.includes('fetch')) {
				errorMessage.textContent = 'Erreur de connexion au serveur. Veuillez vérifier votre connexion internet.';
			} else {
				errorMessage.textContent = 'Une erreur inattendue est survenue. Veuillez réessayer.';
			}
			errorMessage.style.display = 'block';
		} finally {
			// Réactiver le bouton et restaurer le texte original
			submitButton.disabled = false;
			submitButton.innerHTML = originalButtonText;
		}
	});
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>