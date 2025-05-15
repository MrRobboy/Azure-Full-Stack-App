<?php

/**
 * Server Diagnostics Script
 * 
 * Ce script permet de diagnostiquer la configuration serveur et l'environnement PHP
 * pour identifier d'éventuels problèmes de déploiement sur Azure App Service.
 */

// Paramétrer les en-têtes pour la réponse
header('Content-Type: text/html; charset=utf-8');

// Fonction pour récupérer l'adresse IP du client
function getClientIP()
{
	$keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
	foreach ($keys as $key) {
		if (array_key_exists($key, $_SERVER)) {
			foreach (explode(',', $_SERVER[$key]) as $ip) {
				$ip = trim($ip);
				if (filter_var($ip, FILTER_VALIDATE_IP)) {
					return $ip;
				}
			}
		}
	}
	return 'Unknown';
}

// Fonction pour récupérer les en-têtes de la requête
function getRequestHeaders()
{
	$headers = [];
	foreach ($_SERVER as $key => $value) {
		if (substr($key, 0, 5) === 'HTTP_') {
			$headerName = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
			$headers[$headerName] = $value;
		}
	}
	return $headers;
}

// Fonction pour vérifier les extensions PHP
function checkExtensions()
{
	$requiredExtensions = [
		'pdo' => 'PDO (Database)',
		'pdo_sqlsrv' => 'PDO SQL Server Driver',
		'sqlsrv' => 'SQL Server Driver',
		'openssl' => 'OpenSSL',
		'curl' => 'cURL',
		'json' => 'JSON',
		'mbstring' => 'Multibyte String',
		'xml' => 'XML'
	];

	$results = [];
	foreach ($requiredExtensions as $ext => $name) {
		$results[$ext] = [
			'name' => $name,
			'installed' => extension_loaded($ext)
		];
	}

	return $results;
}

// Fonction pour tester la connectivité réseau
function testConnectivity()
{
	$targets = [
		'app-frontend-esgi-app.azurewebsites.net' => 'Frontend Azure App',
		'app-backend-esgi-app.azurewebsites.net' => 'Backend Azure App',
		'github.com' => 'GitHub',
		'azure-sql-server.database.windows.net' => 'Azure SQL Database',
		'registry.npmjs.org' => 'NPM Registry'
	];

	$results = [];
	foreach ($targets as $host => $name) {
		$port = 443; // HTTPS par défaut
		$timeout = 5; // 5 secondes de timeout

		$startTime = microtime(true);
		$connection = @fsockopen('ssl://' . $host, $port, $errno, $errstr, $timeout);
		$endTime = microtime(true);

		if ($connection) {
			fclose($connection);
			$results[$host] = [
				'name' => $name,
				'connected' => true,
				'time' => round(($endTime - $startTime) * 1000, 2) . ' ms'
			];
		} else {
			$results[$host] = [
				'name' => $name,
				'connected' => false,
				'error' => "($errno) $errstr"
			];
		}
	}

	return $results;
}

// Collecter les données de diagnostic
$diagnosticInfo = [
	'server' => [
		'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
		'name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
		'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown',
		'port' => $_SERVER['SERVER_PORT'] ?? 'Unknown',
		'time' => date('Y-m-d H:i:s'),
		'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
		'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'Unknown',
		'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
		'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
		'remote_addr' => getClientIP(),
		'https' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
	],
	'php' => [
		'version' => phpversion(),
		'sapi' => php_sapi_name(),
		'extensions' => checkExtensions(),
		'upload_max_filesize' => ini_get('upload_max_filesize'),
		'post_max_size' => ini_get('post_max_size'),
		'memory_limit' => ini_get('memory_limit'),
		'max_execution_time' => ini_get('max_execution_time'),
		'display_errors' => ini_get('display_errors'),
		'timezone' => date_default_timezone_get()
	],
	'request' => [
		'headers' => getRequestHeaders(),
		'get' => $_GET,
		'post' => count($_POST) > 0 ? 'POST data exists' : 'No POST data'
	],
	'connectivity' => testConnectivity(),
	'environment' => [
		'app_environment' => getenv('APP_ENV') ?: 'Not set',
		'azure_website_name' => getenv('WEBSITE_SITE_NAME') ?: 'Not set',
		'azure_instance' => getenv('WEBSITE_INSTANCE_ID') ?: 'Not set',
		'temp_dir' => sys_get_temp_dir(),
		'is_azure' => (bool)(getenv('WEBSITE_SITE_NAME'))
	]
];

