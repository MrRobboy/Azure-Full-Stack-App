# Guide de Performance

## Vue d'ensemble

Ce guide détaille les optimisations de performance mises en place dans l'application et les bonnes pratiques à suivre.

## Optimisations

### 1. Cache

#### Configuration

```php
// Configuration
define('CACHE_ENABLED', true);
define('CACHE_DIR', __DIR__ . '/../cache');
define('CACHE_DURATION', 3600);
define('CACHE_PREFIX', 'api_cache_');

// Gestion
function getCache(string $key): ?string
{
    if (!CACHE_ENABLED) {
        return null;
    }
    $file = CACHE_DIR . '/' . CACHE_PREFIX . md5($key);
    if (file_exists($file) && time() - filemtime($file) < CACHE_DURATION) {
        return file_get_contents($file);
    }
    return null;
}

function setCache(string $key, string $value): void
{
    if (!CACHE_ENABLED) {
        return;
    }
    $file = CACHE_DIR . '/' . CACHE_PREFIX . md5($key);
    file_put_contents($file, $value);
}
```

#### Endpoints Exclus

```php
// Configuration
define('CACHE_EXCLUDED_ENDPOINTS', [
    'auth/login',
    'auth/logout',
    'status.php'
]);

// Vérification
function isCacheable(string $endpoint): bool
{
    return !in_array($endpoint, CACHE_EXCLUDED_ENDPOINTS);
}
```

### 2. Compression

#### Configuration

```php
// Configuration
define('COMPRESSION_ENABLED', true);
define('COMPRESSION_LEVEL', 6);
define('COMPRESSION_MIN_SIZE', 1024);

// Types de contenu
define('COMPRESSION_TYPES', [
    'application/json',
    'text/html',
    'text/plain',
    'text/css',
    'application/javascript'
]);

// Compression
function compressResponse(string $content, string $type): string
{
    if (!COMPRESSION_ENABLED || strlen($content) < COMPRESSION_MIN_SIZE) {
        return $content;
    }
    if (in_array($type, COMPRESSION_TYPES)) {
        return gzencode($content, COMPRESSION_LEVEL);
    }
    return $content;
}
```

### 3. Timeouts

#### Configuration

```php
// Configuration
define('CURL_TIMEOUT', 30);
define('CURL_CONNECT_TIMEOUT', 5);
define('ADAPTIVE_TIMEOUT', true);

// Timeouts par endpoint
define('ENDPOINT_TIMEOUTS', [
    'status.php' => 5,
    'auth/login' => 10,
    'matieres' => 15,
    'notes' => 20
]);

// Gestion
function getTimeout(string $endpoint): int
{
    if (ADAPTIVE_TIMEOUT && isset(ENDPOINT_TIMEOUTS[$endpoint])) {
        return ENDPOINT_TIMEOUTS[$endpoint];
    }
    return CURL_TIMEOUT;
}
```

### 4. Métriques

#### Configuration

```php
// Configuration
define('METRICS_ENABLED', true);
define('METRICS_DIR', __DIR__ . '/../metrics');
define('METRICS_RETENTION', 7 * 24 * 3600);

// Collecteurs
define('METRICS_COLLECTORS', [
    'response_time',
    'memory_usage',
    'cache_hits',
    'cache_misses',
    'compression_ratio'
]);

// Collecte
function collectMetrics(string $type, array $data): void
{
    if (!METRICS_ENABLED) {
        return;
    }
    $file = METRICS_DIR . '/' . $type . '.json';
    $metrics = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    $metrics[] = array_merge($data, ['timestamp' => time()]);
    file_put_contents($file, json_encode($metrics));
}
```

## Bonnes Pratiques

### 1. Requêtes cURL

#### Configuration

```php
// Configuration
function configureCurl($ch, string $endpoint): void
{
    curl_setopt($ch, CURLOPT_TIMEOUT, getTimeout($endpoint));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, CURL_CONNECT_TIMEOUT);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
}
```

#### Gestion des Erreurs

```php
// Gestion
function handleCurlError($ch, string $endpoint): void
{
    $error = curl_error($ch);
    $errno = curl_errno($ch);
    logError("cURL error ($errno): $error", [
        'endpoint' => $endpoint,
        'timeout' => getTimeout($endpoint)
    ]);
}
```

