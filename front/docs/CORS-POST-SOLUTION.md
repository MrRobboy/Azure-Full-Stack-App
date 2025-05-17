# Solution aux problèmes CORS et erreurs POST 404 sur Azure

Ce document explique la solution mise en place pour résoudre les problèmes de communication entre le frontend et le backend déployés sur Azure App Service, particulièrement les erreurs CORS et les requêtes POST qui échouent avec des erreurs 404.

## Problèmes identifiés

1. **Erreurs CORS** : Le frontend ne peut pas communiquer avec le backend à cause des restrictions de partage de ressources entre origines (CORS).
2. **Erreurs 404 sur les requêtes POST** : Les requêtes POST vers les fichiers proxy PHP fonctionnent localement mais échouent sur Azure avec des erreurs 404.
3. **Configuration IIS/Azure** : Le serveur web IIS sur Azure traite différemment les méthodes HTTP, particulièrement POST et OPTIONS.

## Solution implémentée

### 1. Configuration du Frontend

#### Fichiers modifiés/créés :

- **web.config** : Règles de réécriture améliorées pour traiter correctement les requêtes POST et OPTIONS.
- **applicationHost.xdt** : Configuration CORS injectée au niveau IIS.
- **post-test.php** : Script de diagnostic pour tester différentes méthodes de communication POST.
- **options-handler.php** : Gestionnaire spécifique pour les requêtes OPTIONS.

### 2. Configuration du Backend

#### Fichiers modifiés/créés :

- **.htaccess** : Configuration Apache optimisée pour CORS et traitement des méthodes HTTP.
- **post-handler.php** : Gestionnaire spécifique pour intercepter et traiter toutes les requêtes POST.
- **web.config** : Configuration IIS améliorée pour rediriger les requêtes POST et OPTIONS.
- **nginx.conf** : Configuration Nginx alternative si utilisé au lieu d'IIS/Apache.
- **web.startup.js** : Script de démarrage pour initialiser la configuration CORS.
- **setup-cors.bat** : Script batch pour configurer CORS sur Azure App Service.

### 3. Mécanismes de contournement dans le JavaScript frontend

- **config.js** : Détection de l'environnement Azure, test de différents chemins proxy.
- **handlePostRequest()** : Fonction de fallback qui utilise différentes méthodes pour contourner les erreurs de POST.
- **iframePostFallback()** : Méthode alternative utilisant un iframe pour contourner les limitations CORS.

## Comment déployer cette solution

1. **Déploiement du Frontend** :

      - Copier les fichiers `web.config`, `applicationHost.xdt` et `options-handler.php` dans le répertoire racine.
      - Vérifier que `js/config.js` utilise la détection d'environnement et les mécanismes de contournement.
      - Télécharger `post-test.php` pour diagnostiquer les problèmes POST.

2. **Déploiement du Backend** :

      - Copier les fichiers `web.config`, `.htaccess` et `post-handler.php` dans le répertoire racine.
      - Si nécessaire, exécuter `setup-cors.bat` pour configurer CORS supplémentaires.
      - Vérifier que toutes les routes API fonctionnent avec le routeur principal.

3. **Tester la communication** :
      - Accéder à `https://[votre-frontend].azurewebsites.net/post-test.php` pour vérifier si les méthodes POST fonctionnent.
      - Vérifier dans les journaux d'activité quelles méthodes fonctionnent le mieux.

## Comment ça marche

1. **Requêtes OPTIONS** :

      - Les requêtes OPTIONS (pre-flight CORS) sont interceptées et répondent immédiatement avec un statut 204 No Content.
      - Les en-têtes CORS appropriés sont ajoutés à toutes les réponses.

2. **Requêtes POST** :

      - Côté backend, toutes les requêtes POST sont redirigées vers `post-handler.php` qui garantit leur traitement correct.
      - Côté frontend, la fonction `handlePostRequest()` essaie différentes méthodes jusqu'à ce qu'une fonctionne.

3. **Contournement CORS** :
      - Les en-têtes CORS sont ajoutés à plusieurs niveaux (PHP, serveur web, middleware).
      - Des règles spécifiques garantissent que les requêtes entre domaines sont correctement autorisées.

## Dépannage

Si des problèmes persistent :

1. **Vérifier les journaux** :

      - Les scripts PHP génèrent des journaux détaillés via `error_log()` pour faciliter le diagnostic.
      - Consulter les journaux Azure App Service pour voir les erreurs HTTP.

2. **Tester avec post-test.php** :

      - Ce script teste plusieurs méthodes et indique laquelle fonctionne le mieux dans votre environnement.

3. **Configurations alternatives** :
      - Si votre backend n'utilise pas IIS, adaptez la configuration pour Apache (`.htaccess`) ou Nginx (`nginx.conf`).

## Conclusion

Cette solution résout les problèmes courants d'intégration entre applications frontend et backend déployées sur Azure App Service en utilisant plusieurs niveaux de configuration (serveur, application) et des mécanismes de contournement côté client pour garantir la communication même dans des environnements restrictifs.
