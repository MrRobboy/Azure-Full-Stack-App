<?php

/**
 * Security Configuration
 * 
 * This file contains all security-related configurations for the application.
 */

// Rate limiting configuration
define('RATE_LIMIT_CONFIG', [
	'enabled' => true,
	'max_requests' => 1000,
	'window' => 3600
]);

// CORS configuration
define('CORS_CONFIG', [
	'allowed_origins' => ['*'],
	'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
	'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'Origin'],
	'exposed_headers' => ['X-Rate-Limit-Remaining', 'X-Rate-Limit-Reset'],
	'max_age' => 86400,
	'allow_credentials' => true
]);

// Security headers configuration
define('SECURITY_HEADERS', [
	'X-Content-Type-Options: nosniff',
	'X-Frame-Options: DENY',
	'X-XSS-Protection: 1; mode=block',
	'Strict-Transport-Security: max-age=31536000; includeSubDomains',
	'Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\'; style-src \'self\' \'unsafe-inline\';',
	'Access-Control-Allow-Origin: *',
	'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS',
	'Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin',
	'Access-Control-Max-Age: 86400',
	'Access-Control-Allow-Credentials: true'
]);

// Input validation configuration
define('INPUT_VALIDATION_CONFIG', [
	'enabled' => true,
	'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
	'max_length' => 1000
]);

/**
 * Get CORS headers
 */
function getCorsHeaders()
{
	$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
	return [
		'Access-Control-Allow-Origin' => $origin,
		'Access-Control-Allow-Methods' => implode(', ', CORS_CONFIG['allowed_methods']),
		'Access-Control-Allow-Headers' => implode(', ', CORS_CONFIG['allowed_headers']),
		'Access-Control-Expose-Headers' => implode(', ', CORS_CONFIG['exposed_headers']),
		'Access-Control-Max-Age' => CORS_CONFIG['max_age'],
		'Access-Control-Allow-Credentials' => CORS_CONFIG['allow_credentials'] ? 'true' : 'false'
	];
}

/**
 * Check rate limit
 */
function checkRateLimit($ip)
{
	if (!RATE_LIMIT_CONFIG['enabled']) {
		return true;
	}

	$cacheDir = sys_get_temp_dir() . '/rate_limit';
	if (!is_dir($cacheDir)) {
		mkdir($cacheDir, 0755, true);
	}

	$cacheFile = $cacheDir . '/' . md5($ip) . '.json';
	$now = time();
	$window = RATE_LIMIT_CONFIG['window'];
	$maxRequests = RATE_LIMIT_CONFIG['max_requests'];

	if (file_exists($cacheFile)) {
		$data = json_decode(file_get_contents($cacheFile), true);
		if ($data && $data['timestamp'] > ($now - $window)) {
			if ($data['count'] >= $maxRequests) {
				error_log("Rate limit exceeded for IP: $ip");
				return false;
			}
			$data['count']++;
		} else {
			$data = ['count' => 1, 'timestamp' => $now];
		}
	} else {
		$data = ['count' => 1, 'timestamp' => $now];
	}

	file_put_contents($cacheFile, json_encode($data));
	return true;
}

/**
 * Set security headers
 */
function setSecurityHeaders()
{
	foreach (SECURITY_HEADERS as $header) {
		header($header);
	}
}

/**
 * Validate input
 */
function validateInput($input)
{
	if (!INPUT_VALIDATION_CONFIG['enabled']) {
		return $input;
	}

	if (strlen($input) > INPUT_VALIDATION_CONFIG['max_length']) {
		error_log("Input too long: " . substr($input, 0, 100) . "...");
		return false;
	}

	// Basic sanitization
	$input = strip_tags($input);
	$input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

	return $input;
}
