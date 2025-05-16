<?php

/**
 * Simple Login - Version simplifiée pour contourner les limitations des requêtes POST
 * ATTENTION: Méthode non sécurisée, à utiliser uniquement pour des tests/exercices
 */
session_start();

// Si déjà connecté, rediriger vers le dashboard
if (isset($_SESSION['user']) && !empty($_SESSION['token'])) {
	header('Location: dashboard.php');
	exit;
}

// Traitement de la connexion par méthode GET (pour exercice uniquement)
$login_error = '';
if (isset($_GET['email']) && isset($_GET['password'])) {
	$email = $_GET['email'];
	$password = $_GET['password'];

	// URL du backend pour vérifier les identifiants (utilisation directe)
	$backendBaseUrl = 'https://app-backend-esgi-app.azurewebsites.net';

	// Utiliser file_get_contents pour une requête GET simplifiée
	// Note: Normalement on utiliserait une requête POST sécurisée
	$check_url = $backendBaseUrl . '/api/auth/check-credentials?email=' . urlencode($email) . '&password=' . urlencode($password);

	// Log de la tentative
	error_log('Tentative de connexion simplifiée pour: ' . $email);

	try {
		// Désactiver les vérifications SSL pour le test (NON RECOMMANDÉ en production)
		$context = stream_context_create([
			'ssl' => [
				'verify_peer' => false,
				'verify_peer_name' => false
			]
		]);

		// Effectuer la requête
		$response = @file_get_contents($check_url, false, $context);

		if ($response !== false) {
			$data = json_decode($response, true);

			if (isset($data['success']) && $data['success']) {
				// Stocker les informations de session
				$_SESSION['user'] = $data['user'] ?? [];
				$_SESSION['token'] = $data['token'] ?? 'simple-token-' . time();
				$_SESSION['loggedIn'] = true;
				$_SESSION['loginTime'] = time();

				// Rediriger vers le dashboard
				header('Location: dashboard.php');
				exit;
			} else {
				$login_error = $data['message'] ?? 'Identifiants incorrects';
			}
		} else {
			$login_error = 'Erreur de connexion au serveur backend';
		}
	} catch (Exception $e) {
		$login_error = 'Exception: ' . $e->getMessage();
	}
}

// Affichage de la page
?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Connexion Simplifiée</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			background-color: #f5f5f5;
			margin: 0;
			padding: 20px;
			display: flex;
			justify-content: center;
			align-items: center;
			min-height: 100vh;
		}

		.login-container {
			background-color: white;
			padding: 30px;
			border-radius: 8px;
			box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
			width: 100%;
			max-width: 400px;
		}

		h1 {
			text-align: center;
			color: #0078D4;
			margin-bottom: 20px;
		}

		.info-box {
			background-color: #e6f7ff;
			border: 1px solid #91d5ff;
			padding: 10px;
			border-radius: 4px;
			margin-bottom: 20px;
			font-size: 14px;
		}

		.form-group {
			margin-bottom: 15px;
		}

		label {
			display: block;
			margin-bottom: 5px;
			font-weight: bold;
		}

		input[type="email"],
		input[type="password"] {
			width: 100%;
			padding: 10px;
			border: 1px solid #ddd;
			border-radius: 4px;
			box-sizing: border-box;
		}

		button {
			background-color: #0078D4;
			color: white;
			border: none;
			padding: 12px;
			border-radius: 4px;
			width: 100%;
			cursor: pointer;
			font-weight: bold;
		}

		button:hover {
			background-color: #005a9e;
		}

		.error {
			color: red;
			margin-bottom: 15px;
		}

		.links {
			text-align: center;
			margin-top: 20px;
		}

		.links a {
			color: #0078D4;
			text-decoration: none;
			margin: 0 10px;
		}
	</style>
</head>

<body>
	<div class="login-container">
		<h1>Connexion Simplifiée</h1>

		<div class="info-box">
			<strong>Note importante:</strong> Cette page utilise une méthode de connexion simplifiée
			pour contourner les problèmes de requêtes POST sur Azure. Ne pas utiliser en production.
		</div>

		<?php if (!empty($login_error)): ?>
			<div class="error"><?= htmlspecialchars($login_error) ?></div>
		<?php endif; ?>

		<form method="GET" action="simple-login.php">
			<div class="form-group">
				<label for="email">Email</label>
				<input type="email" id="email" name="email" required>
			</div>

			<div class="form-group">
				<label for="password">Mot de passe</label>
				<input type="password" id="password" name="password" required>
			</div>

			<button type="submit">Se connecter</button>
		</form>

		<div class="links">
			<a href="login.php">Connexion standard</a>
			<a href="index.php">Accueil</a>
		</div>
	</div>
</body>

</html>