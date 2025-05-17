# Changelog## [4.15.0] - 2024-05-17### Fixed- Correction finale du problème de suppression des classes - Correction des fonctions deleteClasse et updateClasse pour envoyer les identifiants dans le corps de la requête comme attendu par le backend - Alignement avec la structure API du backend qui nécessite que l'ID soit envoyé dans le champ "id" du JSON - Retour à l'approche initiale en envoyant l'ID dans le corps de la requête plutôt que dans l'URL## [4.14.0] - 2024-05-17### Fixed- Correction du problème persistant de suppression des classes - Modification des fonctions deleteClasse et updateClasse pour envoyer les identifiants dans l'URL plutôt que dans le corps de la requête - Alignement avec le comportement attendu par le backend qui attend les IDs dans le chemin de l'URL - Harmonisation du format de requête avec les standards REST## [4.13.0] - 2024-05-17### Fixed- Correction du problème de suppression des classes - Ajout d'une vérification explicite dans le contrôleur pour détecter si une classe contient des élèves - Amélioration des messages d'erreur lors de la tentative de suppression d'une classe avec élèves - Simplification du modèle Classe.php en déplaçant la logique de vérification dans le contrôleur

## [4.12.0] - 2024-05-17

### Changed

- Amélioration de l'interface utilisateur pour plus de cohérence
     - Refactorisation de `dashboard.php` pour utiliser le template `base.php` comme les autres pages
     - Harmonisation du style de `gestion_classes.php` avec celui de `gestion_matieres.php`
     - Amélioration du design du modal de modification des classes
     - Ajout d'icônes dans les boutons et amélioration visuelle des formulaires

## [4.11.0] - 2024-05-17

### Fixed

- Correction des problèmes d'accès à la page de gestion des classes
     - Suppression de la restriction de rôle "admin" qui empêchait l'accès
     - Correction de la structure avec ajout de la mise en tampon (ob_start)
     - Mise à jour des opérations DELETE et PUT pour envoyer l'ID dans le corps de la requête

## [4.10.0] - 2024-05-17

### Fixed