### 2. Gestion de la Mémoire

#### Configuration

```php
// Configuration
define('MEMORY_LIMIT', '256M');
ini_set('memory_limit', MEMORY_LIMIT);

// Monitoring
function monitorMemory(): void
{
    $usage = memory_get_usage(true);
    $peak = memory_get_peak_usage(true);
    collectMetrics('memory_usage', [
        'current' => $usage,
        'peak' => $peak,
        'limit' => return_bytes(MEMORY_LIMIT)
    ]);
}
```

#### Nettoyage

```php
// Nettoyage
function cleanupMemory(): void
{
    if (function_exists('gc_collect_cycles')) {
        gc_collect_cycles();
    }
}
```

### 3. Logging

#### Configuration

```php
// Configuration
define('LOG_LEVEL', 'error');
define('LOG_FILE', __DIR__ . '/../logs/error.log');
define('LOG_MAX_SIZE', 10 * 1024 * 1024);
define('LOG_MAX_FILES', 5);

// Rotation
function rotateLogs(): void
{
    if (file_exists(LOG_FILE) && filesize(LOG_FILE) > LOG_MAX_SIZE) {
        for ($i = LOG_MAX_FILES - 1; $i > 0; $i--) {
            $old = LOG_FILE . '.' . $i;
            $new = LOG_FILE . '.' . ($i + 1);
            if (file_exists($old)) {
                rename($old, $new);
            }
        }
        rename(LOG_FILE, LOG_FILE . '.1');
    }
}
```

## Monitoring

### 1. Dashboard

#### Configuration

```php
// Configuration
define('DASHBOARD_ENABLED', true);
define('DASHBOARD_REFRESH', 60);
define('DASHBOARD_METRICS', [
    'response_time',
    'memory_usage',
    'cache_ratio',
    'error_rate'
]);

// Génération
function generateDashboard(): array
{
    $metrics = [];
    foreach (DASHBOARD_METRICS as $metric) {
        $metrics[$metric] = getMetricStats($metric);
    }
    return $metrics;
}
```

### 2. Alertes

#### Configuration

```php
// Configuration
define('ALERT_THRESHOLDS', [
    'response_time' => 1000,
    'memory_usage' => 0.8,
    'cache_ratio' => 0.5,
    'error_rate' => 0.1
]);

// Vérification
function checkAlerts(array $metrics): void
{
    foreach ($metrics as $metric => $value) {
        if (isset(ALERT_THRESHOLDS[$metric]) && $value > ALERT_THRESHOLDS[$metric]) {
            sendAlert($metric, $value);
        }
    }
}
```

## Maintenance

### 1. Nettoyage

#### Cache

```php
// Nettoyage
function cleanupCache(): void
{
    if (!CACHE_ENABLED) {
        return;
    }
    $files = glob(CACHE_DIR . '/' . CACHE_PREFIX . '*');
    foreach ($files as $file) {
        if (time() - filemtime($file) > CACHE_DURATION) {
            unlink($file);
        }
    }
}
```

#### Métriques

```php
// Nettoyage
function cleanupMetrics(): void
{
    if (!METRICS_ENABLED) {
        return;
    }
    $files = glob(METRICS_DIR . '/*.json');
    foreach ($files as $file) {
        $metrics = json_decode(file_get_contents($file), true);
        $metrics = array_filter($metrics, function($metric) {
            return $metric['timestamp'] > time() - METRICS_RETENTION;
        });
        file_put_contents($file, json_encode(array_values($metrics)));
    }
}
```

### 2. Optimisation

#### Configuration

```php
// Configuration
define('OPTIMIZATION_ENABLED', true);
define('OPTIMIZATION_INTERVAL', 3600);

// Exécution
function runOptimization(): void
{
    if (!OPTIMIZATION_ENABLED) {
        return;
    }
    cleanupCache();
    cleanupMetrics();
    rotateLogs();
}
```

## Support

### 1. Documentation

#### Ressources

- Guide de performance
- Procédures d'optimisation
- Checklist de performance
- Templates de rapport

#### Formation

- Performance des développeurs
- Bonnes pratiques
- Outils de monitoring
- Mises à jour régulières

### 2. Contact

- Email : performance@example.com
- Slack : #performance
- Jira : PROJ-123
