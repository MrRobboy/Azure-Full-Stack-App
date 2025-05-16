# Solution pour les problèmes CORS sur Azure

Ce document explique la solution mise en place pour résoudre les problèmes de CORS entre le frontend et le backend sur Azure.

## Problème initial

Les requêtes cross-origin entre le frontend (https://app-frontend-esgi-app.azurewebsites.net) et le backend (https://app-backend-esgi-app.azurewebsites.net) échouaient avec des erreurs CORS:

```
Access to fetch at 'https://app-backend-esgi-app.azurewebsites.net/api-cors.php' from origin 'https://app-frontend-esgi-app.azurewebsites.net' has been blocked by CORS policy: No 'Access-Control-Allow-Origin' header is present on the requested resource.
```

## Cause principale

1. **Configuration Nginx/IIS incomplète**: Le serveur web sur Azure ne transmettait pas correctement les en-têtes CORS.
2. **Problème de routage**: Les requêtes OPTIONS préflight n'étaient pas correctement gérées.
3. **Configuration PHP et headers**: Les en-têtes CORS étaient appliqués trop tard dans le cycle de traitement.
4. **Spécificités d'Azure**: Le Reverse Proxy d'Azure App Service supprime ou modifie certains en-têtes HTTP.

## Solution mise en place

### 1. Fichiers d'API directs

Nous avons créé plusieurs points d'entrée API directs à la racine du projet:

- `api-auth-login.php`: Endpoint d'authentification direct
- `api-notes.php`: Endpoint pour les notes
- `api-router.php`: Router API général
- `api-cors.php`: Gestion des requêtes CORS OPTIONS
- `cors-test.php`: Outil de diagnostic CORS

### 2. CORS Proxy côté frontend

La solution la plus efficace mise en place est un **proxy CORS côté frontend**:

- `azure-cors-proxy.php`: Un proxy PHP qui fait les requêtes côté serveur vers le backend
- `direct-login.php`: Version optimisée du proxy spécifique à l'authentification

Cette approche contourne complètement les problèmes CORS en:

- Faisant les requêtes côté serveur (PHP) plutôt que côté client (JavaScript)
- Transmettant fidèlement tous les en-têtes et données entre le client et le backend
- Évitant le besoin de configurer CORS sur le serveur backend

### 3. Configuration backend optimisée

Malgré l'utilisation du proxy, nous avons également amélioré la configuration backend:

#### En-têtes CORS dans les fichiers PHP

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

#### Configuration .htaccess

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

#### Configuration web.config pour IIS

Modification du fichier web.config pour utiliser des outboundRules et un rewrite pour les requêtes OPTIONS:

```xml
<rewrite>
    <outboundRules>
        <!-- Add CORS headers to all responses -->
        <rule name="AddCorsHeaders" preCondition="PreflightRequest" enabled="true">
            <match serverVariable="RESPONSE_Access-Control-Allow-Origin" pattern=".*" />
            <action type="Rewrite" value="https://app-frontend-esgi-app.azurewebsites.net" />
        </rule>
        <!-- autres règles CORS -->
    </outboundRules>

    <rules>
        <!-- Special handling for OPTIONS requests -->
        <rule name="Options Method" stopProcessing="true">
            <match url=".*" />
            <conditions>
                <add input="{REQUEST_METHOD}" pattern="^OPTIONS$" />
            </conditions>
            <action type="Rewrite" url="api-cors.php" />
        </rule>
        <!-- autres règles -->
    </rules>
</rewrite>
```

### 4. Priorité de solution dans config.js

La configuration frontend a été modifiée pour privilégier les nouvelles solutions:

1. `azure-cors-proxy.php` (solution principale)
2. Accès direct aux endpoints API
3. Autres méthodes de contournement

## Comment tester

1. Ouvrez `https://app-frontend-esgi-app.azurewebsites.net/cors-test.html`
2. Testez les différents endpoints pour vérifier que les en-têtes CORS sont correctement appliqués
3. Utilisez `https://app-frontend-esgi-app.azurewebsites.net/test-direct-endpoints.php` pour tester les endpoints directs

## Résolution des problèmes

Si les problèmes CORS persistent:

1. Vérifiez que `azure-cors-proxy.php` fonctionne correctement
2. Consultez les logs dans `/logs/cors_proxy_errors.log`
3. Vérifiez que le backend est accessible depuis le serveur frontend avec un simple `curl` ou via le test du proxy

## Configuration frontend

Le frontend a été mis à jour pour utiliser les endpoints directs en priorité dans `config.js`. En cas d'échec, il tentera d'utiliser des méthodes alternatives.
