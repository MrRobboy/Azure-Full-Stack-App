<?php
header('Content-Type: text/html');
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>

<head>
	<title>API Debug Panel</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			max-width: 1000px;
			margin: 0 auto;
			padding: 20px;
		}

		.panel {
			background: #f9f9f9;
			border: 1px solid #ddd;
			padding: 15px;
			margin-bottom: 20px;
			border-radius: 4px;
		}

		.response {
			background: #f5f5f5;
			padding: 15px;
			border: 1px solid #ddd;
			border-radius: 4px;
			max-height: 300px;
			overflow: auto;
			margin-top: 10px;
		}

		.success {
			color: green;
		}

		.error {
			color: red;
		}

		.warn {
			color: orange;
		}

		.code {
			font-family: monospace;
			background: #f0f0f0;
			padding: 2px 4px;
			border-radius: 2px;
		}

		table {
			width: 100%;
			border-collapse: collapse;
		}

		th,
		td {
			padding: 8px;
			text-align: left;
			border-bottom: 1px solid #ddd;
		}

		th {
			background-color: #f2f2f2;
		}

		button {
			padding: 8px 12px;
			background: #4285f4;
			color: white;
			border: none;
			border-radius: 4px;
			cursor: pointer;
			margin: 5px;
		}

		select,
		input {
			padding: 8px;
			margin: 5px;
			border: 1px solid #ddd;
			border-radius: 4px;
		}
	</style>
</head>

