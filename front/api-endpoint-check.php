<?php
header('Content-Type: text/html; charset=UTF-8');

// Test configuration
$api_base_url = 'https://app-backend-esgi-app.azurewebsites.net';
$api_endpoints = [
	// Format: [endpoint path, method, description, test_data]
	['status.php', 'GET', 'Server Status Check', null],
	['api/auth/login', 'POST', 'User Login', ['email' => 'admin@test.com', 'password' => 'password123']],
	['api/users', 'GET', 'Get Users List', null],
	['api/auth/me', 'GET', 'Get Current User', null]
];

// Determine if we're on Azure or local environment
$hostname = $_SERVER['HTTP_HOST'] ?? '';
$is_azure = strpos($hostname, 'azurewebsites.net') !== false;
$proxy_path = $is_azure ? '/simple-proxy.php' : 'simple-proxy.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>API Endpoint Check</title>
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

		.button {
			background-color: #0066cc;
			color: white;
			border: none;
			padding: 10px 15px;
			margin-right: 10px;
			cursor: pointer;
			border-radius: 4px;
			display: inline-block;
		}

		.button:hover {
			background-color: #0055aa;
		}

		.endpoint-row {
			margin-bottom: 15px;
			padding: 10px;
			border: 1px solid #ddd;
			border-radius: 4px;
		}

		.result-container {
			margin-top: 10px;
			display: none;
		}

		.loading {
			display: inline-block;
			width: 20px;
			height: 20px;
			border: 3px solid rgba(0, 0, 0, 0.1);
			border-radius: 50%;
			border-top-color: #0066cc;
			animation: spin 1s ease-in-out infinite;
			vertical-align: middle;
			margin-left: 10px;
		}

		@keyframes spin {
			to {
				transform: rotate(360deg);
			}
		}
	</style>
</head>

