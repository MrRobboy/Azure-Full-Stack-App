<?php

/**
 * Générateur de Proxy - Crée un proxy personnalisé basé sur les configurations détectées
 */

// Configuration de base
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Création du répertoire de logs si nécessaire
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
	mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/proxy-generator.log');

// Récupérer l'action
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Fonction pour générer un proxy personnalisé
function generateProxy($config)
{
	$apiBase = $config['api_base'] ?? 'https://app-backend-esgi-app.azurewebsites.net';
	$authPath = $config['auth_path'] ?? 'api-auth-login.php';
	$statusPath = $config['status_path'] ?? 'status.php';
	$notesPath = $config['notes_path'] ?? 'api-notes.php';
	$routerPath = $config['router_path'] ?? 'api-router.php';
	$proxyName = $config['proxy_name'] ?? 'custom-proxy.php';

	// Vérifier si le nom du proxy est sécurisé
	if (!preg_match('/^[a-zA-Z0-9_-]+\.php$/', $proxyName)) {
		return [
			'success' => false,
			'message' => 'Nom de proxy invalide. Utilisez uniquement des lettres, chiffres, tirets et underscores.'
		];
	}

	// Contenu du proxy
	$proxyContent = <<<EOT
<?php
/**
 * Proxy personnalisé généré par proxy-generator.php
 * Date de génération: {$config['date']}
 */

// Configuration
\$API_BASE = '{$apiBase}';
\$AUTH_PATH = '{$authPath}';
\$STATUS_PATH = '{$statusPath}';
\$NOTES_PATH = '{$notesPath}';
\$ROUTER_PATH = '{$routerPath}';

// Journalisation
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Création du répertoire de logs si nécessaire
\$logDir = __DIR__ . '/logs';
if (!is_dir(\$logDir)) {
    mkdir(\$logDir, 0755, true);
}
ini_set('error_log', \$logDir . '/{$proxyName}.log');

// Journalisation de base
error_log("Proxy personnalisé accédé: " . \$_SERVER['REQUEST_URI']);
error_log("Méthode: " . \$_SERVER['REQUEST_METHOD']);

// En-têtes CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Traiter les requêtes OPTIONS
if (\$_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Obtenir l'endpoint demandé
\$endpoint = isset(\$_GET['endpoint']) ? \$_GET['endpoint'] : '';
if (empty(\$endpoint)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Endpoint manquant'
    ]);
    exit;
}

// Mapper l'endpoint à la bonne URL
\$targetUrl = \$API_BASE;

// Construire l'URL cible
if (strpos(\$endpoint, 'auth') !== false || strpos(\$endpoint, 'login') !== false) {
    // Endpoint d'authentification
    \$targetUrl .= '/' . \$AUTH_PATH;
} else if (strpos(\$endpoint, 'status') !== false) {
    // Endpoint de statut
    \$targetUrl .= '/' . \$STATUS_PATH;
} else if (strpos(\$endpoint, 'note') !== false || strpos(\$endpoint, 'matiere') !== false) {
    // Endpoint des notes/matières
    \$targetUrl .= '/' . \$NOTES_PATH;
    
    // Ajouter les paramètres de requête
    \$query = '';
    foreach (\$_GET as \$key => \$value) {
        if (\$key !== 'endpoint') {
            \$query .= (\$query === '' ? '?' : '&') . \$key . '=' . urlencode(\$value);
        }
    }
    \$targetUrl .= \$query;
} else {
    // Utiliser le routeur par défaut
    \$targetUrl .= '/' . \$ROUTER_PATH;
}

error_log("URL cible: " . \$targetUrl);

// Initialiser cURL
\$ch = curl_init(\$targetUrl);

// Configuration de base
curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt(\$ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt(\$ch, CURLOPT_TIMEOUT, 30);
curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, \$_SERVER['REQUEST_METHOD']);
curl_setopt(\$ch, CURLOPT_ENCODING, '');

// Transmettre les en-têtes pertinents
\$headers = [];
foreach (getallheaders() as \$name => \$value) {
    if (strtolower(\$name) !== 'host') {
        \$headers[] = \$name . ': ' . \$value;
    }
}
\$headers[] = 'X-Generated-Proxy: true';
curl_setopt(\$ch, CURLOPT_HTTPHEADER, \$headers);

// Transmettre le corps pour POST/PUT
if (\$_SERVER['REQUEST_METHOD'] === 'POST' || \$_SERVER['REQUEST_METHOD'] === 'PUT') {
    \$input = file_get_contents('php://input');
    curl_setopt(\$ch, CURLOPT_POSTFIELDS, \$input);
    error_log("Corps de requête: " . substr(\$input, 0, 200));
}

// Récupérer l'en-tête et le corps
curl_setopt(\$ch, CURLOPT_HEADER, true);

// Exécuter la requête
\$response = curl_exec(\$ch);
\$error = curl_error(\$ch);

// Gérer les erreurs
if (\$response === false) {
    error_log("Erreur cURL: " . \$error);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de connexion: ' . \$error
    ]);
    exit;
}

// Récupérer les informations
\$headerSize = curl_getinfo(\$ch, CURLINFO_HEADER_SIZE);
\$httpCode = curl_getinfo(\$ch, CURLINFO_HTTP_CODE);

// Fermer cURL
curl_close(\$ch);

// Séparer l'en-tête et le corps
\$headerText = substr(\$response, 0, \$headerSize);
\$body = substr(\$response, \$headerSize);

// Définir le code de statut
http_response_code(\$httpCode);

