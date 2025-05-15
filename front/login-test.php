<?php
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Login Test</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			line-height: 1.6;
			margin: 20px;
			max-width: 800px;
			margin: 0 auto;
			padding: 20px;
		}

		h1 {
			color: #333;
		}

		.test-section {
			background-color: #f5f5f5;
			border: 1px solid #ddd;
			padding: 15px;
			margin-bottom: 20px;
			border-radius: 4px;
		}

		button {
			background-color: #4CAF50;
			color: white;
			border: none;
			padding: 10px 15px;
			text-align: center;
			text-decoration: none;
			display: inline-block;
			font-size: 16px;
			margin: 4px 2px;
			cursor: pointer;
			border-radius: 4px;
		}

		pre {
			background-color: #f9f9f9;
			border: 1px solid #ddd;
			padding: 10px;
			overflow: auto;
			max-height: 300px;
		}

		.success {
			color: green;
		}

		.error {
			color: red;
		}

		.info {
			background-color: #e7f3fe;
			border-left: 6px solid #2196F3;
			padding: 10px;
			margin-bottom: 15px;
		}

		.input-row {
			margin-bottom: 10px;
		}

		.input-row label {
			display: inline-block;
			width: 100px;
		}

		input {
			padding: 8px;
			width: 250px;
		}
	</style>
</head>

