<?php
session_start();

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user']) && !empty($_SESSION['token'])) {
	header('Location: dashboard.php');
	exit;
}

// Vérification automatique des fichiers proxy pour Azure
if (strpos($_SERVER['HTTP_HOST'], 'azurewebsites.net') !== false) {
	// Sur Azure, vérifier si les fichiers proxy existent
	$proxy_files = [
		'simple-proxy.php',
		'api-bridge.php',
		'direct-login.php',
		'local-proxy.php'
	];

	$missing_files = [];
	foreach ($proxy_files as $file) {
		if (!file_exists(__DIR__ . '/' . $file)) {
			$missing_files[] = $file;
		}
	}

	// Si des fichiers sont manquants, exécuter l'auto-déployer
	if (count($missing_files) > 0) {
		// Générer les fichiers manquants via auto-deployer.php
		if (file_exists(__DIR__ . '/auto-deployer.php')) {
			include_once __DIR__ . '/auto-deployer.php';
			// Après l'auto-déploiement, continuer normalement
		} else {
			// Crée un simple proxy sur place si nécessaire
			$simple_proxy_content = '<?php header("Content-Type: application/json"); echo file_get_contents("https://app-backend-esgi-app.azurewebsites.net/".($_GET["endpoint"] ?? "status.php"));';
			@file_put_contents(__DIR__ . '/simple-proxy.php', $simple_proxy_content);
		}
	}
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
	// Vérificateur en ligne pour détecter si les fichiers proxy sont accessibles(function() {    const isAzure = window.location.hostname.includes('azurewebsites.net');    if (!isAzure) return; // Seulement sur Azure    // Tente d'accéder au fichier status.php directement    fetch('status.php').then(response => {        if (response.ok) {            console.log('Le fichier status.php est accessible');        } else {            console.warn('status.php n\'est pas accessible, tentative de génération automatique');            // Essayons de générer le fichier status.php via l'auto-deployer            fetch('auto-deployer.php', {                method: 'POST',                headers: { 'X-Requested-With': 'XMLHttpRequest' }            }).then(deployResponse => {                if (deployResponse.ok) {                    console.log('Auto-deployer exécuté avec succès');                } else {                    console.error('Échec de l\'auto-deployer');                }            }).catch(e => console.error('Erreur lors de l\'exécution de l\'auto-deployer:', e));        }    }).catch(e => console.error('Erreur lors de l\'accès à status.php:', e));})();
</script>
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
			// Utiliser un endpoint qui existe vraiment sur l'API
			// On évite status.php qui pourrait ne pas exister, et on préfère directement tester l'API
			const response = await fetch(`${path}?endpoint=${encodeURIComponent('api/status')}`);
			const success = response.ok;
			console.log(`Proxy test ${path}: ${success ? 'SUCCESS' : 'FAILED'} (${response.status})`);

			// Si succès, essayons de lire la réponse pour vérifier que c'est bien du JSON valide
			if (success) {
				try {
					const text = await response.text();
					console.log(`Proxy ${path} response:`, text);
					// On vérifie si la réponse est du JSON valide
					JSON.parse(text);
				} catch (jsonErr) {
					console.warn(`Proxy ${path} returned invalid JSON:`, jsonErr);
					// Mais on ignore cette erreur, tant que la connexion est établie
				}
			}

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

			// Utiliser le proxy qui est déjà testé et fonctionne
			console.log('Utilisation du proxy pour la connexion: ' + proxyPath);
			NotificationSystem.info("Connexion via proxy...");

			// Construire l'URL d'API en utilisant le proxy
			const loginUrl = `${proxyPath}?endpoint=${encodeURIComponent(loginEndpoint)}`;
			console.log('URL de connexion via proxy:', loginUrl);

			// Options de requête
			const fetchOptions = {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					email,
					password
				}),
				credentials: 'include'
			};

			// Faire la requête via le proxy
			let response;
			try {
				console.log('Envoi de la requête via proxy...');

				response = await fetch(loginUrl, fetchOptions);
				console.log('Réponse du proxy reçue:', response);

				if (!response.ok) {
					// Si le proxy principal échoue, essayer l'API bridge comme solution de secours
					throw new Error('Échec de la requête via proxy: ' + response.status);
				}
			} catch (proxyErr) {
				console.error("Erreur de connexion via proxy:", proxyErr);

				// Essayer avec l'api-bridge comme solution de dernier recours
				try {
					console.log('Tentative avec api-bridge...');
					NotificationSystem.info("Tentative avec api-bridge...");

					// Utiliser le api-bridge qui fait une requête côté serveur
					const bridgeUrl = 'api-bridge.php?endpoint=' + encodeURIComponent(loginEndpoint);
					console.log('URL bridge:', bridgeUrl);

					response = await fetch(bridgeUrl, fetchOptions);
					console.log('Réponse api-bridge reçue:', response);

					if (!response.ok) {
						throw new Error('Échec de la requête api-bridge: ' + response.status);
					}
				} catch (bridgeErr) {
					console.error("Erreur avec api-bridge:", bridgeErr);

					// Solution de dernier recours - utiliser direct-login.php
					try {
						console.log('Tentative avec direct-login.php...');
						NotificationSystem.info("Tentative avec solution de secours...");

						// direct-login.php fait la requête côté serveur avec PHP
						response = await fetch('direct-login.php', fetchOptions);
						console.log('Réponse direct-login reçue:', response);

						if (!response.ok) {
							throw new Error('Toutes les tentatives ont échoué');
						}
					} catch (directErr) {
						console.error("Toutes les tentatives ont échoué:", directErr);
						throw new Error("Impossible de communiquer avec le backend: " + directErr.message);
					}
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