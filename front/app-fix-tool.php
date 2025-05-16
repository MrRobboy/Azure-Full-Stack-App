<?php
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>

<head>
	<title>Azure App Fix Tool</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			max-width: 800px;
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
	<h1>Azure App Fix Tool</h1>

	<div class="info">
		<p>This tool verifies and tests your proxy configuration to ensure your application works correctly.</p>
	</div>

	<div id="diagnostics">
		<h2>System Diagnostics</h2>
		<button onclick="runDiagnostics()">Run Diagnostics</button>
		<div id="diagnosticsResults"></div>
	</div>

	<div id="endpoints">
		<h2>API Endpoint Tests</h2>
		<button onclick="testEndpoint('status.php', 'Status')">Test Status</button>
		<button onclick="testEndpoint('api/user/profile', 'User Profile')">Test User Profile</button>
		<button onclick="testEndpoint('matieres', 'Matières')">Test Matières</button>
		<div id="endpointResults"></div>
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
		<li>Special handling added for matières endpoint</li>
		<li>Direct proxy for status.php using api-bridge.php</li>
		<li>Fallback mechanism for all endpoints</li>
	</ul>

	<script>
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
			} else {
				html += '<div class="error">Could not detect appConfig. Script may not be loaded correctly.</div>';
				html += '<script src="js/config.js?v=' + Date.now() + '"><\/script>';
			}

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
				'unified-proxy.php': 'Original proxy'
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