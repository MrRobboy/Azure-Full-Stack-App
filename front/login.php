<?php
session_start();

// Si l'utilisateur est déjà connecté, rediriger vers le tableau de bord
if (isset($_SESSION['prof_id'])) {
	header('Location: dashboard.php');
	exit();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Connexion - Gestion Scolaire</title>
	<link rel="stylesheet" href="css/common.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
	<header class="header">
		<div class="header-container">
			<a href="index.php" class="logo">
				<img src="images/school-badge.png" alt="Logo École" class="school-badge">
				Gestion Scolaire
			</a>
		</div>
	</header>

	<main class="login-container">
		<div class="login-card">
			<h2><i class="fas fa-sign-in-alt"></i> Connexion</h2>

			<?php if (isset($_GET['error'])): ?>
				<div class="alert alert-danger">
					<i class="fas fa-exclamation-circle"></i>
					<?php echo htmlspecialchars($_GET['error']); ?>
				</div>
			<?php endif; ?>

			<form id="loginForm" method="POST" action="unified-proxy.php?endpoint=auth/login">
				<div class="form-group">
					<div class="input-group">
						<i class="fas fa-user input-group-icon"></i>
						<input type="text"
							class="form-control"
							id="username"
							name="email"
							placeholder="Email"
							required>
					</div>
				</div>

				<div class="form-group">
					<div class="input-group">
						<i class="fas fa-lock input-group-icon"></i>
						<input type="password"
							class="form-control"
							id="password"
							name="password"
							placeholder="Mot de passe"
							required>
					</div>
				</div>

				<div class="form-group">
					<button type="submit" class="btn btn-primary" style="width: 100%;">
						<i class="fas fa-sign-in-alt"></i>
						Se connecter
					</button>
				</div>
			</form>

			<div class="text-center mt-3">
				<a href="index.php" class="btn btn-secondary">
					<i class="fas fa-arrow-left"></i>
					Retour à l'accueil
				</a>
			</div>
		</div>
	</main>

	<script>
		document.getElementById('loginForm').addEventListener('submit', async function(e) {
			e.preventDefault();

			const submitButton = this.querySelector('button[type="submit"]');
			const originalText = submitButton.innerHTML;

			try {
				submitButton.disabled = true;
				submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connexion en cours...';

				const formData = new FormData(this);
				const data = {
					email: formData.get('email'),
					password: formData.get('password')
				};

				console.log('Tentative de connexion avec:', {
					email: data.email,
					password: '***'
				});

				const response = await fetch('unified-proxy.php?endpoint=auth/login', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify(data)
				});

				console.log('Statut de la réponse:', response.status);
				const responseData = await response.json();
				console.log('Réponse complète:', responseData);

				if (!response.ok) {
					throw new Error(responseData.message || `Erreur HTTP: ${response.status}`);
				}

				if (!responseData.success) {
					throw new Error(responseData.message || 'Erreur de connexion');
				}

				if (!responseData.data) {
					throw new Error('Données de réponse invalides');
				}

				// Vérifier que toutes les données nécessaires sont présentes
				const requiredFields = ['id_prof', 'nom', 'prenom'];
				const missingFields = requiredFields.filter(field => !responseData.data[field]);

				if (missingFields.length > 0) {
					throw new Error(`Données manquantes: ${missingFields.join(', ')}`);
				}

				// Préparer les données pour la session
				const sessionData = {
					id_prof: responseData.data.id_prof,
					nom: responseData.data.nom,
					prenom: responseData.data.prenom,
					role: responseData.data.role || 'Enseignant'
				};

				console.log('Données de session à envoyer:', sessionData);

				// Stocker les informations dans la session PHP
				const sessionResponse = await fetch('set-session.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify(sessionData)
				});

				const sessionResult = await sessionResponse.json();
				console.log('Réponse de set-session.php:', sessionResult);

				if (!sessionResponse.ok || !sessionResult.success) {
					throw new Error(sessionResult.message || 'Erreur lors de la création de la session');
				}

				// Redirection vers le dashboard
				window.location.href = 'dashboard.php';

			} catch (error) {
				console.error('Erreur détaillée:', error);
				// Afficher l'erreur dans la div d'alerte
				const errorDiv = document.createElement('div');
				errorDiv.className = 'alert alert-danger';
				errorDiv.innerHTML = `
					<i class="fas fa-exclamation-circle"></i>
					${error.message}
				`;
				// Supprimer l'ancienne alerte si elle existe
				const oldAlert = document.querySelector('.alert');
				if (oldAlert) {
					oldAlert.remove();
				}
				// Insérer la nouvelle alerte après le titre
				const title = document.querySelector('h2');
				title.parentNode.insertBefore(errorDiv, title.nextSibling);
			} finally {
				submitButton.disabled = false;
				submitButton.innerHTML = '<i class="fas fa-sign-in-alt"></i> Se connecter';
			}
		});
	</script>
</body>

</html>