<body>
	<div class="container">
		<h1>API Endpoint Check Tool</h1>

		<div class="section">
			<h2>Environment Information</h2>
			<table>
				<tr>
					<th>Environment</th>
					<td><?php echo $is_azure ? 'Azure' : 'Local'; ?></td>
				</tr>
				<tr>
					<th>Hostname</th>
					<td><?php echo htmlspecialchars($hostname); ?></td>
				</tr>
				<tr>
					<th>API Base URL</th>
					<td><?php echo htmlspecialchars($api_base_url); ?></td>
				</tr>
				<tr>
					<th>Proxy Path</th>
					<td><?php echo htmlspecialchars($proxy_path); ?></td>
				</tr>
			</table>
		</div>

		<div class="section">
			<h2>API Endpoint Tests</h2>
			<p>Click on each button to test the corresponding endpoint via the proxy or directly.</p>

			<div class="endpoint-list">
				<?php foreach ($api_endpoints as $index => $endpoint): ?>
					<div class="endpoint-row">
						<h3><?php echo htmlspecialchars($endpoint[2]); ?></h3>
						<p>
							<strong>Path:</strong> <?php echo htmlspecialchars($endpoint[0]); ?><br>
							<strong>Method:</strong> <?php echo htmlspecialchars($endpoint[1]); ?>
						</p>

						<button class="button test-proxy" data-index="<?php echo $index; ?>">
							Test via Proxy
						</button>

						<button class="button test-direct" data-index="<?php echo $index; ?>">
							Test Direct API
						</button>

						<div class="result-container" id="result-proxy-<?php echo $index; ?>">
							<h4>Proxy Test Result</h4>
							<pre class="result-content"></pre>
						</div>

						<div class="result-container" id="result-direct-<?php echo $index; ?>">
							<h4>Direct API Test Result</h4>
							<pre class="result-content"></pre>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="section">
			<h2>Custom Endpoint Test</h2>
			<p>Test a custom endpoint:</p>

			<div>
				<label for="custom-endpoint">Endpoint Path:</label>
				<input type="text" id="custom-endpoint" style="width: 300px; padding: 5px;" placeholder="api/your/endpoint">
			</div>

			<div style="margin-top: 10px;">
				<label for="custom-method">Method:</label>
				<select id="custom-method" style="padding: 5px;">
					<option value="GET">GET</option>
					<option value="POST">POST</option>
					<option value="PUT">PUT</option>
					<option value="DELETE">DELETE</option>
				</select>
			</div>

			<div style="margin-top: 10px;">
				<label for="custom-data">Data (JSON for POST/PUT):</label>
				<textarea id="custom-data" style="width: 100%; height: 100px; padding: 5px;" placeholder='{"key": "value"}'></textarea>
			</div>

			<div style="margin-top: 10px;">
				<button class="button" id="test-custom-proxy">Test Custom Endpoint via Proxy</button>
				<button class="button" id="test-custom-direct">Test Custom Endpoint Directly</button>
			</div>

			<div class="result-container" id="custom-result">
				<h4>Custom Test Result</h4>
				<pre class="result-content"></pre>
			</div>
		</div>
	</div>

	<script>
		// Store API endpoint data
		const apiEndpoints = <?php echo json_encode($api_endpoints); ?>;
		const apiBaseUrl = '<?php echo $api_base_url; ?>';
		const proxyPath = '<?php echo $proxy_path; ?>';

		// Helper function to show loading indicator
		function showLoading(button) {
			// Create loading spinner if it doesn't exist
			if (!button.querySelector('.loading')) {
				const loading = document.createElement('span');
				loading.className = 'loading';
				button.appendChild(loading);
			}
			button.disabled = true;
		}

		// Helper function to hide loading indicator
		function hideLoading(button) {
			const loading = button.querySelector('.loading');
			if (loading) {
				loading.remove();
			}
			button.disabled = false;
		}

		// Helper function to display test results
		function displayResult(containerId, data, isError = false) {
			const container = document.getElementById(containerId);
			container.style.display = 'block';

			const resultContent = container.querySelector('.result-content');

			if (isError) {
				resultContent.innerHTML = `<span class="error">Error: ${data.message}</span>\n\n${data.stack || ''}`;
				return;
			}

			let formattedContent = '';

			// Format response status
			formattedContent += `Status: ${data.status}\n`;

			// Format response data
			if (data.response) {
				if (typeof data.response === 'object') {
					formattedContent += `\nResponse:\n${JSON.stringify(data.response, null, 2)}`;
				} else {
					formattedContent += `\nResponse:\n${data.response}`;
				}
			}

			resultContent.textContent = formattedContent;
		}

		// Test endpoint via proxy
		async function testEndpointViaProxy(endpoint, method, data = null) {
			try {
				const proxyUrl = `${proxyPath}?endpoint=${encodeURIComponent(endpoint)}`;

				const options = {
					method: method,
					headers: {
						'Content-Type': 'application/json'
					},
					credentials: 'include'
				};

				if (data && (method === 'POST' || method === 'PUT')) {
					options.body = JSON.stringify(data);
				}

				const response = await fetch(proxyUrl, options);
				const status = response.status;
				const responseText = await response.text();

				let responseData;
				try {
					responseData = JSON.parse(responseText);
				} catch (e) {
					responseData = responseText;
				}

				return {
					status,
					response: responseData
				};
			} catch (error) {
				throw error;
			}
		}

		// Test endpoint directly
		async function testEndpointDirectly(endpoint, method, data = null) {
			try {
				const directUrl = `${apiBaseUrl}/${endpoint.replace(/^\//, '')}`;

				const options = {
					method: method,
					headers: {
						'Content-Type': 'application/json'
					},
					credentials: 'include'
				};

				if (data && (method === 'POST' || method === 'PUT')) {
					options.body = JSON.stringify(data);
				}

				const response = await fetch(directUrl, options);
				const status = response.status;
				const responseText = await response.text();

				let responseData;
				try {
					responseData = JSON.parse(responseText);
				} catch (e) {
					responseData = responseText;
				}

				return {
					status,
					response: responseData
				};
			} catch (error) {
				throw error;
			}
		}

		// Setup event listeners for predefined endpoints
		document.querySelectorAll('.test-proxy').forEach(button => {
			button.addEventListener('click', async function() {
				const index = this.getAttribute('data-index');
				const endpoint = apiEndpoints[index][0];
				const method = apiEndpoints[index][1];
				const data = apiEndpoints[index][3];
				const resultContainerId = `result-proxy-${index}`;

				showLoading(this);

				try {
					const result = await testEndpointViaProxy(endpoint, method, data);
					displayResult(resultContainerId, result);
				} catch (error) {
					displayResult(resultContainerId, error, true);
				} finally {
					hideLoading(this);
				}
			});
		});

		document.querySelectorAll('.test-direct').forEach(button => {
			button.addEventListener('click', async function() {
				const index = this.getAttribute('data-index');
				const endpoint = apiEndpoints[index][0];
				const method = apiEndpoints[index][1];
				const data = apiEndpoints[index][3];
				const resultContainerId = `result-direct-${index}`;

				showLoading(this);

				try {
					const result = await testEndpointDirectly(endpoint, method, data);
					displayResult(resultContainerId, result);
				} catch (error) {
					displayResult(resultContainerId, error, true);
				} finally {
					hideLoading(this);
				}
			});
		});

		// Setup event listeners for custom endpoint test
		document.getElementById('test-custom-proxy').addEventListener('click', async function() {
			const endpoint = document.getElementById('custom-endpoint').value.trim();
			const method = document.getElementById('custom-method').value;
			const dataStr = document.getElementById('custom-data').value.trim();

			if (!endpoint) {
				alert('Please enter an endpoint path');
				return;
			}

			let data = null;
			if (dataStr && (method === 'POST' || method === 'PUT')) {
				try {
					data = JSON.parse(dataStr);
				} catch (e) {
					alert('Invalid JSON data');
					return;
				}
			}

			showLoading(this);

			try {
				const result = await testEndpointViaProxy(endpoint, method, data);
				displayResult('custom-result', result);
			} catch (error) {
				displayResult('custom-result', error, true);
			} finally {
				hideLoading(this);
				document.getElementById('custom-result').style.display = 'block';
			}
		});

		document.getElementById('test-custom-direct').addEventListener('click', async function() {
			const endpoint = document.getElementById('custom-endpoint').value.trim();
			const method = document.getElementById('custom-method').value;
			const dataStr = document.getElementById('custom-data').value.trim();

			if (!endpoint) {
				alert('Please enter an endpoint path');
				return;
			}

			let data = null;
			if (dataStr && (method === 'POST' || method === 'PUT')) {
				try {
					data = JSON.parse(dataStr);
				} catch (e) {
					alert('Invalid JSON data');
					return;
				}
			}

			showLoading(this);

			try {
				const result = await testEndpointDirectly(endpoint, method, data);
				displayResult('custom-result', result);
			} catch (error) {
				displayResult('custom-result', error, true);
			} finally {
				hideLoading(this);
				document.getElementById('custom-result').style.display = 'block';
			}
		});
	</script>
</body>

</html>