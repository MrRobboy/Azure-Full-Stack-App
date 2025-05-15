<?php
header('Content-Type: text/html; charset=UTF-8');

// Configuration
$api_base_url = 'https://app-backend-esgi-app.azurewebsites.net';
$test_endpoint = 'auth/login';
$test_data = ['email' => 'admin@test.com', 'password' => 'password123'];

// URL patterns to try - add more patterns based on common frameworks
$url_patterns = [
	// Basic variations
	"{base}/api/{endpoint}",
	"{base}/{endpoint}",

	// Framework specific patterns
	"{base}/index.php/api/{endpoint}",
	"{base}/index.php/{endpoint}",
	"{base}/public/api/{endpoint}",
	"{base}/public/index.php/api/{endpoint}",
	"{base}/public/{endpoint}",

	// API version patterns
	"{base}/v1/api/{endpoint}",
	"{base}/v1/{endpoint}",
	"{base}/api/v1/{endpoint}",

	// Special cases for authentication
	"{base}/api/auth/login",
	"{base}/auth/login",
	"{base}/login",
	"{base}/api/login"
];

// Try a HEAD request first to check if server exists
$ch = curl_init($api_base_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_exec($ch);
$server_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$server_available = ($server_status >= 200 && $server_status < 500);

// Generate URLs to test
$urls_to_test = [];
foreach ($url_patterns as $pattern) {
	$url = str_replace(
		['{base}', '{endpoint}'],
		[$api_base_url, $test_endpoint],
		$pattern
	);
	$urls_to_test[] = $url;
}

// Remove duplicates
$urls_to_test = array_unique($urls_to_test);

?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>API Path Finder</title>
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

		.url-item {
			margin-bottom: 10px;
			padding: 10px;
			border: 1px solid #ddd;
			border-radius: 4px;
		}

		.url-item.success {
			background-color: rgba(0, 128, 0, 0.1);
			border-color: green;
		}

		.url-item.error {
			background-color: rgba(255, 0, 0, 0.05);
		}

		.url-item h3 {
			margin-top: 0;
		}

		pre {
			background-color: #f4f4f4;
			border: 1px solid #ddd;
			padding: 10px;
			overflow: auto;
			max-height: 200px;
			white-space: pre-wrap;
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

		.result-container {
			margin-top: 10px;
		}

		.results-summary {
			margin-top: 20px;
			padding: 15px;
			background-color: #f0f0f0;
			border-radius: 4px;
		}

		.try-form {
			margin-top: 20px;
			padding: 15px;
			background-color: #f0f8ff;
			border-radius: 4px;
		}

		.try-form input {
			width: 100%;
			padding: 8px;
			margin-bottom: 10px;
		}
	</style>
</head>

<body>
	<div class="container">
		<h1>API Path Finder Tool</h1>

		<div class="section">
			<h2>Backend Server Status</h2>
			<p class="<?php echo $server_available ? 'success' : 'error'; ?>">
				Server status: <?php echo $server_status; ?>
				(<?php echo $server_available ? 'Available' : 'May be unavailable'; ?>)
			</p>
			<p>Base URL: <?php echo htmlspecialchars($api_base_url); ?></p>
			<p>Test Endpoint: <?php echo htmlspecialchars($test_endpoint); ?></p>
		</div>

		<div class="section">
			<h2>API URL Pattern Test</h2>
			<p>This tool will test various URL patterns to find the correct API endpoint structure.</p>
			<button id="start-test">Start Testing All Patterns</button>
			<button id="clear-results">Clear Results</button>
			<span id="test-progress" style="margin-left: 10px;"></span>

			<div class="results-summary" id="results-summary" style="display: none;">
				<h3>Summary</h3>
				<p>Tested: <span id="count-tested">0</span> URLs</p>
				<p class="success">Successful: <span id="count-success">0</span> URLs</p>
				<p class="error">Failed: <span id="count-failed">0</span> URLs</p>
			</div>

			<div id="url-results"></div>
		</div>

		<div class="section">
			<h2>Try Custom URL</h2>
			<div class="try-form">
				<p>Test a specific API endpoint:</p>
				<input type="text" id="custom-url" placeholder="Enter full URL (e.g., https://app-backend-esgi-app.azurewebsites.net/api/auth/login)">
				<button id="test-custom-url">Test URL</button>
				<div id="custom-result" class="result-container" style="display: none;"></div>
			</div>
		</div>
	</div>

	<script>
		// List of URLs to test
		const urlsToTest = <?php echo json_encode($urls_to_test); ?>;
		const testData = <?php echo json_encode($test_data); ?>;

		// DOM elements
		const startButton = document.getElementById('start-test');
		const clearButton = document.getElementById('clear-results');
		const resultsContainer = document.getElementById('url-results');
		const progressElement = document.getElementById('test-progress');
		const resultsSummary = document.getElementById('results-summary');
		const countTested = document.getElementById('count-tested');
		const countSuccess = document.getElementById('count-success');
		const countFailed = document.getElementById('count-failed');

		// Test statistics
		let stats = {
			tested: 0,
			success: 0,
			failed: 0
		};

		// Test a single URL
		async function testUrl(url) {
			try {
				const response = await fetch(url, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify(testData)
				});

				const status = response.status;
				let responseText;

				try {
					responseText = await response.text();
				} catch (e) {
					responseText = "Error reading response";
				}

				let responseData;
				try {
					responseData = JSON.parse(responseText);
				} catch (e) {
					responseData = responseText;
				}

				return {
					url,
					status,
					response: responseData,
					success: status >= 200 && status < 300
				};
			} catch (error) {
				return {
					url,
					status: 0,
					response: error.message,
					success: false
				};
			}
		}

		// Display a result
		function displayResult(result) {
			const resultItem = document.createElement('div');
			resultItem.className = `url-item ${result.success ? 'success' : 'error'}`;

			let resultHTML = `
                <h3>${result.success ? '✅ SUCCESS' : '❌ FAILED'}</h3>
                <p><strong>URL:</strong> ${result.url}</p>
                <p><strong>Status:</strong> ${result.status}</p>
            `;

			if (result.response) {
				let responseDisplay;
				if (typeof result.response === 'object') {
					responseDisplay = JSON.stringify(result.response, null, 2);
				} else {
					// Truncate long strings
					responseDisplay = String(result.response).length > 500 ?
						String(result.response).substring(0, 500) + '...' :
						result.response;
				}
				resultHTML += `<pre>${responseDisplay}</pre>`;
			}

			resultItem.innerHTML = resultHTML;
			resultsContainer.appendChild(resultItem);

			// Update stats
			stats.tested++;
			if (result.success) {
				stats.success++;
			} else {
				stats.failed++;
			}

			// Update summary
			countTested.textContent = stats.tested;
			countSuccess.textContent = stats.success;
			countFailed.textContent = stats.failed;
			resultsSummary.style.display = 'block';
		}

		// Test all URLs
		async function testAllUrls() {
			// Reset stats
			stats = {
				tested: 0,
				success: 0,
				failed: 0
			};

			startButton.disabled = true;
			progressElement.innerHTML = `<span class="loading"></span> Testing URLs (0/${urlsToTest.length})`;

			for (let i = 0; i < urlsToTest.length; i++) {
				const url = urlsToTest[i];
				progressElement.innerHTML = `<span class="loading"></span> Testing URLs (${i+1}/${urlsToTest.length})`;

				const result = await testUrl(url);
				displayResult(result);

				// Short delay to avoid overwhelming the server
				await new Promise(resolve => setTimeout(resolve, 100));
			}

			progressElement.innerHTML = `Completed testing ${urlsToTest.length} URLs`;
			startButton.disabled = false;
		}

		// Start testing
		startButton.addEventListener('click', testAllUrls);

		// Clear results
		clearButton.addEventListener('click', function() {
			resultsContainer.innerHTML = '';
			resultsSummary.style.display = 'none';
			stats = {
				tested: 0,
				success: 0,
				failed: 0
			};
			progressElement.innerHTML = '';
		});

		// Test custom URL
		document.getElementById('test-custom-url').addEventListener('click', async function() {
			const customUrl = document.getElementById('custom-url').value.trim();
			if (!customUrl) {
				alert('Please enter a URL to test');
				return;
			}

			this.disabled = true;
			this.innerHTML = '<span class="loading"></span> Testing...';

			const customResult = document.getElementById('custom-result');
			customResult.style.display = 'block';
			customResult.innerHTML = '<p>Testing...</p>';

			const result = await testUrl(customUrl);

			let resultHTML = `
                <h3 class="${result.success ? 'success' : 'error'}">
                    ${result.success ? '✅ SUCCESS' : '❌ FAILED'}
                </h3>
                <p><strong>URL:</strong> ${result.url}</p>
                <p><strong>Status:</strong> ${result.status}</p>
            `;

			if (result.response) {
				let responseDisplay;
				if (typeof result.response === 'object') {
					responseDisplay = JSON.stringify(result.response, null, 2);
				} else {
					responseDisplay = String(result.response);
				}
				resultHTML += `<pre>${responseDisplay}</pre>`;
			}

			customResult.innerHTML = resultHTML;
			this.disabled = false;
			this.innerHTML = 'Test URL';
		});
	</script>
</body>

</html>