// Si la requête est faite en AJAX ou avec un paramètre format=json, retourner JSON
if ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')
	|| (isset($_GET['format']) && $_GET['format'] === 'json')
) {
	header('Content-Type: application/json');
	echo json_encode($diagnosticInfo);
	exit;
}

// Fonction pour formater les tableaux en HTML
function formatArray($array, $depth = 0)
{
	if (!is_array($array)) {
		return '<span class="value">' . htmlspecialchars(var_export($array, true)) . '</span>';
	}

	$output = '<ul class="array-list">';
	foreach ($array as $key => $value) {
		$output .= '<li>';
		$output .= '<span class="key">' . htmlspecialchars($key) . ':</span> ';

		if (is_array($value)) {
			$output .= formatArray($value, $depth + 1);
		} else {
			$output .= '<span class="value ' . getValueClass($value) . '">' . htmlspecialchars(var_export($value, true)) . '</span>';
		}

		$output .= '</li>';
	}
	$output .= '</ul>';

	return $output;
}

function getValueClass($value)
{
	if (is_bool($value)) {
		return $value ? 'status-ok' : 'status-error';
	}

	if (is_string($value)) {
		if ($value === 'OK' || $value === 'Yes' || $value === 'Enabled') {
			return 'status-ok';
		}
		if ($value === 'Error' || $value === 'No' || $value === 'Disabled') {
			return 'status-error';
		}
	}

	return '';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Diagnostics Serveur - Azure App Service</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			line-height: 1.6;
			color: #333;
			max-width: 1200px;
			margin: 0 auto;
			padding: 20px;
			background-color: #f5f5f5;
		}

		h1,
		h2,
		h3 {
			color: #0078D7;
		}

		header {
			background-color: #0078D7;
			color: white;
			padding: 1rem;
			margin-bottom: 2rem;
			border-radius: 4px;
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
		}

		header h1 {
			margin: 0;
			color: white;
		}

		section {
			background: white;
			margin-bottom: 2rem;
			padding: 1.5rem;
			border-radius: 4px;
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
		}

		.array-list {
			list-style: none;
			padding-left: 20px;
			margin: 0;
		}

		.key {
			font-weight: bold;
			color: #0078D7;
		}

		.value {
			font-family: monospace;
		}

		.status-ok {
			color: #107C10;
		}

		.status-error {
			color: #D83B01;
		}

		table {
			width: 100%;
			border-collapse: collapse;
			margin-bottom: 1rem;
		}

		th,
		td {
			text-align: left;
			padding: 0.5rem;
			border-bottom: 1px solid #e5e5e5;
		}

		th {
			background-color: #f9f9f9;
		}

		tr:hover {
			background-color: #f5f5f5;
		}

		.extensions-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
			gap: 1rem;
		}

		.extension-card {
			border: 1px solid #e5e5e5;
			border-radius: 4px;
			padding: 1rem;
			display: flex;
			align-items: center;
		}

		.extension-status {
			margin-right: 1rem;
			font-size: 1.5rem;
		}

		.connectivity-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
			gap: 1rem;
		}

		.connectivity-card {
			border: 1px solid #e5e5e5;
			border-radius: 4px;
			padding: 1rem;
		}

		.server-info {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
			gap: 1rem;
		}

		.server-info-item {
			background-color: #f9f9f9;
			padding: 1rem;
			border-radius: 4px;
		}

		.server-info-item h3 {
			margin-top: 0;
			font-size: 1rem;
			color: #555;
		}

		.server-info-item p {
			margin-bottom: 0;
			font-size: 1.1rem;
			font-weight: bold;
		}

		footer {
			margin-top: 2rem;
			text-align: center;
			color: #666;
			font-size: 0.9rem;
		}
	</style>
</head>