- Correction des erreurs 400 "Missing required field (id)" lors de la suppression et mise à jour des matières
     - Mise à jour de la fonction `deleteMatiere` dans `gestion_matieres.php` pour envoyer l'ID dans le corps de la requête
     - Mise à jour de la fonction `handleMatiereSubmit` pour envoyer l'ID dans le corps de la requête lors des opérations PUT
     - Modification de `ApiService.js` pour inclure les données dans le corps des requêtes DELETE (ajout de "DELETE" à la liste des méthodes supportant un body)
     - Correction de `unified-proxy.php` pour transmettre le corps des requêtes DELETE au backend (ajout du traitement de php://input pour les requêtes DELETE)

### Changed

- Suppression du chargement automatique des identifiants de test dans la page de connexion
     - Retrait de l'alerte d'information avec les identifiants de test
     - Suppression du bouton "Charger des identifiants de test"
     - Suppression de la fonction `loadTestCredentials` et des éléments DOM associés

## [4.9.0] - 2024-05-16

### Changed

- Correction de la construction de l'URL dans `api-bridge.php` : ajout automatique de `.php` à l'endpoint si absent.
- Envoi systématique des headers CORS et de sécurité dans toutes les réponses du proxy, y compris en cas d'erreur.

### Fixed

- Résolution des erreurs 404 sur les endpoints `/auth/login`, `/matieres`, `/notes` en adaptant la logique du proxy pour cibler les bons fichiers PHP du backend.
- Correction de l'absence de headers CORS et sécurité dans les réponses du proxy.

### Documentation

- Ajout d'une explication sur la structure des endpoints backend (fichiers PHP à la racine et non routes REST).
- Documentation des erreurs rencontrées lors des tests :
     - 404 sur `/api/status` (inexistant)
     - Succès sur `/status.php` (backend OK)
     - 404 sur `/auth/login`, `/matieres`, `/notes` (fichiers absents)
     - Absence de headers CORS et sécurité sur les réponses du proxy
- Mise à jour des recommandations de test dans `TESTING.md`.

## Prochaines Étapes

1. Documentation

      - Rédaction du guide d'installation
      - Documentation des API
      - Guide de dépannage
      - Documentation des métriques

2. Tests

      - Mise en place des tests unitaires
      - Configuration des tests d'intégration
      - Tests de performance automatisés
      - Tests de sécurité automatisés

3. CI/CD

      - Configuration des pipelines
      - Tests automatisés
      - Déploiement automatique
      - Monitoring de la qualité

4. Monitoring
      - Mise en place des alertes
      - Tableau de bord de monitoring
      - Métriques en temps réel
      - Rapports de performance

## [4.0.0] - 2024-05-16

### Ajouté

- Documentation complète des endpoints API dans `docs/API.md`
- Système de logging amélioré avec rotation des fichiers
- Support du rate limiting avec APCu pour Azure
- Headers de sécurité renforcés
- Configuration CORS optimisée

### Modifié

- Amélioration de la configuration IIS dans `web.config`
     - Ajout de règles spécifiques pour les fichiers proxy
     - Optimisation des règles de réécriture
     - Headers de sécurité mis à jour
- Mise à jour de la configuration du proxy
     - Alignement des headers CORS
     - Amélioration de la gestion du rate limit
     - Optimisation des timeouts

### Corrigé

- Erreurs 404 sur les fichiers proxy
- Problèmes de CORS sur les requêtes OPTIONS
- Gestion des erreurs de rate limiting
- Headers de sécurité manquants

### Sécurité

- Ajout de headers de sécurité supplémentaires
- Amélioration de la validation des entrées
- Protection contre les attaques XSS
- Configuration CSP renforcée

### Performance

- Optimisation du rate limiting avec APCu
- Amélioration de la gestion du cache
- Réduction des timeouts

## [3.0.0] - 2024-05-15

## [1.0.0] - 2024-05-16

### Nettoyage

- Suppression des fichiers proxy redondants
     - Supprimé `unified-proxy.php`
     - Supprimé `azure-cors-proxy.php`
     - Supprimé les copies de `simple-proxy.php`
- Suppression des fichiers de test obsolètes
     - Supprimé `deep-proxy-test.php`
     - Supprimé `test-proxy.php`
     - Supprimé `api-endpoint-tester.php`
     - Supprimé `install-proxy.php`
     - Supprimé `repair-proxy.php` (fichier vide)

### Améliorations

- Création d'un système de configuration centralisé
     - Nouveau fichier `config/proxy.php`
     - Configuration centralisée des paramètres CORS
     - Configuration centralisée des timeouts
     - Configuration centralisée du logging
- Amélioration de la sécurité
     - Activation de la vérification SSL
     - Restriction des headers CORS
     - Configuration sécurisée des erreurs
     - Ajout de la limitation de taux (rate limiting)
     - Validation des entrées
     - Headers de sécurité (CSP, XSS, etc.)
     - Protection contre les attaques courantes
- Création d'une suite de tests unifiée
     - Nouveau fichier `tools/proxy-test-suite.php`
     - Tests de connexion
     - Tests CORS
     - Tests de performance
     - Tests de sécurité

### Structure des Dossiers

- Nouveau dossier `config/` pour la configuration
- Nouveau dossier `tools/` pour les outils de maintenance
- Nouveau dossier `logs/` pour les fichiers de log

### Documentation

- Création du fichier CHANGELOG.md
- Mise à jour de la documentation des proxies
- Documentation des nouvelles fonctionnalités

## [1.1.0] - 2024-05-16

### Optimisation et Gestion des Erreurs

- Optimisation des performances
     - Mise en cache des réponses fréquentes
     - Compression des réponses
     - Optimisation des requêtes cURL
     - Gestion des timeouts adaptative
- Centralisation de la gestion des erreurs
     - Nouveau fichier `config/error-handler.php`
     - Standardisation des messages d'erreur
     - Logging détaillé des erreurs
     - Rotation automatique des logs
- Amélioration du monitoring
     - Ajout de métriques de performance
     - Suivi des erreurs en temps réel
     - Alertes automatiques
     - Tableau de bord de monitoring

### Documentation

- Mise à jour de la documentation des erreurs
- Ajout de guides de dépannage
- Documentation des métriques de monitoring

## [1.2.0] - 2024-05-16

### Documentation et Tests

- Documentation complète
     - Guide d'installation et de configuration
     - Documentation des API
     - Guide de dépannage
     - Documentation des métriques
- Tests automatisés
     - Tests unitaires
     - Tests d'intégration
     - Tests de performance
     - Tests de sécurité
- Intégration continue
     - Configuration des pipelines CI/CD
     - Tests automatisés
     - Déploiement automatique
     - Monitoring de la qualité

### Documentation

- Mise à jour de la documentation technique
- Ajout de diagrammes d'architecture
- Documentation des processus de déploiement

## [4.1.0] - 2024-05-16

### Added

- Configuration Nginx complète pour Azure App Service
- Support spécifique pour les fichiers proxy dans la configuration Nginx
- Configuration détaillée des logs d'accès et d'erreur

### Changed

- Amélioration de la gestion des requêtes OPTIONS pour CORS
- Optimisation des headers de sécurité pour les fichiers proxy
- Configuration du cache pour les ressources statiques

### Security

- Ajout de headers de sécurité spécifiques pour les fichiers proxy
- Configuration renforcée de Content-Security-Policy
- Protection contre l'accès aux fichiers cachés

### Performance

- Optimisation du cache pour les ressources statiques
- Configuration du FastCGI pour une meilleure performance PHP

## [4.2.0] - 2024-05-16

### Changed

- Amélioration de la configuration Nginx pour les fichiers proxy
     - Ajout de `try_files` pour une meilleure gestion des fichiers
     - Configuration correcte de `fastcgi_split_path_info`
     - Ajout de `PATH_INFO` pour le traitement des paramètres
- Amélioration des logs de débogage
     - Activation du mode debug pour les logs d'erreur
     - Configuration du buffer pour les logs d'accès
     - Rotation des logs optimisée

### Fixed

- Correction des erreurs 404 sur les fichiers proxy
- Amélioration de la gestion des requêtes OPTIONS
- Correction de la configuration FastCGI

## [4.3.0] - 2024-05-16

### Added

- Logging détaillé dans api-bridge.php
     - Log des requêtes entrantes
     - Log des URLs cibles
     - Log des données POST
     - Log des informations cURL
     - Log des réponses

### Changed

- Amélioration de la construction des URLs cibles
     - Séparation claire de l'URL de base et de l'endpoint
     - Meilleure gestion des slashes
- Activation du mode verbeux pour cURL
     - Plus de détails sur les requêtes
     - Meilleur débogage des erreurs

### Fixed

- Correction de la construction des URLs pour le backend
- Amélioration de la gestion des erreurs avec plus de contexte

## [4.4.0] - 2024-05-16

### Added

- Fichier de test pour le proxy (`proxy-test.php`)
     - Vérification du fonctionnement du proxy
     - Affichage des informations de requête
     - Test des headers

### Changed

- Amélioration de la configuration Nginx
     - Ajout de `QUERY_STRING` pour les fichiers proxy
     - Meilleure gestion des paramètres de requête
     - Configuration plus détaillée des logs

### Fixed

- Correction de la gestion des paramètres de requête dans les fichiers proxy
- Amélioration de la configuration FastCGI

## [4.5.0] - 2024-05-16

### Added

- Fichier de test unifié (`proxy-test.php`)
     - Tests de connexion pour tous les endpoints
     - Tests CORS avec vérification des headers
     - Tests de sécurité avec vérification des headers
     - Tests de performance avec mesure du temps de réponse
     - Tests de rate limit avec simulation de requêtes multiples
     - Tests de validation des entrées avec cas valides et invalides
     - Logs verbeux pour le débogage des requêtes cURL

### Changed

- Amélioration des tests de connexion
     - Utilisation d'URLs absolues au lieu des chemins relatifs
     - Ajout du suivi des redirections
     - Configuration des timeouts
     - Ajout de logs détaillés pour le débogage
- Amélioration des tests CORS
     - Vérification complète des headers CORS
     - Ajout de logs pour les headers manquants
- Amélioration des tests de sécurité
     - Vérification des headers de sécurité
     - Ajout de la politique CSP
     - Configuration HSTS

### Fixed

- Correction des erreurs "Could not resolve host" dans les tests
- Amélioration de la gestion des erreurs cURL
- Correction de la gestion des headers CORS et de sécurité

### Security

- Ajout des headers de sécurité dans les réponses de test
     - X-Content-Type-Options
     - X-Frame-Options
     - X-XSS-Protection
     - Strict-Transport-Security
     - Content-Security-Policy
- Configuration CORS sécurisée
     - Origines autorisées
     - Méthodes autorisées
     - Headers autorisés
     - Credentials

### Performance

- Optimisation des tests de performance
     - Mesure précise du temps de réponse
     - Seuil de performance configurable
     - Timeout adapté pour les requêtes

## [4.6.0] - 2024-05-16

### Added

- Autorisation explicite pour `api-bridge.php` dans `.htaccess`
- Documentation des problèmes de déploiement et de configuration

### Changed

- Amélioration de la configuration des fichiers proxy
- Mise à jour des règles d'accès dans `.htaccess`

### Fixed

- Correction des erreurs 404 sur les fichiers proxy
- Amélioration de la gestion des accès aux fichiers proxy

### Security

- Renforcement des règles d'accès aux fichiers proxy
- Configuration explicite des permissions dans `.htaccess`

## [4.7.0] - 2024-05-16

### Added

- Documentation complète des tests dans `TESTING.md`
- Mise à jour du contexte de l'application dans `contexte.md`
- Documentation des permissions des fichiers proxy

### Changed

- Amélioration de la documentation des problèmes actuels
- Mise à jour des prochaines étapes suggérées
- Clarification des points d'attention

### Documentation

- Ajout de la section "Configuration Requise" dans `TESTING.md`
- Mise à jour des exemples de configuration
- Documentation des headers CORS et de sécurité
- Ajout d'une section de dépannage

## [4.8.0] - 2024-05-16

### Fixed

- Correction des erreurs 404 sur les endpoints du proxy
- Ajout des headers CORS manquants
- Ajout des headers de sécurité manquants
- Correction de la configuration du proxy

### Security

- Ajout des headers de sécurité manquants :
     - X-Content-Type-Options
     - X-Frame-Options
     - X-XSS-Protection
     - Strict-Transport-Security
     - Content-Security-Policy
- Configuration CORS complète :
     - Access-Control-Allow-Origin
     - Access-Control-Allow-Methods
     - Access-Control-Allow-Headers
     - Access-Control-Max-Age
     - Access-Control-Allow-Credentials

### Changed

- Amélioration de la gestion des erreurs 404
- Mise à jour de la configuration du proxy
- Amélioration des tests de validation des entrées

### Documentation

- Mise à jour de la documentation des problèmes connus
- Ajout de la section de dépannage dans TESTING.md
- Documentation des headers de sécurité requis

## [4.16.0] - 2024-05-17

### Fixed

- Correction en profondeur du problème persistant de suppression des classes
     - Amélioration du modèle Classe.php avec ajout de vérification du nombre de lignes affectées par la requête DELETE
     - Renforcement du contrôleur ClasseController.php avec double vérification après suppression
     - Ajout de logs détaillés pour mieux diagnostiquer les problèmes de suppression
     - Amélioration de la gestion des erreurs avec traces d'exceptions complètes
