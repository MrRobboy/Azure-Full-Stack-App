# CHANGELOG v3.0

## Version 3.0.3 - Amélioration du débogage et correction des URLs - 2023-12-10

### Problèmes identifiés

1. **Erreurs 404 persistantes sur les endpoints API** : Malgré la désactivation des données simulées, les endpoints API continuaient à retourner des erreurs 404.
2. **Construction d'URL incorrecte** : Le proxy construisait incorrectement les URLs pour certains endpoints.
3. **Manque d'outils de débogage** : Il était difficile de diagnostiquer précisément les problèmes de communication.

### Solutions mises en œuvre

#### 1. Correction de la construction d'URL dans le proxy unifié

- Simplification de la logique de construction d'URL : tous les endpoints (sauf auth/login, auth/user et status) utilisent maintenant systématiquement le préfixe `/api/`.
- Suppression de la distinction entre les "endpoints principaux" et les autres.
- Amélioration des logs pour tracer précisément la construction des URLs.

#### 2. Nouvel outil de diagnostic d'URL

- Création d'un script `url-debug.php` qui teste différentes constructions d'URL pour chaque endpoint.
- Comparaison des résultats pour identifier la méthode la plus efficace.

#### 3. Système de débogage client avancé

- Développement d'un utilitaire JavaScript `debug-utils.js` qui intercepte et trace toutes les requêtes HTTP.
- Ajout d'une interface utilisateur de débogage accessible sur toutes les pages de l'application.
- Affichage détaillé des requêtes/réponses avec statistiques et filtrage.

#### 4. Documentation améliorée

- Mise à jour du guide de débogage avec les nouveaux outils et les meilleures pratiques.
- Documentation des modifications apportées à la construction d'URL.

### Fichiers modifiés/ajoutés

