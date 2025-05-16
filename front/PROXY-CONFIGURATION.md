# Azure Proxy Configuration

## Overview

This document explains the proxy configuration for the Azure Full Stack App, including the challenges faced and the solutions implemented.

## Proxy Files

1. **azure-proxy.php** (NEW) - Optimized proxy for Azure that combines the best features of simple-proxy.php and api-bridge.php.
2. **api-bridge.php** - Original proxy with enhanced security features (redirected to azure-proxy.php).
3. **simple-proxy.php** - Minimal proxy that works reliably.
4. **matieres-proxy.php** - Specialized proxy for matières (redirected to azure-proxy.php).

## Web.config Setup

The web.config file is configured to:

1. Redirect all api-bridge.php requests to azure-proxy.php
2. Directly handle azure-proxy.php requests
3. Directly handle simple-proxy.php requests as a fallback
4. Set proper CORS and security headers

## Client-Side Configuration

The config.js file is updated to prioritize proxy files in the following order:

1. azure-proxy.php (primary)
2. simple-proxy.php (fallback)
3. api-bridge.php (legacy)
4. matieres-proxy.php (specialized)
5. unified-proxy.php (original)

## Features Implemented

### 1. CORS Headers

- Dynamic origin handling
- Preflight request support
- Proper allowed methods and headers
- Credentials support

### 2. Security Headers

- X-Content-Type-Options
- X-Frame-Options
- X-XSS-Protection
- Strict-Transport-Security
- Content-Security-Policy

### 3. Rate Limiting

- File-based storage
- 1000 requests per hour limit
- IP-based tracking
- Proper error handling

### 4. Input Validation

- Length validation
- Basic sanitization
- Method validation

### 5. Error Handling

- Comprehensive error logging
- Formatted JSON error responses
- Detailed error codes

### 6. Enhanced URL Handling

- Special endpoint mapping
- Proper query string handling
- Automatic .php extension handling

## Azure-Specific Optimizations

1. **Fallback Configuration**: If security.php is not found, falls back to built-in configurations.
2. **Enhanced Logging**: Creates logs directory if missing.
3. **IIS Compatibility**: Special web.config rules for Azure's IIS web server.
4. **Header Management**: Proper order of CORS and security headers.
5. **Response Handling**: Proper handling of various response types.

## Troubleshooting

If the proxy is not working properly:

1. Check web server logs
2. Verify azure-proxy.php is accessible
3. Try simple-proxy.php as a fallback
4. Check rate limiting settings
5. Verify CORS configuration

## Configuration Updates

To use a different proxy or modify the configuration:

1. Update the proxyUrls array in config.js
2. Modify the web.config rewrite rules if needed
3. Adjust security settings in security.php
4. Update endpoint mapping in azure-proxy.php
