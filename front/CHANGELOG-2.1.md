# CHANGELOG v2.1 - Solutions optimisées pour Azure

## v2.1.1 (2025-05-17)

### Ajouts majeurs

- **Mock API local** (`local-api-mock.php`) pour simuler les réponses du backend avec les tokens d'authentification locale
- **Données mocké pour le développement** incluant notes, matières et profil utilisateur
- **Mise à jour de la page de test** pour détecter automatiquement les tokens locaux et utiliser le Mock API
- **Documentation enrichie** expliquant comment utiliser le Mock API pour le développement

### Changements techniques

- Détection automatique des tokens locaux (`LOCAL_AUTH.*`) et redirection vers le Mock API
- Simulation de données de production pour faciliter le développement frontend
- Journalisation détaillée des requêtes vers le Mock API

### Corrections de bugs

- Résolution du problème des tokens locaux non reconnus par le backend
- Accès aux ressources protégées en mode développement

## v2.1.0 (2025-05-16)

### Ajouts majeurs

- **Proxy général optimisé** (`optimal-proxy.php`) pour router intelligemment les requêtes vers les bons endpoints backend
- **Proxy d'authentification amélioré** (`auth-proxy.php`) avec support multi-endpoints et authentification locale
- **Page de test spécifique pour l'authentification** (`test-auth-solution.php`) avec interface utilisateur complète
- **Documentation détaillée** sur les solutions mises en place (`docs/AUTH-SOLUTION.md` et `docs/OPTIMIZED-SOLUTIONS.md`)

### Changements techniques

#### Solution multi-endpoints

Le proxy d'authentification tente maintenant plusieurs chemins possibles pour trouver l'endpoint d'authentification fonctionnel :

- `api-auth-login.php`
- `api-auth.php`
- `auth.php`
- `api/auth/login`
- `auth/login`
- `login.php`

#### Authentification locale

Implémentation d'une solution d'authentification locale pour le développement avec:

- Utilisateurs de test prédéfinis (admin, user, guest)
- Génération de tokens JWT simulés
- Fonctionnalité de repli automatique si le backend est inaccessible

#### Optimisations du proxy général

- Routage intelligent basé sur l'analyse du backend
- Gestion optimisée des erreurs 404
- Journalisation détaillée pour faciliter le débogage
- Support pour les redirections et la décompression

### Corrections de bugs

- Résolution du problème d'erreur 404 sur l'endpoint d'authentification
- Meilleure gestion des réponses non-JSON du serveur
- Correction de la transmission des paramètres de requête

### Documentation

- Guide détaillé sur l'utilisation de l'authentification locale vs backend
- Documentation des utilisateurs de test disponibles
- Explications techniques sur le fonctionnement des proxys
- Guide d'intégration pour les développeurs frontend

## Utilisation recommandée

Cette mise à jour permet de développer le frontend indépendamment du backend en utilisant l'authentification locale (`auth-proxy.php?local=true`) et le Mock API pour les ressources protégées, tout en conservant la possibilité de se connecter au backend réel quand il est disponible.
