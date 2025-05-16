<?php

/**
 * Headers Handler
 * 
 * This file handles all CORS and security headers for the application.
 */

// Load configuration
require_once __DIR__ . '/config/proxy.php';

/**
 * Set all required headers
 */
function setHeaders()
{
	// Set CORS headers
	$corsHeaders = getCorsHeaders();
	foreach ($corsHeaders as $header => $value) {
		header("$header: $value");
	}

	// Set security headers
	foreach (SECURITY_CONFIG['headers'] as $header) {
		header($header);
	}
}

/**
 * Handle preflight requests
 */
function handlePreflight()
{
	if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
		setHeaders();
		exit(0);
	}
}

// Set headers for all requests
setHeaders();

// Handle preflight requests
handlePreflight();
