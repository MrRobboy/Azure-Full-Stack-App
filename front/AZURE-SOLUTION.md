# Solution pour les problèmes d'accès proxy sur Azure

## Problème identifié

L'application déployée sur Azure rencontre un problème spécifique : les fichiers de proxy sont accessibles en GET (code 200) mais retournent des erreurs 404 lors des requêtes POST. Ce comportement est lié à la configuration du serveur web nginx sur Azure qui ne traite pas correctement les requêtes POST vers les fichiers PHP dans certains contextes.

## Symptômes

- Les fichiers proxy sont correctement déployés (vérifiable via azure-diagnostics.php)
- Les requêtes GET vers les proxies fonctionnent (status 200)
- Les requêtes POST vers les mêmes fichiers échouent avec un status 404
- La connexion échoue systématiquement avec le message "Impossible de communiquer avec le backend"

## Solution mise en œuvre

Nous avons implémenté plusieurs approches pour contourner ce problème :

### 1. Amélioration du fichier de configuration JavaScript (config.js)

Le fichier `config.js` a été modifié pour :

- Détecter automatiquement l'environnement Azure
- Tester plusieurs chemins de proxy possibles
- Implémenter une fonction `handlePostRequest()` qui gère spécifiquement les problèmes de 404 sur Azure
- Ajouter une méthode de contournement via iframe pour les requêtes POST qui échouent

### 2. Optimisation de login.php

Le fichier `login.php` a été mis à jour pour :

- Utiliser la nouvelle fonction `handlePostRequest()` si disponible
- Implémenter un fallback direct si la fonction n'est pas disponible
- Améliorer la gestion des erreurs et le logging

### 3. Création d'un fichier direct-login.php optimisé

Une version améliorée de `direct-login.php` qui :

- Effectue les requêtes directement au backend depuis le serveur (sans passer par le navigateur)
- Contourne les problèmes CORS et 404
- Offre une meilleure journalisation des erreurs

### 4. Outil de diagnostic avancé (proxy-advanced-test.html)

Un nouvel outil de diagnostic qui permet de :

- Tester tous les chemins de proxy disponibles
- Vérifier les méthodes GET et POST
- Tester les méthodes alternatives (iframe)
- Générer des recommandations basées sur les résultats

## Comment utiliser ces solutions

1. **Pour les développeurs** : Visitez `proxy-advanced-test.html` pour analyser l'état des proxies et voir les recommandations

2. **Pour l'application** :

      - La solution est implémentée automatiquement dans le code
      - En cas d'erreur de connexion, la tentative via `direct-login.php` sera utilisée

3. **Diagnostics** :
      - Utilisez `azure-diagnostics.php` pour vérifier l'état des fichiers
      - Consultez les logs d'erreurs pour plus de détails

## Fonctionnement technique du contournement

Le contournement principal utilise une approche en trois étapes :

1. Tentative via proxy normal (si accessible)
2. En cas d'erreur 404, utilisation de la méthode iframe (contourne les limitations CORS et 404)
3. En dernier recours, utilisation de `direct-login.php` qui fait la requête côté serveur

La méthode iframe fonctionne en :

- Créant un iframe caché
- Soumettant un formulaire HTML ciblant cet iframe
- Contournant ainsi les restrictions CORS et les problèmes de requêtes POST

## Notes pour le futur

- Cette solution est spécifique à Azure et pourrait nécessiter des ajustements si la configuration du serveur change
- Une solution plus propre serait de configurer correctement nginx sur Azure pour gérer les requêtes POST vers les fichiers PHP
- Consulter la documentation Azure sur les règles de réécriture d'URL pour les applications PHP
