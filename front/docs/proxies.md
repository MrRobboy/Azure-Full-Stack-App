# Fichiers Proxy pour l'Application Azure Full-Stack

Ce document décrit les différents fichiers proxy utilisés pour communiquer entre le frontend et le backend.

## Proxies disponibles

### 1. `simple-proxy.php`

- **Description**: Proxy simple qui transmet les requêtes au backend sans validation JWT
- **Fonctionnalités**:
     - Transmet les requêtes HTTP (GET, POST, PUT, DELETE)
     - Conserve les en-têtes et les corps des requêtes
     - Ne vérifie pas l'authentification
- **Utilisation**: Idéal pour tester les endpoints sans authentification

### 2. `simplified-jwt-bridge.php`

- **Description**: Service d'authentification simplifié qui simule la génération et validation JWT
- **Fonctionnalités**:
     - Accepte n'importe quelle combinaison email/mot de passe
     - Génère un token JWT valide
     - Simule la validation de tokens JWT
- **Utilisation**: Pour l'endpoint d'authentification (`auth/login`)

### 3. `azure-proxy.php`

- **Description**: Fichier de redirection vers `simple-proxy.php`
- **Fonctionnalités**:
     - Redirige automatiquement vers `simple-proxy.php`
     - Préserve tous les paramètres de la requête
- **Utilisation**: Requis par le frontend qui essaie d'accéder à ce fichier

### 4. `api-bridge.php`

- **Description**: Routeur intelligent qui sélectionne le proxy approprié
- **Fonctionnalités**:
     - Redirige vers `simplified-jwt-bridge.php` pour les endpoints d'authentification
     - Redirige vers `simple-proxy.php` pour tous les autres endpoints
- **Utilisation**: Requis par le frontend pour l'authentification

### 5. `test-proxy.php`

- **Description**: Outil de diagnostic pour vérifier l'état des proxies
- **Fonctionnalités**:
     - Vérifie l'existence des différents fichiers proxy
     - Fournit des informations sur l'environnement
- **Utilisation**: Diagnostic et débogage

## Architecture

```
Frontend                  Proxies                     Backend
+----------+     +------------------------+     +-------------+
|          |     |                        |     |             |
|  React   +---->+  api-bridge.php        +---->+  API REST   |
|   App    |     |  (routeur)             |     |             |
|          |     |    |                   |     |             |
+----------+     |    v                   |     +-------------+
                 |  simplified-jwt-bridge |
                 |    ou                  |
                 |  simple-proxy.php      |
                 |                        |
                 +------------------------+
```

## Configuration du Frontend

Le frontend est configuré pour utiliser ces proxies dans cet ordre:

1. `azure-proxy.php` (pour la vérification)
2. `api-bridge.php` (pour les appels API)

## Cas d'utilisation

1. **Login**:

      - Frontend -> `api-bridge.php?endpoint=auth/login` -> `simplified-jwt-bridge.php` -> Token JWT généré

2. **Accès aux données**:
      - Frontend -> `api-bridge.php?endpoint=notes` -> `simple-proxy.php` -> Backend -> Données

## Test des proxies

Pour vérifier que les proxies fonctionnent correctement:

1. Accédez à `test-proxy.php` pour voir l'état de tous les proxies
2. Essayez de vous connecter avec n'importe quelles credentials
3. Accédez aux endpoints API après avoir obtenu un token
