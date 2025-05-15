<?php
// API Diagnostics - Tests multiple backend URL configurations to find what works
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

// Set time limit to avoid timeouts during testing
set_time_limit(60);

// Basic configuration - try these backend URLs
$possible_backends = [
	'https://app-backend-esgi-app.azurewebsites.net',  // Original configured backend
	'https://app-frontend-esgi-app.azurewebsites.net', // Same as frontend (might be on same server)
	// Add more variants if needed
];

// Different API endpoint patterns to test
$endpoint_patterns = [
	'status.php',           // Basic status endpoint
	'api/auth/login',       // Standard API path with api/ prefix
	'auth/login',           // API path without api/ prefix
	'/api/auth/login',      // API path with leading slash
	'/auth/login',          // API path with leading slash, no api/ prefix
];

// Function to make a test request
function test_endpoint($base_url, $endpoint, $method = 'GET', $post_data = null)
{
	$url = rtrim($base_url, '/') . '/' . ltrim($endpoint, '/');
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

	// Add headers
	$headers = ['Content-Type: application/json'];
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	// If POST data is provided
	if ($method === 'POST' && $post_data) {
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	}

	$response = curl_exec($ch);
	$info = curl_getinfo($ch);
	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$error = curl_error($ch);
	curl_close($ch);

	$content_type = isset($info['content_type']) ? $info['content_type'] : 'unknown';

	// Format the response data for display
	if (strpos($content_type, 'json') !== false && $response) {
		try {
			$json = json_decode($response, true);
			if ($json) {
				$formatted_response = json_encode($json, JSON_PRETTY_PRINT);
			} else {
				$formatted_response = $response;
			}
		} catch (Exception $e) {
			$formatted_response = $response;
		}
	} else {
		// Truncate long responses
		if (strlen($response) > 500) {
			$formatted_response = substr($response, 0, 500) . '... (truncated)';
		} else {
			$formatted_response = $response;
		}
	}

	return [
		'url' => $url,
		'method' => $method,
		'status' => $status,
		'content_type' => $content_type,
		'response' => $formatted_response,
		'error' => $error,
		'time' => $info['total_time'],
		'success' => ($status >= 200 && $status < 300)
	];
}

// Start the tests
$results = [];

// Test proxy first
$proxy_url = 'simple-proxy.php';
$proxy_status_url = $proxy_url . '?endpoint=status.php';
$proxy_result = test_endpoint('./', $proxy_status_url);
$results['proxy'] = [
	'name' => 'Proxy Test (simple-proxy.php)',
	'url' => $proxy_url,
	'tests' => ['status.php' => $proxy_result]
];

// Test direct backend access for each backend URL
foreach ($possible_backends as $backend) {
	$backend_results = [];

	// Test each endpoint pattern
	foreach ($endpoint_patterns as $endpoint) {
		$backend_results[$endpoint] = test_endpoint($backend, $endpoint);
	}

	// If testing a login endpoint, also try a POST request
	$login_data = json_encode([
		'email' => 'admin@test.com',
		'password' => 'password'
	]);
	$backend_results['POST api/auth/login'] = test_endpoint($backend, 'api/auth/login', 'POST', $login_data);

	$results['backends'][] = [
		'name' => "Backend: $backend",
		'url' => $backend,
		'tests' => $backend_results
	];
}

// Generate HTML report
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>API Diagnostics Results</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			margin: 20px;
			line-height: 1.6;
		}

		.container {
			max-width: 1200px;
			margin: 0 auto;
		}

		h1,
		h2,
		h3 {
			color: #333;
		}

		table {
			width: 100%;
			border-collapse: collapse;
			margin-bottom: 20px;
		}

		th,
		td {
			padding: 10px;
			border: 1px solid #ddd;
			text-align: left;
		}

		th {
			background-color: #f5f5f5;
		}

		.success {
			background-color: #dff0d8;
			color: #3c763d;
		}

		.error {
			background-color: #f2dede;
			color: #a94442;
		}

		.warning {
			background-color: #fcf8e3;
			color: #8a6d3b;
		}

		pre {
			background-color: #f5f5f5;
			padding: 10px;
			border-radius: 4px;
			overflow-x: auto;
			max-height: 200px;
			overflow-y: auto;
			font-size: 12px;
		}

		.summary {
			margin-bottom: 30px;
			padding: 15px;
			border-radius: 4px;
			background-color: #f9f9f9;
			border-left: 5px solid #5bc0de;
		}
	</style>
</head>

