# Guide de dépannage des APIs sur Azure

Ce document fournit des instructions pour résoudre les problèmes d'API sur l'application hébergée sur Azure.

## Mise à jour importante: Azure avec Nginx

Suite à des investigations récentes, nous avons découvert que l'infrastructure backend fonctionne avec Nginx (`nginx/1.26.2`) et non IIS comme initialement prévu. Cela explique pourquoi certaines configurations dans `web.config` n'étaient pas appliquées.

## Nouvelle structure de routage

Pour résoudre les problèmes de routage sur Nginx, nous avons implémenté les solutions suivantes:

1. **Routeur PHP central**: Modification du fichier `index.php` pour agir comme un routeur central qui gère toutes les requêtes API
2. **Correction du format d'URL**: Standardisation sur le format `/api/[resource]` pour toutes les requêtes API
3. **Correction des problèmes de session**: Résolution des avertissements liés à la session PHP qui interrompaient les réponses JSON
4. **Amélioration du proxy backend**: Mise à jour pour prioriser le nouveau format d'URL API

### Format d'URL recommandé

Le format d'URL recommandé pour toutes les requêtes API est désormais:

```
https://app-backend-esgi-app.azurewebsites.net/api/[resource]
```

Exemple: `https://app-backend-esgi-app.azurewebsites.net/api/classes`

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

1. **Au niveau Nginx** - Peut être configuré dans le serveur web
2. **Au niveau PHP** - Défini dans chaque script PHP

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
- Vérifier les en-têtes CORS dans les scripts PHP
- Tester avec Direct API Tester pour isoler le problème

### Problème : 404 Not Found pour les endpoints API

Solutions :

- Utiliser le format d'URL avec préfixe API: `/api/[resource]`
- Vérifier avec l'outil test-routing.php pour comprendre la configuration du serveur
- Utiliser l'outil API Tester pour tester différents formats d'URL

### Problème : Erreur "SyntaxError: Unexpected token '<'"

Solutions :

- Vérifier les paramètres de session PHP pour éviter les warnings
- Désactiver l'affichage des erreurs PHP (`display_errors = Off`)
- Utiliser le mode debug dans le proxy pour voir l'HTML exact renvoyé
- Corriger les erreurs PHP dans le script backend identifiées

### Problème : 401 Unauthorized pour les API

Solutions :

- Vérifier que vous êtes authentifié (cookies de session)
- Essayer d'accéder d'abord à status.php pour vérifier le statut global

## Utilisation du proxy

Le proxy backend a été amélioré pour essayer automatiquement plusieurs formats d'URL API, ce qui augmente les chances de réussite.

Exemple d'utilisation avec le mode debug :

```javascript
fetch("backend-proxy.php?endpoint=api/status&debug=1")
	.then((response) => response.json())
	.then((data) => console.log(data));
```

### Nouveaux outils de diagnostic

1. **direct-api-tester.html** : Interface pour tester les API directement sans le proxy
2. **test-direct-api.php** : Script PHP pour tester les endpoints API avec plus de détails
3. **test-routing.php** : Script pour vérifier la configuration du serveur et des routes

## Configuration recommandée

Après les tests avec les nouveaux outils, il est recommandé d'utiliser le format d'URL avec préfixe API (`/api/[resource]`) dans toutes vos requêtes API.
