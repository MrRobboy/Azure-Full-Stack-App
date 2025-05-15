<?php
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>API Test</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			line-height: 1.6;
			margin: 20px;
			max-width: 800px;
			margin: 0 auto;
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

		button:hover {
			background-color: #45a049;
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
	</style>
</head>

<body>
	<h1>API Test Page</h1>
	<p>This page tests both the proxy and direct API connections.</p>

	<div class="test-section">
		<h2>Test Proxy Status</h2>
		<button onclick="testProxyStatus()">Test Status via Proxy</button>
		<div id="proxy-result"></div>
	</div>

	<div class="test-section">
		<h2>Test Backend Status</h2>
		<button onclick="testBackendStatus()">Test Status Directly</button>
		<div id="backend-result"></div>
	</div>

	<div class="test-section">
		<h2>Test Login via Proxy</h2>
		<button onclick="testLogin()">Test Login</button>
		<div id="login-result"></div>
	</div>

	<script>
		// Test the status endpoint via proxy
		async function testProxyStatus() {
			const resultDiv = document.getElementById('proxy-result');
			resultDiv.innerHTML = '<p>Testing...</p>';

			try {
				const response = await fetch('simple-proxy.php?endpoint=status.php');
				const status = response.status;
				let text = await response.text();
				let data;

				try {
					data = JSON.parse(text);
					text = JSON.stringify(data, null, 2);
				} catch (e) {
					// Text is not JSON, keep as is
				}

				resultDiv.innerHTML = `
                    <p class="${status === 200 ? 'success' : 'error'}">
                        Status: ${status}
                    </p>
                    <pre>${text}</pre>
                `;
			} catch (error) {
				resultDiv.innerHTML = `
                    <p class="error">Error: ${error.message}</p>
                    <pre>${error.stack}</pre>
                `;
			}
		}

		// Test the status endpoint directly
		async function testBackendStatus() {
			const resultDiv = document.getElementById('backend-result');
			resultDiv.innerHTML = '<p>Testing...</p>';

			try {
				const response = await fetch('https://app-backend-esgi-app.azurewebsites.net/status.php');
				const status = response.status;
				let text = await response.text();
				let data;

				try {
					data = JSON.parse(text);
					text = JSON.stringify(data, null, 2);
				} catch (e) {
					// Text is not JSON, keep as is
				}

				resultDiv.innerHTML = `
                    <p class="${status === 200 ? 'success' : 'error'}">
                        Status: ${status}
                    </p>
                    <pre>${text}</pre>
                `;
			} catch (error) {
				resultDiv.innerHTML = `
                    <p class="error">Error: ${error.message}</p>
                    <pre>${error.stack}</pre>
                `;
			}
		}

		// Test login via proxy
		async function testLogin() {
			const resultDiv = document.getElementById('login-result');
			resultDiv.innerHTML = '<p>Testing...</p>';

			try {
				const loginData = {
					email: 'admin@test.com',
					password: 'password123'
				};

				const response = await fetch('simple-proxy.php?endpoint=api/auth/login', {
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
					text = JSON.stringify(data, null, 2);
				} catch (e) {
					// Text is not JSON, keep as is
				}

				resultDiv.innerHTML = `
                    <p class="${status === 200 ? 'success' : 'error'}">
                        Status: ${status}
                    </p>
                    <pre>${text}</pre>
                `;
			} catch (error) {
				resultDiv.innerHTML = `
                    <p class="error">Error: ${error.message}</p>
                    <pre>${error.stack}</pre>
                `;
			}
		}
	</script>
</body>

</html>