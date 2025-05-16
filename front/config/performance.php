<?php

/**
 * Configuration des performances
 * 
 * Ce fichier gère :
 * - La mise en cache
 * - La compression
 * - Les timeouts
 * - Les métriques de performance
 */

// Configuration du cache
define('CACHE_ENABLED', true);
define('CACHE_DIR', __DIR__ . '/../cache');
define('CACHE_DURATION', 3600); // 1 heure
define('CACHE_PREFIX', 'api_cache_');

// Configuration de la compression
define('COMPRESSION_ENABLED', true);
define('COMPRESSION_LEVEL', 6); // Niveau de compression (0-9)
define('COMPRESSION_MIN_SIZE', 1024); // Taille minimale pour la compression (1KB)

// Configuration des timeouts
define('DEFAULT_TIMEOUT', 30); // Timeout par défaut en secondes
define('CONNECT_TIMEOUT', 5); // Timeout de connexion en secondes
define('ADAPTIVE_TIMEOUT', true); // Timeout adaptatif basé sur les performances

// Configuration des métriques
define('METRICS_ENABLED', true);
define('METRICS_DIR', __DIR__ . '/../metrics');
define('METRICS_RETENTION', 7 * 24 * 3600); // 7 jours

// Configuration de la mise en cache
$cacheConfig = [
	'enabled' => CACHE_ENABLED,
	'directory' => CACHE_DIR,
	'duration' => CACHE_DURATION,
	'prefix' => CACHE_PREFIX,
	'excluded_endpoints' => [
		'auth/login',
		'auth/logout',
		'status.php'
	]
];

// Configuration de la compression
$compressionConfig = [
	'enabled' => COMPRESSION_ENABLED,
	'level' => COMPRESSION_LEVEL,
	'min_size' => COMPRESSION_MIN_SIZE,
	'content_types' => [
		'application/json',
		'text/html',
		'text/plain',
		'text/css',
		'application/javascript'
	]
];

// Configuration des timeouts
$timeoutConfig = [
	'default' => DEFAULT_TIMEOUT,
	'connect' => CONNECT_TIMEOUT,
	'adaptive' => ADAPTIVE_TIMEOUT,
	'endpoints' => [
		'status.php' => 5,
		'auth/login' => 10,
		'matieres' => 15,
		'notes' => 20
	]
];

// Configuration des métriques
$metricsConfig = [
	'enabled' => METRICS_ENABLED,
	'directory' => METRICS_DIR,
	'retention' => METRICS_RETENTION,
	'collectors' => [
		'response_time',
		'memory_usage',
		'cache_hits',
		'cache_misses',
		'compression_ratio'
	]
];

/**
 * Initialise la configuration des performances
 */
function initPerformanceConfig()
{
	// Créer les répertoires nécessaires
	if (!file_exists(CACHE_DIR)) {
		mkdir(CACHE_DIR, 0755, true);
	}
	if (!file_exists(METRICS_DIR)) {
		mkdir(METRICS_DIR, 0755, true);
	}

	// Nettoyer le cache expiré
	cleanupCache();

	// Nettoyer les métriques anciennes
	cleanupMetrics();
}

/**
 * Nettoie le cache expiré
 */
function cleanupCache()
{
	if (!CACHE_ENABLED) return;

	$files = glob(CACHE_DIR . '/' . CACHE_PREFIX . '*');
	$now = time();

	foreach ($files as $file) {
		if (filemtime($file) < ($now - CACHE_DURATION)) {
			unlink($file);
		}
	}
}

/**
 * Nettoie les métriques anciennes
 */
function cleanupMetrics()
{
	if (!METRICS_ENABLED) return;

	$files = glob(METRICS_DIR . '/*.json');
	$now = time();

	foreach ($files as $file) {
		if (filemtime($file) < ($now - METRICS_RETENTION)) {
			unlink($file);
		}
	}
}

/**
 * Récupère la configuration du cache
 */
function getCacheConfig()
{
	global $cacheConfig;
	return $cacheConfig;
}

/**
 * Récupère la configuration de la compression
 */
function getCompressionConfig()
{
	global $compressionConfig;
	return $compressionConfig;
}

/**
 * Récupère la configuration des timeouts
 */
function getTimeoutConfig()
{
	global $timeoutConfig;
	return $timeoutConfig;
}

/**
 * Récupère la configuration des métriques
 */
function getMetricsConfig()
{
	global $metricsConfig;
	return $metricsConfig;
}

// Initialiser la configuration
initPerformanceConfig();
