# API Troubleshooting Guide

This guide provides detailed steps to diagnose and fix issues with API communication between the frontend and backend, particularly focusing on CORS issues and authentication problems.

## Diagnosis Tools

We've created several diagnostic tools to help identify issues:

1. **CORS Diagnostic Tool**: `/front/test/cors-diagnostic.html`

      - Allows testing different API endpoints with and without the proxy
      - Shows detailed information about CORS headers
      - Provides insights into whether direct communication is possible

2. **API Tester**: `/front/test/api-tester.php`
      - Tests specific API endpoints with various authentication states
      - Sends test requests and displays results

## Common Issues and Solutions

### 1. CORS Issues (Browser to Backend)

**Symptoms**:

- Console errors like "Access to fetch at X from origin Y has been blocked by CORS policy"
- "Failed to fetch" errors in the browser console
- API requests work via the proxy but fail with direct access

**Solutions**:

A. **Use the Backend Proxy (Quick Fix)**:

- Set `useProxy: true` in `front/js/config.js`
- This routes all API requests through the same-origin proxy

B. **Fix Backend CORS Configuration**:

- Ensure the backend's `web.config` has proper CORS headers:
     ```xml
     <httpProtocol>
       <customHeaders>
         <add name="Access-Control-Allow-Origin" value="https://app-frontend-esgi-app.azurewebsites.net" />
         <add name="Access-Control-Allow-Methods" value="GET, POST, PUT, DELETE, OPTIONS" />
         <add name="Access-Control-Allow-Headers" value="Origin, X-Requested-With, Content-Type, Accept, Authorization" />
         <add name="Access-Control-Allow-Credentials" value="true" />
       </customHeaders>
     </httpProtocol>
     ```
- Ensure correct handling of OPTIONS requests:
     ```xml
     <handlers>
       <remove name="OPTIONSVerbHandler" />
       <add name="OPTIONSVerbHandler" path="*" verb="OPTIONS" modules="ProtocolSupportModule" resourceType="Unspecified" requireAccess="None" />
     </handlers>
     ```

C. **Configure Azure App Service CORS**:

- Go to Azure Portal → App Service → API → CORS
- Add your frontend domain to allowed origins
- Enable Access-Control-Allow-Credentials

### 2. Authentication Issues

**Symptoms**:

- Login attempts fail with 401 Unauthorized
- Session doesn't persist between requests
- Error messages about invalid credentials even with correct login

**Solutions**:

A. **Check Session Configuration**:

- Ensure both frontend and backend use compatible session settings
- Verify that cookies are properly configured for cross-domain usage:
     ```php
     session_set_cookie_params([
       'lifetime' => $cookieParams['lifetime'],
       'path' => '/',
       'domain' => '.azurewebsites.net', // Shared domain
       'secure' => true,
       'httponly' => true,
       'samesite' => 'None'
     ]);
     ```

B. **Use Credentials in Fetch Requests**:

- Always include `credentials: 'include'` in fetch requests:
     ```javascript
     fetch(url, {
     	method: "POST",
     	headers: {
     		"Content-Type": "application/json"
     	},
     	body: JSON.stringify(data),
     	credentials: "include"
     });
     ```

C. **Verify Authentication Logic**:

- Check that `routes/api.php` correctly processes login requests
- Verify that session storage is properly initialized

### 3. URL Rewriting Issues

**Symptoms**:

- 404 errors when accessing API routes
- Direct PHP files work but `/api/*` routes don't

**Solutions**:

A. **Check URL Rewriting Rules**:

- Verify `web.config` contains proper rewrite rules for API endpoints
- Ensure the index.php router correctly processes API requests

B. **Test Direct File Access**:

- Try accessing direct PHP files like `status.php`
- If direct files work but API routes don't, focus on rewriting rules

## Testing Process

When fixing issues, follow this systematic testing approach:

1. **Test with Proxy First**:

      - Enable proxy mode in config.js
      - Test basic API functionality (status endpoint)
      - Test login functionality

2. **Test Direct Communication**:

      - Disable proxy mode
      - Use CORS diagnostic tool to check for CORS headers
      - Test OPTIONS requests
      - Test basic API endpoints

3. **Gradually Add Complexity**:
      - If basic endpoints work, test authentication
      - Then test more complex API operations

## Emergency Fallback

If you can't immediately fix CORS or URL rewriting issues:

1. **Rely on the Proxy**:

      - The backend proxy is a reliable workaround
      - Set `useProxy: true` in config.js
      - This will ensure the application functions while you fix underlying issues

2. **Log Detailed Errors**:
      - Check the browser console for detailed error messages
      - Use PHP error logs on the backend to diagnose issues
      - Enable verbose logging in the proxy

## Advanced Troubleshooting

For complex issues:

1. **Network Analysis**:

      - Use browser developer tools to analyze network requests
      - Check HTTP status codes and response headers
      - Look for preflight OPTIONS requests

2. **Authentication Flow**:

      - Check if cookies are being sent and received
      - Verify that session data persists between requests
      - Test with simple endpoints that don't require authentication

3. **Server Configuration**:
      - Review Azure App Service settings
      - Check for any IP restrictions or firewall issues
      - Verify correct PHP version and extensions

Remember that CORS issues can be frustrating to debug but following this systematic approach will help identify and resolve the problems.
