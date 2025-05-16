# Changelog

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

## Prochaines Étapes

1. Implémentation de la rotation des logs
2. Ajout de tests automatisés
3. Amélioration de la documentation
4. Optimisation des performances