<body>
	<h1>API Debug Panel</h1>

	<div class="panel">
		<h2>API Endpoint Tester</h2>
		<form id="apiForm">
			<div>
				<label for="proxyFile">Proxy File:</label>
				<select id="proxyFile" name="proxyFile">
					<option value="unified-proxy.php">unified-proxy.php</option>
					<option value="matieres-proxy.php">matieres-proxy.php</option>
					<option value="simple-proxy.php">simple-proxy.php</option>
					<option value="api-bridge.php">api-bridge.php</option>
				</select>
			</div>
			<div>
				<label for="endpoint">API Endpoint:</label>
				<input type="text" id="endpoint" name="endpoint" value="matieres" size="40">
			</div>
			<div>
				<label for="method">HTTP Method:</label>
				<select id="method" name="method">
					<option value="GET">GET</option>
					<option value="POST">POST</option>
					<option value="PUT">PUT</option>
					<option value="DELETE">DELETE</option>
				</select>
			</div>
			<div>
				<label for="requestBody">Request Body (JSON):</label>
				<textarea id="requestBody" name="requestBody" rows="5" cols="60" placeholder="Enter JSON data"></textarea>
			</div>
			<button type="submit">Send Request</button>
		</form>

		<div id="apiResponse" class="response" style="display:none;">
			<h3>Response:</h3>
			<pre id="responseContent"></pre>
		</div>
	</div>

	<div class="panel">
		<h2>Common API Endpoints</h2>
		<table>
			<tr>
				<th>Endpoint</th>
				<th>Description</th>
				<th>Action</th>
			</tr>
			<tr>
				<td class="code">matieres</td>
				<td>Get all subjects (matières)</td>
				<td><button onclick="testEndpoint('matieres')">Test</button></td>
			</tr>
			<tr>
				<td class="code">api/user/profile</td>
				<td>Get current user profile</td>
				<td><button onclick="testEndpoint('api/user/profile')">Test</button></td>
			</tr>
			<tr>
				<td class="code">api/classes</td>
				<td>Get all classes</td>
				<td><button onclick="testEndpoint('api/classes')">Test</button></td>
			</tr>
			<tr>
				<td class="code">api/profs</td>
				<td>Get all professors</td>
				<td><button onclick="testEndpoint('api/profs')">Test</button></td>
			</tr>
			<tr>
				<td class="code">api/eleves</td>
				<td>Get all students</td>
				<td><button onclick="testEndpoint('api/eleves')">Test</button></td>
			</tr>
			<tr>
				<td class="code">status.php</td>
				<td>Application status check</td>
				<td><button onclick="testEndpoint('status.php')">Test</button></td>
			</tr>
		</table>
	</div>

	<div class="panel">
		<h2>Proxy Diagnostics</h2>
		<div>
			<button onclick="checkProxyFiles()">Check Proxy Files</button>
			<button onclick="window.location.href='deep-proxy-test.php'">Run Deep Proxy Test</button>
			<button onclick="window.location.href='repair-proxy.php'">Repair Proxy System</button>
		</div>
		<div id="proxyDiagnostics" class="response" style="display:none;"></div>
	</div>

	<script>
		// Function to test an endpoint
		function testEndpoint(endpoint) {
			document.getElementById('endpoint').value = endpoint;
			document.getElementById('apiForm').dispatchEvent(new Event('submit'));
		}

		// Handle form submission
		document.getElementById('apiForm').addEventListener('submit', async function(e) {
			e.preventDefault();

			const proxyFile = document.getElementById('proxyFile').value;
			const endpoint = document.getElementById('endpoint').value;
			const method = document.getElementById('method').value;
			const requestBody = document.getElementById('requestBody').value;

			const responseDiv = document.getElementById('apiResponse');
			const responseContent = document.getElementById('responseContent');
			responseDiv.style.display = 'block';
			responseContent.innerHTML = 'Loading...';

			try {
				const url = `${proxyFile}?endpoint=${encodeURIComponent(endpoint)}`;

				const options = {
					method: method,
					headers: {
						'Content-Type': 'application/json',
						'Accept': 'application/json'
					}
				};

				// Add request body for POST, PUT requests
				if (['POST', 'PUT'].includes(method) && requestBody.trim()) {
					try {
						// Validate JSON
						JSON.parse(requestBody);
						options.body = requestBody;
					} catch (e) {
						responseContent.innerHTML = `<span class="error">Invalid JSON in request body: ${e.message}</span>`;
						return;
					}
				}

				// Make the request
				const response = await fetch(url, options);
				const contentType = response.headers.get('content-type');

				// Display status code
				let statusText = response.ok ?
					`<span class="success">Status: ${response.status} ${response.statusText}</span>` :
					`<span class="error">Status: ${response.status} ${response.statusText}</span>`;

				// Handle response based on content type
				if (contentType && contentType.includes('application/json')) {
					try {
						const data = await response.json();
						responseContent.innerHTML = statusText + '<br><br>' + JSON.stringify(data, null, 2);
					} catch (e) {
						const text = await response.text();
						responseContent.innerHTML = statusText +
							`<br><br><span class="error">Error parsing JSON: ${e.message}</span>` +
							`<br><br>Raw response:<br>${text}`;
					}
				} else {
					const text = await response.text();
					responseContent.innerHTML = statusText +
						`<br><br>Content-Type: ${contentType || 'none'}<br><br>${text}`;
				}
			} catch (error) {
				responseContent.innerHTML = `<span class="error">Error: ${error.message}</span>`;
			}
		});

		// Check proxy files
		async function checkProxyFiles() {
			const diagnosticsDiv = document.getElementById('proxyDiagnostics');
			diagnosticsDiv.style.display = 'block';
			diagnosticsDiv.innerHTML = 'Checking proxy files...';

			const proxyFiles = [
				'unified-proxy.php',
				'matieres-proxy.php',
				'simple-proxy.php',
				'api-bridge.php',
				'status.php'
			];

			let resultsHtml = '<h3>Proxy File Check</h3><table><tr><th>File</th><th>Status</th><th>Size</th></tr>';

			for (const file of proxyFiles) {
				try {
					const response = await fetch(file, {
						method: 'HEAD'
					});
					const status = response.ok ?
						`<span class="success">Found (${response.status})</span>` :
						`<span class="error">Error (${response.status})</span>`;

					// Try to get file size
					const contentLength = response.headers.get('content-length') || 'Unknown';

					resultsHtml += `<tr><td>${file}</td><td>${status}</td><td>${contentLength} bytes</td></tr>`;
				} catch (error) {
					resultsHtml += `<tr><td>${file}</td><td><span class="error">Not found</span></td><td>-</td></tr>`;
				}
			}

			resultsHtml += '</table>';

			// Add recommendation based on results
			resultsHtml += '<h3>Recommendations</h3>';
			resultsHtml += '<ul>';
			resultsHtml += '<li>If files are missing, run the <a href="repair-proxy.php">Repair Proxy System</a> tool</li>';
			resultsHtml += '<li>If you encounter 404 errors, make sure your web.config allows PHP files to be executed</li>';
			resultsHtml += '<li>For permission issues in Azure, you may need to redeploy the application</li>';
			resultsHtml += '</ul>';

			diagnosticsDiv.innerHTML = resultsHtml;
		}
	</script>
</body>

</html>