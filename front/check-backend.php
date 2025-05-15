<?php
header('Content-Type: text/html; charset=UTF-8');

// List of possible backend base URLs to try
$possible_backends = [
	// Current URL
	"https://app-backend-esgi-app.azurewebsites.net",

	// Common variations
	"https://api-backend-esgi-app.azurewebsites.net",
	"https://backend-esgi-app.azurewebsites.net",
	"https://esgi-app-backend.azurewebsites.net",

	// Try without SSL
	"http://app-backend-esgi-app.azurewebsites.net",

	// Try with different ports
	"https://app-backend-esgi-app.azurewebsites.net:443",
	"https://app-backend-esgi-app.azurewebsites.net:8080",

	// Try the frontend URL (sometimes the backend is on the same server)
	"https://app-frontend-esgi-app.azurewebsites.net",

	// Try other possible Azure domains
	"https://app-backend-esgi-app.azurewebapps.net",
	"https://app-backend-esgi-app.scm.azurewebsites.net"
];

// Pages to check at each backend
$pages_to_check = [
	"",                // Root path
	"/",               // Root with slash
	"/index.php",      // Common entry point
	"/status.php",     // Known working endpoint
	"/api",            // API base
	"/api/",           // API base with slash
	"/api/auth/login"  // Login endpoint
];

// Function to check if a URL is accessible
function checkUrl($url)
{
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	$response = curl_exec($ch);
	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$error = curl_error($ch);
	$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
	$size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
	curl_close($ch);

	return [
		'status' => $status,
		'error' => $error,
		'content_type' => $contentType,
		'size' => $size,
		'response' => substr($response, 0, 500) // First 500 chars
	];
}

// Results storage
$results = [];

