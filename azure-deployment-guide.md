# Guide de déploiement sur Azure

Ce guide explique comment configurer et vérifier la communication entre les trois composants de l'application déployés sur Azure:

- Frontend: https://app-frontend-esgi-app.azurewebsites.net
- Backend: https://app-backend-esgi-app.azurewebsites.net
- Base de données: sql-esgi-app.database.windows.net

## Configuration des services

### 1. Configuration de la Base de Données Azure SQL

1. Vérifiez que le serveur SQL autorise les connexions depuis les services Azure:

      - Dans le portail Azure, accédez à votre serveur SQL
      - Sous "Sécurité", assurez-vous que "Autoriser les services et ressources Azure à accéder à ce serveur" est activé
      - Ajoutez l'adresse IP du backend dans les règles de pare-feu

2. Vérifiez que la base de données est initialisée:
      - Exécutez le script SQL fourni dans `BDD/azureT-sql.sql` si ce n'est pas déjà fait

### 2. Configuration du Backend

1. Les fichiers de configuration du backend sont déjà mis à jour avec:

      - La connexion à Azure SQL Server dans `back/config/config.php`
      - Les règles CORS appropriées pour autoriser le frontend Azure
      - Le fichier `web.config` pour le routage des requêtes API

2. Vérifiez que les extensions PHP requises sont installées:
      - Dans le portail Azure, allez dans App Services > Votre backend > Configuration
      - Sous "Paramètres de l'application", assurez-vous que l'extension `pdo_sqlsrv` est activée
      - Si nécessaire, ajoutez une variable d'application: `PHP_INI_SCAN_DIR=d:\home\site\ini`
      - Et créez un fichier `d:\home\site\ini\extensions.ini` avec:
           ```ini
           extension=pdo_sqlsrv
           extension=sqlsrv
           ```

### 3. Configuration du Frontend

1. Les fichiers de configuration du frontend sont déjà mis à jour avec:
      - L'URL de l'API backend dans `front/js/config.js`
      - Un fichier `web.config` pour le routage des requêtes

## Vérification de la Communication

Une page de test a été créée pour vérifier la communication entre les composants:
https://app-frontend-esgi-app.azurewebsites.net/test-api-connection.php

Cette page effectue automatiquement les tests suivants:

1. Connexion du frontend au backend
2. Connexion du backend à la base de données
3. Test de différents endpoints API (classes, matières, examens)

## Dépannage

### Problèmes de connexion à la base de données

1. **Erreur d'authentification**:

      - Vérifiez les identifiants dans `back/config/config.php`
      - Vérifiez que l'utilisateur a les permissions nécessaires

2. **Erreur de connexion**:
      - Vérifiez que le pare-feu SQL Azure autorise les connexions à partir de l'App Service ou des services Azure
      - Vérifiez que le port 1433 est ouvert

### Problèmes CORS

1. **Erreurs CORS dans la console du navigateur**:

      - Vérifiez les en-têtes CORS dans `back/.htaccess` et `back/web.config`
      - Assurez-vous que les origines autorisées incluent le domaine frontend exact

2. **Problèmes d'authentification**:
      - Vérifiez que `Access-Control-Allow-Credentials` est défini sur `true`
      - Vérifiez que les cookies sont configurés correctement dans `back/routes/api.php`

### Problèmes de routage

1. **Erreurs 404**:
      - Vérifiez les règles de réécriture dans les fichiers `.htaccess` et `web.config`
      - Activez les journaux de débogage dans les App Services Azure

## GitHub Actions

Les GitHub Actions sont configurées pour déployer automatiquement les modifications:

1. Pour le **frontend**:

      - Les commits sur la branche principale déclenchent un déploiement vers `app-frontend-esgi-app.azurewebsites.net`

2. Pour le **backend**:
      - Les commits sur la branche principale déclenchent un déploiement vers `app-backend-esgi-app.azurewebsites.net`

## Surveillance et Maintenance

1. **Logs**:

      - Pour voir les logs d'erreurs PHP: Portail Azure > App Service > Journalisation
      - Activez l'option "Journal d'erreurs PHP" pour capturer les erreurs

2. **Surveillance des performances**:

      - Activez Application Insights pour surveiller les performances et les temps de réponse

3. **Maintenance de la base de données**:
      - Planifiez des sauvegardes régulières de votre base de données
      - Utilisez Azure SQL Database Advisor pour des recommandations de performances
