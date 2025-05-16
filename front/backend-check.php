<?php

/**
 * Backend Check - Vérification détaillée du backend
 * 
 * Cet outil teste la connectivité et la structure du backend
 * pour identifier les problèmes potentiels.
 */

// En-têtes pour la sortie
header('Content-Type: text/html; charset=utf-8');

// Configuration backend
$backend_url = 'https://app-backend-esgi-app.azurewebsites.net';
$timeout = 10; // secondes

// Structure du site à explorer
$paths_to_check = [
	// Dossiers racine courants
	'/',
	'/api',
	'/api/',
	'/auth',
	'/public',
	'/assets',

	// Fichiers spécifiques
	'/index.php',
	'/status.php',
	'/api/index.php',

	// Fichiers de configuration accessibles
	'/web.config',
	'/.htaccess',
	'/robots.txt',
	'/sitemap.xml',

	// Points d'entrée potentiels
	'/api-documentation',
	'/swagger',
	'/docs',
	'/api/docs'
];

// Fonction pour tester un chemin
function test_path($base_url, $path)
{
	$url = $base_url . $path;
	$start_time = microtime(true);

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_NOBODY, false);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Backend-Check/1.0');

	$response = curl_exec($ch);
	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$time = round((microtime(true) - $start_time) * 1000, 2); // en ms
	$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
	$size = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
	$error = curl_error($ch);

	// Séparer l'en-tête et le corps
	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$header = substr($response, 0, $header_size);
	$body = substr($response, $header_size);

	curl_close($ch);

	// Déterminer le type de contenu
	$type = 'unknown';
	if (strpos($content_type, 'text/html') !== false) {
		$type = 'html';
	} elseif (strpos($content_type, 'application/json') !== false) {
		$type = 'json';
	} elseif (strpos($content_type, 'text/plain') !== false) {
		$type = 'text';
	} elseif (strpos($content_type, 'text/xml') !== false || strpos($content_type, 'application/xml') !== false) {
		$type = 'xml';
	}

	// Extraire les infos des en-têtes
	$headers = [];
	foreach (explode("\r\n", $header) as $line) {
		if (strpos($line, ':') !== false) {
			list($key, $value) = explode(':', $line, 2);
			$headers[trim($key)] = trim($value);
		}
	}

	// Extraire le titre des pages HTML
	$title = '';
	if ($type === 'html' && preg_match('/<title>(.*?)<\/title>/i', $body, $matches)) {
		$title = $matches[1];
	}

	// Parser le JSON si c'est du JSON
	$json_data = null;
	if ($type === 'json') {
		try {
			$json_data = json_decode($body, true);
		} catch (Exception $e) {
			// Ignorer si le JSON est invalide
		}
	}

	return [
		'url' => $url,
		'path' => $path,
		'status' => $status,
		'success' => ($status >= 200 && $status < 400),
		'time' => $time,
		'size' => $size,
		'content_type' => $content_type,
		'type' => $type,
		'title' => $title,
		'headers' => $headers,
		'body' => substr($body, 0, 500), // Limiter la taille
		'json_data' => $json_data,
		'error' => $error
	];
}

// Test de ping basique du backend
function ping_backend($url)
{
	$start_time = microtime(true);
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_exec($ch);
	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$time = round((microtime(true) - $start_time) * 1000, 2); // en ms
	$error = curl_error($ch);
	curl_close($ch);

	return [
		'accessible' => ($status > 0),
		'status' => $status,
		'time' => $time,
		'error' => $error
	];
}

// Test de l'existence du backend
$ping_result = ping_backend($backend_url);

// Tester les paths si le backend répond
$results = [];
if ($ping_result['accessible']) {
	foreach ($paths_to_check as $path) {
		$results[] = test_path($backend_url, $path);
	}
}

// Identifier les chemins qui ont fonctionné
$working_paths = array_filter($results, function ($result) {
	return $result['success'];
});

// Identifier les structures potentielles d'API
$api_structure = [];
foreach ($results as $result) {
	if (strpos($result['path'], '/api') === 0 && $result['success']) {
		$api_structure[] = $result;
	}
}

// Identifier les endpoints JSON (probablement des APIs)
$json_endpoints = array_filter($results, function ($result) {
	return $result['type'] === 'json' && $result['success'];
});

// Analyser les redirections
$redirects = array_filter($results, function ($result) {
	return isset($result['headers']['Location']);
});

?>
<!DOCTYPE html>
<html>

