<?php
// Deployment helper script for Azure
// This script ensures that proxy files are accessible from all necessary paths

// Display header
echo "========================================\n";
echo "Azure Deployment Helper for Proxy Files\n";
echo "========================================\n\n";

// Current directory
$current_dir = dirname(__FILE__);
echo "Current directory: $current_dir\n";

// Source proxy file
$source_proxy = $current_dir . '/simple-proxy.php';
if (!file_exists($source_proxy)) {
    die("ERROR: Source proxy file does not exist at: $source_proxy\n");
}
echo "Source proxy found at: $source_proxy\n";

// Directories to check/create
$directories = [
    $current_dir,                      // Current directory (already exists)
    dirname($current_dir),             // Parent directory
    $current_dir . '/proxy',           // Proxy subdirectory
    $current_dir . '/api'              // API subdirectory
];

// Create directories and copy proxy files
foreach ($directories as $dir) {
    if ($dir !== $current_dir && !is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "Created directory: $dir\n";
        } else {
            echo "WARNING: Failed to create directory: $dir\n";
            continue;
        }
    }

    // Only copy to directories other than current (already has the file)
    if ($dir !== $current_dir) {
        $target_file = $dir . '/simple-proxy.php';
        if (copy($source_proxy, $target_file)) {
            echo "Copied proxy to: $target_file\n";
        } else {
            echo "WARNING: Failed to copy proxy to: $target_file\n";
        }
    }
}

// Create a special health-check file
$health_check = $current_dir . '/proxy-health.php';
$health_content = <<<EOT
<?php
header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'message' => 'Proxy health check successful',
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => \$_SERVER['SERVER_NAME'],
    'request_uri' => \$_SERVER['REQUEST_URI'],
    'script_filename' => \$_SERVER['SCRIPT_FILENAME'],
    'document_root' => \$_SERVER['DOCUMENT_ROOT']
]);
EOT;

file_put_contents($health_check, $health_content);
echo "Created health check file: $health_check\n";

// Create an .htaccess file to ensure proxy files are accessible
$htaccess_file = $current_dir . '/.htaccess';
$htaccess_content = <<<EOT
# Ensure PHP files are properly executed
<FilesMatch "\.php$">
    SetHandler application/x-httpd-php
</FilesMatch>

# Allow access to proxy files from any location
<Files "simple-proxy.php">
    Order Allow,Deny
    Allow from all
</Files>

<Files "proxy-health.php">
    Order Allow,Deny
    Allow from all
</Files>

# Enable CORS for all proxy requests
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, POST, OPTIONS, PUT, DELETE"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
</IfModule>
EOT;

file_put_contents($htaccess_file, $htaccess_content);
echo "Created/updated .htaccess file: $htaccess_file\n";

// Update web.config with enhanced proxy rules
$web_config_file = $current_dir . '/web.config';
$web_config_content = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <!-- Process PHP files normally -->
                <rule name="PHPHandler" stopProcessing="true">
                    <match url="^.*\.php$" />
                    <action type="None" />
                </rule>
                
                <!-- Special rule to ensure proxy is accessible from anywhere -->
                <rule name="SimpleProxyAccess" stopProcessing="true">
                    <match url="^(.*/)?simple-proxy\.php$" />
                    <action type="Rewrite" url="simple-proxy.php" />
                </rule>
                
                <!-- Health check endpoint -->
                <rule name="ProxyHealthCheck" stopProcessing="true">
                    <match url="^(.*/)?proxy-health$" />
                    <action type="Rewrite" url="proxy-health.php" />
                </rule>
                
                <!-- Other rules from existing web.config -->
                <rule name="ApiTestEndpoint" stopProcessing="true">
                    <match url="^test-api$" />
                    <action type="Rewrite" url="test-api-connection.php" />
                </rule>
                
                <rule name="LoginRedirect" stopProcessing="true">
                    <match url="^login$" />
                    <action type="Rewrite" url="login.php" />
                </rule>
                
                <rule name="DashboardRedirect" stopProcessing="true">
                    <match url="^dashboard$" />
                    <action type="Rewrite" url="dashboard.php" />
                </rule>
                
                <rule name="GestionRedirects" stopProcessing="true">
                    <match url="^gestion/([a-z_]+)(/.*)?$" />
                    <action type="Rewrite" url="gestion_{R:1}.php{R:2}" appendQueryString="true" />
                </rule>
            </rules>
        </rewrite>
        
        <staticContent>
            <clientCache cacheControlMode="UseMaxAge" cacheControlMaxAge="1.00:00:00" />
            <mimeMap fileExtension=".woff" mimeType="application/font-woff" />
            <mimeMap fileExtension=".woff2" mimeType="application/font-woff2" />
        </staticContent>
        
        <httpProtocol>
            <customHeaders>
                <add name="Access-Control-Allow-Origin" value="*" />
                <add name="Access-Control-Allow-Methods" value="GET, POST, OPTIONS, PUT, DELETE" />
                <add name="Access-Control-Allow-Headers" value="Content-Type, Authorization, X-Requested-With" />
            </customHeaders>
        </httpProtocol>
    </system.webServer>
