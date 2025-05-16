<?php

/**
 * Simple Login - Version alternative de login qui utilise GET au lieu de POST
 * 
 * Ce script est une solution de contournement pour les problèmes CORS/POST sur Azure
 * Il accepte les paramètres par GET et communique avec le backend en PHP côté serveur
 */

// Ajouter des en-têtes CORS pour sécuriser les appels
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Log pour le débogage
error_log("Simple-login.php called with method: " . $_SERVER['REQUEST_METHOD']);
error_log("Query string: " . $_SERVER['QUERY_STRING']);

// Détecter si on veut une réponse JSON (pour les appels depuis JavaScript)
$wantJson = isset($_GET['json']) && $_GET['json'] === 'true';

// Variables pour stocker les erreurs et résultats
$error = null;
$result = [
	'success' => false,
	'message' => '',
	'user' => null,
	'token' => null
];

// Vérifier si les identifiants ont été fournis
if (isset($_GET['email']) && isset($_GET['password'])) {
	$email = $_GET['email'];
	$password = $_GET['password'];

	error_log("Login attempt for email: " . $email);

	// Configuration du backend
	$api_base_url = 'https://app-backend-esgi-app.azurewebsites.net';
	$login_endpoint = '/api/auth/login';
	$fallback_endpoint = '/api/auth/check-credentials';

	// Données à envoyer
	$data = [
		'email' => $email,
		'password' => $password
	];

	// NOUVELLE APPROCHE: Essayer d'abord avec le endpoint GET authentifié
	try {
		error_log("Tentative avec l'endpoint GET authentifié: " . $fallback_endpoint);
		$get_auth_url = $api_base_url . $fallback_endpoint . '?email=' . urlencode($email) . '&password=' . urlencode($password);

		// Utiliser cURL pour la requête GET
		$ch = curl_init($get_auth_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curl_error = curl_error($ch);
		curl_close($ch);

		if ($response && $http_code >= 200 && $http_code < 300) {
			$result = json_decode($response, true);
			error_log("GET auth response: " . json_encode($result));

			if ($result && isset($result['token'])) {
				error_log("GET authentication successful!");
				// Pas besoin d'essayer les autres méthodes
				goto process_result;
			}
		} else {
			error_log("GET auth failed: HTTP " . $http_code . ", Error: " . $curl_error);
		}
	} catch (Exception $e) {
		error_log("Exception during GET auth: " . $e->getMessage());
	}

	// Fallback to normal POST methods if GET auth failed
	$api_url = $api_base_url . $login_endpoint;

	// Essayer d'abord avec file_get_contents (méthode 1)
	try {
		$options = [
			'http' => [
				'header' => "Content-type: application/json\r\n",
				'method' => 'POST',
				'content' => json_encode($data),
				'timeout' => 15
			]
		];

		$context = stream_context_create($options);
		$response = @file_get_contents($api_url, false, $context);

		if ($response !== false) {
			$result = json_decode($response, true);
			error_log("Login response: " . json_encode($result));
		} else {
			// Si ça échoue, essayer avec cURL (méthode 2)
			if (function_exists('curl_init')) {
				$ch = curl_init($api_url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
				curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
				curl_setopt($ch, CURLOPT_TIMEOUT, 15);

				$response = curl_exec($ch);
				$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				$curl_error = curl_error($ch);
				curl_close($ch);

				if ($response && $http_code >= 200 && $http_code < 300) {
					$result = json_decode($response, true);
					error_log("Login response (cURL): " . json_encode($result));
				} else {
					$error = "Erreur de communication avec le serveur (" . $http_code . "): " . $curl_error;
					error_log("cURL error: " . $error);
				}
			} else {
				$error = "Impossible de communiquer avec le serveur backend";
				error_log("file_get_contents failed and cURL not available");
			}
		}
	} catch (Exception $e) {
		$error = "Exception: " . $e->getMessage();
		error_log("Exception during login: " . $e->getMessage());
	}

	process_result:
	// Si on a un résultat mais pas de token, c'est une erreur
	if ($result && !isset($result['token'])) {
		$error = isset($result['message']) ? $result['message'] : "Identifiants incorrects";
	}

	// Si on a un token, on est connecté
	if ($result && isset($result['token']) && $result['token']) {
		// Utiliser la session PHP pour stocker les données
		session_start();
		$_SESSION['user'] = $result['user'];
		$_SESSION['token'] = $result['token'];

		// Si on veut du JSON, renvoyer le résultat directement
		if ($wantJson) {
			header('Content-Type: application/json');
			echo json_encode($result);
			exit;
		}

		// Sinon, rediriger vers le dashboard
		header('Location: dashboard.php');
		exit;
	}
} else if ($wantJson) {
	// Si on veut du JSON mais qu'on n'a pas les données, renvoyer une erreur
	$error = "Données de connexion manquantes";
}

// Si on veut du JSON et qu'il y a une erreur, renvoyer l'erreur en JSON
if ($wantJson) {
	header('Content-Type: application/json');
	echo json_encode([
		'success' => false,
		'message' => $error ?: "Échec de la connexion",
		'error' => $error
	]);
	exit;
}

// Vérifier si on est dans une boucle de redirection
$is_loop = isset($_GET['redirected']) && $_GET['redirected'] === 'true';

// Sinon, afficher le formulaire HTML
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

		.warning-box {
			background-color: #fff3e0;
			border: 1px solid #ffcc80;
			padding: 10px;
			border-radius: 4px;
			margin-bottom: 20px;
			font-size: 14px;
			color: #e65100;
		}

		.button-secondary {
			background-color: #757575;
			color: white;
			border: none;
			padding: 10px;
			border-radius: 4px;
			cursor: pointer;
			font-weight: bold;
			text-decoration: none;
			display: inline-block;
			margin-top: 10px;
		}

		.button-secondary:hover {
			background-color: #616161;
		}
	</style>
</head>

<body>
	<div class="login-container">
		<h1>Connexion Simplifiée</h1>

		<?php if ($is_loop): ?>
			<div class="warning-box">
				<strong>Attention!</strong> Vous semblez être dans une boucle de redirection.
				<p>Le backend ne semble pas configuré correctement ou l'URL d'authentification est incorrecte.</p>
				<a href="login.php" class="button-secondary">Retourner à la page de connexion principale</a>
			</div>
		<?php else: ?>
			<div class="info-box">
				<strong>Note importante:</strong> Cette page utilise une méthode de connexion simplifiée
				pour contourner les problèmes de requêtes POST sur Azure. Ne pas utiliser en production.
			</div>
		<?php endif; ?>

		<?php if ($error): ?>
			<div class="error"><?php echo htmlspecialchars($error); ?></div>
		<?php endif; ?>

		<form method="GET" action="simple-login.php">
			<input type="hidden" name="redirected" value="true">
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

		<?php if ($is_loop): ?>
			<div style="margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd;">
				<h3>Diagnostic</h3>
				<p>L'API backend ne répond pas correctement. Vérifiez les points suivants:</p>
				<ul>
					<li>Le backend est bien déployé et en fonctionnement</li>
					<li>L'URL du backend est correcte: <code><?php echo htmlspecialchars($api_url); ?></code></li>
					<li>L'endpoint d'authentification existe sur le backend</li>
					<li>Les requêtes CORS sont bien configurées</li>
				</ul>
				<p><a href="test-endpoints.php" target="_blank">Voir l'outil de diagnostic des endpoints</a></p>
			</div>
		<?php endif; ?>
	</div>
</body>

</html>