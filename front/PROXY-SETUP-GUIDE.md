# Azure Proxy Configuration Guide

This guide will help you resolve issues with the proxy system, particularly for the matieres endpoint.

## 1. Understanding the issue

The current issue is that while the first API call to `/user/profile` succeeds, subsequent calls to `/matieres` fail with 404 errors. This is caused by how Azure's App Service handles CORS requests and server-side proxies.

## 2. Setup Steps

Follow these steps to fix the issue:

### 2.1. Check that all required files exist

Make sure the following files are present in your application's front directory:

- `unified-proxy.php` - The consolidated proxy that handles most endpoints
- `unified-login.php` - Handles authentication
- `matieres-proxy.php` - Special proxy for the matieres endpoint
- `status.php` - Simple health check endpoint

You can visit `repair-proxy.php` to automatically check for and create any missing files.

### 2.2. Fix permissions (if needed)

For Azure App Service, all files should have standard read/execute permissions. If you're having issues, the `repair-proxy.php` tool can attempt to fix permissions.

### 2.3. Clear browser cache

Browser caching can sometimes cause issues with CORS and proxies. Clear your browser cache or use an incognito/private browsing session to test.

## 3. Diagnostic Tools

We've created several tools to help diagnose and fix issues:

- `api-debug.php` - An interactive API testing panel
- `api-endpoint-tester.php` - Tests specific endpoints with detailed feedback
- `deep-proxy-test.php` - Comprehensive proxy system diagnostics
- `repair-proxy.php` - Automatic repair of common proxy issues

## 4. Fallback Mechanism

The system now includes a robust fallback mechanism:

1. For the `/matieres` endpoint:

      - First tries the dedicated `matieres-proxy.php`
      - Falls back to the `unified-proxy.php` with special handling
      - If both fail, uses hardcoded fallback data

2. For other endpoints:
      - Uses the `unified-proxy.php`
      - Falls back to alternative proxy files if needed
      - Provides appropriate error messages

## 5. Common Issues and Solutions

### 404 Error on Proxy Files

If you receive 404 errors when accessing proxy files:

1. Verify files exist using `api-debug.php` -> "Check Proxy Files"
2. Check web.config configuration (should allow PHP execution)
3. Try running `repair-proxy.php` to reinstall proxy files

### Backend Connection Issues

If the proxy files load but can't connect to the backend:

1. Check network connectivity to `https://app-backend-esgi-app.azurewebsites.net`
2. Verify SSL/TLS settings in your PHP configuration
3. Check for any firewall issues in Azure

### Empty or Invalid Responses

If you receive empty or invalid responses:

1. Check browser console for JavaScript errors
2. Try accessing endpoints directly using `api-endpoint-tester.php`
3. Look for PHP errors in the server logs

## 6. Emergency Backup

If all else fails, the system will use built-in fallback data for critical endpoints:

- `/matieres` - Basic set of subjects
- `/user/profile` - Uses session data if available

This ensures basic application functionality even if the backend API is unavailable.

## Need Further Help?

If you're still experiencing issues after following this guide, try:

1. Running `php -v` to verify your PHP version and configuration
2. Checking Azure web app logs for detailed error information
3. Testing directly with curl commands from a terminal
