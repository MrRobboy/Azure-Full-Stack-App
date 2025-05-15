# Guide de débogage des problèmes API sur Azure

Ce guide est destiné à aider au diagnostic et à la résolution des problèmes courants rencontrés avec les APIs sur l'application hébergée sur Azure.

## Solution mise en place pour Azure avec Nginx

L'infrastructure backend utilise Nginx comme serveur web (`nginx/1.26.2`), ce qui a nécessité une approche différente pour la configuration du routage par rapport à Apache ou IIS.

1. Problème détecté : Nginx ne traite pas les règles de réécriture du fichier `web.config` de la même manière qu'IIS
2. Solution implémentée :
      - Modification du fichier `index.php` pour servir de routeur central
      - Mise à jour du fichier `.htaccess` pour diriger toutes les requêtes vers notre routeur
      - Adaptation du proxy côté frontend pour privilégier le format d'URL avec préfixe `/api/`

### Nouvelle structure de routage

La nouvelle structure de routage implémentée :

1. Toutes les requêtes sont d'abord traitées par `index.php`
2. Les URL avec le format `/api/[resource]` sont automatiquement dirigées vers le bon contrôleur
3. Format préféré : `https://app-backend-esgi-app.azurewebsites.net/api/classes`

## Erreur: "SyntaxError: Unexpected token '<'"

Cette erreur se produit généralement lorsque l'API renvoie du HTML au lieu du JSON attendu.

### Causes possibles

1. **Erreurs PHP affichées**: Les erreurs PHP sont affichées dans la réponse au lieu d'être uniquement journalisées
2. **Pages d'erreur Nginx**: Nginx renvoie une page d'erreur HTML (404) au lieu d'une réponse JSON
3. **Problèmes de redirection**: Une redirection vers une page de connexion ou une autre page HTML
4. **Erreurs de script**: Erreurs de syntaxe ou d'exécution dans les scripts PHP
5. **Problèmes de session PHP**: Erreurs liées à la session PHP (`session_set_cookie_params`)

### Solutions

1. **Utiliser le mode debug**:

      - Activez le mode debug dans l'API Tester (`backend-proxy.php?endpoint=...&debug=1`)
      - Examinez le contenu HTML renvoyé pour identifier l'erreur spécifique

2. **Configurer PHP correctement**:

      ```php
      error_reporting(E_ALL);
      ini_set('display_errors', 0); // Désactiver l'affichage des erreurs
      ```

3. **Tester avec l'outil Direct API Tester**:

      - Utilisez `direct-api-tester.html` pour tester les endpoints sans le proxy
      - Comparez les résultats entre les appels directs et via le proxy

4. **Vérifier les logs PHP**:

      - Vérifiez les logs d'erreur sur le serveur Azure pour identifier les problèmes PHP

5. **Corriger les problèmes de session**:
      ```php
      // Vérifier si la session est déjà active
      if (session_status() == PHP_SESSION_NONE) {
          session_set_cookie_params([...]);
          session_start();
      }
      ```

## Erreurs 404 (Not Found)

### Causes possibles

1. **Format d'URL incorrect**: L'URL utilisée ne correspond pas à la structure attendue par le serveur
2. **Règles de réécriture mal configurées**: Les règles dans web.config ne sont pas traitées par Nginx
3. **Problèmes de routage API**: La structure de routage API ne fonctionne pas comme prévu

### Solutions

1. **Utiliser le format d'URL avec préfixe API**:

      - Format correct: `/api/[resource]` (ex: `/api/classes`)
      - Éviter: `/[resource]` ou `/routes/api.php?resource=[resource]`

2. **Utiliser le routeur PHP**:

      - Toutes les requêtes doivent passer par `index.php`
      - Les URL sont analysées et routées vers le bon contrôleur

3. **Utiliser l'auto-découverte**:

      - L'outil API Tester inclut une fonction d'auto-découverte qui teste plusieurs formats d'URL

4. **Tester avec l'outil test-routing.php**:
      - `/test-routing.php` pour vérifier la configuration du serveur et des routes

## Problèmes CORS

### Causes possibles

1. **En-têtes CORS manquants**: Les en-têtes CORS requis ne sont pas envoyés par le serveur backend
2. **Configuration Nginx incorrecte**: La configuration CORS dans Nginx est incorrecte
3. **PHP écrase les en-têtes**: Les scripts PHP écrasent les en-têtes CORS définis au niveau de Nginx

### Solutions

1. **Utiliser le proxy backend**:

      - Le proxy backend contourne les problèmes CORS en faisant des requêtes server-to-server

2. **Vérifier les en-têtes CORS dans PHP**:

      ```php
      header('Access-Control-Allow-Origin: https://app-frontend-esgi-app.azurewebsites.net');
      header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
      header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
      header('Access-Control-Allow-Credentials: true');
      ```

3. **Tests directs des endpoints avec l'outil direct-api-tester.html**

## Problèmes d'authentification

### Causes possibles

1. **Cookies de session non envoyés**: Les cookies de session ne sont pas envoyés avec les requêtes API
2. **Session expirée**: La session utilisateur a expiré
3. **Problèmes de domaines croisés**: Problèmes de cookies entre domaines différents

### Solutions

1. **Inclure les credentials dans les requêtes fetch**:

      ```javascript
      fetch(url, {
      	credentials: "include"
      });
      ```

2. **Vérifier l'état de la session**:
      - Tester l'endpoint status.php pour vérifier l'état de la session

## Outils de débogage disponibles

1. **backend-proxy.php**: Proxy côté serveur qui contourne les problèmes CORS et facilite le débogage

      - Utilisation: `backend-proxy.php?endpoint=api/classes&debug=1`

2. **api-tester.php**: Interface pour tester différents formats d'URL API

      - Test de différents endpoints avec et sans le préfixe "api/"
      - Auto-découverte des formats d'URL fonctionnels

3. **test-direct-api.php**: Tester les endpoints API directement

      - Fournit des informations détaillées sur la réponse

4. **direct-api-tester.html**: Interface utilisateur pour test-direct-api.php

5. **test-routing.php**: Teste la configuration du serveur et des routes
      - Fournit des informations sur le type de serveur et les fichiers de configuration

## Conseils généraux

1. **Utiliser le format d'URL préféré**: `/api/[resource]` (ex: `/api/classes`)
2. **Commencer simple**: Testez d'abord les endpoints les plus simples comme `/api-test.php`
3. **Vérifiez la configuration CORS**: Assurez-vous que la configuration CORS est correcte
4. **Utilisez le mode debug**: Activez le mode debug pour obtenir plus d'informations
5. **Vérifiez les logs**: Examinez les logs PHP pour identifier les erreurs
