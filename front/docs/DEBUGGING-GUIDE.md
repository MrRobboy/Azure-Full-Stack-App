# Guide de débogage des communications front-end/back-end

## Introduction

Avec la désactivation des données simulées dans le proxy unifié (version 3.0.2), vous pouvez désormais voir les erreurs réelles retournées par le back-end. Ce guide vous aidera à interpréter ces erreurs et à déboguer efficacement les problèmes de communication entre le front-end et le back-end.

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
```

## Codes d'erreur HTTP courants

### 404 Not Found

Cette erreur indique que l'endpoint demandé n'existe pas sur le back-end. Vérifiez :

- L'URL de l'endpoint dans la requête
- Les routes définies dans `/back/routes/api.php`
- Les règles de routage du proxy unifié dans `/front/unified-proxy.php`

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

### 1. Vérification rapide

Commencez par une vérification rapide des connexions :

```javascript
// Dans la console du navigateur
fetch("unified-proxy.php?endpoint=status")
	.then((response) => response.json())
	.then((data) => console.log("Status:", data))
	.catch((error) => console.error("Error:", error));
```

### 2. Analyser les logs du proxy

Consultez les logs du proxy pour voir exactement ce qui a été envoyé au back-end et ce qui a été reçu en retour.

### 3. Tester l'endpoint directement

Utilisez un outil comme Postman ou cURL pour tester l'endpoint directement, sans passer par le proxy :

```bash
curl -v https://app-backend-esgi-app.azurewebsites.net/api/matieres
```

### 4. Vérifier la configuration du proxy

Si vous suspectez un problème dans le proxy, vérifiez les configurations dans :

- `/front/unified-proxy.php`
- `/front/js/config.js`
- `/front/js/api-service.js`

### 5. Tester avec un autre proxy

Pour isoler la source du problème, essayez différentes méthodes d'accès au back-end :

- Utilisez `/front/test-unified-proxy.php` pour tester le proxy unifié
- Créez une requête directe sans proxy pour comparer les résultats

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

**Solutions** :

- Vérifier les routes dans `/back/routes/api.php`
- Vérifier l'URL de base dans `/front/js/config.js`
- Vérifier la configuration du serveur web sur Azure

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

### Ajout de logs personnalisés

Pour ajouter des logs personnalisés dans vos pages :

```javascript
console.log("Debug:", data);
fetch("unified-proxy.php?endpoint=log", {
	method: "POST",
	headers: { "Content-Type": "application/json" },
	body: JSON.stringify({
		type: "debug",
		message: "Test message",
		data: data
	})
});
```

## Conclusion

Le débogage efficace repose sur la compréhension des erreurs réelles plutôt que sur des suppositions. La désactivation des données simulées permet une vision claire des problèmes et accélère leur résolution.

Pour toute assistance supplémentaire, n'hésitez pas à consulter les autres guides dans le dossier `/front/docs/` ou à contacter l'équipe de développement.
