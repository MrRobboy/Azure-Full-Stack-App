# Guide de Dépannage

## Problèmes Courants et Solutions

### 1. Problèmes de Connexion

#### Erreur 404 sur le proxy

**Symptômes** :

- Erreur 404 lors de l'accès à `api-bridge.php`
- Les requêtes ne sont pas transmises au backend

**Solutions** :

1. Vérifier les règles de réécriture :

      ```bash
      # Apache
      cat .htaccess

      # Nginx
      cat nginx.conf
      ```

2. Vérifier les permissions :

      ```bash
      ls -l api-bridge.php
      chmod 644 api-bridge.php
      ```

3. Vérifier la configuration du serveur web :

      ```bash
      # Apache
      apache2ctl -S

      # Nginx
      nginx -t
      ```

#### Erreurs CORS

**Symptômes** :

- Erreurs CORS dans la console du navigateur
- Les requêtes sont bloquées par le navigateur

**Solutions** :

1. Vérifier les origines autorisées :

      ```php
      // config/proxy.php
      define('CORS_ALLOWED_ORIGINS', [
          'https://app-frontend-esgi-app.azurewebsites.net',
          'http://localhost:8080'
      ]);
      ```

2. Vérifier les headers CORS :

      ```bash
      curl -I -X OPTIONS https://app-frontend-esgi-app.azurewebsites.net/api-bridge.php
      ```

3. Vérifier la configuration SSL :
      ```bash
      openssl s_client -connect app-backend-esgi-app.azurewebsites.net:443
      ```

### 2. Problèmes de Performance

#### Temps de réponse lents

**Symptômes** :

- Temps de réponse > 1 seconde
- Timeouts fréquents

**Solutions** :

1. Vérifier la configuration du cache :

      ```php
      // config/performance.php
      define('CACHE_ENABLED', true);
      define('CACHE_DURATION', 3600);
      ```

2. Vérifier les timeouts :

      ```php
      // config/proxy.php
      define('CURL_TIMEOUT', 30);
      define('CURL_CONNECT_TIMEOUT', 5);
      ```

3. Vérifier la compression :
      ```php
      // config/performance.php
      define('COMPRESSION_ENABLED', true);
      define('COMPRESSION_LEVEL', 6);
      ```

#### Problèmes de mémoire

**Symptômes** :

- Erreurs "Memory limit exceeded"
- Performances dégradées

**Solutions** :

1. Vérifier la limite de mémoire :

      ```php
      // php.ini
      memory_limit = 256M
      ```

2. Optimiser le cache :

      ```bash
      # Nettoyer le cache
      rm -rf cache/*
      ```

3. Vérifier les logs :
      ```bash
      tail -f logs/error.log
      ```

### 3. Problèmes de Sécurité

#### Rate Limiting

**Symptômes** :

- Erreurs 429 (Too Many Requests)
- Blocage des requêtes

**Solutions** :

1. Vérifier la configuration du rate limiting :

      ```php
      // config/proxy.php
      define('RATE_LIMIT_REQUESTS', 100);
      define('RATE_LIMIT_WINDOW', 3600);
      ```

2. Vérifier les logs d'erreurs :

      ```bash
      grep "RATE_LIMIT" logs/error.log
      ```

3. Ajuster les limites si nécessaire :
      ```php
      // config/proxy.php
      define('RATE_LIMIT_REQUESTS', 200); // Augmenter la limite
      ```

#### Validation des entrées

**Symptômes** :

- Erreurs de validation
- Données rejetées

**Solutions** :

1. Vérifier les règles de validation :

      ```php
      // config/proxy.php
      define('INPUT_MAX_LENGTH', 1000);
      define('INPUT_ALLOWED_CHARS', '/^[a-zA-Z0-9\s\-_.,]+$/');
      ```

2. Vérifier les logs de validation :

      ```bash
      grep "VALIDATION" logs/error.log
      ```

3. Ajuster les règles si nécessaire :
      ```php
      // config/proxy.php
      define('INPUT_MAX_LENGTH', 2000); // Augmenter la limite
      ```

### 4. Problèmes de Logging

#### Rotation des logs

**Symptômes** :

- Fichiers de log trop volumineux
- Erreurs d'écriture

**Solutions** :

1. Vérifier la configuration de rotation :

      ```php
      // config/error-handler.php
      define('MAX_LOG_SIZE', 10 * 1024 * 1024);
      define('MAX_LOG_FILES', 5);
      ```

2. Nettoyer les logs manuellement :

      ```bash
      # Sauvegarder les logs actuels
      mv logs/error.log logs/error.log.bak

      # Créer un nouveau fichier
      touch logs/error.log
      chmod 644 logs/error.log
      ```

3. Vérifier l'espace disque :
      ```bash
      df -h
      ```

#### Métriques

**Symptômes** :

- Métriques manquantes
- Données incorrectes

**Solutions** :

1. Vérifier la configuration des métriques :

      ```php
      // config/performance.php
      define('METRICS_ENABLED', true);
      define('METRICS_RETENTION', 7 * 24 * 3600);
      ```

2. Vérifier les fichiers de métriques :

      ```bash
      ls -l metrics/
      ```

3. Nettoyer les métriques anciennes :
      ```bash
      find metrics/ -type f -mtime +7 -delete
      ```

## Outils de Diagnostic

### 1. Suite de Tests

```bash
# Exécuter tous les tests
php tools/proxy-test-suite.php

# Tester un endpoint spécifique
php tools/proxy-test-suite.php --endpoint=status.php
```

### 2. Vérification des Logs

```bash
# Voir les dernières erreurs
tail -f logs/error.log

# Chercher des erreurs spécifiques
grep "ERROR" logs/error.log

# Analyser les erreurs par type
awk '{print $1}' logs/error.log | sort | uniq -c
```

### 3. Monitoring

```bash
# Vérifier les métriques
cat metrics/performance.json

# Vérifier le cache
ls -l cache/

# Vérifier l'utilisation de la mémoire
ps aux | grep php
```

## Support

Pour toute question ou problème non résolu :

1. Consulter la documentation complète
2. Vérifier les logs d'erreurs
3. Utiliser l'outil de test
4. Contacter l'équipe de support avec :
      - Description du problème
      - Logs d'erreurs pertinents
      - Étapes de reproduction
      - Configuration actuelle
