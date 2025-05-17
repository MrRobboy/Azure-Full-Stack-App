# Azure Full Stack App - CORS and API Routing Fix Guide

This guide explains how to fix CORS issues and URL rewriting problems in the Azure Full Stack App.

## Problem Overview

The application is experiencing two main issues:

1. **CORS Issues**: Direct browser-to-backend communication is blocked by CORS restrictions.
2. **URL Rewriting Issues**: API routes return 404 errors because the URL rewriting configuration is not properly set up.

## Solution

### 1. Backend Configuration Changes

#### A. Update web.config

The `web.config` file has been updated to:

- Properly handle OPTIONS requests for CORS preflight
- Add appropriate CORS headers
- Enhance URL rewriting rules to correctly route API requests

Key changes:

- Added explicit handling for direct PHP scripts
- Improved API routing with fallbacks
- Added a default handler for unmatched requests

#### B. Update nginx_config

The nginx configuration has been updated to:

- Handle CORS headers correctly for all response types
- Properly respond to OPTIONS preflight requests
- Implement proper URL rewriting with try_files directives

Key changes:

- Updated location blocks to use try_files instead of rewrite
- Improved routing for API endpoints
- Added proper logging configuration

#### C. Updated index.php Router

The main entry point (`index.php`) has been enhanced to:

- Add CORS headers at the application level
- Properly handle OPTIONS requests
- Improve URI parsing and routing logic
- Add detailed logging for troubleshooting

### 2. Azure Portal Configuration

In addition to the file changes, make sure to:

1. **Configure CORS in Azure App Service**:

      - Go to your backend App Service in the Azure Portal
      - Navigate to API > CORS
      - Add `https://app-frontend-esgi-app.azurewebsites.net` to the allowed origins
      - Check "Enable Access-Control-Allow-Credentials"
      - Save the changes

2. **Review Application Settings**:
      - Ensure the application is configured to use the updated web.config

### 3. Deployment Process

1. Deploy the updated files to your Azure App Service
2. Restart the App Service to ensure all changes take effect
3. Clear browser cache and test the API connections

## Testing the Solution

Once deployed, you should be able to:

1. Make direct API calls from the frontend to the backend
2. Access API routes like `/api/classes` successfully
3. See proper handling of OPTIONS preflight requests

## Fallback Solution

If issues persist, the current proxy approach (`backend-proxy.php`) can continue to be used as it works around both the CORS and routing issues effectively.

## Monitoring and Troubleshooting

- Check the application logs in the Azure Portal
- Review the PHP error logs for any remaining issues
- Use browser developer tools to inspect CORS headers on responses

This comprehensive approach should resolve both the CORS issues and the URL rewriting problems, allowing direct communication between your frontend and backend applications.