// Check each backend
foreach ($possible_backends as $backend) {
	$backend_results = [];

	foreach ($pages_to_check as $page) {
		$url = $backend . $page;
		$result = checkUrl($url);
		$backend_results[$page] = $result;
	}

	$results[$backend] = $backend_results;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Backend Server Check</title>
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

		.warning {
			color: orange;
			font-weight: bold;
		}

		.error {
			color: red;
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
			font-size: 14px;
		}

		th {
			background-color: #f2f2f2;
		}

		tr:nth-child(even) {
			background-color: #f9f9f9;
		}

		pre {
			background-color: #f4f4f4;
			border: 1px solid #ddd;
			padding: 10px;
			overflow: auto;
			max-height: 150px;
			white-space: pre-wrap;
			font-size: 12px;
		}

		.status-good {
			background-color: rgba(0, 128, 0, 0.1);
		}

		.status-maybe {
			background-color: rgba(255, 165, 0, 0.1);
		}

		.status-bad {
			background-color: rgba(255, 0, 0, 0.05);
		}

		.summary-box {
			border: 1px solid #ddd;
			padding: 15px;
			margin-top: 20px;
			border-radius: 4px;
			background-color: #f0f8ff;
		}

		.manual-check {
			margin-top: 20px;
			padding: 15px;
			background-color: #f0f8ff;
			border-radius: 4px;
		}

		input[type="text"] {
			width: 100%;
			padding: 8px;
			margin-bottom: 10px;
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
	</style>
</head>

<body>
	<div class="container">
		<h1>Backend Server Check</h1>
		<p>This tool checks various potential backend URLs to find the correct one.</p>

		<div class="section">
			<h2>Summary of Findings</h2>
			<div class="summary-box">
				<?php
				$working_urls = [];
				$maybe_urls = [];

				foreach ($results as $backend => $pages) {
					foreach ($pages as $page => $result) {
						$url = $backend . $page;
						$status = $result['status'];

						// Consider 200-299 as working
						if ($status >= 200 && $status < 300) {
							$working_urls[$url] = $result;
						}
						// Consider 300-399 as maybe working (redirects)
						else if ($status >= 300 && $status < 400) {
							$maybe_urls[$url] = $result;
						}
					}
				}

				if (count($working_urls) > 0) {
					echo "<h3 class='success'>✅ Working URLs Found</h3>";
					echo "<ul>";
					foreach ($working_urls as $url => $result) {
						$content_type = htmlspecialchars($result['content_type'] ?? 'Unknown');
						echo "<li><strong>" . htmlspecialchars($url) . "</strong> - Status: {$result['status']}, Type: {$content_type}</li>";
					}
					echo "</ul>";
				} else if (count($maybe_urls) > 0) {
					echo "<h3 class='warning'>⚠️ Potential URLs Found (Redirects)</h3>";
					echo "<ul>";
					foreach ($maybe_urls as $url => $result) {
						echo "<li><strong>" . htmlspecialchars($url) . "</strong> - Status: {$result['status']}</li>";
					}
					echo "</ul>";
				} else {
					echo "<h3 class='error'>❌ No Working URLs Found</h3>";
					echo "<p>All tested URLs returned error status codes.</p>";
				}
				?>

				<h3>Recommendation:</h3>
				<p>
					<?php
					if (count($working_urls) > 0) {
						$best_url = array_keys($working_urls)[0];
						echo "Try using <strong>" . htmlspecialchars($best_url) . "</strong> as your backend API URL.";
					} else if (count($maybe_urls) > 0) {
						$best_url = array_keys($maybe_urls)[0];
						echo "Try following the redirect at <strong>" . htmlspecialchars($best_url) . "</strong> to find the actual API URL.";
					} else {
						echo "Check with your system administrator to confirm the correct backend URL.";
					}
					?>
				</p>
			</div>
		</div>

		<div class="section">
			<h2>Detailed Results</h2>
			<?php foreach ($results as $backend => $pages): ?>
				<h3><?php echo htmlspecialchars($backend); ?></h3>
				<table>
					<tr>
						<th>Path</th>
						<th>Status</th>
						<th>Content Type</th>
						<th>Size</th>
						<th>Response Preview</th>
					</tr>
					<?php foreach ($pages as $page => $result):
						$status_class = '';
						if ($result['status'] >= 200 && $result['status'] < 300) {
							$status_class = 'status-good';
						} else if ($result['status'] >= 300 && $result['status'] < 400) {
							$status_class = 'status-maybe';
						} else {
							$status_class = 'status-bad';
						}
					?>
						<tr class="<?php echo $status_class; ?>">
							<td><?php echo htmlspecialchars($page); ?></td>
							<td><?php echo $result['status']; ?></td>
							<td><?php echo htmlspecialchars($result['content_type'] ?? 'N/A'); ?></td>
							<td><?php echo $result['size']; ?></td>
							<td>
								<?php if (!empty($result['response'])): ?>
									<pre><?php echo htmlspecialchars($result['response']); ?></pre>
								<?php else: ?>
									<em>No response or empty</em>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php endforeach; ?>
		</div>

		<div class="section">
			<h2>Manual URL Check</h2>
			<div class="manual-check">
				<p>Enter a specific URL to test:</p>
				<input type="text" id="manual-url" placeholder="Enter full URL (e.g., https://api.example.com/status)">
				<button id="check-url">Check URL</button>
				<div id="url-result" style="margin-top: 10px; display: none;"></div>
			</div>
		</div>
	</div>

	<script>
		document.getElementById('check-url').addEventListener('click', async function() {
			const url = document.getElementById('manual-url').value.trim();
			if (!url) {
				alert('Please enter a URL to check');
				return;
			}

			this.disabled = true;
			this.innerHTML = 'Checking...';

			const resultElement = document.getElementById('url-result');
			resultElement.style.display = 'block';
			resultElement.innerHTML = '<p>Checking URL...</p>';

			try {
				const response = await fetch(url, {
					method: 'GET',
					headers: {
						'Accept': 'application/json, text/plain, */*'
					}
				});

				const status = response.status;
				let responseText = await response.text();

				let statusClass = 'error';
				if (status >= 200 && status < 300) {
					statusClass = 'success';
				} else if (status >= 300 && status < 400) {
					statusClass = 'warning';
				}

				let resultHTML = `
                    <h3 class="${statusClass}">Status: ${status}</h3>
                    <p><strong>URL:</strong> ${url}</p>
                    <p><strong>Headers:</strong></p>
                    <pre>`;

				// Get response headers
				let headers = '';
				response.headers.forEach((value, key) => {
					headers += `${key}: ${value}\n`;
				});

				resultHTML += headers + '</pre>';

				if (responseText) {
					// Try to format as JSON if possible
					try {
						const json = JSON.parse(responseText);
						responseText = JSON.stringify(json, null, 2);
					} catch (e) {
						// Not JSON, leave as is
					}

					resultHTML += `<p><strong>Response Body:</strong></p><pre>${responseText}</pre>`;
				}

				resultElement.innerHTML = resultHTML;
			} catch (error) {
				resultElement.innerHTML = `
                    <h3 class="error">Error</h3>
                    <p>${error.message}</p>
                `;
			} finally {
				this.disabled = false;
				this.innerHTML = 'Check URL';
			}
		});
	</script>
</body>

</html>