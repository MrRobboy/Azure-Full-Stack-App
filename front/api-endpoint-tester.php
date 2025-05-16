<?php
header('Content-Type: text/html');
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : 'matieres';
$method = isset($_GET['method']) ? strtoupper($_GET['method']) : 'GET';
$proxyFile = isset($_GET['proxy']) ? $_GET['proxy'] : 'unified-proxy.php';
?>
<!DOCTYPE html>
<html>

<head>
	<title>API Endpoint Tester</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			max-width: 800px;
			margin: 0 auto;
			padding: 20px;
		}

		.result {
			background: #f5f5f5;
			padding: 15px;
			border-radius: 5px;
			margin-top: 20px;
		}

		.success {
			color: green;
		}

		.error {
			color: red;
		}

		pre {
			background: #f0f0f0;
			padding: 10px;
			border-radius: 5px;
			overflow: auto;
			max-height: 300px;
		}

		form {
			margin-bottom: 20px;
		}

		label {
			display: inline-block;
			width: 150px;
			margin-bottom: 10px;
		}

		input,
		select {
			padding: 5px;
			width: 300px;
		}

		button {
			padding: 10px 15px;
			background: #4285f4;
			color: white;
			border: none;
			border-radius: 4px;
			cursor: pointer;
			margin-top: 10px;
		}
	</style>
</head>

<body>
	<h1>API Endpoint Tester</h1>

	<form method="GET">
		<div>
			<label for="endpoint">API Endpoint:</label>
			<input type="text" id="endpoint" name="endpoint" value="<?php echo htmlspecialchars($endpoint); ?>">
		</div>
		<div>
			<label for="method">HTTP Method:</label>
			<select id="method" name="method">
				<option value="GET" <?php echo ($method == 'GET') ? 'selected' : ''; ?>>GET</option>
				<option value="POST" <?php echo ($method == 'POST') ? 'selected' : ''; ?>>POST</option>
				<option value="PUT" <?php echo ($method == 'PUT') ? 'selected' : ''; ?>>PUT</option>
				<option value="DELETE" <?php echo ($method == 'DELETE') ? 'selected' : ''; ?>>DELETE</option>
			</select>
		</div>
		<div>
			<label for="proxy">Proxy File:</label>
			<select id="proxy" name="proxy">
				<option value="unified-proxy.php" <?php echo ($proxyFile == 'unified-proxy.php') ? 'selected' : ''; ?>>unified-proxy.php</option>
				<option value="matieres-proxy.php" <?php echo ($proxyFile == 'matieres-proxy.php') ? 'selected' : ''; ?>>matieres-proxy.php</option>
				<option value="simple-proxy.php" <?php echo ($proxyFile == 'simple-proxy.php') ? 'selected' : ''; ?>>simple-proxy.php</option>
				<option value="api-bridge.php" <?php echo ($proxyFile == 'api-bridge.php') ? 'selected' : ''; ?>>api-bridge.php</option>
			</select>
		</div>
		<button type="submit">Test Endpoint</button>
	</form>

	<?php
	if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($endpoint)) {
		echo "<h2>Testing: {$endpoint} via {$proxyFile}</h2>";

		try {
			$proxyUrl = "{$proxyFile}?endpoint=" . urlencode($endpoint);

			// Check if proxy file exists
			if (!file_exists($proxyFile)) {
				throw new Exception("Proxy file {$proxyFile} does not exist");
			}

			// Log the request
			error_log("API Endpoint Tester: Testing {$endpoint} via {$proxyFile}");

			// Initialize cURL
			$ch = curl_init($proxyUrl);
			curl_setopt_array($ch, [
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER => true,
				CURLOPT_CUSTOMREQUEST => $method,
				CURLOPT_USERAGENT => 'API Endpoint Tester/1.0',
				CURLOPT_TIMEOUT => 15,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_VERBOSE => true
			]);

			// Get both headers and body
			$response = curl_exec($ch);
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($response, 0, $header_size);
			$body = substr($response, $header_size);
			$info = curl_getinfo($ch);
			$error = curl_error($ch);

			curl_close($ch);

			echo "<div class='result'>";

			if (!empty($error)) {
				echo "<div class='error'><strong>cURL Error:</strong> $error</div>";
			}

			echo "<h3>Response Status: <span class='" . ($info['http_code'] < 400 ? 'success' : 'error') . "'>" . $info['http_code'] . "</span></h3>";
			echo "<h3>Request Information:</h3>";
			echo "<pre>" . htmlspecialchars(json_encode([
				'url' => $proxyUrl,
				'method' => $method,
				'total_time' => $info['total_time'],
				'size_download' => $info['size_download'],
				'content_type' => $info['content_type'],
			], JSON_PRETTY_PRINT)) . "</pre>";

			echo "<h3>Response Headers:</h3>";
			echo "<pre>" . htmlspecialchars($header) . "</pre>";

			echo "<h3>Response Body:</h3>";

			// Format JSON response for better readability
			$isJson = false;
			$prettyBody = $body;

			try {
				$jsonData = json_decode($body, true);
				if (json_last_error() === JSON_ERROR_NONE) {
					$prettyBody = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
					$isJson = true;
				}
			} catch (Exception $e) {
				// Not valid JSON, use original body
			}

			echo "<pre>" . htmlspecialchars($prettyBody) . "</pre>";

			// Additional debug information for JSON responses
			if ($isJson) {
				$jsonData = json_decode($body, true);
				echo "<h3>JSON Response Structure:</h3>";
				echo "<pre>";

				if (isset($jsonData['success'])) {
					echo "success: " . ($jsonData['success'] ? 'true' : 'false') . "\n";
				}

				if (isset($jsonData['data']) && is_array($jsonData['data'])) {
					echo "data: array with " . count($jsonData['data']) . " items\n";

					if (!empty($jsonData['data'])) {
						echo "First item keys: " . implode(', ', array_keys($jsonData['data'][0])) . "\n";
					}
				}

				if (isset($jsonData['message'])) {
					echo "message: " . $jsonData['message'] . "\n";
				}

				if (isset($jsonData['error'])) {
					echo "error: " . $jsonData['error'] . "\n";
				}

				echo "</pre>";
			}

			echo "</div>";
		} catch (Exception $e) {
			echo "<div class='result error'>";
			echo "<h3>Error:</h3>";
			echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
			echo "</div>";
		}
	}
	?>
</body>

</html>