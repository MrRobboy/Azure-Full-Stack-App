# CHANGELOG 2.0

## [2.0.0] - 2023-11-15

### Ajouté

- Création d'un nouveau proxy optimisé (`new-proxy.php`) pour la communication front-back
- Ajout d'une page de test dédiée (`test-new-proxy.php`) pour vérifier le fonctionnement du proxy
- Documentation complète du nouveau proxy dans `docs/NEW-PROXY.md`

### Améliorations

- Simplification du processus de communication entre le frontend et le backend
- Optimisation de la gestion des en-têtes CORS
- Meilleure transmission des cookies de session pour l'authentification
- Journalisation détaillée pour faciliter le débogage
- Gestion robuste des erreurs avec réponses formatées
- Structure modulaire et fonctions utilitaires bien organisées

### Corrections

- Résolution des problèmes CORS persistants entre le frontend et le backend
- Amélioration de la transmission des cookies pour maintenir les sessions
- Gestion optimisée des requêtes OPTIONS préflight

### Technique

- Détection automatique de l'origine des requêtes
- Transmission fidèle des en-têtes HTTP entre client et backend
- Conservation du corps des requêtes POST/PUT/PATCH
- Gestion améliorée des paramètres de requête
- Timeout de 30 secondes pour éviter les requêtes bloquées
- Création automatique des répertoires de logs

### Notes d'implémentation

- Aucune configuration spéciale requise, fonctionne immédiatement après déploiement
- Compatible avec le développement local (localhost) et l'environnement Azure
- S'intègre dans le système existant via config.js
- Peut être adopté progressivement sans casser les fonctionnalités existantes

### Prochaines étapes

- Ajouter une option de cache pour améliorer les performances
- Implémenter un système de rate limiting configurable
- Développer des outils de monitoring plus avancés

## [2.0.1] - 2025-05-16

### Résultats des Tests

- ✅ **Test statut** : Connexion réussie au backend, réponse 200 OK avec informations détaillées sur le serveur
- ⚠️ **Test matières** : Échec avec code 401 (Unauthorized) - Authentification requise comme attendu
- ❌ **Test auth** : Problème avec le parsing JSON, réponse compressée non traitée correctement
- ✅ **Test CORS** : Requête OPTIONS gérée correctement avec réponse 204 No Content

### Corrections à appliquer

- Ajouter la gestion de décompression des réponses (gzip, deflate, br, zstd)
- Masquer les informations sensibles du serveur dans les réponses de statut
- Améliorer les messages d'erreur pour l'authentification
- Clarifier dans la documentation que l'accès aux matières nécessite une authentification

### Mise à jour technique

- Ajouter l'en-tête `Accept-Encoding` pour indiquer le support de la décompression
- Implémenter une fonction de décompression pour les différents formats
- Filtrer les informations sensibles des réponses de statut
- Améliorer la gestion des erreurs d'authentification avec des messages plus clairs

## [2.0.2] - 2025-05-16

### Ajouté

- Création d'un endpoint d'authentification spécialisé (`direct-auth.php`) pour résoudre les problèmes d'authentification
- Ajout d'un nouveau bouton de test pour l'authentification directe sur la page de test

### Améliorations

- Détection et gestion améliorées des réponses HTML inattendues
- Vérification renforcée de la validité des réponses JSON
- Messages d'erreur plus détaillés pour faciliter le débogage
- Journalisation enrichie des réponses d'authentification

### Corrections

- Résolution du problème de réponse HTML reçue lors de l'authentification
- Amélioration de la gestion des erreurs avec messages informatifs
- Traitement spécial pour les réponses d'authentification non-JSON

### Technique

- Validation stricte des formats de réponse attendus
- Filtrage des informations sensibles dans les logs d'authentification
- Journalisation détaillée des réponses pour faciliter le débogage
- Gestion optimisée des cookies de session pour l'authentification

### Notes d'implémentation

- L'authentification directe peut être utilisée comme alternative au proxy standard pour les problèmes d'auth
- Les développeurs peuvent choisir entre le proxy générique et l'endpoint spécialisé selon le contexte

## [2.0.3] - 2025-05-16

### Ajouté

- Création d'une solution d'authentification locale (`auth-api-fix.php`) pour contourner les problèmes d'accès à l'API
- Ajout d'un bouton "Tester l'auth locale" dans la page de test
- Implémentation d'un test automatique des matières après authentification réussie

### Corrections

- Correction des problèmes de chemins dans les requêtes d'authentification
- Implémentation d'une authentification côté frontend avec des utilisateurs de test
- Résolution des erreurs 404 lors des tentatives d'authentification
- Ajout de journalisation détaillée pour diagnostiquer les problèmes d'URL

### Technique

- Stockage du token d'authentification dans localStorage
- Transmission du token pour les requêtes authentifiées via le header Authorization
- Implémentation d'une vérification locale des identifiants
- Support de session PHP et token JWT pour l'authentification

### Notes d'implémentation

- Cette solution permet de tester l'authentification sans dépendre de l'API backend
- Les identifiants de test prédéfinis facilitent le développement et les tests
- Solution temporaire jusqu'à résolution des problèmes d'accès à l'API backend
