# Guide de débogage des communications front-end/back-end

## Introduction

Avec la désactivation des données simulées dans le proxy unifié (version 3.0.2), vous pouvez désormais voir les erreurs réelles retournées par le back-end. Ce guide vous aidera à interpréter ces erreurs et à déboguer efficacement les problèmes de communication entre le front-end et le back-end.

## Nouveaux outils de débogage (version 3.0.3)

### 1. Outil d'inspection des requêtes

Une interface de débogage est désormais disponible dans toutes les pages de l'application. Elle permet de:

- Visualiser en temps réel les requêtes HTTP
- Examiner les détails des requêtes et réponses
- Identifier les problèmes de communication avec le backend

Pour l'utiliser:

1. Cherchez le bouton "🔍 Debug" en bas à droite de n'importe quelle page
2. Cliquez sur ce bouton pour ouvrir le panneau de débogage
3. Consultez les statistiques et les détails des requêtes

### 2. Outil de diagnostic d'URL

Un nouvel outil (`url-debug.php`) a été ajouté pour tester les différentes constructions d'URL et identifier la plus efficace pour communiquer avec le backend.

Pour l'utiliser:

1. Accédez à la page de test du proxy unifié (`test-unified-proxy.php`)
2. Exécutez le test de diagnostic d'URL (section 5)
3. Analysez les résultats pour comprendre quelle construction d'URL fonctionne le mieux

### 3. Logs améliorés

Le proxy unifié dispose maintenant d'un système de journalisation plus détaillé qui capture:

- La construction exacte des URLs
- Les détails complets des erreurs HTTP
- Les informations cURL détaillées

## Journal du proxy

Le proxy unifié enregistre toutes les requêtes et réponses dans des fichiers de log situés dans le dossier `/front/logs/`. Ces logs contiennent des informations précieuses pour le débogage :

- Méthode HTTP utilisée (GET, POST, PUT, DELETE)
- URL complète de la requête
- Paramètres transmis
- Code de statut HTTP de la réponse
- Contenu de la réponse

### Accéder aux logs

```bash
# Afficher le dernier fichier de log
cat /front/logs/proxy-YYYY-MM-DD.log | tail -n 100

# Rechercher des erreurs spécifiques
grep "Erreur" /front/logs/proxy-YYYY-MM-DD.log

# Consulter les logs de diagnostic d'URL
cat /front/logs/url-debug-YYYY-MM-DD.log
```

## Codes d'erreur HTTP courants

### 404 Not Found

Cette erreur indique que l'endpoint demandé n'existe pas sur le back-end. Vérifiez :

- L'URL de l'endpoint dans la requête
- Les routes définies dans `/back/routes/api.php`
- Les règles de routage du proxy unifié dans `/front/unified-proxy.php`

**Mise à jour**: La construction d'URL a été simplifiée dans le proxy unifié. Tous les endpoints (sauf auth/login, auth/user et status) utilisent maintenant le préfixe `/api/` directement.

### 401 Unauthorized / 403 Forbidden

Ces erreurs indiquent un problème d'authentification ou d'autorisation. Vérifiez :

- Si l'utilisateur est correctement connecté
- Si le token JWT est transmis correctement
- Si l'utilisateur a les droits nécessaires pour accéder à la ressource

### 500 Internal Server Error

Cette erreur indique un problème côté serveur. Vérifiez :

- Les logs d'erreur PHP sur le serveur back-end
- Les exceptions capturées dans les contrôleurs
- Les erreurs de base de données

### CORS Errors

Les erreurs CORS sont généralement visibles uniquement dans la console du navigateur. Si vous observez des erreurs de type "Cross-Origin Request Blocked", vérifiez :

- Les en-têtes CORS dans la réponse du back-end
- La configuration du proxy unifié
- Les en-têtes `Access-Control-Allow-*` dans les requêtes OPTIONS

## Étapes de débogage

### 1. Utiliser l'outil de débogage intégré

Le nouvel outil de débogage (bouton "🔍 Debug") est la façon la plus simple et rapide de diagnostiquer les problèmes.

### 2. Vérification rapide

Commencez par une vérification rapide des connexions :

```javascript
// Dans la console du navigateur
fetch("unified-proxy.php?endpoint=status")
	.then((response) => response.json())
	.then((data) => console.log("Status:", data))
	.catch((error) => console.error("Error:", error));
```

### 3. Analyser les logs du proxy

Consultez les logs du proxy pour voir exactement ce qui a été envoyé au back-end et ce qui a été reçu en retour.

### 4. Utiliser l'outil de diagnostic d'URL

Lancez l'outil de diagnostic d'URL pour tester différentes constructions d'URL et identifier celle qui fonctionne pour chaque endpoint.

### 5. Tester l'endpoint directement

Utilisez un outil comme Postman ou cURL pour tester l'endpoint directement, sans passer par le proxy :

```bash
curl -v https://app-backend-esgi-app.azurewebsites.net/api/matieres
```

### 6. Vérifier la configuration du proxy

Si vous suspectez un problème dans le proxy, vérifiez les configurations dans :

- `/front/unified-proxy.php`
- `/front/js/config.js`
- `/front/js/api-service.js`

## Interprétation des réponses

### Format de réponse JSON standard

```json
{
  "success": true|false,
  "message": "Message descriptif",
  "data": [...]
}
```