<body>
	<div class="container">
		<h1>API Diagnostics Results</h1>

		<div class="summary">
			<h2>Summary</h2>
			<p>Timestamp: <?= date('Y-m-d H:i:s') ?></p>
			<p>Server: <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></p>
			<p>Host: <?= $_SERVER['HTTP_HOST'] ?? 'Unknown' ?></p>
		</div>

		<h2>Proxy Test Results</h2>
		<table>
			<tr>
				<th>Endpoint</th>
				<th>Status</th>
				<th>Time (s)</th>
				<th>Content Type</th>
				<th>Response</th>
			</tr>
			<?php foreach ($results['proxy']['tests'] as $endpoint => $result): ?>
				<tr class="<?= $result['success'] ? 'success' : 'error' ?>">
					<td><?= htmlspecialchars($result['url']) ?></td>
					<td><?= $result['status'] ?></td>
					<td><?= round($result['time'], 3) ?></td>
					<td><?= htmlspecialchars($result['content_type']) ?></td>
					<td>
						<?php if ($result['error']): ?>
							<p class="error">Error: <?= htmlspecialchars($result['error']) ?></p>
						<?php endif; ?>
						<pre><?= htmlspecialchars($result['response']) ?></pre>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>

		<h2>Backend Tests</h2>
		<?php foreach ($results['backends'] as $backend): ?>
			<h3><?= htmlspecialchars($backend['name']) ?></h3>
			<table>
				<tr>
					<th>Endpoint</th>
					<th>Method</th>
					<th>Status</th>
					<th>Time (s)</th>
					<th>Content Type</th>
					<th>Response</th>
				</tr>
				<?php foreach ($backend['tests'] as $endpoint => $result): ?>
					<tr class="<?= $result['success'] ? 'success' : 'error' ?>">
						<td><?= htmlspecialchars($result['url']) ?></td>
						<td><?= $result['method'] ?></td>
						<td><?= $result['status'] ?></td>
						<td><?= round($result['time'], 3) ?></td>
						<td><?= htmlspecialchars($result['content_type']) ?></td>
						<td>
							<?php if ($result['error']): ?>
								<p class="error">Error: <?= htmlspecialchars($result['error']) ?></p>
							<?php endif; ?>
							<pre><?= htmlspecialchars($result['response']) ?></pre>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php endforeach; ?>

		<h2>Recommendations</h2>
		<ul>
			<?php if ($results['proxy']['tests']['status.php']['success']): ?>
				<li class="success">✅ Proxy is working for status.php</li>
			<?php else: ?>
				<li class="error">❌ Proxy is not working correctly for status.php</li>
			<?php endif; ?>

			<?php
			$working_backends = 0;
			$working_login = false;

			foreach ($results['backends'] as $backend) {
				if (isset($backend['tests']['status.php']) && $backend['tests']['status.php']['success']) {
					$working_backends++;
				}

				if (
					isset($backend['tests']['POST api/auth/login']) &&
					($backend['tests']['POST api/auth/login']['status'] == 200 ||
						$backend['tests']['POST api/auth/login']['status'] == 401)
				) {
					$working_login = true;
				}
			}
			?>

			<?php if ($working_backends > 0): ?>
				<li class="success">✅ Found <?= $working_backends ?> reachable backend server(s)</li>
			<?php else: ?>
				<li class="error">❌ No backend servers are reachable - API calls will fail</li>
			<?php endif; ?>

			<?php if ($working_login): ?>
				<li class="success">✅ Login endpoint is available</li>
			<?php else: ?>
				<li class="warning">⚠️ Login endpoint returns errors - consider using mock responses</li>
			<?php endif; ?>
		</ul>

		<h2>Conclusion</h2>
		<div class="summary">
			<?php if ($results['proxy']['tests']['status.php']['success'] && $working_backends > 0): ?>
				<p>The basic connectivity is working, but API endpoints may need additional configuration.</p>
				<p>If API endpoints are returning 404, consider:</p>
				<ol>
					<li>Checking if the API is properly deployed on the backend server</li>
					<li>Enable mock responses in simple-proxy.php for testing</li>
					<li>Verify the correct API paths on the backend</li>
				</ol>
			<?php elseif (!$results['proxy']['tests']['status.php']['success']): ?>
				<p>The proxy isn't working at all. Check:</p>
				<ol>
					<li>Proxy file exists and is accessible</li>
					<li>PHP is properly configured on the server</li>
					<li>Network connectivity between frontend and backend</li>
				</ol>
			<?php else: ?>
				<p>Backend servers cannot be reached. Check:</p>
				<ol>
					<li>Backend URLs are correct</li>
					<li>Backend servers are running</li>
					<li>Network connectivity between frontend and backend</li>
				</ol>
			<?php endif; ?>
		</div>
	</div>
</body>

</html>