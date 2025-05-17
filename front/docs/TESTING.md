# Guide de Test de l'Application

## Configuration Requise

### Permissions des Fichiers Proxy

Les fichiers suivants doivent être accessibles et exécutables :

- `api-bridge.php`
- `simple-proxy.php`
- `proxy-health.php`

### Headers CORS Configurés

Les headers CORS suivants sont configurés pour permettre les requêtes depuis n'importe quelle origine :

```http
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With
Access-Control-Expose-Headers: X-Rate-Limit-Remaining, X-Rate-Limit-Reset
Access-Control-Max-Age: 86400
```

### Headers de Sécurité

Les headers de sécurité suivants sont configurés :

```http
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Strict-Transport-Security: max-age=31536000; includeSubDomains
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';
```

## Tests de Connexion

### Test du Proxy

```bash
curl -X GET "https://app-frontend-esgi-app.azurewebsites.net/api-bridge.php?endpoint=status.php"
```

### Test des Endpoints

```bash
# Test de l'endpoint status
curl -X GET "https://app-frontend-esgi-app.azurewebsites.net/api-bridge.php?endpoint=status.php"

# Test de l'authentification
curl -X POST "https://app-frontend-esgi-app.azurewebsites.net/api-bridge.php?endpoint=auth/login.php" \
  -H "Content-Type: application/json" \
  -d '{"username":"test","password":"test"}'

# Test des matières
curl -X GET "https://app-frontend-esgi-app.azurewebsites.net/api-bridge.php?endpoint=matieres.php" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Test des notes
curl -X GET "https://app-frontend-esgi-app.azurewebsites.net/api-bridge.php?endpoint=notes.php" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Tests CORS

### Test des Headers CORS

```bash
curl -X OPTIONS "https://app-frontend-esgi-app.azurewebsites.net/api-bridge.php" \
  -H "Origin: http://localhost:8080" \
  -H "Access-Control-Request-Method: GET" \
  -H "Access-Control-Request-Headers: Content-Type, Authorization" \
  -v
```

## Tests de Sécurité

### Test des Headers de Sécurité

```bash
curl -X GET "https://app-frontend-esgi-app.azurewebsites.net/api-bridge.php?endpoint=status.php" \
  -v
```

### Test de la Limite de Taux

```bash
# Test de la limite de 100 requêtes par heure
for i in {1..101}; do
  curl -X GET "https://app-frontend-esgi-app.azurewebsites.net/api-bridge.php?endpoint=status.php"
done
```

## Tests de Performance

### Test de Temps de Réponse

```bash
time curl -X GET "https://app-frontend-esgi-app.azurewebsites.net/api-bridge.php?endpoint=status.php"
```

## Dépannage

### Erreurs Courantes

#### 404 sur les Endpoints

**Symptômes** :

- Erreur 404 lors de l'accès aux endpoints
- Message "File not found"

**Solutions** :

1. Vérifier que les fichiers proxy sont bien déployés
2. Vérifier la configuration IIS
3. Vérifier les permissions des fichiers
4. Vérifier les logs d'erreur

#### Headers CORS Manquants

**Symptômes** :

- Erreurs CORS dans la console du navigateur
- Requêtes bloquées par le navigateur

**Headers Manquants** :

- Access-Control-Allow-Origin
- Access-Control-Allow-Methods
- Access-Control-Allow-Headers
- Access-Control-Expose-Headers
- Access-Control-Max-Age

**Solutions** :

1. Vérifier la configuration CORS dans `config/proxy.php`
2. Vérifier que les headers sont bien envoyés
3. Vérifier les logs du proxy

#### Headers de Sécurité Manquants

**Symptômes** :

- Avertissements de sécurité dans la console
- Headers de sécurité manquants dans la réponse

**Headers Manquants** :

- X-Content-Type-Options
- X-Frame-Options
- X-XSS-Protection
- Strict-Transport-Security
- Content-Security-Policy

**Solutions** :

1. Vérifier la configuration des headers dans `config/proxy.php`
2. Vérifier que les headers sont bien envoyés
3. Vérifier les logs du proxy

#### Erreurs de Validation des Entrées

**Symptômes** :

- Erreurs 400 Bad Request
- Messages d'erreur de validation

**Solutions** :

1. Vérifier la longueur des entrées
2. Vérifier le format des données
3. Vérifier les logs de validation

### Logs de Debug

Les logs détaillés sont disponibles pour chaque test en ajoutant le paramètre `debug=true` :

```bash
curl -X GET "https://app-frontend-esgi-app.azurewebsites.net/api-bridge.php?endpoint=status.php&debug=true"
```

### Vérification de la Configuration

1. Vérifier la configuration IIS :

      - Règles de réécriture
      - Gestion des fichiers PHP
      - Configuration CORS

2. Vérifier la configuration du proxy :

      - Fichiers de configuration
      - Permissions
      - Logs

3. Vérifier la configuration des tests :
      - URLs correctes
      - Headers appropriés
      - Données de test valides

### Erreurs rencontrées lors des tests précédents

- Succès sur `/status.php` (backend OK)
- 404 sur `/api/status` (inexistant)
- 404 sur `/auth/login`, `/matieres`, `/notes` (fichiers absents)
- Absence de headers CORS et sécurité sur les réponses du proxy (corrigé)
