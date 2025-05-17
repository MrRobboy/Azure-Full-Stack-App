# Azure Web App Proxy Testing Guide

## Overview of Changes

We've implemented several changes to address the proxy and CORS issues:

1. Created a simplified proxy (`simple-proxy.php`) that should work more reliably on Azure
2. Updated configuration to always use the proxy
3. Created diagnostic tools to help identify issues
4. Modified login.php to work with the simplified proxy

## Testing Steps

### 1. Deployment Verification

First, verify that the files are properly deployed to Azure:

- Visit `https://app-frontend-esgi-app.azurewebsites.net/check-deployment.php`
     - This will show if all files are correctly deployed and their status

### 2. Test Basic Proxy Functionality

- Visit `https://app-frontend-esgi-app.azurewebsites.net/api-test.php`
     - Click "Test Status via Proxy" to verify the proxy can reach the backend
     - Click "Test Backend Status" to see if direct communication works (likely will fail due to CORS)
     - Click "Test Login via Proxy" to test the login functionality

### 3. Test the Updated Login Page

- Visit `https://app-frontend-esgi-app.azurewebsites.net/login.php`
     - Try logging in with test credentials
     - Check browser console for any errors

## Troubleshooting

If issues persist after deployment:

### Proxy 404 Errors

If you see 404 errors for the proxy:

1. Check that `simple-proxy.php` is correctly deployed
2. Verify permissions are correct in Azure App Service
3. Look for any deployment errors in the Azure Portal logs

### Login Failures

If login still fails:

1. Check browser console for specific error messages
2. Check if the proxy can reach the backend API (using api-test.php)
3. Verify that the backend API is correctly processing authentication requests

### CORS Issues

If you see CORS errors:

1. Remember that our solution uses the proxy to bypass CORS issues
2. Ensure the config.js has `useProxy: true`
3. Verify that the login page is using the proxy for API calls

## Long-term Solutions

While the proxy solution works as a workaround, consider:

1. Properly configuring CORS in Azure App Service for both frontend and backend
2. Updating backend web.config and index.php to handle OPTIONS requests correctly
3. Setting proper session cookie parameters to allow cross-domain authentication

## How the New Solution Works

The simplified proxy works by:

1. Receiving the request with the `endpoint` parameter
2. Forwarding the request to the backend server using either curl or file_get_contents
3. Returning the response to the browser

This approach eliminates CORS issues because:

- The browser only communicates with the frontend domain
- The proxy makes server-to-server requests which aren't subject to CORS restrictions
- All cookies are properly maintained within the same domain

## Next Steps

Once these changes are working:

1. Continue developing the application using the proxy for reliability
2. Implement proper CORS configuration when time permits
3. Consider consolidating frontend and backend under a single domain for simplicity