<body>
	<header>
		<h1>Diagnostics Serveur - Azure App Service</h1>
		<p>Cet outil analyse la configuration de votre environnement PHP et Azure.</p>
	</header>

	<section>
		<h2>Informations Serveur</h2>
		<div class="server-info">
			<div class="server-info-item">
				<h3>Logiciel Serveur</h3>
				<p><?php echo htmlspecialchars($diagnosticInfo['server']['software']); ?></p>
			</div>
			<div class="server-info-item">
				<h3>Nom du Serveur</h3>
				<p><?php echo htmlspecialchars($diagnosticInfo['server']['name']); ?></p>
			</div>
			<div class="server-info-item">
				<h3>Port</h3>
				<p><?php echo htmlspecialchars($diagnosticInfo['server']['port']); ?></p>
			</div>
			<div class="server-info-item">
				<h3>HTTPS</h3>
				<p class="<?php echo $diagnosticInfo['server']['https'] ? 'status-ok' : 'status-error'; ?>">
					<?php echo $diagnosticInfo['server']['https'] ? 'Activé' : 'Désactivé'; ?>
				</p>
			</div>
			<div class="server-info-item">
				<h3>Date/Heure Serveur</h3>
				<p><?php echo htmlspecialchars($diagnosticInfo['server']['time']); ?></p>
			</div>
			<div class="server-info-item">
				<h3>Adresse IP Client</h3>
				<p><?php echo htmlspecialchars($diagnosticInfo['server']['remote_addr']); ?></p>
			</div>
		</div>

		<h3>Détails Serveur</h3>
		<?php echo formatArray($diagnosticInfo['server']); ?>
	</section>

	<section>
		<h2>Configuration PHP</h2>
		<div class="server-info">
			<div class="server-info-item">
				<h3>Version PHP</h3>
				<p><?php echo htmlspecialchars($diagnosticInfo['php']['version']); ?></p>
			</div>
			<div class="server-info-item">
				<h3>Interface SAPI</h3>
				<p><?php echo htmlspecialchars($diagnosticInfo['php']['sapi']); ?></p>
			</div>
			<div class="server-info-item">
				<h3>Limite Mémoire</h3>
				<p><?php echo htmlspecialchars($diagnosticInfo['php']['memory_limit']); ?></p>
			</div>
			<div class="server-info-item">
				<h3>Temps Max Exécution</h3>
				<p><?php echo htmlspecialchars($diagnosticInfo['php']['max_execution_time']); ?> secondes</p>
			</div>
			<div class="server-info-item">
				<h3>Taille Max Upload</h3>
				<p><?php echo htmlspecialchars($diagnosticInfo['php']['upload_max_filesize']); ?></p>
			</div>
			<div class="server-info-item">
				<h3>Taille Max POST</h3>
				<p><?php echo htmlspecialchars($diagnosticInfo['php']['post_max_size']); ?></p>
			</div>
		</div>

		<h3>Extensions PHP</h3>
		<div class="extensions-grid">
			<?php foreach ($diagnosticInfo['php']['extensions'] as $ext => $info): ?>
				<div class="extension-card">
					<div class="extension-status <?php echo $info['installed'] ? 'status-ok' : 'status-error'; ?>">
						<?php echo $info['installed'] ? '✓' : '✗'; ?>
					</div>
					<div>
						<strong><?php echo htmlspecialchars($info['name']); ?></strong>
						<div><?php echo $info['installed'] ? 'Installée' : 'Non installée'; ?></div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</section>

	<section>
		<h2>Connectivité Réseau</h2>
		<div class="connectivity-grid">
			<?php foreach ($diagnosticInfo['connectivity'] as $host => $info): ?>
				<div class="connectivity-card">
					<h3><?php echo htmlspecialchars($info['name']); ?></h3>
					<p class="<?php echo $info['connected'] ? 'status-ok' : 'status-error'; ?>">
						<?php if ($info['connected']): ?>
							✓ Connecté (<?php echo htmlspecialchars($info['time']); ?>)
						<?php else: ?>
							✗ Échec (<?php echo htmlspecialchars($info['error']); ?>)
						<?php endif; ?>
					</p>
					<div class="host"><?php echo htmlspecialchars($host); ?></div>
				</div>
			<?php endforeach; ?>
		</div>
	</section>

	<section>
		<h2>Variables d'Environnement Azure</h2>
		<?php echo formatArray($diagnosticInfo['environment']); ?>
	</section>

	<section>
		<h2>En-têtes de Requête</h2>
		<?php echo formatArray($diagnosticInfo['request']['headers']); ?>
	</section>

	<footer>
		<p>Azure App Service Diagnostics - <?php echo date('Y-m-d H:i:s'); ?></p>
		<p>
			<a href="?format=json">Voir en JSON</a> |
			<a href="https://learn.microsoft.com/fr-fr/azure/app-service/overview" target="_blank">Documentation Azure App Service</a>
		</p>
	</footer>
</body>

</html>