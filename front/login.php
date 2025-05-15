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
		'simple-proxy.php' => '<?php
// Simple proxy for Azure - minimal version
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

$endpoint = isset($_GET["endpoint"]) ? $_GET["endpoint"] : "";
if (empty($endpoint)) {
    echo json_encode(["error" => "No endpoint specified"]);
    exit;
}

$backend_url = "https://app-backend-esgi-app.azurewebsites.net";
$url = $backend_url . ($endpoint[0] !== "/" ? "/" : "") . $endpoint;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER["REQUEST_METHOD"]);
if ($_SERVER["REQUEST_METHOD"] === "POST" || $_SERVER["REQUEST_METHOD"] === "PUT") {
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents("php://input"));
}
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($httpCode);
echo $response;',

		'api-bridge.php' => '<?php
// API Bridge - Last Resort Fallback
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

$endpoint = isset($_GET["endpoint"]) ? $_GET["endpoint"] : "";
if (empty($endpoint)) {
    echo json_encode(["error" => "No endpoint specified"]);
    exit;
}

$backend_url = "https://app-backend-esgi-app.azurewebsites.net";
$url = $backend_url . ($endpoint[0] !== "/" ? "/" : "") . $endpoint;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER["REQUEST_METHOD"]);
if ($_SERVER["REQUEST_METHOD"] === "POST" || $_SERVER["REQUEST_METHOD"] === "PUT") {
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents("php://input"));
}
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($httpCode);
echo $response;',

		'direct-login.php' => '<?php
// Direct Login - Se connecter sans proxy
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data["email"]) || !isset($data["password"])) {
    http_response_code(400);
    echo json_encode(["error" => "Email and password required"]);
    exit;
}

$loginUrl = "https://app-backend-esgi-app.azurewebsites.net/api/auth/login";

$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Accept: application/json"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($httpCode);
echo $response;',

		'local-proxy.php' => '<?php
// Proxy ultra-simplifié
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

$endpoint = isset($_GET["endpoint"]) ? $_GET["endpoint"] : "";
$backend_url = "https://app-backend-esgi-app.azurewebsites.net/";
echo file_get_contents($backend_url . $endpoint);',

		'status.php' => '<?php
// Status file
header("Content-Type: application/json");
echo json_encode([
    "success" => true,
    "status" => "ok",
    "server" => $_SERVER["SERVER_NAME"],
    "timestamp" => date("Y-m-d H:i:s"),
    "environment" => strpos($_SERVER["HTTP_HOST"], "azurewebsites.net") !== false ? "azure" : "local"
]);'
	];

	// Créer les fichiers manquants
	foreach ($proxy_files as $filename => $content) {
		// Vérifier et créer dans le répertoire racine
		if (!file_exists(__DIR__ . '/' . $filename)) {
			@file_put_contents(__DIR__ . '/' . $filename, $content);
			error_log("Created proxy file: " . $filename);
		}

		// Vérifier et créer dans /api/ et /proxy/ si les répertoires existent
		$extra_dirs = ['/api', '/proxy'];
		foreach ($extra_dirs as $dir) {
			if (!is_dir(__DIR__ . $dir)) {
				@mkdir(__DIR__ . $dir, 0777);
				error_log("Created directory: " . $dir);
			}

			if (!file_exists(__DIR__ . $dir . '/' . $filename)) {
				@file_put_contents(__DIR__ . $dir . '/' . $filename, $content);
				error_log("Created proxy file in $dir: " . $filename);
			}
		}
	}

	// Vérifier si web.config existe et contient les règles nécessaires
	if (!file_exists(__DIR__ . '/web.config')) {
		$web_config_content = '<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <rule name="PHP Files" stopProcessing="true">
                    <match url="^.*\.php$" />
                    <action type="None" />
                </rule>
                
                <rule name="SimpleProxyAccess" stopProcessing="true">
                    <match url="^(.*/)?simple-proxy\.php$" />
                    <action type="Rewrite" url="simple-proxy.php" />
                </rule>
                
                <rule name="API Bridge Access" stopProcessing="true">
                    <match url="^(.*/)?api-bridge\.php$" />
                    <action type="Rewrite" url="api-bridge.php" />
                </rule>
                
                <rule name="Local Proxy Access" stopProcessing="true">
                    <match url="^(.*/)?local-proxy\.php$" />
                    <action type="Rewrite" url="local-proxy.php" />
                </rule>
                
                <rule name="Direct Login Access" stopProcessing="true">
                    <match url="^(.*/)?direct-login\.php$" />
                    <action type="Rewrite" url="direct-login.php" />
                </rule>
                
                <rule name="Status Access" stopProcessing="true">
                    <match url="^status\.php$" />
                    <action type="Rewrite" url="status.php" />
                </rule>
            </rules>
        </rewrite>
        
        <httpProtocol>
            <customHeaders>
                <add name="Access-Control-Allow-Origin" value="*" />
                <add name="Access-Control-Allow-Methods" value="GET, POST, OPTIONS, PUT, DELETE" />
                <add name="Access-Control-Allow-Headers" value="Content-Type, Authorization, X-Requested-With" />
            </customHeaders>
        </httpProtocol>
    </system.webServer>
</configuration>';
		@file_put_contents(__DIR__ . '/web.config', $web_config_content);
		error_log("Created web.config");
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
	console.log('API base URL from config:', appConfig.backendBaseUrl);

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
			"proxy/simple-proxy.php",
			"api/simple-proxy.php",
			// Chemins absolus
			"/simple-proxy.php",
			"/api-bridge.php",
			"/local-proxy.php",
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