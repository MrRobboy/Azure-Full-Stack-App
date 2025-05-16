# Compréhension du Projet Azure Full Stack

## Vue d'Ensemble

Le projet consiste en une application web full-stack déployée sur Azure, composée de deux Web Apps distinctes :

1. **Frontend Web App**

      - URL : https://app-frontend-esgi-app.azurewebsites.net
      - Technologies : PHP, JavaScript, HTML/CSS
      - Connecté à GitHub pour le déploiement continu
      - Dossier source : `Azure-Full-Stack-App/front`

2. **Backend Web App**
      - URL : https://app-backend-esgi-app.azurewebsites.net
      - API Base URL : https://app-backend-esgi-app.azurewebsites.net/api
      - Technologies : API REST
      - Dossier source : `Azure-Full-Stack-App/back`

## Historique et Migration

- Application initialement hébergée sur une VM Debian
- Migration vers Azure Web Apps
- Problèmes majeurs de CORS lors de la migration
- Développement de solutions de contournement via des proxies

## Architecture Technique

### Frontend

- Structure MVC classique
- Système de proxy pour contourner les problèmes CORS
- Fichiers clés :
     - `api-bridge.php` : Proxy principal
     - `login.php` : Gestion de l'authentification
     - `dashboard.php` : Interface principale
     - Divers fichiers de gestion (notes, matières, etc.)

### Backend

- Architecture API REST
- Structure organisée :
     - `/controllers` : Logique métier
     - `/models` : Modèles de données
     - `/routes` : Définition des endpoints
     - `/services` : Services métier
     - `/config` : Configuration
     - `/database` : Scripts DB

## Points d'Attention

1. **CORS et Proxy**

      - Problèmes de communication entre frontend et backend
      - Solutions de contournement via des proxies
      - Configuration complexe des headers CORS

2. **Déploiement**

      - GitHub Actions configurées pour les deux Web Apps
      - Déploiement automatique lors des pushs
      - Synchronisation continue entre GitHub et Azure

3. **Sécurité**
      - Gestion de l'authentification
      - Validation des entrées
      - Protection des endpoints API

## Flux de Communication

1. **Requête Frontend → Backend**

      ```
      Navigateur → Frontend → Proxy (api-bridge.php) → Backend API → Base de données
      ```

2. **Réponse Backend → Frontend**
      ```
      Base de données → Backend API → Proxy → Frontend → Navigateur
      ```

## Défis Techniques

1. **CORS**

      - Restrictions de sécurité Azure
      - Solutions de proxy complexes
      - Configuration des headers

2. **Déploiement**

      - Synchronisation GitHub/Azure
      - Gestion des environnements
      - Configuration des Web Apps

3. **Performance**
      - Latence due aux proxies
      - Optimisation des requêtes
      - Gestion du cache

## Points à Surveiller

1. **Proxy**

      - Fonctionnement correct de api-bridge.php
      - Gestion des erreurs
      - Performance

2. **Déploiement**

      - Suivi des GitHub Actions
      - Vérification des déploiements
      - Logs Azure

3. **Sécurité**
      - Validation des entrées
      - Gestion des sessions
      - Protection des endpoints
