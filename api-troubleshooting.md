# Guide de dépannage des APIs sur Azure

Ce document fournit des instructions pour résoudre les problèmes d'API sur l'application hébergée sur Azure.

## Structure des URLs

L'application utilise plusieurs formats d'URL en fonction de l'endpoint :

### Formats d'URL API testés

1. **Format direct (sans préfixe api/)** :

      ```
      https://app-backend-esgi-app.azurewebsites.net/[endpoint]
      ```

2. **Format avec préfixe api/** :

      ```
      https://app-backend-esgi-app.azurewebsites.net/api/[endpoint]
      ```

3. **Format de routeur API direct** :

      ```
      https://app-backend-esgi-app.azurewebsites.net/routes/api.php?resource=[endpoint]
      ```

4. **Endpoints PHP directs** :
      ```
      https://app-backend-esgi-app.azurewebsites.net/[script].php
      ```

## Configuration CORS

La configuration CORS est gérée à deux niveaux :

1. **Au niveau IIS (web.config)** - Définit les en-têtes CORS globaux
2. **Au niveau PHP** - Définit les en-têtes CORS dans chaque script PHP

## Solutions pour les problèmes CORS

Nous avons implémenté plusieurs approches pour résoudre les problèmes CORS :

1. **Proxy Backend côté frontend** : Un script PHP qui sert de proxy pour éviter les problèmes CORS en faisant des requêtes server-to-server.

      ```
      https://app-frontend-esgi-app.azurewebsites.net/backend-proxy.php?endpoint=[endpoint]
      ```

2. **Test direct via l'outil API Tester** : Un outil dédié pour tester différents formats d'URL API

      ```
      https://app-frontend-esgi-app.azurewebsites.net/api-tester.php
      ```

3. **Scripts CORS dédiés** : Des scripts spécifiques qui définissent correctement les en-têtes CORS
      - `pure-cors-test.php`
      - `azure-cors.php`
      - `test-cors.php`

## Guides de dépannage

### Problème : Erreur CORS (No 'Access-Control-Allow-Origin')

Solutions :

- Utiliser le proxy backend
- Vérifier les en-têtes CORS dans le web.config
- Vérifier les en-têtes CORS dans le script PHP

### Problème : 404 Not Found pour les endpoints API

Solutions :

- Vérifier l'URL exacte avec l'outil API Tester
- Tester les trois formats d'URL (direct, avec api/, route)
- Vérifier les règles de réécriture dans web.config

### Problème : 401 Unauthorized pour les API

Solutions :

- Vérifier que vous êtes authentifié (cookies de session)
- Essayer d'accéder d'abord à status.php pour vérifier le statut global

## Utilisation du proxy

Le proxy backend a été amélioré pour essayer automatiquement plusieurs formats d'URL API, ce qui augmente les chances de réussite.

Exemple d'utilisation :

```javascript
fetch("backend-proxy.php?endpoint=status")
	.then((response) => response.json())
	.then((data) => console.log(data));
```

Pour les endpoints API, il tentera automatiquement les formats suivants :

- `/classes`
- `/api/classes`
- `/routes/api.php?resource=classes`

## Configuration recommandée

Après les tests avec l'API Tester, il est recommandé d'utiliser le format qui fonctionne de manière consistante dans votre fichier config.js.