// Renvoyer la réponse
echo \$body;
EOT;

	// Écrire le fichier
	$fullPath = __DIR__ . '/' . $proxyName;
	$result = file_put_contents($fullPath, $proxyContent);

	if ($result === false) {
		return [
			'success' => false,
			'message' => 'Impossible d\'écrire le fichier proxy.'
		];
	}

	return [
		'success' => true,
		'message' => 'Proxy personnalisé généré avec succès.',
		'proxy_name' => $proxyName,
		'file_path' => $fullPath,
		'config' => $config
	];
}

// Traiter l'action de génération de proxy
if ($action === 'generate_proxy') {
	$config = [
		'api_base' => $_POST['api_base'] ?? 'https://app-backend-esgi-app.azurewebsites.net',
		'auth_path' => $_POST['auth_path'] ?? 'api-auth-login.php',
		'status_path' => $_POST['status_path'] ?? 'status.php',
		'notes_path' => $_POST['notes_path'] ?? 'api-notes.php',
		'router_path' => $_POST['router_path'] ?? 'api-router.php',
		'proxy_name' => $_POST['proxy_name'] ?? 'custom-proxy.php',
		'date' => date('Y-m-d H:i:s')
	];

	$result = generateProxy($config);

	header('Content-Type: application/json');
	echo json_encode($result);
	exit;
}

// Afficher l'interface utilisateur
?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Générateur de Proxy</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			max-width: 800px;
			margin: 0 auto;
			padding: 20px;
		}

		.card {
			border: 1px solid #ddd;
			border-radius: 8px;
			padding: 20px;
			margin-bottom: 20px;
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
		}

		h1,
		h2 {
			color: #333;
		}

		.form-group {
			margin-bottom: 15px;
		}

		label {
			display: block;
			margin-bottom: 5px;
			font-weight: bold;
		}

		input[type="text"] {
			width: 100%;
			padding: 8px;
			border: 1px solid #ddd;
			border-radius: 4px;
		}

		button {
			background-color: #4CAF50;
			color: white;
			border: none;
			padding: 10px 15px;
			border-radius: 4px;
			cursor: pointer;
			font-size: 16px;
		}

		button:hover {
			background-color: #45a049;
		}

		.success {
			color: #28a745;
			font-weight: bold;
		}

		.error {
			color: #dc3545;
			font-weight: bold;
		}

		#result {
			margin-top: 20px;
			padding: 15px;
			display: none;
		}
	</style>
</head>

<body>
	<h1>Générateur de Proxy</h1>

	<div class="card">
		<h2>Configuration du Proxy</h2>
		<p>Configurez les chemins d'API pour générer un proxy personnalisé adapté à votre backend.</p>

		<form id="proxyForm">
			<div class="form-group">
				<label for="api_base">URL de base de l'API:</label>
				<input type="text" id="api_base" name="api_base" value="https://app-backend-esgi-app.azurewebsites.net">
			</div>

			<div class="form-group">
				<label for="auth_path">Chemin d'authentification:</label>
				<input type="text" id="auth_path" name="auth_path" value="api-auth-login.php">
			</div>

			<div class="form-group">
				<label for="status_path">Chemin de statut:</label>
				<input type="text" id="status_path" name="status_path" value="status.php">
			</div>

			<div class="form-group">
				<label for="notes_path">Chemin des notes/matières:</label>
				<input type="text" id="notes_path" name="notes_path" value="api-notes.php">
			</div>

			<div class="form-group">
				<label for="router_path">Chemin du routeur API:</label>
				<input type="text" id="router_path" name="router_path" value="api-router.php">
			</div>

			<div class="form-group">
				<label for="proxy_name">Nom du fichier proxy:</label>
				<input type="text" id="proxy_name" name="proxy_name" value="custom-proxy.php">
			</div>

			<button type="button" id="generateButton">Générer le Proxy</button>
		</form>
	</div>

	<div id="result" class="card">
		<h2>Résultat</h2>
		<div id="resultMessage"></div>
	</div>

	<div class="card">
		<h2>Comment utiliser</h2>
		<ol>
			<li>Configurez les chemins d'API en fonction des résultats de l'explorateur de backend</li>
			<li>Cliquez sur "Générer le Proxy" pour créer un proxy personnalisé</li>
			<li>Utilisez le proxy généré dans votre application frontend</li>
			<li>Exemple d'utilisation : <code>fetch('custom-proxy.php?endpoint=login')</code></li>
		</ol>
	</div>

	<script>
		document.getElementById('generateButton').addEventListener('click', async function() {
			const form = document.getElementById('proxyForm');
			const formData = new FormData(form);
			formData.append('action', 'generate_proxy');

			const resultDiv = document.getElementById('result');
			const resultMessage = document.getElementById('resultMessage');

			try {
				const response = await fetch('proxy-generator.php', {
					method: 'POST',
					body: formData
				});

				const data = await response.json();

				resultDiv.style.display = 'block';

				if (data.success) {
					resultMessage.innerHTML = `
                        <p class="success">${data.message}</p>
                        <p>Proxy généré : <strong>${data.proxy_name}</strong></p>
                        <p>Pour utiliser ce proxy :</p>
                        <pre>fetch('${data.proxy_name}?endpoint=login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        email: 'user@example.com',
        password: 'password'
    })
});</pre>
                    `;
				} else {
					resultMessage.innerHTML = `<p class="error">${data.message}</p>`;
				}
			} catch (error) {
				resultDiv.style.display = 'block';
				resultMessage.innerHTML = `<p class="error">Erreur: ${error.message}</p>`;
			}
		});
	</script>
</body>

</html>