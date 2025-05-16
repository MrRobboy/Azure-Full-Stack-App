# Solution pour les problèmes d'accès API sur Azure

## Problème identifié

L'application déployée sur Azure rencontre un problème spécifique : les endpoints API définis dans routes/api.php retournent systématiquement des erreurs 404, que ce soit en GET ou en POST. Ce comportement est lié à la configuration du serveur web nginx sur Azure qui ne traite pas correctement les routes API définies dans le backend.

## Symptômes

- Les fichiers du backend sont correctement déployés (vérifiable via azure-init.php)
- Toutes les requêtes vers /api/\* échouent avec un status 404
- La connexion échoue systématiquement avec le message "Impossible de communiquer avec le backend"
- Les diagnostics montrent que le serveur exécute nginx/1.26.2 avec PHP 8.2.27

## Solution mise en œuvre

Nous avons implémenté une approche à deux niveaux pour résoudre ce problème :

### 1. Création d'endpoints API directs dans la racine du backend

Pour contourner les problèmes de routage nginx, nous avons créé des fichiers PHP dédiés à l'API dans la racine :

- `api-auth-login.php` : Point d'entrée spécifique pour l'authentification
- `api-notes.php` : Point d'entrée pour la gestion des notes
- `api-router.php` : Router général pour les autres endpoints API
- `nginx-adapter.php` : Adaptateur pour le routage nginx

### 2. Configuration nginx optimisée

Création d'un fichier `default.conf` qui :

- Configure correctement les en-têtes CORS
- Définit les routes pour les endpoints API
- Gère proprement les requêtes OPTIONS pour CORS

### 3. Optimisation du frontend pour utiliser les endpoints directs

Le fichier `config.js` et les proxys ont été mis à jour pour :

- Tenter d'abord les nouveaux endpoints API directs
- Revenir aux méthodes existantes en cas d'échec
- Améliorer la gestion des erreurs et le logging

### 4. Outil de test dédié

Création de `test-direct-endpoints.php` qui permet de :

- Tester tous les nouveaux endpoints directs
- Vérifier les méthodes GET et POST
- Diagnostiquer les problèmes de communication

## Comment utiliser ces solutions

1. **Pour les développeurs** : Utilisez `test-direct-endpoints.php` pour vérifier l'état des endpoints API

2. **Pour la mise en production** :

      - Assurez-vous que tous les fichiers API directs sont déployés dans la racine du backend
      - Vérifiez que le fichier `.user.ini` est correctement déployé pour les paramètres PHP
      - Configurez nginx pour utiliser les paramètres du fichier `default.conf`

3. **Diagnostics** :
      - `azure-init.php` : Affiche l'état de déploiement et les variables d'environnement
      - `deployment-complete.php` : Vérifie que le déploiement est complet
      - `test-api.php` : Teste tous les endpoints API

## Fonctionnement technique

Cette solution fonctionne en :

1. Contournant le routage complexe avec des fichiers PHP accessibles directement
2. Utilisant des adaptateurs pour communiquer avec le code existant
3. Configurant correctement les en-têtes CORS pour permettre la communication cross-domain
4. Mettant en place une gestion robuste des erreurs et du logging

## Notes pour le futur

- Cette solution est conçue spécifiquement pour Azure App Service avec Linux (nginx)
- Si le serveur est migré vers Windows (IIS), utilisez le fichier web.config inclus
- Pour une solution plus robuste à long terme, envisagez d'utiliser un framework comme Laravel ou Symfony qui gère nativement le routage
