# Azure Proxy Solution for Full-Stack App

This package provides a comprehensive solution for proxy communication issues between frontend and backend components deployed on Azure App Services.

## Problem Description

The main issue was that the proxy file (`simple-proxy.php`) was not accessible at the expected paths on Azure, causing 404 errors when the frontend attempted to communicate with the backend API.

## Solution Components

This solution provides multiple approaches to ensure reliable communication between frontend and backend:

### 1. Deployment Helper Script

The `deploy-proxy.php` script automatically:

- Copies the proxy file to multiple locations
- Creates necessary directories
- Updates the web.config for proper URL rewriting
- Creates diagnostic and testing tools

Run it with:

```
https://your-frontend-app.azurewebsites.net/deploy-proxy.php
```

### 2. Enhanced Configuration (enhanced-config.js)

This improved configuration script:

- Dynamically tests multiple proxy paths to find working ones
- Automatically falls back to alternate paths if the primary one fails
- Provides detailed logging for troubleshooting
- Works in both Azure and local environments

To use it, add to your main HTML file:

```html
<script src="js/enhanced-config.js"></script>
```

### 3. API Bridge (api-bridge.php)

A fallback solution that makes server-side requests to the backend API:

- Bypasses CORS limitations that affect browser-based requests
- Works even when direct frontend-to-backend communication fails
- Forwards cookies and authentication information correctly

### 4. Diagnostics Tools

#### Proxy Diagnostics Tool (proxy-diagnostics.php)

A comprehensive diagnostic tool that:

- Tests all possible proxy paths
- Verifies connectivity to the backend
- Inspects file system permissions and availability
- Provides detailed recommendations

Access it at:

```
https://your-frontend-app.azurewebsites.net/proxy-diagnostics.php
```

#### Proxy Test Page (proxy-test.html)

A simple test page that checks all possible proxy paths in the browser.

## Deployment Instructions

1. **Deploy the entire frontend directory to Azure**

      - Ensure all files including `.htaccess` and `web.config` are included

2. **Run the deployment helper**

      - Visit `https://your-app.azurewebsites.net/deploy-proxy.php`
      - This will automatically set up all necessary files

3. **Test proxy availability**

      - Visit `https://your-app.azurewebsites.net/proxy-diagnostics.php`
      - Look for working proxy paths

4. **Update your config.js**
      - Replace your existing config.js with enhanced-config.js, or
      - Update the proxy URL in your existing config.js to use a working path

## Troubleshooting Steps

If you're still experiencing issues after deployment:

1. **Check for working proxy paths**

      - Use the proxy diagnostics tool to find working paths
      - Try using the API bridge as a last resort

2. **Verify backend is accessible**

      - Confirm the backend API is running
      - Test direct connectivity to the backend

3. **Check Azure configuration**

      - Ensure PHP is properly configured
      - Enable detailed error logging in Azure portal
      - Check application logs for specific errors

4. **CORS issues**
      - Check if browser console shows CORS errors
      - Verify the backend has proper CORS headers

## Architecture Explanation

### How It Works

1. **Path Detection**: The enhanced configuration dynamically tests multiple proxy paths to find which ones work in the current environment.

2. **Fallback Mechanism**: If a proxy request fails, the system will automatically try alternate paths.

3. **Server-Side Bridge**: As a last resort, the API bridge makes server-side requests to the backend, bypassing CORS limitations.

4. **IIS Integration**: The web.config ensures that proxy files are accessible from any path through URL rewriting.

## Best Practices for Azure Deployment

1. **Always include web.config**

      - Essential for IIS on Azure App Service

2. **Deploy proxy files to multiple locations**

      - Increases chances of finding a working path

3. **Enable detailed logging**

      - Helps diagnose issues in production

4. **Use server-side proxying**
      - More reliable than direct client-to-API communication

## Contact and Support

If you need additional help with this solution, please contact the development team at [your-contact-info].

## License

[Your license information]