<head>
	<title>Diagnostic du Backend</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<style>
		body {
			font-family: Arial, sans-serif;
			line-height: 1.6;
			color: #333;
			max-width: 1200px;
			margin: 0 auto;
			padding: 20px;
		}

		h1,
		h2,
		h3 {
			color: #0078D4;
		}

		pre {
			background-color: #f5f5f5;
			padding: 10px;
			border-radius: 5px;
			overflow-x: auto;
		}

		.card {
			border: 1px solid #ddd;
			border-radius: 5px;
			padding: 15px;
			margin-bottom: 15px;
			background-color: #fff;
		}

		.success {
			color: green;
		}

		.warning {
			color: orange;
		}

		.error {
			color: red;
		}

		.status-badge {
			display: inline-block;
			padding: 3px 8px;
			border-radius: 3px;
			font-size: 12px;
			font-weight: bold;
			color: white;
		}

		.status-2xx {
			background-color: #4caf50;
		}

		.status-3xx {
			background-color: #ff9800;
		}

		.status-4xx {
			background-color: #f44336;
		}

		.status-5xx {
			background-color: #9c27b0;
		}

		table {
			width: 100%;
			border-collapse: collapse;
		}

		table,
		th,
		td {
			border: 1px solid #ddd;
		}

		th,
		td {
			padding: 8px;
			text-align: left;
		}

		th {
			background-color: #f2f2f2;
		}

		.results-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
			grid-gap: 15px;
		}

		.panel {
			border: 1px solid #ddd;
			border-radius: 5px;
			margin-bottom: 20px;
		}

		.panel-header {
			background-color: #f8f8f8;
			padding: 10px 15px;
			border-bottom: 1px solid #ddd;
			font-weight: bold;
		}

		.panel-body {
			padding: 15px;
		}

		.response-preview {
			max-height: 200px;
			overflow-y: auto;
			font-family: monospace;
			font-size: 12px;
			white-space: pre-wrap;
			word-break: break-all;
		}
	</style>
</head>

