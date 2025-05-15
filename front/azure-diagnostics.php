<?php
// Azure Diagnostics Tool
error_reporting(E_ALL);
ini_set("display_errors", 1);
header("Content-Type: text/html; charset=UTF-8");

function checkPath($path)
{
	$exists = file_exists($path);
	$size = $exists ? filesize($path) : 0;
	$readable = $exists ? is_readable($path) : false;
	$writable = $exists ? is_writable($path) : false;
	$perms = $exists ? substr(sprintf('%o', fileperms($path)), -4) : 'none';

	return [
		'path' => $path,
		'exists' => $exists,
		'size' => $size,
		'readable' => $readable,
		'writable' => $writable,
		'permissions' => $perms
	];
}

// Check server info
$serverInfo = [
	'php_version' => PHP_VERSION,
	'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
	'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
	'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
	'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'unknown',
	'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
	'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
	'is_azure' => strpos($_SERVER['HTTP_HOST'] ?? '', 'azurewebsites.net') !== false,
	'time' => date('Y-m-d H:i:s')
];

// Check proxy files
$proxyFiles = [
	'simple-proxy.php',
	'api-bridge.php',
	'direct-login.php',
	'local-proxy.php',
	'local-proxy-fix.php',
	'status.php',
	'login.php',
	'index.php',
	'dashboard.php',
	'proxy-test.php',
	'/simple-proxy.php',
	'/api-bridge.php',
	'/direct-login.php',
	'/local-proxy.php',
	'/local-proxy-fix.php'
];

$fileResults = [];
foreach ($proxyFiles as $file) {
	$fileResults[$file] = checkPath(__DIR__ . '/' . $file);
}

// Test URL access
$urlsToTest = [
	'login.php',
	'index.php',
	'simple-proxy.php',
	'api-bridge.php',
	'direct-login.php',
	'local-proxy.php',
	'local-proxy-fix.php'
];

$urlResults = [];
foreach ($urlsToTest as $url) {
	$fullUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/' . $url;
	$ch = curl_init($fullUrl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_exec($ch);
	$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	$urlResults[$url] = [
		'url' => $fullUrl,
		'status_code' => $statusCode,
		'accessible' => $statusCode >= 200 && $statusCode < 400
	];
}

// Create test proxy file if it doesn't exist
$fixProxyPath = __DIR__ . '/local-proxy-fix.php';
if (!file_exists($fixProxyPath)) {
	$fixProxyContent = file_get_contents(__DIR__ . '/local-proxy-fix.php');
	if (file_put_contents($fixProxyPath, $fixProxyContent)) {
		$fileResults['local-proxy-fix.php'] = checkPath($fixProxyPath);
	}
}

// Try to create files in multiple locations
$directories = ['', '/api', '/proxy'];
$createdFiles = [];

foreach ($directories as $dir) {
	$dirPath = __DIR__ . $dir;
	if (!is_dir($dirPath) && $dir !== '') {
		if (@mkdir($dirPath, 0755, true)) {
			$createdFiles[] = "Created directory: $dir";
		}
	}

	if (is_dir($dirPath) || $dir === '') {
		$testFile = $dirPath . '/test-' . time() . '.txt';
		if (@file_put_contents($testFile, 'Test file')) {
			$createdFiles[] = "Created test file: $testFile";
			@unlink($testFile);
		}
	}
}

// Generate HTML response
?>
<!DOCTYPE html>
<html>

<head>
	<title>Azure Diagnostics Tool</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			margin: 20px;
		}

		h1,
		h2 {
			color: #0078D4;
		}

		.card {
			border: 1px solid #ddd;
			border-radius: 4px;
			padding: 15px;
			margin-bottom: 20px;
		}

		table {
			border-collapse: collapse;
			width: 100%;
		}

		th,
		td {
			border: 1px solid #ddd;
			padding: 8px;
			text-align: left;
		}

		th {
			background-color: #f2f2f2;
		}

		.success {
			color: green;
		}

		.error {
			color: red;
		}

		.warning {
			color: orange;
		}

		.actions {
			margin-top: 20px;
			padding: 15px;
			background-color: #f8f8f8;
			border-radius: 4px;
		}

		.btn {
			padding: 8px 16px;
			background-color: #0078D4;
			color: white;
			border: none;
			border-radius: 4px;
			cursor: pointer;
		}
	</style>