</configuration>
EOT;

file_put_contents($web_config_file, $web_config_content);
echo "Updated web.config file: $web_config_file\n";

// Create a proxy-test.html file to verify access to all proxy locations
$test_html_file = $current_dir . '/proxy-test.html';
$test_html_content = <<<EOT
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Azure Proxy Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-item { margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; }
        .success { background-color: #d4edda; }
        .failure { background-color: #f8d7da; }
        .pending { background-color: #fff3cd; }
        h2 { margin-top: 30px; }
        pre { background: #f8f9fa; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>Azure Proxy Test Page</h1>
    <p>This page tests if the proxy file is accessible from different paths.</p>
    
    <div id="results">
        <div class="test-item pending">Testing proxy paths...</div>
    </div>
    
    <h2>Test Results</h2>
    <pre id="output">Running tests...</pre>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const resultsDiv = document.getElementById('results');
            const outputPre = document.getElementById('output');
            
            // Paths to test
            const paths = [
                'simple-proxy.php',
                '/simple-proxy.php',
                '../simple-proxy.php',
                '../../simple-proxy.php',
                'proxy/simple-proxy.php',
                '/proxy/simple-proxy.php',
                'api/simple-proxy.php',
                '/api/simple-proxy.php',
                'proxy-health.php',
                '/proxy-health',
                'proxy-health'
            ];
            
            let results = {};
            let testsDone = 0;
            
            // Test each path
            paths.forEach(path => {
                const div = document.createElement('div');
                div.className = 'test-item pending';
                div.textContent = `Testing: ${path}`;
                resultsDiv.appendChild(div);
                
                fetch(path + '?endpoint=status.php', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (response.ok) {
                        div.className = 'test-item success';
                        div.textContent = `✅ Success: ${path}`;
                        results[path] = 'Success';
                    } else {
                        div.className = 'test-item failure';
                        div.textContent = `❌ Failed (${response . status}): ${path}`;
                        results[path] = `Failed: ${response . status}`;
                    }
                    return response.text();
                })
                .catch(error => {
                    div.className = 'test-item failure';
                    div.textContent = `❌ Error: ${path} - ${error . message}`;
                    results[path] = `Error: ${error . message}`;
                })
                .finally(() => {
                    testsDone++;
                    if (testsDone === paths.length) {
                        outputPre.textContent = JSON.stringify(results, null, 2);
                    }
                });
            });
        });
    </script>
</body>
</html>
EOT;

file_put_contents($test_html_file, $test_html_content);
echo "Created proxy test HTML file: $test_html_file\n";

// Create deploy instructions
$instructions_file = $current_dir . '/azure-proxy-deployment.md';
$instructions_content = <<<EOT
# Azure Proxy Deployment Instructions

This document explains how to deploy and verify the proxy configuration on Azure.

## Deployment Steps

1. **Run the deployment helper script**:
   - This script has already created necessary files in multiple locations
   - It updated the web.config to ensure proxy accessibility
   - It created a health check endpoint

2. **Deploy to Azure**:
   - Deploy the entire frontend directory to Azure App Service
   - Make sure all files including .htaccess and web.config are included

3. **Verify deployment**:
   - Visit https://your-app-name.azurewebsites.net/proxy-health
   - It should display JSON with server information
   - Visit https://your-app-name.azurewebsites.net/proxy-test.html
   - This page will test all possible proxy paths

## Troubleshooting

If the proxy is still not accessible:

1. **Check Kudu console**:
   - Go to https://your-app-name.scm.azurewebsites.net/
   - Navigate to Debug Console > CMD
   - Verify the files exist in the expected locations

2. **Check Application Logs**:
   - In Azure Portal, go to your App Service
   - Under Monitoring, select "Log stream"
   - Look for any errors related to the proxy files

3. **Enable detailed error messages**:
   - In Azure Portal, go to your App Service
   - Under Settings > Configuration
   - Set "Detailed Error Logging" to On

4. **Try additional locations**:
   - You can manually copy simple-proxy.php to other locations
   - Update the config.js file to test those locations

## Update Config.js

Update the config.js file to use the working proxy path:

\`\`\`javascript
// After finding a working proxy path from the test page
appConfig.proxyUrl = "the/working/path/simple-proxy.php"; 
\`\`\`

EOT;

file_put_contents($instructions_file, $instructions_content);
echo "Created deployment instructions: $instructions_file\n";

echo "\n========================================\n";
echo "Deployment helper completed successfully!\n";
echo "Follow the instructions in azure-proxy-deployment.md to verify your deployment.\n";
echo "========================================\n";