<body>
	<h1>Login Test Page</h1>
	<p>This page tests the authentication API through different methods.</p>

	<div class="info">
		<p><strong>Current environment:</strong> <span id="environment"></span></p>
		<p><strong>Hostname:</strong> <span id="hostname"></span></p>
		<p><strong>Proxy path:</strong> <span id="proxy-path"></span></p>
	</div>

	<div class="test-section">
		<h2>1. Test Proxy Status</h2>
		<p>First, let's check if the proxy is working at all:</p>
		<button onclick="testProxyStatus()">Test Status via Proxy</button>
		<div id="proxy-result"></div>
	</div>

	<div class="test-section">
		<h2>2. Simple Login Test</h2>
		<div class="input-row">
			<label for="email">Email:</label>
			<input type="email" id="email" value="admin@test.com">
		</div>
		<div class="input-row">
			<label for="password">Password:</label>
			<input type="password" id="password" value="password123">
		</div>
		<button onclick="testLogin()">Test Login</button>
		<div id="login-result"></div>
	</div>

	<div class="test-section">
		<h2>3. Direct API Test</h2>
		<p>Try direct API call (will likely fail due to CORS):</p>
		<button onclick="testDirectLogin()">Test Direct Login</button>
		<div id="direct-result"></div>
	</div>

	<div class="test-section">
		<h2>4. Alternative Path Test</h2>
		<p>Try login with alternative path format:</p>
		<button onclick="testAlternativePath()">Test with Alt Path</button>
		<div id="alt-path-result"></div>
	</div>

	<script>
		// Determine environment
		const hostname = window.location.hostname;
		const isAzure = hostname.includes('azurewebsites.net');
		const proxyPath = isAzure ? '/simple-proxy.php' : 'simple-proxy.php';

		// Update environment info
		document.getElementById('environment').textContent = isAzure ? 'Azure' : 'Local';
		document.getElementById('hostname').textContent = hostname;
		document.getElementById('proxy-path').textContent = proxyPath;

		// Helper to display results
		function displayResult(elementId, title, status, data) {
			const container = document.getElementById(elementId);
			let html = `<h3>${title}</h3>`;

			if (status === 'success') {
				html += `<p class="success">Success</p>`;
			} else if (status === 'error') {
				html += `<p class="error">Error</p>`;
			} else {
				html += `<p>Status: ${status}</p>`;
			}

			if (data) {
				if (typeof data === 'object') {
					html += `<pre>${JSON.stringify(data, null, 2)}</pre>`;
				} else {
					html += `<pre>${data}</pre>`;
				}
			}

			container.innerHTML = html;
		}

		// Test the proxy status
		async function testProxyStatus() {
			const resultElement = document.getElementById('proxy-result');
			resultElement.innerHTML = '<p>Testing...</p>';

			try {
				const response = await fetch(`${proxyPath}?endpoint=status.php`);
				const status = response.status;
				const responseText = await response.text();

				let responseData;
				try {
					responseData = JSON.parse(responseText);
				} catch (e) {
					responseData = responseText;
				}

				displayResult(
					'proxy-result',
					'Proxy Status Test',
					status === 200 ? 'success' : 'error', {
						status,
						response: responseData
					}
				);
			} catch (error) {
				displayResult(
					'proxy-result',
					'Proxy Status Test',
					'error', {
						message: error.message,
						stack: error.stack
					}
				);
			}
		}

		// Test login via proxy
		async function testLogin() {
			const resultElement = document.getElementById('login-result');
			resultElement.innerHTML = '<p>Testing...</p>';

			const email = document.getElementById('email').value;
			const password = document.getElementById('password').value;

			try {
				const loginEndpoint = 'api/auth/login';
				const proxyUrl = `${proxyPath}?endpoint=${encodeURIComponent(loginEndpoint)}`;

				console.log('Login URL:', proxyUrl);

				const response = await fetch(proxyUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						email,
						password
					}),
					credentials: 'include'
				});

				const status = response.status;
				const responseText = await response.text();

				let responseData;
				try {
					responseData = JSON.parse(responseText);
				} catch (e) {
					responseData = responseText;
				}

				displayResult(
					'login-result',
					'Login Test',
					status === 200 ? 'success' : 'error', {
						status,
						response: responseData
					}
				);
			} catch (error) {
				displayResult(
					'login-result',
					'Login Test',
					'error', {
						message: error.message,
						stack: error.stack
					}
				);
			}
		}

		// Test direct API call (will likely fail due to CORS)
		async function testDirectLogin() {
			const resultElement = document.getElementById('direct-result');
			resultElement.innerHTML = '<p>Testing...</p>';

			const email = document.getElementById('email').value;
			const password = document.getElementById('password').value;

			try {
				const response = await fetch('https://app-backend-esgi-app.azurewebsites.net/api/auth/login', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						email,
						password
					}),
					credentials: 'include'
				});

				const status = response.status;
				const responseText = await response.text();

				let responseData;
				try {
					responseData = JSON.parse(responseText);
				} catch (e) {
					responseData = responseText;
				}

				displayResult(
					'direct-result',
					'Direct API Test',
					status === 200 ? 'success' : 'error', {
						status,
						response: responseData
					}
				);
			} catch (error) {
				displayResult(
					'direct-result',
					'Direct API Test',
					'error', {
						message: error.message,
						stack: error.stack
					}
				);
			}
		}

		// Test with alternative path format
		async function testAlternativePath() {
			const resultElement = document.getElementById('alt-path-result');
			resultElement.innerHTML = '<p>Testing...</p>';

			const email = document.getElementById('email').value;
			const password = document.getElementById('password').value;

			try {
				// Try without the api/ prefix
				const loginEndpoint = 'auth/login';
				const proxyUrl = `${proxyPath}?endpoint=${encodeURIComponent(loginEndpoint)}`;

				console.log('Alternative Login URL:', proxyUrl);

				const response = await fetch(proxyUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						email,
						password
					}),
					credentials: 'include'
				});

				const status = response.status;
				const responseText = await response.text();

				let responseData;
				try {
					responseData = JSON.parse(responseText);
				} catch (e) {
					responseData = responseText;
				}

				displayResult(
					'alt-path-result',
					'Alternative Path Test',
					status === 200 ? 'success' : 'error', {
						status,
						response: responseData
					}
				);
			} catch (error) {
				displayResult(
					'alt-path-result',
					'Alternative Path Test',
					'error', {
						message: error.message,
						stack: error.stack
					}
				);
			}
		}
	</script>
</body>

</html>