<body>
	<h1>Diagnostic du Backend</h1>
	<p>Cet outil analyse la structure et l'accessibilité du backend.</p>

	<div class="panel">
		<div class="panel-header">Résultat du ping</div>
		<div class="panel-body">
			<p>URL testée: <code><?php echo $backend_url; ?></code></p>

			<?php if ($ping_result['accessible']): ?>
				<p class="success">✓ Le backend est accessible</p>
				<ul>
					<li>Status: <?php echo $ping_result['status']; ?></li>
					<li>Temps de réponse: <?php echo $ping_result['time']; ?> ms</li>
				</ul>
			<?php else: ?>
				<p class="error">✗ Le backend n'est pas accessible</p>
				<p>Erreur: <?php echo $ping_result['error'] ?: 'Aucune réponse'; ?></p>
				<p>Vérifiez que l'URL est correcte et que le service est en cours d'exécution.</p>
			<?php endif; ?>
		</div>
	</div>

	<?php if ($ping_result['accessible']): ?>

		<h2>Chemins accessibles</h2>
		<?php if (count($working_paths) > 0): ?>
			<table>
				<tr>
					<th>Chemin</th>
					<th>Status</th>
					<th>Type</th>
					<th>Temps</th>
					<th>Taille</th>
				</tr>
				<?php foreach ($working_paths as $result): ?>
					<tr>
						<td><a href="<?php echo $result['url']; ?>" target="_blank"><?php echo $result['path']; ?></a></td>
						<td>
							<?php
							$status_class = '';
							if ($result['status'] >= 200 && $result['status'] < 300) $status_class = 'status-2xx';
							else if ($result['status'] >= 300 && $result['status'] < 400) $status_class = 'status-3xx';
							else if ($result['status'] >= 400 && $result['status'] < 500) $status_class = 'status-4xx';
							else if ($result['status'] >= 500) $status_class = 'status-5xx';
							?>
							<span class="status-badge <?php echo $status_class; ?>"><?php echo $result['status']; ?></span>
						</td>
						<td><?php echo $result['type']; ?></td>
						<td><?php echo $result['time']; ?> ms</td>
						<td><?php echo $result['size']; ?> bytes</td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php else: ?>
			<p class="warning">Aucun chemin accessible trouvé.</p>
		<?php endif; ?>

		<h2>Structure de l'API</h2>
		<?php if (count($api_structure) > 0): ?>
			<div class="results-grid">
				<?php foreach ($api_structure as $result): ?>
					<div class="card">
						<h3><?php echo $result['path']; ?></h3>
						<p>Status: <span class="status-badge <?php echo $result['status'] < 400 ? 'status-2xx' : 'status-4xx'; ?>"><?php echo $result['status']; ?></span></p>
						<p>Type: <?php echo $result['type']; ?></p>
						<?php if ($result['type'] === 'json' && $result['json_data']): ?>
							<div class="response-preview">
								<?php echo htmlspecialchars(json_encode($result['json_data'], JSON_PRETTY_PRINT)); ?>
							</div>
						<?php else: ?>
							<div class="response-preview">
								<?php echo htmlspecialchars($result['body']); ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php else: ?>
			<p class="warning">Aucune structure d'API détectée.</p>
		<?php endif; ?>

		<h2>Résultats détaillés</h2>
		<div class="results-grid">
			<?php foreach ($results as $result): ?>
				<div class="card">
					<h3><?php echo $result['path']; ?></h3>
					<p>Status: <span class="status-badge <?php echo $result['status'] < 400 ? 'status-2xx' : 'status-4xx'; ?>"><?php echo $result['status']; ?></span></p>
					<p>Type: <?php echo $result['content_type']; ?></p>
					<?php if ($result['title']): ?>
						<p>Titre: <?php echo $result['title']; ?></p>
					<?php endif; ?>
					<?php if (isset($result['headers']['Location'])): ?>
						<p>Redirection vers: <?php echo $result['headers']['Location']; ?></p>
					<?php endif; ?>
					<div class="response-preview">
						<?php echo htmlspecialchars($result['body']); ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<h2>Recommandations</h2>
		<div class="panel">
			<div class="panel-header">Actions recommandées</div>
			<div class="panel-body">
				<ul>
					<?php if (count($json_endpoints) === 0): ?>
						<li class="error">Aucun endpoint JSON trouvé. Vérifiez que votre API est configurée correctement et renvoie des réponses JSON.</li>
					<?php endif; ?>

					<?php if (count($working_paths) === 0): ?>
						<li class="error">Aucun chemin ne fonctionne. Vérifiez que le backend est correctement déployé.</li>
					<?php else: ?>
						<li class="success">Le backend répond. Utilisez les chemins qui fonctionnent pour configurer votre frontend.</li>
					<?php endif; ?>

					<?php if (array_filter($results, function ($r) {
						return $r['path'] === '/api' && $r['success'];
					})): ?>
						<li class="success">Le chemin /api est accessible. Assurez-vous d'utiliser ce préfixe pour vos appels d'API.</li>
					<?php endif; ?>

					<?php if (array_filter($results, function ($r) {
						return $r['status'] >= 500;
					})): ?>
						<li class="error">Certains endpoints renvoient des erreurs 5xx. Le backend peut avoir des problèmes internes.</li>
					<?php endif; ?>
				</ul>

				<h3>URL pour votre configuration frontend</h3>
				<p>Basé sur les tests, voici les URL recommandées pour votre configuration:</p>

				<?php if (count($working_paths) > 0): ?>
					<pre>
// URL de base
backendBaseUrl: "<?php echo $backend_url; ?>"

// URL de base de l'API (à ajuster selon votre structure)
<?php if (array_filter($results, function ($r) {
						return $r['path'] === '/api' && $r['success'];
					})): ?>
apiBaseUrl: "<?php echo $backend_url; ?>/api"
<?php else: ?>
apiBaseUrl: "<?php echo $backend_url; ?>"
<?php endif; ?>

// Endpoint d'authentification (à vérifier)
authEndpoint: "auth/login" // ou "api/auth/login", "login", etc.
                    </pre>
				<?php endif; ?>
			</div>
		</div>

	<?php endif; ?>

	<div class="panel">
		<div class="panel-header">Étapes suivantes</div>
		<div class="panel-body">
			<p>Basé sur ces résultats, vérifiez les points suivants:</p>
			<ol>
				<li>Consultez la documentation de votre API pour connaître les endpoints exacts à utiliser</li>
				<li>Vérifiez que les configurations CORS sont correctes sur le backend</li>
				<li>Confirmez la structure des URLs d'API et la manière dont elles doivent être appelées</li>
				<li>Testez manuellement les endpoints d'authentification pour vérifier leur format exact</li>
			</ol>
			<p><a href="login.php">Retourner à la page de connexion</a></p>
		</div>
	</div>
</body>

</html>