</head>

<body>
	<h1>Azure Diagnostics Tool</h1>

	<div class="card">
		<h2>Server Information</h2>
		<table>
			<?php foreach ($serverInfo as $key => $value): ?>
				<tr>
					<th><?= htmlspecialchars($key) ?></th>
					<td><?= htmlspecialchars($value) ?></td>
				</tr>
			<?php endforeach; ?>
		</table>
	</div>

	<div class="card">
		<h2>File System Check</h2>
		<table>
			<tr>
				<th>File</th>
				<th>Exists</th>
				<th>Size</th>
				<th>Readable</th>
				<th>Writable</th>
				<th>Permissions</th>
				<th>Path</th>
			</tr>
			<?php foreach ($fileResults as $file => $result): ?>
				<tr>
					<td><?= htmlspecialchars($file) ?></td>
					<td class="<?= $result['exists'] ? 'success' : 'error' ?>"><?= $result['exists'] ? 'Yes' : 'No' ?></td>
					<td><?= $result['size'] ?> bytes</td>
					<td class="<?= $result['readable'] ? 'success' : 'error' ?>"><?= $result['readable'] ? 'Yes' : 'No' ?></td>
					<td class="<?= $result['writable'] ? 'success' : 'error' ?>"><?= $result['writable'] ? 'Yes' : 'No' ?></td>
					<td><?= $result['permissions'] ?></td>
					<td><?= htmlspecialchars($result['path']) ?></td>
				</tr>
			<?php endforeach; ?>
		</table>
	</div>

	<div class="card">
		<h2>URL Access Check</h2>
		<table>
			<tr>
				<th>URL</th>
				<th>Status Code</th>
				<th>Accessible</th>
			</tr>
			<?php foreach ($urlResults as $url => $result): ?>
				<tr>
					<td><a href="<?= htmlspecialchars($result['url']) ?>" target="_blank"><?= htmlspecialchars($url) ?></a></td>
					<td class="<?= $result['status_code'] < 400 ? 'success' : 'error' ?>"><?= $result['status_code'] ?></td>
					<td class="<?= $result['accessible'] ? 'success' : 'error' ?>"><?= $result['accessible'] ? 'Yes' : 'No' ?></td>
				</tr>
			<?php endforeach; ?>
		</table>
	</div>

	<div class="card">
		<h2>File Creation Tests</h2>
		<?php if (empty($createdFiles)): ?>
			<p class="error">No files could be created. Check permissions.</p>
		<?php else: ?>
			<ul>
				<?php foreach ($createdFiles as $file): ?>
					<li class="success"><?= htmlspecialchars($file) ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

	<div class="actions">
		<h2>Recommended Actions</h2>

		<?php if (isset($urlResults['login.php']) && !$urlResults['login.php']['accessible']): ?>
			<div class="warning">
				<p>Login page is not accessible. Here are some suggestions:</p>
				<ul>
					<li>Check if login.php exists and is readable (see file system check above)</li>
					<li>Check web.config for proper URL rewriting rules</li>
					<li>Try accessing login.php directly using the link above</li>
					<li>Check Azure logs for any PHP errors</li>
				</ul>
			</div>
		<?php endif; ?>

		<?php
		$workingProxies = array_filter($urlResults, function ($result) {
			return $result['accessible'] && strpos($result['url'], 'proxy') !== false;
		});

		if (empty($workingProxies)):
		?>
			<div class="warning">
				<p>No working proxy files found. Here are some suggestions:</p>
				<ul>
					<li>Try using the fixed proxy file (local-proxy-fix.php)</li>
					<li>Update your JavaScript config to use direct-login.php instead of proxy files</li>
					<li>Check web.config for proper URL rewriting rules</li>
				</ul>
			</div>
		<?php else: ?>
			<div class="success">
				<p>Working proxy files found:</p>
				<ul>
					<?php foreach ($workingProxies as $url => $result): ?>
						<li><?= htmlspecialchars($url) ?> (Status: <?= $result['status_code'] ?>)</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<p>
			<a href="deploy-proxy.php" class="btn">Run Proxy Deployment Script</a>
			<a href="proxy-test.php" class="btn">Run Proxy Test</a>
			<a href="/" class="btn">Go to Home Page</a>
		</p>
	</div>
</body>

</html>