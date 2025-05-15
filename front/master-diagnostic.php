<?php
header('Content-Type: text/html; charset=UTF-8');

// Collect basic system info
$server_info = [
	'php_version' => PHP_VERSION,
	'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
	'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
	'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown',
	'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
	'time' => date('Y-m-d H:i:s'),
];

// Get important files status
$files_to_check = [
	'simple-proxy.php',
	'backend-proxy.php',
	'js/config.js',
	'login.php',
	'api-test.php'
];

$file_status = [];
foreach ($files_to_check as $file) {
	$full_path = __DIR__ . '/' . $file;
	$file_status[$file] = [
		'exists' => file_exists($full_path),
		'readable' => is_readable($full_path),
		'size' => file_exists($full_path) ? filesize($full_path) : 0,
		'path' => $full_path
	];
}

// Check if curl is available
$system_capabilities = [
	'curl_enabled' => function_exists('curl_init'),
	'allow_url_fopen' => ini_get('allow_url_fopen')
];

// Get the host name for environment detection
$hostname = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'unknown';
$is_azure = strpos($hostname, 'azurewebsites.net') !== false;
$environment = $is_azure ? 'Azure' : 'Local';

?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Master Diagnostic</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			line-height: 1.6;
			margin: 0;
			padding: 20px;
			color: #333;
		}

		h1,
		h2,
		h3 {
			color: #0066cc;
		}

		.container {
			max-width: 1200px;
			margin: 0 auto;
		}

		.section {
			background-color: #f9f9f9;
			border: 1px solid #ddd;
			border-radius: 4px;
			padding: 15px;
			margin-bottom: 20px;
		}

		.success {
			color: green;
			font-weight: bold;
		}

		.error {
			color: red;
			font-weight: bold;
		}

		.warning {
			color: orange;
			font-weight: bold;
		}

		table {
			width: 100%;
			border-collapse: collapse;
			margin-bottom: 20px;
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

		pre {
			background-color: #f4f4f4;
			border: 1px solid #ddd;
			padding: 10px;
			overflow: auto;
			max-height: 300px;
			white-space: pre-wrap;
		}

		.button-row {
			margin: 20px 0;
		}

		button {
			background-color: #0066cc;
			color: white;
			border: none;
			padding: 10px 15px;
			margin-right: 10px;
			cursor: pointer;
			border-radius: 4px;
		}

		button:hover {
			background-color: #0055aa;
		}

		.test-result {
			margin-top: 10px;
			padding: 10px;
			border: 1px solid #ddd;
			border-radius: 4px;
		}
	</style>
</head>

<body>
	<div class="container">
		<h1>Full Stack App - Master Diagnostic</h1>

		<div class="section">
			<h2>Environment Information</h2>
			<table>
				<tr>
					<th>Environment</th>
					<td><?php echo $environment; ?></td>
				</tr>
				<tr>
					<th>PHP Version</th>
					<td><?php echo $server_info['php_version']; ?></td>
				</tr>
				<tr>
					<th>Server Software</th>
					<td><?php echo $server_info['server_software']; ?></td>
				</tr>
				<tr>
					<th>Document Root</th>
					<td><?php echo $server_info['document_root']; ?></td>
				</tr>
				<tr>
					<th>Script Filename</th>
					<td><?php echo $server_info['script_filename']; ?></td>
				</tr>
				<tr>
					<th>Current Time</th>
					<td><?php echo $server_info['time']; ?></td>
				</tr>
			</table>
		</div>

		<div class="section">
			<h2>System Capabilities</h2>
			<table>
				<tr>
					<th>CURL Available</th>
					<td class="<?php echo $system_capabilities['curl_enabled'] ? 'success' : 'error'; ?>">
						<?php echo $system_capabilities['curl_enabled'] ? 'Yes' : 'No'; ?>
					</td>
				</tr>
				<tr>
					<th>allow_url_fopen</th>
					<td class="<?php echo $system_capabilities['allow_url_fopen'] ? 'success' : 'error'; ?>">
						<?php echo $system_capabilities['allow_url_fopen'] ? 'Enabled' : 'Disabled'; ?>
					</td>
				</tr>
			</table>
		</div>

		<div class="section">
			<h2>File Status</h2>
			<table>
				<tr>
					<th>File</th>
					<th>Exists</th>
					<th>Readable</th>
					<th>Size</th>
					<th>Path</th>
				</tr>
				<?php foreach ($file_status as $file => $status): ?>
					<tr>
						<td><?php echo $file; ?></td>
						<td class="<?php echo $status['exists'] ? 'success' : 'error'; ?>">
							<?php echo $status['exists'] ? 'Yes' : 'No'; ?>
						</td>
						<td class="<?php echo $status['readable'] ? 'success' : 'error'; ?>">
							<?php echo $status['readable'] ? 'Yes' : 'No'; ?>
						</td>
						<td><?php echo $status['size']; ?> bytes</td>
						<td><?php echo $status['path']; ?></td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<div class="section">
			<h2>API Tests</h2>
			<div class="button-row">
				<button onclick="testSimpleProxy()">Test Simple Proxy</button>
				<button onclick="testOriginalProxy()">Test Original Proxy</button>
				<button onclick="testDirectApi()">Test Direct API</button>
				<button onclick="testLogin()">Test Login</button>
			</div>
			<div id="api-results"></div>
		</div>

		<div class="section">
			<h2>Config Status</h2>
			<button onclick="checkConfig()">Check Config</button>
			<div id="config-result"></div>
		</div>

		<div class="section">
			<h2>Links to Other Diagnostic Tools</h2>
			<ul>
				<li><a href="api-test.php" target="_blank">API Test Page</a></li>
				<li><a href="check-deployment.php" target="_blank">Deployment Check</a></li>
				<li><a href="<?php echo $is_azure ? '/simple-proxy.php' : 'simple-proxy.php'; ?>?endpoint=status.php" target="_blank">Direct Simple Proxy Test</a></li>
			</ul>
		</div>
	</div>

	<script>
		// Path adjustments for Azure
		const isAzure = window.location.hostname.includes('azurewebsites.net');
		const simpleProxyPath = isAzure ? '/simple-proxy.php' : 'simple-proxy.php';
		const originalProxyPath = isAzure ? '/backend-proxy.php' : 'backend-proxy.php';

		// Helper function to format results
		function displayResult(containerId, title, status, details) {
			const container = document.getElementById(containerId);
			const resultDiv = document.createElement('div');
			resultDiv.className = 'test-result';

			let statusClass = status === 'success' ? 'success' : (status === 'warning' ? 'warning' : 'error');

			let detailsHtml = '';
			if (details) {
				if (typeof details === 'object') {
					detailsHtml = `<pre>${JSON.stringify(details, null, 2)}</pre>`;
				} else {
					detailsHtml = `<pre>${details}</pre>`;
				}
			}

			resultDiv.innerHTML = `
                <h3>${title}</h3>
                <p class="${statusClass}">${status === 'success' ? 'Success' : (status === 'warning' ? 'Warning' : 'Error')}</p>
                ${detailsHtml}
                <p><small>Timestamp: ${new Date().toLocaleTimeString()}</small></p>
            `;

			container.prepend(resultDiv);
		}

		// Test the simple proxy
		async function testSimpleProxy() {
			try {
				const response = await fetch(`${simpleProxyPath}?endpoint=status.php`);
				const status = response.status;
				let text = await response.text();
				let data;

				try {
					data = JSON.parse(text);
					displayResult('api-results', 'Simple Proxy Test', 'success', {
						status,
						data
					});
				} catch (e) {
					displayResult('api-results', 'Simple Proxy Test', 'warning', {
						status,
						response: text,
						error: e.message
					});
				}
			} catch (error) {
				displayResult('api-results', 'Simple Proxy Test', 'error', {
					error: error.message,
					stack: error.stack
				});
			}
		}

		// Test the original proxy
		async function testOriginalProxy() {
			try {
				const response = await fetch(`${originalProxyPath}?endpoint=status.php`);
				const status = response.status;
				let text = await response.text();
				let data;

				try {
					data = JSON.parse(text);
					displayResult('api-results', 'Original Proxy Test', 'success', {
						status,
						data
					});
				} catch (e) {
					displayResult('api-results', 'Original Proxy Test', 'warning', {
						status,
						response: text,
						error: e.message
					});
				}
			} catch (error) {
				displayResult('api-results', 'Original Proxy Test', 'error', {
					error: error.message,
					stack: error.stack
				});
			}
		}

		// Test direct API access
		async function testDirectApi() {
			try {
				const response = await fetch('https://app-backend-esgi-app.azurewebsites.net/status.php');
				const status = response.status;
				let text = await response.text();
				let data;

				try {
					data = JSON.parse(text);
					displayResult('api-results', 'Direct API Test', 'success', {
						status,
						data
					});
				} catch (e) {
					displayResult('api-results', 'Direct API Test', 'warning', {
						status,
						response: text,
						error: e.message
					});
				}
			} catch (error) {
				displayResult('api-results', 'Direct API Test', 'error', {
					error: error.message,
					stack: error.stack
				});
			}
		}

		// Test login
		async function testLogin() {
			try {
				const loginData = {
					email: 'admin@test.com',
					password: 'password123'
				};

				const response = await fetch(`${simpleProxyPath}?endpoint=api/auth/login`, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify(loginData),
					credentials: 'include'
				});

				const status = response.status;
				let text = await response.text();
				let data;

				try {
					data = JSON.parse(text);
					displayResult('api-results', 'Login Test', status === 200 ? 'success' : 'warning', {
						status,
						data
					});
				} catch (e) {
					displayResult('api-results', 'Login Test', 'warning', {
						status,
						response: text,
						error: e.message
					});
				}
			} catch (error) {
				displayResult('api-results', 'Login Test', 'error', {
					error: error.message,
					stack: error.stack
				});
			}
		}

		// Check config status
		async function checkConfig() {
			try {
				// Load config.js
				const configPath = isAzure ? '/js/config.js' : 'js/config.js';

				// Check if config.js exists and is loaded properly
				if (typeof window.appConfig !== 'undefined') {
					displayResult('config-result', 'Config Check', 'success', {
						config: window.appConfig,
						proxyUrl: window.appConfig.proxyUrl,
						environment: isAzure ? 'Azure' : 'Local'
					});
				} else {
					// Try to load it dynamically
					const script = document.createElement('script');
					script.src = configPath + '?v=' + new Date().getTime();
					script.onload = () => {
						if (typeof window.appConfig !== 'undefined') {
							displayResult('config-result', 'Config Check', 'success', {
								config: window.appConfig,
								proxyUrl: window.appConfig.proxyUrl,
								environment: isAzure ? 'Azure' : 'Local',
								note: 'Config was loaded dynamically'
							});
						} else {
							displayResult('config-result', 'Config Check', 'error', {
								error: 'Config object not found after loading script',
								configPath
							});
						}
					};
					script.onerror = (e) => {
						displayResult('config-result', 'Config Check', 'error', {
							error: 'Failed to load config.js',
							event: e.type,
							configPath
						});
					};
					document.head.appendChild(script);
				}
			} catch (error) {
				displayResult('config-result', 'Config Check', 'error', {
					error: error.message,
					stack: error.stack
				});
			}
		}
	</script>
</body>

</html>