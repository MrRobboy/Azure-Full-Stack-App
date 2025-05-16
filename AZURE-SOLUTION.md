# Solution pour les problèmes CORS sur Azure

Ce document explique la solution mise en place pour résoudre les problèmes de CORS entre le frontend et le backend sur Azure.

## Problème initial

Les requêtes cross-origin entre le frontend (https://app-frontend-esgi-app.azurewebsites.net) et le backend (https://app-backend-esgi-app.azurewebsites.net) échouaient avec des erreurs CORS:

```
Access to fetch at 'https://app-backend-esgi-app.azurewebsites.net/api-cors.php' from origin 'https://app-frontend-esgi-app.azurewebsites.net' has been blocked by CORS policy: No 'Access-Control-Allow-Origin' header is present on the requested resource.
```

## Cause principale

1. **Configuration Nginx incomplète**: Nginx sur Azure ne transmettait pas correctement les en-têtes CORS.
2. **Problème de routage**: Les requêtes OPTIONS préflight n'étaient pas correctement gérées.
3. **Configuration PHP et headers**: Les en-têtes CORS étaient appliqués trop tard dans le cycle de traitement.

## Solution mise en place

### 1. Fichiers d'API directs

Nous avons créé plusieurs points d'entrée API directs à la racine du projet:

- `api-auth-login.php`: Endpoint d'authentification direct
- `api-notes.php`: Endpoint pour les notes
- `api-router.php`: Router API général
- `api-cors.php`: Gestion des requêtes CORS OPTIONS
- `cors-test.php`: Outil de diagnostic CORS

### 2. Correction des en-têtes CORS

Chaque fichier API inclut maintenant:

```php
// IMPORTANT: Définir les en-têtes CORS avant toute autre opération
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://app-frontend-esgi-app.azurewebsites.net');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

// Forcer les en-têtes de cache pour éviter les problèmes de mise en cache
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// Désactiver le buffer de sortie pour s'assurer que les en-têtes sont envoyés immédiatement
if (ob_get_level()) ob_end_clean();

// Traiter immédiatement les requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
```

### 3. Configuration .htaccess

Le fichier `.htaccess` a été mis à jour pour appliquer les en-têtes CORS au niveau du serveur web:

```apache
<IfModule mod_headers.c>
    # Définir les en-têtes CORS pour tous les fichiers et particulièrement les fichiers API
    Header always set Access-Control-Allow-Origin "https://app-frontend-esgi-app.azurewebsites.net"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With, Accept, Origin"
    Header always set Access-Control-Allow-Credentials "true"
    Header always set Access-Control-Max-Age "86400"

    # Traitement spécial pour les requêtes OPTIONS préflight
    <If "%{REQUEST_METHOD} == 'OPTIONS'">
        Header always set Status "204"
        Header always set Content-Length "0"
        Header always set Content-Type "text/plain charset=UTF-8"
        RewriteRule ^(.*)$ $1 [R=204,L]
    </If>
</IfModule>
```

### 4. Configuration web.config pour IIS

Un fichier `web.config` a été ajouté pour configurer IIS sur Azure:

```xml
<httpProtocol>
    <customHeaders>
        <add name="Access-Control-Allow-Origin" value="https://app-frontend-esgi-app.azurewebsites.net" />
        <add name="Access-Control-Allow-Methods" value="GET, POST, PUT, DELETE, OPTIONS" />
        <add name="Access-Control-Allow-Headers" value="Content-Type, Authorization, X-Requested-With, Accept, Origin" />
        <add name="Access-Control-Allow-Credentials" value="true" />
        <add name="Access-Control-Max-Age" value="86400" />
    </customHeaders>
</httpProtocol>
```

### 5. Outils de test CORS

Deux outils de test ont été créés:

- `front/cors-test.html`: Test CORS côté frontend
- `front/test-direct-endpoints.php`: Test des endpoints directs

## Comment tester

1. Ouvrez `https://app-frontend-esgi-app.azurewebsites.net/cors-test.html`
2. Testez les différents endpoints pour vérifier que les en-têtes CORS sont correctement appliqués
3. Utilisez `https://app-frontend-esgi-app.azurewebsites.net/test-direct-endpoints.php` pour tester les endpoints directs

## Résolution des problèmes

Si les problèmes CORS persistent:

1. Vérifiez les logs dans `/logs/cors_test.log`
2. Testez l'endpoint de diagnostic: `https://app-backend-esgi-app.azurewebsites.net/cors-test.php`
3. Assurez-vous que `.htaccess` et `web.config` sont correctement déployés

## Configuration frontend

Le frontend a été mis à jour pour utiliser les endpoints directs en priorité dans `config.js`. En cas d'échec, il tentera d'utiliser des méthodes alternatives.
