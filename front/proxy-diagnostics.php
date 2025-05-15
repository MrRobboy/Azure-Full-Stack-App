<?php

/**
 * Azure Proxy Diagnostics Tool
 * 
 * This script provides comprehensive diagnostics for proxy issues
 * on Azure App Service environment.
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'proxy_diagnostics.log');

// Function to log with timestamp
function log_message($message, $level = 'INFO')
{
	$timestamp = date('Y-m-d H:i:s');
	error_log("[$timestamp] [$level] $message");
}

log_message("Proxy diagnostics tool started");

// Get server info
$server_info = [
	'hostname' => $_SERVER['SERVER_NAME'] ?? 'unknown',
	'ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
	'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
	'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
	'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'unknown',
	'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'unknown',
	'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
	'http_host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
	'php_version' => phpversion(),
	'sapi_name' => php_sapi_name(),
	'is_azure' => (stripos($_SERVER['SERVER_NAME'] ?? '', 'azurewebsites.net') !== false)
];

log_message("Server info: " . json_encode($server_info));

// Check for required PHP extensions
$required_extensions = ['curl', 'json', 'openssl'];
$missing_extensions = [];
foreach ($required_extensions as $ext) {
	if (!extension_loaded($ext)) {
		$missing_extensions[] = $ext;
	}
}

// Function to test if a file exists and is readable
function test_file_access($path)
{
	$result = [
		'path' => $path,
		'exists' => file_exists($path),
		'readable' => is_readable($path),
		'is_file' => is_file($path),
		'size' => file_exists($path) ? filesize($path) : null,
		'modified' => file_exists($path) ? date('Y-m-d H:i:s', filemtime($path)) : null
	];

	log_message("File test: " . json_encode($result));
	return $result;
}

// Test various paths for proxy files
$base_path = dirname(__FILE__);
$file_tests = [
	'simple_proxy' => test_file_access($base_path . '/simple-proxy.php'),
	'local_proxy' => test_file_access($base_path . '/local-proxy.php'),
	'api_bridge' => test_file_access($base_path . '/api-bridge.php'),
	'web_config' => test_file_access($base_path . '/web.config'),
	'htaccess' => test_file_access($base_path . '/.htaccess'),
	'proxy_dir' => test_file_access($base_path . '/proxy/simple-proxy.php'),
	'api_dir' => test_file_access($base_path . '/api/simple-proxy.php'),
	'parent_dir' => test_file_access(dirname($base_path) . '/simple-proxy.php')
];

// Test network connectivity
function test_connectivity($url, $timeout = 5)
{
	log_message("Testing connectivity to $url");
	$start_time = microtime(true);
	$result = [
		'url' => $url,
		'success' => false,
		'http_code' => null,
		'time_ms' => 0,
		'error' => null,
		'response_size' => 0
	];

	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_HEADER, true);

	$response = curl_exec($ch);
	$result['time_ms'] = round((microtime(true) - $start_time) * 1000);
	$result['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	if ($response === false) {
		$result['error'] = curl_error($ch);
		log_message("Connectivity test failed: " . $result['error'], 'ERROR');
	} else {
		$result['success'] = ($result['http_code'] >= 200 && $result['http_code'] < 300);
		$result['response_size'] = strlen($response);
		log_message("Connectivity test: Code " . $result['http_code'] . ", Time " . $result['time_ms'] . "ms");
	}

	curl_close($ch);
	return $result;
}

// Test backend connectivity
$connectivity_tests = [
	'backend_status' => test_connectivity('https://app-backend-esgi-app.azurewebsites.net/status.php'),
	'backend_api' => test_connectivity('https://app-backend-esgi-app.azurewebsites.net/api/status')
];

// Test proxy paths
$proxy_paths = [
	'simple-proxy.php',
	'/simple-proxy.php',
	'../simple-proxy.php',
	'proxy/simple-proxy.php',
	'/proxy/simple-proxy.php',
	'api/simple-proxy.php',
	'/api/simple-proxy.php',
	'local-proxy.php',
	'/local-proxy.php',
	'api-bridge.php',
	'/api-bridge.php'
];

$proxy_tests = [];
foreach ($proxy_paths as $path) {
	$target_url = $path . '?endpoint=status.php';
	$proxy_tests[$path] = test_connectivity($target_url);
}

// Attempt to determine current working directory for script context
function get_directory_contents($dir)
{
	$files = [];
	if (is_dir($dir)) {
		if ($handle = opendir($dir)) {
			while (($file = readdir($handle)) !== false) {
				if ($file != "." && $file != "..") {
					$path = $dir . '/' . $file;
					$type = is_dir($path) ? 'directory' : 'file';
					$size = is_file($path) ? filesize($path) : null;
					$files[] = [
						'name' => $file,
						'type' => $type,
						'size' => $size,
						'modified' => date('Y-m-d H:i:s', filemtime($path))
					];
				}
			}
			closedir($handle);
		}
	}
	return $files;
}

$directory_contents = [
	'current_dir' => get_directory_contents($base_path),
	'parent_dir' => get_directory_contents(dirname($base_path)),
	'proxy_dir' => file_exists($base_path . '/proxy') ? get_directory_contents($base_path . '/proxy') : [],
	'api_dir' => file_exists($base_path . '/api') ? get_directory_contents($base_path . '/api') : []
];

// Gather environment variables (with sensitive data masked)
function get_safe_env_vars()
{
	$env_vars = [];
	foreach ($_SERVER as $key => $value) {
		// Skip sensitive variables
		if (preg_match('/(password|key|secret|token)/i', $key)) {
			$env_vars[$key] = '***MASKED***';
		} else {
			$env_vars[$key] = $value;
		}
	}
	return $env_vars;
}

$env_vars = get_safe_env_vars();

// Check if we can access logs
$log_access = [
	'error_log_readable' => is_readable('proxy_diagnostics.log'),
	'api_bridge_log_readable' => is_readable('api_bridge_errors.log'),
	'php_errors_readable' => is_readable('php_errors.log')
];

// Get current URL without query parameters for generating test links
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") .
	"://" . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');

// Prepare diagnostic data
$diagnostics = [
	'timestamp' => date('Y-m-d H:i:s'),
	'server_info' => $server_info,
	'missing_extensions' => $missing_extensions,
	'file_tests' => $file_tests,
	'connectivity_tests' => $connectivity_tests,
	'proxy_tests' => $proxy_tests,
	'directory_contents' => $directory_contents,
	'log_access' => $log_access
];

log_message("Diagnostics complete");

// Format for HTML display or JSON output
$format = isset($_GET['format']) ? $_GET['format'] : 'html';
if ($format === 'json') {
	header('Content-Type: application/json');
	echo json_encode($diagnostics, JSON_PRETTY_PRINT);
	exit;
}

// HTML output
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Azure Proxy Diagnostics</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			margin: 20px;
			color: #333;
		}

		h1,
		h2,
		h3 {
			color: #0078D4;
		}

		.success {
			color: #107C10;
		}

		.failure {
			color: #D83B01;
		}

		.card {
			border: 1px solid #ddd;
			padding: 15px;
			margin-bottom: 20px;
			border-radius: 4px;
		}

		table {
			border-collapse: collapse;
			width: 100%;
			margin-bottom: 20px;
		}

		th,
		td {
			text-align: left;
			padding: 8px;
			border-bottom: 1px solid #ddd;
		}

		th {
			background-color: #f2f2f2;
		}

		tr:hover {
			background-color: #f5f5f5;
		}

		.actions {
			margin: 20px 0;
		}

		.btn {
			display: inline-block;
			padding: 10px 20px;
			background-color: #0078D4;
			color: white;
			text-decoration: none;
			border-radius: 4px;
			margin-right: 10px;
			border: none;
			cursor: pointer;
		}

		.btn:hover {
			background-color: #106EBE;
		}

		pre {
			background: #f5f5f5;
			padding: 10px;
			overflow-x: auto;
		}

		.status-circle {
			display: inline-block;
			width: 12px;
			height: 12px;
			border-radius: 50%;
			margin-right: 5px;
		}

		.status-success {
			background-color: #107C10;
		}

		.status-failure {
			background-color: #D83B01;
		}
	</style>
</head>

<body>
	<h1>Azure Proxy Diagnostics</h1>
	<p>This tool checks for common issues with proxy configuration on Azure App Service.</p>

	<div class="actions">
		<a href="?format=json" class="btn">View as JSON</a>
		<a href="?refresh=true" class="btn">Refresh</a>
		<a href="proxy-test.html" class="btn">Run Proxy Tests</a>
	</div>

	<div class="card">
		<h2>Server Information</h2>
		<table>
			<tr>
				<th>Property</th>
				<th>Value</th>
			</tr>
			<?php foreach ($server_info as $key => $value): ?>
				<tr>
					<td><?= htmlspecialchars($key) ?></td>
					<td><?= htmlspecialchars($value) ?></td>
				</tr>
			<?php endforeach; ?>
		</table>
	</div>

	<div class="card">
		<h2>PHP Extensions</h2>
		<?php if (empty($missing_extensions)): ?>
			<p class="success">All required PHP extensions are installed.</p>
		<?php else: ?>
			<p class="failure">Missing required PHP extensions:</p>
			<ul>
				<?php foreach ($missing_extensions as $ext): ?>
					<li><?= htmlspecialchars($ext) ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

	<div class="card">
		<h2>Proxy File Access</h2>
		<table>
			<tr>
				<th>File</th>
				<th>Status</th>
				<th>Details</th>
			</tr>
			<?php foreach ($file_tests as $name => $test): ?>
				<tr>
					<td><?= htmlspecialchars($test['path']) ?></td>
					<td>
						<?php if ($test['exists'] && $test['readable']): ?>
							<span class="status-circle status-success"></span>
							<span class="success">Accessible</span>
						<?php else: ?>
							<span class="status-circle status-failure"></span>
							<span class="failure">Not accessible</span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ($test['exists']): ?>
							Size: <?= $test['size'] ?> bytes<br>
							Modified: <?= $test['modified'] ?>
						<?php else: ?>
							File not found
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
	</div>

	<div class="card">
		<h2>Backend Connectivity</h2>
		<table>
			<tr>
				<th>Target</th>
				<th>Status</th>
				<th>Response Time</th>
				<th>Details</th>
			</tr>
			<?php foreach ($connectivity_tests as $name => $test): ?>
				<tr>
					<td><?= htmlspecialchars($test['url']) ?></td>
					<td>
						<?php if ($test['success']): ?>
							<span class="status-circle status-success"></span>
							<span class="success">Success (<?= $test['http_code'] ?>)</span>
						<?php else: ?>
							<span class="status-circle status-failure"></span>
							<span class="failure">Failed (<?= $test['http_code'] ?>)</span>
						<?php endif; ?>
					</td>
					<td><?= $test['time_ms'] ?> ms</td>
					<td>
						<?php if ($test['error']): ?>
							Error: <?= htmlspecialchars($test['error']) ?>
						<?php else: ?>
							Response size: <?= $test['response_size'] ?> bytes
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
	</div>

	<div class="card">
		<h2>Proxy Path Tests</h2>
		<table>
			<tr>
				<th>Path</th>
				<th>Status</th>
				<th>Response Time</th>
				<th>Details</th>
			</tr>
			<?php foreach ($proxy_tests as $path => $test): ?>
				<tr>
					<td>
						<a href="<?= htmlspecialchars($path) ?>?endpoint=status.php" target="_blank">
							<?= htmlspecialchars($path) ?>
						</a>
					</td>
					<td>
						<?php if ($test['success']): ?>
							<span class="status-circle status-success"></span>
							<span class="success">Success (<?= $test['http_code'] ?>)</span>
						<?php else: ?>
							<span class="status-circle status-failure"></span>
							<span class="failure">Failed (<?= $test['http_code'] ?>)</span>
						<?php endif; ?>
					</td>
					<td><?= $test['time_ms'] ?> ms</td>
					<td>
						<?php if ($test['error']): ?>
							Error: <?= htmlspecialchars($test['error']) ?>
						<?php else: ?>
							Response size: <?= $test['response_size'] ?> bytes
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
	</div>

	<div class="card">
		<h2>Directory Contents</h2>
		<h3>Current Directory</h3>
		<table>
			<tr>
				<th>Name</th>
				<th>Type</th>
				<th>Size</th>
				<th>Modified</th>
			</tr>
			<?php foreach ($directory_contents['current_dir'] as $file): ?>
				<tr>
					<td><?= htmlspecialchars($file['name']) ?></td>
					<td><?= $file['type'] ?></td>
					<td><?= $file['size'] ? $file['size'] . ' bytes' : '-' ?></td>
					<td><?= $file['modified'] ?></td>
				</tr>
			<?php endforeach; ?>
		</table>

		<?php if (!empty($directory_contents['proxy_dir'])): ?>
			<h3>Proxy Directory</h3>
			<table>
				<tr>
					<th>Name</th>
					<th>Type</th>
					<th>Size</th>
					<th>Modified</th>
				</tr>
				<?php foreach ($directory_contents['proxy_dir'] as $file): ?>
					<tr>
						<td><?= htmlspecialchars($file['name']) ?></td>
						<td><?= $file['type'] ?></td>
						<td><?= $file['size'] ? $file['size'] . ' bytes' : '-' ?></td>
						<td><?= $file['modified'] ?></td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php endif; ?>

		<?php if (!empty($directory_contents['api_dir'])): ?>
			<h3>API Directory</h3>
			<table>
				<tr>
					<th>Name</th>
					<th>Type</th>
					<th>Size</th>
					<th>Modified</th>
				</tr>
				<?php foreach ($directory_contents['api_dir'] as $file): ?>
					<tr>
						<td><?= htmlspecialchars($file['name']) ?></td>
						<td><?= $file['type'] ?></td>
						<td><?= $file['size'] ? $file['size'] . ' bytes' : '-' ?></td>
						<td><?= $file['modified'] ?></td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php endif; ?>
	</div>

	<div class="card">
		<h2>Next Steps</h2>
		<h3>Working Proxy Paths</h3>
		<div id="working-paths">
			<p>Calculating working paths...</p>
		</div>

		<h3>Recommended Actions</h3>
		<div id="recommendations">
			<p>Analyzing results...</p>
		</div>
	</div>

	<script>
		document.addEventListener('DOMContentLoaded', function() {
			// Process data to find working paths
			const proxyTests = <?= json_encode($proxy_tests) ?>;
			const workingPaths = [];

			for (const [path, test] of Object.entries(proxyTests)) {
				if (test.success) {
					workingPaths.push(path);
				}
			}

			// Display working paths
			const workingPathsEl = document.getElementById('working-paths');
			if (workingPaths.length > 0) {
				workingPathsEl.innerHTML = `
                    <p class="success">Found ${workingPaths.length} working proxy path(s):</p>
                    <ul>
                        ${workingPaths.map(path => `<li>${path}</li>`).join('')}
                    </ul>
                    <p>You can use any of these paths in your config.js file:</p>
                    <pre>appConfig.proxyUrl = "${workingPaths[0]}";</pre>
                `;
			} else {
				workingPathsEl.innerHTML = `
                    <p class="failure">No working proxy paths found. See recommendations below.</p>
                `;
			}

			// Generate recommendations
			const recommendationsEl = document.getElementById('recommendations');
			const serverInfo = <?= json_encode($server_info) ?>;
			const fileTests = <?= json_encode($file_tests) ?>;

			let recommendations = [];

			// Add recommendations based on diagnosis
			if (!fileTests.simple_proxy.exists) {
				recommendations.push("The main proxy file (simple-proxy.php) is missing. Run the deployment helper script to create all necessary files.");
			}

			if (!fileTests.web_config.exists) {
				recommendations.push("The web.config file is missing. This is required for URL rewriting on IIS.");
			}

			if (workingPaths.length === 0) {
				recommendations.push("Deploy the proxy files to multiple locations using the deployment helper script.");
				recommendations.push("Check if PHP is properly configured on your Azure App Service.");
				recommendations.push("Enable detailed error logging in the Azure portal.");
				recommendations.push("Try the alternative API bridge: api-bridge.php");
			}

			if (recommendations.length > 0) {
				recommendationsEl.innerHTML = `
                    <ul>
                        ${recommendations.map(rec => `<li>${rec}</li>`).join('')}
                    </ul>
                    <p>To deploy all required files, run:</p>
                    <p><a href="deploy-proxy.php" class="btn">Run Deployment Helper</a></p>
                `;
			} else {
				recommendationsEl.innerHTML = `
                    <p class="success">No critical issues detected. Update your config.js to use one of the working proxy paths.</p>
                `;
			}
		});
	</script>
</body>

</html>