### Format de réponse encapsulée (pour les réponses non-JSON)

```json
{
	"success": false,
	"message": "Réponse non-JSON reçue du serveur",
	"status": 404,
	"raw_response": "<html>...</html>",
	"url": "https://app-backend-esgi-app.azurewebsites.net/api/matieres"
}
```

## Problèmes courants et solutions

### 1. Problème : L'API renvoie 404 pour tous les endpoints

**Causes possibles** :

- Routes mal configurées
- Mauvaise URL de base pour l'API
- Redirection non configurée sur Azure
- Construction d'URL incorrecte dans le proxy

**Solutions** :

- Vérifier les routes dans `/back/routes/api.php`
- Vérifier l'URL de base dans `/front/js/config.js`
- Vérifier la construction d'URL dans `unified-proxy.php`
- Utiliser l'outil de diagnostic d'URL pour tester différentes constructions

### 2. Problème : Certains endpoints fonctionnent, d'autres non

**Causes possibles** :

- Routes spécifiques mal configurées
- Problèmes d'authentification pour certaines routes
- Bugs dans les contrôleurs spécifiques

**Solutions** :

- Tester chaque endpoint individuellement
- Vérifier les logs pour les endpoints problématiques
- Examiner les contrôleurs correspondants

### 3. Problème : Les requêtes GET fonctionnent, mais pas POST/PUT/DELETE

**Causes possibles** :

- Problèmes CORS
- Méthodes HTTP non autorisées sur le serveur
- Validation des données incorrecte

**Solutions** :

- Vérifier les en-têtes CORS pour les requêtes OPTIONS
- Vérifier que le serveur autorise toutes les méthodes HTTP
- Valider le format des données envoyées

## Pour aller plus loin

### Activer temporairement les données simulées

Si vous avez besoin de rétablir temporairement les données simulées pour tester l'interface utilisateur, vous pouvez modifier la constante `ENABLE_MOCK_DATA` dans `/front/unified-proxy.php` :

```php
define('ENABLE_MOCK_DATA', true); // Activer temporairement les données simulées
```

### Développer des tests d'intégration

Pour une approche plus systématique du débogage, envisagez de développer des tests d'intégration automatisés qui vérifient régulièrement la disponibilité et le bon fonctionnement des endpoints API.

## Nouveaux developments (version 3.0.4)

### Nouveaux endpoints API directs

Pour résoudre les problèmes de 404 sur les endpoints API, nous avons créé des scripts PHP dédiés pour chaque type de ressource:

- `/back/api-matieres.php` - Endpoint pour les matières
- `/back/api-classes.php` - Endpoint pour les classes
- `/back/api-examens.php` - Endpoint pour les examens
- `/back/api-profs.php` - Endpoint pour les professeurs
- `/back/api-notes.php` - Endpoint pour les notes (existant)

Le proxy unifié a été mis à jour pour rediriger les requêtes vers ces endpoints spécifiques plutôt que d'utiliser le préfixe `/api/` standard.

### Pourquoi cette approche?

L'approche de routage standard avec `/api/resource` était problématique, car:

1. Le serveur Azure avait des problèmes avec les règles de réécriture pour le routage API
2. Les fichiers `.htaccess` et `web.config` ne routaient pas correctement les requêtes
3. Le routage via `routes/api.php` ne fonctionnait pas comme prévu

Les endpoints directs offrent une solution plus robuste, car ils sont accessibles directement sans dépendre du routage, et chaque endpoint gère uniquement une ressource spécifique.

### Comment tester

Pour tester si les nouveaux endpoints fonctionnent correctement:

```
curl https://app-backend-esgi-app.azurewebsites.net/api-matieres.php
curl https://app-backend-esgi-app.azurewebsites.net/api-classes.php
```

Le proxy unifié fait maintenant ces redirections automatiquement lorsque vous appelez:

```
fetch("unified-proxy.php?endpoint=matieres")
fetch("unified-proxy.php?endpoint=classes")
```

## Identification et Authentification

### Identifiants de Test Dynamiques

À partir de la version 3.0.5, l'application dispose d'un système de chargement dynamique des identifiants de test depuis la base de données. Cela permet de:

1. **Faciliter les tests** - Les identifiants valides sont automatiquement chargés
2. **Améliorer la sécurité** - Les identifiants ne sont plus codés en dur dans les fichiers source
3. **Utiliser des données réelles** - Les tests sont effectués avec des comptes réellement présents en base de données

Pour utiliser cette fonctionnalité:

- Sur la page de connexion, les identifiants sont automatiquement chargés au démarrage
- Si le chargement échoue, utilisez le bouton "Charger des identifiants de test"
- Ces identifiants sont extraits de la table PROF de la base de données

Si le chargement des identifiants échoue:

- Vérifiez que la base de données contient au moins un enregistrement dans la table PROF
- Vérifiez que le proxy unifié est correctement configuré
- Vérifiez les logs du serveur pour des erreurs éventuelles

### Problèmes courants d'authentification

## Conclusion

Le débogage efficace repose sur la compréhension des erreurs réelles plutôt que sur des suppositions. La désactivation des données simulées et l'ajout de nouveaux outils de débogage permettent une vision claire des problèmes et accélèrent leur résolution.

Pour toute assistance supplémentaire, n'hésitez pas à consulter les autres guides dans le dossier `/front/docs/` ou à contacter l'équipe de développement.
