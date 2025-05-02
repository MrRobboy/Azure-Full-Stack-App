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
		<form action="/api/auth/login" method="POST" id="loginForm">
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
				<button type="submit" class="btn btn-primary" style="width: 100%;">
					<i class="fas fa-sign-in-alt"></i> Se connecter
				</button>
			</div>
		</form>
	</div>
</div>

<script>
	document.getElementById('loginForm').addEventListener('submit', async function(e) {
		e.preventDefault();

		const formData = {
			email: document.getElementById('email').value,
			password: document.getElementById('password').value
		};

		try {
			const response = await fetch('/api/auth/login', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify(formData)
			});

			const data = await response.json();

			if (response.ok) {
				window.location.href = '/dashboard.php';
			} else {
				alert(data.error || 'Erreur de connexion');
			}
		} catch (error) {
			console.error('Erreur:', error);
			alert('Une erreur est survenue');
		}
	});
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>