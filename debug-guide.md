# Guide de débogage des problèmes API sur Azure

Ce guide est destiné à aider au diagnostic et à la résolution des problèmes courants rencontrés avec les APIs sur l'application hébergée sur Azure.

## Erreur: "SyntaxError: Unexpected token '<'"

Cette erreur se produit généralement lorsque l'API renvoie du HTML au lieu du JSON attendu.

### Causes possibles

1. **Erreurs PHP affichées**: Les erreurs PHP sont affichées dans la réponse au lieu d'être uniquement journalisées
2. **Pages d'erreur IIS**: IIS renvoie une page d'erreur HTML au lieu d'une réponse JSON
3. **Problèmes de redirection**: Une redirection vers une page de connexion ou une autre page HTML
4. **Erreurs de script**: Erreurs de syntaxe ou d'exécution dans les scripts PHP

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

## Erreurs 404 (Not Found)

### Causes possibles

1. **Format d'URL incorrect**: L'URL utilisée ne correspond pas à la structure attendue par le serveur
2. **Règles de réécriture mal configurées**: Les règles dans web.config ne correspondent pas aux URL demandées
3. **Problèmes de routage API**: La structure de routage API ne fonctionne pas comme prévu

### Solutions

1. **Tester différents formats d'URL**:

      - Direct: `/endpoint`
      - Avec préfixe API: `/api/endpoint`
      - Via routeur: `/routes/api.php?resource=endpoint`

2. **Vérifier les règles de réécriture dans web.config**:

      ```xml
      <rule name="API Router" stopProcessing="true">
          <match url="^api/(.*)$" />
          <action type="Rewrite" url="routes/api.php?resource={R:1}" appendQueryString="true" />
      </rule>
      ```

3. **Utiliser l'auto-découverte**:
      - L'outil API Tester inclut une fonction d'auto-découverte qui teste plusieurs formats d'URL

## Problèmes CORS

### Causes possibles

1. **En-têtes CORS manquants**: Les en-têtes CORS requis ne sont pas envoyés par le serveur backend
2. **Configuration IIS incorrecte**: La configuration CORS dans IIS est incorrecte
3. **PHP écrase les en-têtes**: Les scripts PHP écrasent les en-têtes CORS définis au niveau d'IIS

### Solutions

1. **Utiliser le proxy backend**:

      - Le proxy backend contourne les problèmes CORS en faisant des requêtes server-to-server

2. **Configuration CORS dans web.config**:

      ```xml
      <httpProtocol>
          <customHeaders>
              <add name="Access-Control-Allow-Origin" value="https://app-frontend-esgi-app.azurewebsites.net" />
              <add name="Access-Control-Allow-Methods" value="GET, POST, PUT, DELETE, OPTIONS" />
              <add name="Access-Control-Allow-Headers" value="Content-Type, Authorization, X-Requested-With" />
              <add name="Access-Control-Allow-Credentials" value="true" />
          </customHeaders>
      </httpProtocol>
      ```

3. **Ajouter des en-têtes CORS dans PHP**:
      ```php
      header('Access-Control-Allow-Origin: https://app-frontend-esgi-app.azurewebsites.net');
      header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
      header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
      header('Access-Control-Allow-Credentials: true');
      ```

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

5. **api-test.php**: Endpoint de test qui renvoie des informations sur la requête
      - Utile pour vérifier que les requêtes atteignent le backend

## Conseils généraux

1. **Commencer simple**: Testez d'abord les endpoints les plus simples comme api-test.php
2. **Vérifiez la configuration CORS**: Assurez-vous que la configuration CORS est correcte
3. **Utilisez le mode debug**: Activez le mode debug pour obtenir plus d'informations
4. **Testez différents formats d'URL**: Essayez plusieurs variations d'URL pour trouver celle qui fonctionne
5. **Vérifiez les logs**: Examinez les logs PHP pour identifier les erreurs
