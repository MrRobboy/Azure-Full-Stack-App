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