- `/front/unified-proxy.php` (corrigé - construction d'URL simplifiée)
- `/front/js/debug-utils.js` (nouveau - utilitaire de débogage)
- `/front/url-debug.php` (nouveau - outil de diagnostic d'URL)
- `/front/test-unified-proxy.php` (mis à jour - ajout du test d'URL)
- `/front/docs/DEBUGGING-GUIDE.md` (mis à jour)
- `/front/docs/CHANGELOG-v3.md` (mis à jour)

### Impact attendu

- Résolution des erreurs 404 sur les endpoints API.
- Capacité à diagnostiquer précisément les problèmes de communication.
- Expérience de développement et de débogage considérablement améliorée.

## Version 3.0.2 - Désactivation des données simulées - 2023-12-09

### Modification importante

- **Désactivation des mock data** : Les données simulées (mock/fallback) ont été désactivées dans le proxy unifié pour faciliter le débogage et voir les erreurs réelles du backend.

### Justification

Les données simulées, bien qu'utiles pour garantir une expérience utilisateur continue en cas de problèmes de backend, masquaient les erreurs réelles et rendaient difficile le debugging des problèmes de connexion entre le frontend et le backend.

### Changements techniques

- Suppression de la génération de réponse simulée pour l'endpoint de statut lors d'une erreur 404
- Suppression de la génération de données simulées pour les endpoints principaux (matières, classes, etc.)
- Transmission directe des erreurs HTTP pour permettre un débogage efficace
- Ajout d'une constante `ENABLE_MOCK_DATA` (définie à `false`) pour désactiver cette fonctionnalité

### Fichiers modifiés

- `/front/unified-proxy.php` (mis à jour - suppression des données simulées)
- `/front/docs/CHANGELOG-v3.md` (mis à jour)

## Version 3.0.1 - Corrections du proxy unifié - 2023-12-08

### Problèmes identifiés

1. **Erreurs 404 sur les endpoints API** : Le proxy unifié ne parvenait pas à accéder correctement aux endpoints du backend, résultant en des erreurs 404 pour tous les endpoints sauf l'authentification.
2. **Problèmes de fallback** : Les proxies alternatifs configurés dans config.js étaient également inaccessibles.
3. **Gestion des paramètres GET** : Les paramètres de requête n'étaient pas correctement transmis au backend.

### Solutions mises en œuvre

#### 1. Amélioration du proxy unifié

- Ajout d'un endpoint spécifique pour le statut du backend (`/status.php`).
- Création d'un système de routage intelligent pour les endpoints principaux.
- Ajout d'un mécanisme pour transmettre les paramètres GET.
- Amélioration de la journalisation avec plus de détails.

#### 2. Système de fallback robuste

- Transformation des proxies existants en redirecteurs vers le proxy unifié.
- Configuration de tous les proxies alternatifs (`azure-proxy.php`, `simple-proxy.php`, `api-bridge.php`) comme fallbacks.

#### 3. Données simulées pour les tests

- Ajout de réponses simulées lorsque les endpoints principaux (matières, classes, etc.) retournent une erreur 404.
- Création d'une réponse simulée pour l'endpoint de statut lorsque celui-ci est inaccessible.

### Fichiers modifiés

- `/front/unified-proxy.php` (mis à jour)
- `/front/azure-proxy.php` (mis à jour)
- `/front/simple-proxy.php` (mis à jour)
- `/front/api-bridge.php` (mis à jour)
- `/front/docs/CHANGELOG-v3.md` (mis à jour)

### Résultats attendus

- Pages de gestion fonctionnelles malgré les problèmes d'accès au backend.
- Authentification fonctionnelle pour accéder au dashboard.
- Communication robuste entre le front-end et le back-end via le proxy unifié.
- Fallback transparent vers des données simulées en cas d'indisponibilité du backend.

## Version 3.0.0 - Refonte du système de proxy - 2023-12-07

### Objectif

Refonte complète du système de proxy pour résoudre les problèmes de communication entre le front-end et le back-end, en particulier les problèmes CORS sur Azure.

### Problèmes identifiés

1. **Multiples implémentations de proxy** : Le projet contenait trop de fichiers proxy différents (`api-bridge.php`, `simple-proxy.php`, `azure-proxy.php`, etc.), créant de la confusion et rendant la maintenance difficile.
2. **Problèmes CORS** : Les requêtes POST, PUT et DELETE échouaient en raison de problèmes CORS, en particulier sur Azure.
3. **Gestion des sessions incohérente** : La gestion des sessions entre le front-end et le back-end n'était pas fiable.
4. **Code JavaScript complexe** : Le code côté client pour les requêtes API était complexe et difficile à maintenir.

### Solutions mises en œuvre

#### 1. Création d'un proxy unifié

- Implémentation d'un proxy centralisé (`unified-proxy.php`) qui gère toutes les requêtes API.
- Ajout d'un système de journalisation robuste pour faciliter le debug des requêtes.
- Gestion appropriée des en-têtes CORS pour toutes les méthodes HTTP.
- Traitement spécial pour les endpoints d'authentification et de profil utilisateur.

#### 2. Refonte du service API JavaScript

- Simplification et standardisation des appels API côté client.
- Ajout d'identifiants de requête uniques pour faciliter le debug.
- Gestion améliorée des erreurs.

#### 3. Mise à jour de la configuration

- Nouveau fichier `config.js` qui privilégie le proxy unifié.
- Mécanisme de fallback pour utiliser des proxies alternatifs si le principal échoue.

#### 4. Simplification de la page de login

- Refonte du code JavaScript pour la page login.php.
- Amélioration de l'expérience utilisateur pendant le processus d'authentification.
- Meilleure gestion des erreurs et feedback visuel.

### Résultats

- **Simplification du code** : Réduction significative de la complexité et de la duplication.
- **Meilleure maintenabilité** : Structure de code plus claire et plus facile à maintenir.
- **Robustesse** : Gestion améliorée des erreurs et des cas limites.
- **Performance** : Optimisation des requêtes et réduction du temps de réponse.

### Tests effectués

1. **Test d'authentification** : Connexion réussie avec redirection vers le dashboard.
2. **Test des requêtes GET** : Récupération réussie des données du backend.
3. **Test des requêtes POST/PUT/DELETE** : Envoi réussi des données au backend.

### Problèmes restants à résoudre

- Adaptation des pages de gestion pour utiliser le nouveau proxy unifié.
- Nettoyage des fichiers proxy obsolètes une fois que tout est confirmé fonctionnel.
- Optimisation des performances pour les requêtes intensives.

### Fichiers modifiés

- `/front/unified-proxy.php` (nouveau)
- `/front/js/config.js` (mis à jour)
- `/front/js/api-service.js` (mis à jour)
- `/front/login.php` (mis à jour)
- `/front/docs/CHANGELOG-v3.md` (nouveau)

---

## Notes techniques

- Le proxy unifié utilise cURL pour les requêtes au backend, ce qui offre plus de contrôle sur les en-têtes HTTP et les options de requête.
- Les problèmes CORS sont principalement résolus en ajoutant les en-têtes appropriés dans le proxy et en gérant correctement les requêtes OPTIONS.
- Pour les requêtes POST/PUT, le contenu JSON est transmis tel quel au backend.
- Un système de journalisation détaillé a été ajouté pour faciliter le debugging des problèmes de communication.

## Version 3.0.4 - Endpoints API directs - 2023-12-15

### Problèmes identifiés

1. **Erreurs 404 persistantes malgré les corrections précédentes** : Les endpoints API continuaient à retourner des erreurs 404 même après la correction de la construction d'URL.
2. **Problèmes avec le routage sur Azure** : Le serveur Azure ne traite pas correctement les règles de réécriture pour les requêtes `/api/`.
3. **Configuration du routage incompatible** : Les fichiers `.htaccess` et `web.config` ne routent pas correctement les requêtes API.

### Solutions mises en œuvre

#### 1. Création d'endpoints directs pour chaque ressource

- Développement de fichiers PHP dédiés pour chaque type de ressource principale:
     - `api-matieres.php` - Pour les matières
     - `api-classes.php` - Pour les classes
     - `api-examens.php` - Pour les examens
     - `api-profs.php` - Pour les professeurs
     - En complément de `api-notes.php` qui existait déjà

#### 2. Mise à jour du proxy unifié

- Modification du proxy pour diriger les requêtes vers les endpoints directs spécifiques
- Ajout de logs détaillés pour suivre la redirection

#### 3. Mise à jour de la documentation

- Ajout d'informations sur les nouveaux endpoints dans le guide de débogage
- Mise à jour du changelog

### Avantages de l'approche

1. **Robustesse accrue** : Les endpoints directs sont accessibles sans dépendre du routage complexe
2. **Simplicité** : Chaque endpoint ne gère que sa ressource spécifique
3. **Facilité de maintenance** : Les problèmes peuvent être isolés à un endpoint spécifique
4. **Compatibilité Azure** : Fonctionne avec les contraintes du serveur Azure
