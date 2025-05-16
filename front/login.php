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
	$proxy_files_to_check = [
		'simple-proxy.php',
		'api-bridge.php',
		'direct-login.php',
		'local-proxy.php',
		'local-proxy-fix.php',
		'status.php'
	];

	// Diagnostiquer l'accès au fichier système
	$diagnostics = [];
	$diagnostics['document_root'] = $_SERVER['DOCUMENT_ROOT'] ?? 'unknown';
	$diagnostics['script_filename'] = $_SERVER['SCRIPT_FILENAME'] ?? 'unknown';
	$diagnostics['current_dir'] = __DIR__;
	$diagnostics['can_write_current'] = is_writable(__DIR__);

	error_log('Azure deployment diagnostics: ' . json_encode($diagnostics));

	// Vérifier les fichiers manquants
	$missing_files = [];
	foreach ($proxy_files_to_check as $filename) {
		$filepath = __DIR__ . '/' . $filename;
		$file_exists = file_exists($filepath);

		if (!$file_exists || filesize($filepath) < 100) {
			$missing_files[] = $filename;
		}
	}

	// Si des fichiers sont manquants, rediriger vers le script d'extraction
	if (!empty($missing_files)) {
		if (!file_exists(__DIR__ . '/extract_proxy.php')) {
			// Si extract_proxy.php n'existe pas, créer un message d'erreur
			echo "<div style='color:red;padding:20px;background:#fee'>
				Erreur: Fichiers proxy manquants et extract_proxy.php non disponible.
				Veuillez déployer à nouveau les fichiers ou contacter l'administrateur.
			</div>";
		} else {
			// Rediriger vers le script d'extraction
			header('Location: extract_proxy.php');
			exit;
		}
	}

	// Aussi créer les copies dans d'autres dossiers pour plus de fiabilité
	$extra_dirs = ['/api', '/proxy'];
	foreach ($extra_dirs as $dir) {
		$dir_path = __DIR__ . $dir;

		// Créer le dossier s'il n'existe pas
		if (!is_dir($dir_path)) {
			$dir_created = @mkdir($dir_path, 0755, true);
			error_log("Directory creation " . ($dir_created ? "succeeded" : "failed") . " for " . $dir);
		}

		if (is_dir($dir_path)) {
			foreach ($proxy_files_to_check as $filename) {
				$src_file = __DIR__ . '/' . $filename;
				$extra_filepath = $dir_path . '/' . $filename;

				if (file_exists($src_file) && (!file_exists($extra_filepath) || filesize($extra_filepath) < 100)) {
					$success = @copy($src_file, $extra_filepath);
					if ($success) {
						@chmod($extra_filepath, 0755);
						error_log("Created/Updated proxy file in $dir: $filename");
					}
				}
			}
		}
	}

	// Vérifier si web.config existe et le mettre à jour si nécessaire
	$web_config_file = __DIR__ . '/web.config';
	$create_web_config = !file_exists($web_config_file) || filesize($web_config_file) < 100;

	if ($create_web_config) {
		$web_config_content = '<?xml version="1.0" encoding="UTF-8"?>
<configuration>
	<system.webServer>
		<rewrite>
			<rules>
				<!-- Default document handler -->
				<rule name="DefaultDocument" stopProcessing="true">
					<match url="^$" />
					<action type="Rewrite" url="index.php" />
				</rule>
				
				<!-- Ensure PHP files are processed correctly -->
				<rule name="PHPHandler" stopProcessing="true">
					<match url="^.*\.php$" />
					<action type="None" />
				</rule>
				
				<!-- Ensure root PHP files are properly rewritten -->
				<rule name="RootPhpFiles" stopProcessing="true">
					<match url="^(login|dashboard|index|logout|session-handler|status)$" />
					<action type="Rewrite" url="{R:1}.php" />
				</rule>
				
				<!-- Proxy access from any path of the application -->
				<rule name="ProxyFiles" stopProcessing="true">
					<match url="^(.*/)?(?:simple-proxy|api-bridge|direct-login|local-proxy|local-proxy-fix)\.php$" />
					<action type="Rewrite" url="{R:2}.php" />
				</rule>
				
				<!-- Management pages -->
				<rule name="GestionRedirects" stopProcessing="true">
					<match url="^gestion/([a-z_]+)(/.*)?$" />
					<action type="Rewrite" url="gestion_{R:1}.php{R:2}" appendQueryString="true" />
				</rule>
			</rules>
		</rewrite>
		
		<!-- Enable directory browsing for troubleshooting -->
		<directoryBrowse enabled="true" />
		
		<!-- Add CORS headers for API access -->
		<httpProtocol>
			<customHeaders>
				<add name="Access-Control-Allow-Origin" value="*" />
				<add name="Access-Control-Allow-Methods" value="GET, POST, OPTIONS, PUT, DELETE" />
				<add name="Access-Control-Allow-Headers" value="Content-Type, Authorization, X-Requested-With" />
			</customHeaders>
		</httpProtocol>
		
		<!-- Cache configuration to improve performance -->
		<staticContent>
			<clientCache cacheControlMode="UseMaxAge" cacheControlMaxAge="1.00:00:00" />
			<!-- MIME types for proper site functioning -->
			<mimeMap fileExtension=".woff" mimeType="application/font-woff" />
			<mimeMap fileExtension=".woff2" mimeType="application/font-woff2" />
		</staticContent>
		
		<!-- Default document configuration -->
		<defaultDocument>
			<files>
				<clear />
				<add value="index.php" />
				<add value="index.html" />
				<add value="login.php" />
			</files>
		</defaultDocument>
	</system.webServer>
</configuration>';
		$web_config_result = @file_put_contents($web_config_file, $web_config_content);
		error_log("Web.config " . ($web_config_result ? "created/updated successfully" : "creation failed"));
	}

	// Créer un test.html pour diagnostiquer les problèmes
	$test_html_file = __DIR__ . '/proxy-test.html';
	if (!file_exists($test_html_file)) {
		// Le contenu existant du proxy-test.html peut être conservé
		// Nous ne le modifions pas dans cette version pour éviter plus d'erreurs
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
	// Vérificateur en ligne pour détecter si les fichiers proxy sont accessibles
	(function() {
		const isAzure = window.location.hostname.includes('azurewebsites.net');
		if (!isAzure) return; // Seulement sur Azure

		console.log("Vérification de l'accessibilité des proxies sur Azure...");

		// Essayer plusieurs chemins de proxy possibles
		const pathsToCheck = [
			'simple-proxy.php',
			'api-bridge.php',
			'local-proxy.php',
			'local-proxy-fix.php',
			'/simple-proxy.php',
			'/api/simple-proxy.php',
			'/proxy/simple-proxy.php'
		];

		let proxyFound = false;

		// Fonction pour vérifier un fichier proxy
		async function checkProxy(path) {
			try {
				const response = await fetch(`${path}?endpoint=status.php`);
				if (response.ok) {
					console.log(`Proxy trouvé et fonctionnel: ${path}`);
					proxyFound = true;
					return true;
				}
				return false;
			} catch (e) {
				console.log(`Erreur lors de l'accès à ${path}:`, e.message);
				return false;
			}
		}

		// Vérifier tous les chemins
		Promise.all(pathsToCheck.map(path => checkProxy(path)))
			.then(results => {
				// Si aucun proxy n'est trouvé, recharger la page pour déclencher la création des fichiers
				if (!proxyFound) {
					console.warn("Aucun proxy accessible trouvé. Tentative de création automatique...");
					// Forcer le rechargement de la page pour déclencher la création de fichiers côté serveur
					if (!window.localStorage.getItem('proxy_creation_attempted')) {
						window.localStorage.setItem('proxy_creation_attempted', 'true');
						console.log("Rechargement de la page pour créer les proxies...");
						setTimeout(() => window.location.reload(), 1000);
					} else {
						console.warn("Une tentative de création a déjà été effectuée, vérifiez les permissions du serveur.");
						// Montrer une notification
						if (typeof NotificationSystem !== 'undefined') {
							NotificationSystem.warning("Impossible d'accéder aux proxies. Contactez l'administrateur.");
						}
					}
				} else {
					// Réinitialiser le flag si un proxy est trouvé
					window.localStorage.removeItem('proxy_creation_attempted');
				}
			});
	})();
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
	console.log('API base URL from config:', appConfig.apiBaseUrl);

	// Fonction pour tester un chemin de proxy
	async function testProxyPath(path) {
		try {
			console.log('Testing proxy path:', path);

			// Tester avec différents endpoints pour plus de fiabilité
			const endpointsToTry = [
				'status.php', // Notre fichier de statut local
				'api/status', // Endpoint API de statut
				'api/ping', // Autre endpoint potentiel
				'health' // Endpoint de santé standard
			];

			// Essayer chaque endpoint jusqu'à ce qu'un fonctionne
			for (const endpoint of endpointsToTry) {
				try {
					console.log(`Testing ${path} with endpoint: ${endpoint}`);
					const response = await fetch(`${path}?endpoint=${encodeURIComponent(endpoint)}`);

					if (response.ok) {
						console.log(`Proxy test ${path} with ${endpoint}: SUCCESS (${response.status})`);

						// Vérifier que la réponse est du JSON valide
						try {
							const text = await response.text();
							console.log(`Proxy ${path} response:`, text);
							JSON.parse(text);
						} catch (jsonErr) {
							console.warn(`Proxy ${path} returned invalid JSON, but connection works:`, jsonErr);
							// On ignore cette erreur, tant que la connexion est établie
						}

						return true;
					}

					console.log(`Proxy test ${path} with ${endpoint}: FAILED (${response.status})`);
				} catch (endpointErr) {
					console.log(`Error testing ${path} with ${endpoint}:`, endpointErr.message);
				}
			}

			// Si on arrive ici, aucun endpoint n'a fonctionné
			console.error(`All endpoints failed for proxy path: ${path}`);
			return false;
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
			// Chemins relatifs
			"simple-proxy.php",
			"api-bridge.php",
			"local-proxy.php",
			"local-proxy-fix.php",
			"proxy/simple-proxy.php",
			"api/simple-proxy.php",
			// Chemins absolus
			"/simple-proxy.php",
			"/api-bridge.php",
			"/local-proxy.php",
			"/local-proxy-fix.php",
			"/proxy/simple-proxy.php",
			"/api/simple-proxy.php",
			// Chemins basés sur le chemin actuel
			window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + "/simple-proxy.php",
			window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + "/api-bridge.php",
			window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + "/local-proxy.php"
		];

		console.log('Testing proxy paths in this order:', pathsToTry);
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

			// Données de connexion
			const loginData = {
				email,
				password
			};

			// NOUVELLE MÉTHODE: Utiliser la fonction handlePostRequest de config.js
			let response;
			try {
				// Utiliser la fonction améliorée de contournement de 404
				if (typeof handlePostRequest === 'function') {
					console.log('Utilisation de handlePostRequest pour la connexion');
					response = await handlePostRequest(loginEndpoint, loginData);
				} else {
					// Fallback si la fonction n'est pas disponible
					console.log('Utilisation de la méthode standard pour la connexion');

					// Construire l'URL d'API en utilisant le proxy
					const loginUrl = `${proxyPath}?endpoint=${encodeURIComponent(loginEndpoint)}`;
					console.log('URL de connexion via proxy:', loginUrl);

					// Options de requête
					const fetchOptions = {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json'
						},
						body: JSON.stringify(loginData),
						credentials: 'include'
					};

					response = await fetch(loginUrl, fetchOptions);
				}

				console.log('Réponse reçue:', response);

				if (!response.ok) {
					throw new Error('Échec de la requête: ' + response.status);
				}
			} catch (proxyErr) {
				console.error("Erreur de connexion:", proxyErr);

				// Essayer avec direct-login comme solution de dernier recours
				try {
					console.log('Tentative avec direct-login.php...');
					NotificationSystem.info("Tentative avec solution de secours...");

					// direct-login.php fait la requête côté serveur avec PHP
					response = await fetch('direct-login.php', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json'
						},
						body: JSON.stringify(loginData)
					});

					console.log('Réponse direct-login reçue:', response);

					if (!response.ok) {
						throw new Error('Toutes les tentatives ont échoué');
					}
				} catch (directErr) {
					console.error("Toutes les tentatives ont échoué:", directErr);
					throw new Error("Impossible de communiquer avec le backend: " + directErr.message);
				}
			}

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