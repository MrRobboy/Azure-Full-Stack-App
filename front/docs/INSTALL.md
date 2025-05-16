# Guide d'Installation et de Configuration

## Prérequis

- PHP 7.4 ou supérieur
- Extensions PHP requises :
     - curl
     - json
     - openssl
     - mbstring
- Serveur web (Apache/Nginx)
- Accès en écriture aux dossiers :
     - `/logs`
     - `/cache`
     - `/metrics`

## Installation

1. **Cloner le dépôt**

      ```bash
      git clone [URL_DU_REPO]
      cd Azure-Full-Stack-App/front
      ```

2. **Créer les dossiers nécessaires**

      ```bash
      mkdir -p logs cache metrics
      chmod 755 logs cache metrics
      ```

3. **Configurer les permissions**
      ```bash
      chmod 644 config/*.php
      chmod 755 tools/*.php
      ```

## Configuration

### 1. Configuration du Proxy

Le fichier `config/proxy.php` contient les paramètres principaux :

```php
// URL du backend
define('BACKEND_BASE_URL', 'https://app-backend-esgi-app.azurewebsites.net/api');

// Configuration CORS
define('CORS_ALLOWED_ORIGINS', [
    'https://app-frontend-esgi-app.azurewebsites.net',
    'http://localhost:8080'
]);

// Timeouts
define('CURL_TIMEOUT', 30);
define('CURL_CONNECT_TIMEOUT', 5);
```

### 2. Configuration des Erreurs

Le fichier `config/error-handler.php` gère la gestion des erreurs :

```php
// Configuration des logs
define('ERROR_LOG_DIR', __DIR__ . '/../logs');
define('MAX_LOG_SIZE', 10 * 1024 * 1024); // 10MB
define('MAX_LOG_FILES', 5);
```

### 3. Configuration des Performances

Le fichier `config/performance.php` gère les performances :

```php
// Cache
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 3600); // 1 heure

// Compression
define('COMPRESSION_ENABLED', true);
define('COMPRESSION_LEVEL', 6);
```

## Configuration du Serveur Web

### Apache (.htaccess)

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ api-bridge.php?endpoint=$1 [QSA,L]

# Headers de sécurité
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
Header set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

### Nginx (nginx.conf)

```nginx
location / {
    try_files $uri $uri/ /api-bridge.php?endpoint=$uri&$args;
}

# Headers de sécurité
add_header X-Content-Type-Options "nosniff";
add_header X-Frame-Options "SAMEORIGIN";
add_header X-XSS-Protection "1; mode=block";
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains";
```

## Vérification de l'Installation

1. **Tester le proxy**

      ```bash
      php tools/proxy-test-suite.php
      ```

2. **Vérifier les logs**

      ```bash
      tail -f logs/error.log
      ```

3. **Vérifier le cache**
      ```bash
      ls -l cache/
      ```

## Dépannage

### Problèmes Courants

1. **Erreur 404 sur le proxy**

      - Vérifier les règles de réécriture
      - Vérifier les permissions des fichiers
      - Vérifier la configuration du serveur web

2. **Erreurs CORS**

      - Vérifier les origines autorisées
      - Vérifier les headers CORS
      - Vérifier la configuration SSL

3. **Problèmes de Performance**
      - Vérifier la configuration du cache
      - Vérifier les timeouts
      - Vérifier la compression

### Logs et Monitoring

- **Logs d'erreurs** : `logs/error.log`
- **Logs d'alertes** : `logs/alerts.log`
- **Métriques** : `metrics/`

## Maintenance

### Rotation des Logs

Les logs sont automatiquement rotés quand ils atteignent 10MB. Les anciens logs sont conservés pendant 7 jours.

### Nettoyage du Cache

Le cache est automatiquement nettoyé :

- À chaque requête (vérification)
- Une fois par jour (nettoyage complet)
- Quand l'espace disque est faible

### Métriques

Les métriques sont collectées automatiquement et stockées dans le dossier `metrics/`. Elles sont conservées pendant 7 jours.

## Sécurité

### Headers de Sécurité

Les headers de sécurité suivants sont configurés :

- X-Content-Type-Options
- X-Frame-Options
- X-XSS-Protection
- Strict-Transport-Security
- Content-Security-Policy

### Validation des Entrées

Toutes les entrées sont validées :

- Longueur maximale
- Caractères autorisés
- Protection contre les injections

### Rate Limiting

Le rate limiting est configuré pour :

- Limiter les requêtes par IP
- Limiter les requêtes par endpoint
- Bloquer les IPs malveillantes

## Support

Pour toute question ou problème :

1. Consulter la documentation
2. Vérifier les logs
3. Utiliser l'outil de test
4. Contacter l'équipe de support
