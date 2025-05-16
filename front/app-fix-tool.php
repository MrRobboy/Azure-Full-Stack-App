<?php
session_start();
header('Content-Type: text/html');

// Check if user is logged in
$isLoggedIn = false;
$userData = null;
if (isset($_SESSION['user']) && isset($_SESSION['token'])) {
	$isLoggedIn = true;
	$userData = $_SESSION['user'];
}
?>
<!DOCTYPE html>
<html>

<head>
	<title>Azure App Fix Tool v2</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			max-width: 1000px;
			margin: 0 auto;
			padding: 20px;
		}

		.success {
			color: green;
			background: #e8f5e9;
			padding: 10px;
			margin: 10px 0;
			border-radius: 4px;
		}

		.error {
			color: red;
			background: #ffebee;
			padding: 10px;
			margin: 10px 0;
			border-radius: 4px;
		}

		.warning {
			color: #ff6f00;
			background: #fff8e1;
			padding: 10px;
			margin: 10px 0;
			border-radius: 4px;
		}

		.info {
			color: blue;
			background: #e3f2fd;
			padding: 10px;
			margin: 10px 0;
			border-radius: 4px;
		}

		button {
			background: #4285f4;
			color: white;
			border: none;
			padding: 10px 15px;
			border-radius: 4px;
			cursor: pointer;
			margin: 5px;
		}

		pre {
			background: #f5f5f5;
			padding: 10px;
			overflow: auto;
			border-radius: 4px;
			max-height: 300px;
		}

		.test-result {
			margin-top: 10px;
			padding: 10px;
			border: 1px solid #ddd;
			border-radius: 4px;
		}

		.tab-container {
			margin-top: 20px;
		}

		.tab-header {
			display: flex;
			border-bottom: 1px solid #ddd;
		}

		.tab {
			padding: 10px 15px;
			cursor: pointer;
			background: #f1f1f1;
			margin-right: 5px;
			border-radius: 4px 4px 0 0;
		}

		.tab.active {
			background: #4285f4;
			color: white;
		}

		.tab-content {
			padding: 15px;
			border: 1px solid #ddd;
			border-top: none;
			background: #fff;
		}

		.hidden {
			display: none;
		}

		table {
			width: 100%;
			border-collapse: collapse;
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
	</style>
</head>

<body>
	<h1>Azure App Fix Tool v2</h1>

	<div class="info">
		<p>This tool helps diagnose and fix proxy configuration issues with your Azure web application.</p>
		<?php if ($isLoggedIn): ?>
			<p class="success">You are logged in as: <?php echo htmlspecialchars($userData['nom'] . ' ' . $userData['prenom']); ?> (<?php echo htmlspecialchars($userData['role']); ?>)</p>
		<?php else: ?>
			<p class="warning">You are not logged in. Some tests may not work properly.</p>
		<?php endif; ?>
	</div>

	<div class="tab-container">
		<div class="tab-header">
			<div class="tab active" onclick="showTab('diagnostics')">System Diagnostics</div>
			<div class="tab" onclick="showTab('endpoints')">API Endpoints</div>
			<div class="tab" onclick="showTab('proxies')">Proxy Tests</div>
			<div class="tab" onclick="showTab('fallbacks')">Fallback System</div>
			<div class="tab" onclick="showTab('session')">Session Data</div>
		</div>

		<div id="diagnostics" class="tab-content">
			<h2>System Diagnostics</h2>
			<button onclick="runDiagnostics()">Run Diagnostics</button>
			<div id="diagnosticsResults"></div>
		</div>

		<div id="endpoints" class="tab-content hidden">
			<h2>API Endpoint Tests</h2>
			<button onclick="testEndpoint('status.php', 'Status')">Test Status</button>
			<button onclick="testEndpoint('api/user/profile', 'User Profile')">Test User Profile</button>
			<button onclick="testEndpoint('api/matieres', 'Matières')">Test Matières</button>
			<button onclick="testEndpoint('api/classes', 'Classes')">Test Classes</button>
			<button onclick="testEndpoint('api/examens', 'Examens')">Test Examens</button>
			<button onclick="testEndpoint('api/professeurs', 'Professeurs')">Test Professeurs</button>
			<button onclick="testAllEndpoints()">Test All Endpoints</button>
			<div id="endpointResults"></div>
		</div>

		<div id="proxies" class="tab-content hidden">
			<h2>Proxy Tests</h2>
			<button onclick="testProxy('api-bridge.php')">Test api-bridge.php</button>
			<button onclick="testProxy('matieres-proxy.php')">Test matieres-proxy.php</button>
			<button onclick="testProxy('unified-proxy.php')">Test unified-proxy.php</button>
			<button onclick="testProxy('simple-proxy.php')">Test simple-proxy.php</button>
			<button onclick="testProxy('direct-matieres.php')">Test direct-matieres.php</button>
			<div id="proxyResults"></div>
		</div>

		<div id="fallbacks" class="tab-content hidden">
			<h2>Fallback System Test</h2>
			<button onclick="testFallbackChain('api/matieres')">Test Matières Fallback Chain</button>
			<button onclick="testFallbackChain('api/classes')">Test Classes Fallback Chain</button>
			<button onclick="testFallbackChain('api/examens')">Test Examens Fallback Chain</button>
			<div id="fallbackResults"></div>
		</div>

		<div id="session" class="tab-content hidden">
			<h2>Session Data</h2>
			<?php if ($isLoggedIn): ?>
				<pre><?php echo htmlspecialchars(json_encode($_SESSION, JSON_PRETTY_PRINT)); ?></pre>
			<?php else: ?>
				<div class="warning">No active session found.</div>
				<a href="login.php" class="button">Login</a>
			<?php endif; ?>
		</div>
	</div>

	<h2>Available Tools</h2>
	<div>
		<a href="api-debug.php" target="_blank"><button>API Debug Panel</button></a>
		<a href="api-endpoint-tester.php" target="_blank"><button>API Endpoint Tester</button></a>
		<a href="deep-proxy-test.php" target="_blank"><button>Deep Proxy Test</button></a>
		<a href="dashboard.php?clear_cache=1" target="_blank"><button>Dashboard (Clear Cache)</button></a>
	</div>

	<h2>Recent Updates</h2>
	<ul>
		<li>Config.js updated to version 4.1 with multi-proxy approach</li>
		<li>api-bridge.php enhanced with built-in fallbacks</li>
		<li>direct-matieres.php expanded to support all critical endpoints</li>
		<li>Special handling added for the user profile endpoint</li>
		<li>Fallback chain implemented in API service</li>
	</ul>

	<script src="js/config.js?v=<?php echo time(); ?>"></script>
	<script>
		// Tab navigation
		function showTab(tabId) {
			// Hide all tab contents
			document.querySelectorAll('.tab-content').forEach(content => {
				content.classList.add('hidden');
			});

			// Deactivate all tabs
			document.querySelectorAll('.tab').forEach(tab => {
				tab.classList.remove('active');
			});

			// Show selected tab content
			document.getElementById(tabId).classList.remove('hidden');

			// Activate selected tab
			document.querySelector(`.tab[onclick="showTab('${tabId}')"]`).classList.add('active');
		}

		// Run diagnostics on the system
		async function runDiagnostics() {
			const resultsDiv = document.getElementById('diagnosticsResults');
			resultsDiv.innerHTML = '<div class="info">Running diagnostics...</div>';

			let html = '<h3>Proxy Files</h3><ul>';

			// Check critical proxy files
			const proxyFiles = [
				'unified-proxy.php',
				'matieres-proxy.php',
				'api-bridge.php',
				'simple-proxy.php',
				'direct-matieres.php',
				'status.php'
			];

			for (const file of proxyFiles) {
				try {
					const response = await fetch(file, {
						method: 'HEAD'
					});
					if (response.ok) {
						html += `<li class="success">${file}: Available (${response.status})</li>`;
					} else {
						html += `<li class="error">${file}: Error (${response.status})</li>`;
					}
				} catch (error) {
					html += `<li class="error">${file}: Not found (${error.message})</li>`;
				}
			}

			html += '</ul>';

			// Check config.js version
			html += '<h3>Configuration</h3>';
			if (window.appConfig && window.appConfig.version) {
				html += `<div class="success">Config version: ${window.appConfig.version}</div>`;
				html += `<div class="info">Default proxy: ${window.appConfig.proxyUrl}</div>`;
				html += `<div class="info">Matières proxy: ${window.appConfig.matieresProxyUrl}</div>`;
				html += `<div class="info">Proxy URLs in priority order:</div><ol>`;

				for (const proxyUrl of window.appConfig.proxyUrls) {
					html += `<li>${proxyUrl}</li>`;
				}

				html += `</ol>`;
			} else {
				html += '<div class="error">Could not detect appConfig. Script may not be loaded correctly.</div>';
				html += '<script src="js/config.js?v=' + Date.now() + '"><\/script>';
			}

			resultsDiv.innerHTML = html;
		}

		// Test all endpoints
		async function testAllEndpoints() {
			const endpoints = [{
					endpoint: 'status.php',
					title: 'Status'
				},
				{
					endpoint: 'api/user/profile',
					title: 'User Profile'
				},
				{
					endpoint: 'api/matieres',
					title: 'Matières'
				},
				{
					endpoint: 'api/classes',
					title: 'Classes'
				},
				{
					endpoint: 'api/examens',
					title: 'Examens'
				},
				{
					endpoint: 'api/professeurs',
					title: 'Professeurs'
				}
			];

			const resultsDiv = document.getElementById('endpointResults');
			resultsDiv.innerHTML = '<div class="info">Testing all endpoints...</div>';

			let html = '<h3>Results</h3>';
			html += '<table><tr><th>Endpoint</th><th>Status</th><th>Response Time</th><th>Source</th></tr>';

			for (const {
					endpoint,
					title
				}
				of endpoints) {
				try {
					const startTime = performance.now();

					// Use the ApiService directly if available, otherwise use fetch with appConfig
					let url;
					if (window.appConfig) {
						url = window.appConfig.proxyUrl + '?endpoint=' + encodeURIComponent(endpoint);
					} else {
						url = 'api-bridge.php?endpoint=' + encodeURIComponent(endpoint);
					}

					const response = await fetch(url);
					const endTime = performance.now();
					const duration = (endTime - startTime).toFixed(2);

					if (response.ok) {
						try {
							const data = await response.json();
							let source = "Unknown";

							if (data.source) {
								source = data.source;
							} else if (data.is_direct) {
								source = "direct-matieres.php";
							} else if (data.message && data.message.includes("direct")) {
								source = "Fallback data";
							}

							html += `<tr>
								<td>${title} (${endpoint})</td>
								<td class="success">Success (${response.status})</td>
								<td>${duration}ms</td>
								<td>${source}</td>
							</tr>`;
						} catch (e) {
							html += `<tr>
								<td>${title} (${endpoint})</td>
								<td class="warning">Invalid JSON (${response.status})</td>
								<td>${duration}ms</td>
								<td>Error parsing JSON</td>
							</tr>`;
						}
					} else {
						html += `<tr>
							<td>${title} (${endpoint})</td>
							<td class="error">Failed (${response.status})</td>
							<td>${duration}ms</td>
							<td>N/A</td>
						</tr>`;
					}
				} catch (error) {
					html += `<tr>
						<td>${title} (${endpoint})</td>
						<td class="error">Error</td>
						<td>N/A</td>
						<td>${error.message}</td>
					</tr>`;
				}
			}

			html += '</table>';
			resultsDiv.innerHTML = html;
		}

		// Test specific endpoint
		async function testEndpoint(endpoint, title) {
			const resultsDiv = document.getElementById('endpointResults');
			const testId = 'test-' + endpoint.replace(/[^a-z0-9]/g, '-');

			// Create or get test result container
			let testContainer = document.getElementById(testId);
			if (!testContainer) {
				testContainer = document.createElement('div');
				testContainer.id = testId;
				testContainer.className = 'test-result';
				testContainer.innerHTML = `<h3>${title} (${endpoint})</h3>`;
				resultsDiv.appendChild(testContainer);
			}

			testContainer.innerHTML = `<h3>${title} (${endpoint})</h3><div class="info">Testing...</div>`;

			// Try each proxy method
			const proxies = {
				'matieres-proxy.php': endpoint.includes('matieres') ? 'Primary for matières' : 'Alternative',
				'api-bridge.php': 'Primary for most endpoints',
				'simple-proxy.php': 'Backup option',
				'unified-proxy.php': 'Original proxy',
				'direct-matieres.php': 'Direct data provider'
			};

			let html = '';

			for (const [proxy, description] of Object.entries(proxies)) {
				html += `<h4>${proxy} <small>(${description})</small></h4>`;

				try {
					const url = `${proxy}?endpoint=${encodeURIComponent(endpoint)}`;
					const startTime = performance.now();
					const response = await fetch(url);
					const endTime = performance.now();
					const duration = (endTime - startTime).toFixed(2);

					if (response.ok) {
						let responseText = '';
						try {
							const data = await response.json();
							responseText = `<pre>${JSON.stringify(data, null, 2).substring(0, 300)}${JSON.stringify(data, null, 2).length > 300 ? '...' : ''}</pre>`;
							html += `<div class="success">Success (${response.status}) - ${duration}ms</div>`;
						} catch (e) {
							const text = await response.text();
							responseText = `<div class="error">Not valid JSON: ${e.message}</div><pre>${text.substring(0, 100)}...</pre>`;
							html += `<div class="error">Response not JSON (${response.status}) - ${duration}ms</div>`;
						}
						html += responseText;
					} else {
						html += `<div class="error">Failed (${response.status}) - ${duration}ms</div>`;
					}
				} catch (error) {
					html += `<div class="error">Error: ${error.message}</div>`;
				}
			}

			testContainer.innerHTML = `<h3>${title} (${endpoint})</h3>${html}`;
		}

		// Test a specific proxy file
		async function testProxy(proxyFile) {
			const resultsDiv = document.getElementById('proxyResults');
			const testId = 'proxy-' + proxyFile.replace(/[^a-z0-9]/g, '-');

			// Create or get test result container
			let testContainer = document.getElementById(testId);
			if (!testContainer) {
				testContainer = document.createElement('div');
				testContainer.id = testId;
				testContainer.className = 'test-result';
				testContainer.innerHTML = `<h3>${proxyFile}</h3>`;
				resultsDiv.appendChild(testContainer);
			}

			testContainer.innerHTML = `<h3>${proxyFile}</h3><div class="info">Testing...</div>`;

			// Test the proxy with various endpoints
			const endpoints = ['status.php', 'api/user/profile', 'api/matieres', 'api/classes'];
			let html = '<table><tr><th>Endpoint</th><th>Status</th><th>Response Time</th></tr>';

			for (const endpoint of endpoints) {
				try {
					const url = `${proxyFile}?endpoint=${encodeURIComponent(endpoint)}`;
					const startTime = performance.now();
					const response = await fetch(url);
					const endTime = performance.now();
					const duration = (endTime - startTime).toFixed(2);

					if (response.ok) {
						html += `<tr>
							<td>${endpoint}</td>
							<td class="success">Success (${response.status})</td>
							<td>${duration}ms</td>
						</tr>`;
					} else {
						html += `<tr>
							<td>${endpoint}</td>
							<td class="error">Failed (${response.status})</td>
							<td>${duration}ms</td>
						</tr>`;
					}
				} catch (error) {
					html += `<tr>
						<td>${endpoint}</td>
						<td class="error">Error: ${error.message}</td>
						<td>N/A</td>
					</tr>`;
				}
			}

			html += '</table>';
			testContainer.innerHTML = `<h3>${proxyFile}</h3>${html}`;
		}

		// Test the fallback chain for a specific endpoint
		async function testFallbackChain(endpoint) {
			const resultsDiv = document.getElementById('fallbackResults');
			const testId = 'fallback-' + endpoint.replace(/[^a-z0-9]/g, '-');

			// Create or get test result container
			let testContainer = document.getElementById(testId);
			if (!testContainer) {
				testContainer = document.createElement('div');
				testContainer.id = testId;
				testContainer.className = 'test-result';
				testContainer.innerHTML = `<h3>Fallback Chain for ${endpoint}</h3>`;
				resultsDiv.appendChild(testContainer);
			}

			testContainer.innerHTML = `<h3>Fallback Chain for ${endpoint}</h3><div class="info">Testing...</div>`;

			// Define the fallback chain
			const fallbackChain = [{
					name: 'Primary: Specialized Proxy',
					url: endpoint.includes('matieres') ?
						`matieres-proxy.php?endpoint=${encodeURIComponent(endpoint)}` : `api-bridge.php?endpoint=${encodeURIComponent(endpoint)}`
				},
				{
					name: 'Fallback 1: Alternative Proxy',
					url: `simple-proxy.php?endpoint=${encodeURIComponent(endpoint)}`
				},
				{
					name: 'Fallback 2: Unified Proxy',
					url: `unified-proxy.php?endpoint=${encodeURIComponent(endpoint)}`
				},
				{
					name: 'Last Resort: Direct Data',
					url: `direct-matieres.php?endpoint=${encodeURIComponent(endpoint)}`
				}
			];

			let html = '<table><tr><th>Fallback Method</th><th>Status</th><th>Response Time</th><th>Data Available</th></tr>';

			for (const fallback of fallbackChain) {
				try {
					const startTime = performance.now();
					const response = await fetch(fallback.url);
					const endTime = performance.now();
					const duration = (endTime - startTime).toFixed(2);

					let dataStatus = 'Unknown';
					if (response.ok) {
						try {
							const data = await response.json();
							if (data && (data.data || (data.success === true))) {
								dataStatus = 'Yes';
							} else {
								dataStatus = 'No valid data';
							}
						} catch (e) {
							dataStatus = 'Invalid JSON';
						}

						html += `<tr>
							<td>${fallback.name}</td>
							<td class="success">Success (${response.status})</td>
							<td>${duration}ms</td>
							<td>${dataStatus}</td>
						</tr>`;
					} else {
						html += `<tr>
							<td>${fallback.name}</td>
							<td class="error">Failed (${response.status})</td>
							<td>${duration}ms</td>
							<td>No</td>
						</tr>`;
					}
				} catch (error) {
					html += `<tr>
						<td>${fallback.name}</td>
						<td class="error">Error: ${error.message}</td>
						<td>N/A</td>
						<td>No</td>
					</tr>`;
				}
			}

			html += '</table>';
			testContainer.innerHTML = `<h3>Fallback Chain for ${endpoint}</h3>${html}`;
		}

		// Load config.js to access properties
		document.addEventListener('DOMContentLoaded', function() {
			const script = document.createElement('script');
			script.src = 'js/config.js?v=' + Date.now();
			document.head.appendChild(script);

			// Run diagnostics on load
			setTimeout(runDiagnostics, 500);
		});
	</script>
</body>

</html>