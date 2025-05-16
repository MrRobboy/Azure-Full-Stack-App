# Guide de Sécurité

## Vue d'ensemble

Ce guide détaille les mesures de sécurité mises en place dans l'application et les bonnes pratiques à suivre.

## Mesures de Sécurité

### 1. Authentification

#### JWT

```php
// Configuration
define('JWT_SECRET', 'your-secret-key');
define('JWT_EXPIRATION', 3600);

// Validation
function validateToken(string $token): bool
{
    try {
        $decoded = JWT::decode($token, JWT_SECRET, ['HS256']);
        return $decoded->exp > time();
    } catch (Exception $e) {
        return false;
    }
}
```

#### Rate Limiting

```php
// Configuration
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 3600);

// Vérification
function checkRateLimit(string $ip): bool
{
    $key = "rate_limit:$ip";
    $requests = redis()->incr($key);
    if ($requests === 1) {
        redis()->expire($key, RATE_LIMIT_WINDOW);
    }
    return $requests <= RATE_LIMIT_REQUESTS;
}
```

### 2. Validation des Entrées

#### Sanitization

```php
// Configuration
define('INPUT_MAX_LENGTH', 1000);
define('INPUT_ALLOWED_CHARS', '/^[a-zA-Z0-9\s\-_.,]+$/');

// Validation
function validateInput(string $input): bool
{
    if (strlen($input) > INPUT_MAX_LENGTH) {
        return false;
    }
    return preg_match(INPUT_ALLOWED_CHARS, $input) === 1;
}
```

#### XSS Protection

```php
// Headers
header('X-XSS-Protection: 1; mode=block');
header('Content-Security-Policy: default-src \'self\'');

// Sanitization
function sanitizeOutput(string $output): string
{
    return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
}
```

### 3. CORS

#### Configuration

```php
// Configuration
define('CORS_ALLOWED_ORIGINS', [
    'https://app-frontend-esgi-app.azurewebsites.net',
    'http://localhost:8080'
]);

// Headers
function setCorsHeaders(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if (in_array($origin, CORS_ALLOWED_ORIGINS)) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }
}
```

### 4. SSL/TLS

#### Configuration

```php
// Configuration
define('SSL_VERIFY', true);
define('SSL_CERT', '/path/to/cert.pem');

// Vérification
function verifySSL(): bool
{
    if (!SSL_VERIFY) {
        return true;
    }
    return file_exists(SSL_CERT);
}
```

### 5. Headers de Sécurité

#### Configuration

```php
// Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Content-Security-Policy: default-src \'self\'');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
```

## Bonnes Pratiques

### 1. Gestion des Erreurs

#### Logging

```php
// Configuration
define('LOG_LEVEL', 'error');
define('LOG_FILE', '/path/to/error.log');

// Logging
function logError(string $message, array $context = []): void
{
    if (LOG_LEVEL === 'error') {
        error_log(json_encode([
            'message' => $message,
            'context' => $context,
            'timestamp' => date('c')
        ]), 3, LOG_FILE);
    }
}
```

#### Affichage

```php
// Configuration
define('DISPLAY_ERRORS', false);

// Affichage
function displayError(int $code, string $message): void
{
    http_response_code($code);
    if (DISPLAY_ERRORS) {
        echo json_encode(['error' => $message]);
    } else {
        echo json_encode(['error' => 'Internal Server Error']);
    }
}
```

### 2. Gestion des Sessions

#### Configuration

```php
// Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600);
```

#### Validation

```php
// Validation
function validateSession(): bool
{
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    if ($_SESSION['last_activity'] < time() - 3600) {
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}
```

### 3. Gestion des Fichiers

#### Upload

```php
// Configuration
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024);
define('UPLOAD_ALLOWED_TYPES', ['image/jpeg', 'image/png']);

// Validation
function validateUpload(array $file): bool
{
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        return false;
    }
    if (!in_array($file['type'], UPLOAD_ALLOWED_TYPES)) {
        return false;
    }
    return true;
}
```

#### Stockage

```php
// Configuration
define('UPLOAD_DIR', '/path/to/uploads');
define('UPLOAD_PERMISSIONS', 0644);

// Stockage
function storeFile(array $file): string
{
    $filename = uniqid() . '_' . basename($file['name']);
    $path = UPLOAD_DIR . '/' . $filename;
    move_uploaded_file($file['tmp_name'], $path);
    chmod($path, UPLOAD_PERMISSIONS);
    return $filename;
}
```

## Monitoring

### 1. Logs de Sécurité

#### Configuration

```php
// Configuration
define('SECURITY_LOG', '/path/to/security.log');

// Logging
function logSecurityEvent(string $event, array $data = []): void
{
    $log = [
        'event' => $event,
        'data' => $data,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'timestamp' => date('c')
    ];
    error_log(json_encode($log), 3, SECURITY_LOG);
}
```

#### Types d'Événements

- Tentatives de login
- Accès non autorisés
- Erreurs de validation
- Violations de rate limit

### 2. Alertes

#### Configuration

```php
// Configuration
define('ALERT_EMAIL', 'security@example.com');
define('ALERT_THRESHOLDS', [
    'login_attempts' => 5,
    'rate_limit' => 100,
    'validation_errors' => 10
]);

// Alertes
function sendAlert(string $type, array $data): void
{
    $message = "Security Alert: $type\n";
    $message .= json_encode($data, JSON_PRETTY_PRINT);
    mail(ALERT_EMAIL, "Security Alert: $type", $message);
}
```

## Maintenance

### 1. Mises à Jour

#### Vérification

```bash
# Vérifier les dépendances
composer audit

# Vérifier les vulnérabilités
php tools/security-scan.php
```

#### Application

```bash
# Mettre à jour les dépendances
composer update

# Appliquer les patches
git pull origin master
```

### 2. Audit

#### Configuration

```php
// Configuration
define('AUDIT_ENABLED', true);
define('AUDIT_LOG', '/path/to/audit.log');

// Audit
function audit(string $action, array $data = []): void
{
    if (AUDIT_ENABLED) {
        $log = [
            'action' => $action,
            'data' => $data,
            'user' => $_SESSION['user_id'] ?? null,
            'timestamp' => date('c')
        ];
        error_log(json_encode($log), 3, AUDIT_LOG);
    }
}
```

#### Types d'Actions

- Création de compte
- Modification de données
- Suppression de données
- Changements de configuration

## Support

### 1. Incident Response

#### Process

1. Détection
2. Analyse
3. Containment
4. Éradication
5. Recovery
6. Post-mortem

#### Contact

- Email : security@example.com
- Phone : +1-234-567-8900
- Slack : #security-incidents

### 2. Documentation

#### Ressources

- Guide de sécurité
- Procédures d'incident
- Checklist de sécurité
- Templates de rapport

#### Formation

- Sécurité des développeurs
- Bonnes pratiques
- Gestion des incidents
- Mises à jour régulières
