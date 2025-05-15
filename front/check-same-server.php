<?php
header('Content-Type: text/html; charset=UTF-8');

// Check if we're on Azure
$hostname = $_SERVER['HTTP_HOST'] ?? '';
$is_azure = strpos($hostname, 'azurewebsites.net') !== false;

// Get current server info
$server_info = [
	'hostname' => $_SERVER['HTTP_HOST'] ?? 'Unknown',
	'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
	'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
	'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown',
	'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
	'server_addr' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
	'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
	'php_version' => PHP_VERSION,
	'current_dir' => __DIR__,
	'parent_dir' => dirname(__DIR__),
];

// Check for status.php in various local directories
$local_paths = [
	__DIR__ . '/status.php',
	__DIR__ . '/api/status.php',
	dirname(__DIR__) . '/status.php',
	dirname(__DIR__) . '/api/status.php',
	dirname(__DIR__) . '/backend/status.php',
	dirname(__DIR__) . '/backend/api/status.php',
	dirname(dirname(__DIR__)) . '/status.php',
	dirname(dirname(__DIR__)) . '/backend/status.php',
];

$local_status_file = null;
foreach ($local_paths as $path) {
	if (file_exists($path)) {
		$local_status_file = $path;
		break;
	}
}

// Check local directory structure
$dirs_to_check = [
	__DIR__,
	dirname(__DIR__),
	dirname(dirname(__DIR__)),
];

$directory_listings = [];
foreach ($dirs_to_check as $dir) {
	if (is_dir($dir) && is_readable($dir)) {
		$files = scandir($dir);
		$directory_listings[$dir] = $files;
	}
}

// Check if current domain has API or backend path
$api_paths_to_check = [
	'/api/',
	'/api/status.php',
	'/backend/',
	'/backend/api/',
	'/backend/status.php',
	'/api/auth/login',
	'/auth/login'
];

$url_checks = [];
$http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
$protocol = $is_https ? 'https://' : 'http://';
$base_url = $protocol . $http_host;

foreach ($api_paths_to_check as $path) {
	$url = $base_url . $path;
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$response = curl_exec($ch);
	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
	curl_close($ch);

	$url_checks[$path] = [
		'url' => $url,
		'status' => $status,
		'content_type' => $content_type,
		'response_preview' => substr($response, 0, 200)
	];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Same-Server API Check</title>
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

		.status-good {
			background-color: rgba(0, 128, 0, 0.1);
		}

		.status-maybe {
			background-color: rgba(255, 165, 0, 0.1);
		}

		.status-bad {
			background-color: rgba(255, 0, 0, 0.05);
		}
	</style>
</head>

<body>
	<div class="container">
		<h1>Same-Server API Check</h1>
		<p>This tool checks if the backend API might be on the same server as the frontend.</p>

		<div class="section">
			<h2>Server Information</h2>
			<table>
				<?php foreach ($server_info as $key => $value): ?>
					<tr>
						<th><?php echo htmlspecialchars($key); ?></th>
						<td><?php echo htmlspecialchars($value); ?></td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<div class="section">
			<h2>Local API Check</h2>
			<?php if ($local_status_file): ?>
				<p class="success">✅ Found local status.php file at: <?php echo htmlspecialchars($local_status_file); ?></p>
				<p>Content:</p>
				<pre><?php echo htmlspecialchars(file_get_contents($local_status_file)); ?></pre>
			<?php else: ?>
				<p class="error">❌ No local status.php file found in the checked directories.</p>
			<?php endif; ?>
		</div>

		<div class="section">
			<h2>Directory Structure</h2>
			<?php foreach ($directory_listings as $dir => $files): ?>
				<h3><?php echo htmlspecialchars($dir); ?></h3>
				<ul>
					<?php foreach ($files as $file): ?>
						<li><?php echo htmlspecialchars($file); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endforeach; ?>
		</div>

		<div class="section">
			<h2>Same-Domain API Check</h2>
			<p>Checking if API endpoints exist on the same domain (<?php echo htmlspecialchars($base_url); ?>):</p>
			<table>
				<tr>
					<th>Path</th>
					<th>Status</th>
					<th>Content Type</th>
					<th>Response Preview</th>
				</tr>
				<?php foreach ($url_checks as $path => $check):
					$status_class = '';
					if ($check['status'] >= 200 && $check['status'] < 300) {
						$status_class = 'status-good';
					} else if ($check['status'] >= 300 && $check['status'] < 400) {
						$status_class = 'status-maybe';
					} else {
						$status_class = 'status-bad';
					}
				?>
					<tr class="<?php echo $status_class; ?>">
						<td><?php echo htmlspecialchars($path); ?></td>
						<td><?php echo $check['status']; ?></td>
						<td><?php echo htmlspecialchars($check['content_type'] ?? 'N/A'); ?></td>
						<td>
							<?php if (!empty($check['response_preview'])): ?>
								<pre><?php echo htmlspecialchars($check['response_preview']); ?></pre>
							<?php else: ?>
								<em>No response or empty</em>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>

		<div class="section">
			<h2>Summary</h2>
			<?php
			$found_local = $local_status_file !== null;

			$found_api = false;
			foreach ($url_checks as $check) {
				if ($check['status'] >= 200 && $check['status'] < 300) {
					$found_api = true;
					break;
				}
			}

			if ($found_local || $found_api):
			?>
				<p class="success">✅ There's evidence that the API might be on the same server as the frontend.</p>
				<p>Recommendations:</p>
				<ul>
					<?php if ($found_local): ?>
						<li>Use local file paths to access the API rather than making HTTP requests.</li>
					<?php endif; ?>

					<?php if ($found_api): ?>
						<li>Use relative URLs for API endpoints (e.g., "/api/auth/login" instead of the full URL).</li>
					<?php endif; ?>

					<li>Update your configuration to use the same-domain API endpoints instead of cross-domain ones.</li>
				</ul>
			<?php else: ?>
				<p class="error">❌ No evidence found that the API is on the same server as the frontend.</p>
				<p>Make sure the backend server is properly configured and accessible.</p>
			<?php endif; ?>
		</div>
	</div>
</body>

</html>