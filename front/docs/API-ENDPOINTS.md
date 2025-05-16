# Guide des endpoints d'API

Ce document décrit les différents endpoints d'API disponibles dans l'application et comment les utiliser correctement avec les JWT générés.

## Architecture de l'API

Le backend Azure utilise une architecture REST avec des chemins d'API structurés. Cependant, notre frontend a besoin d'adaptateurs pour traduire entre les formats attendus et les formats réels du backend.

## Endpoints Backend vs Frontend

| Ressource    | Endpoint Frontend         | Endpoint Backend Réel | Adaptateur              |
| ------------ | ------------------------- | --------------------- | ----------------------- |
| Notes        | `api-notes.php`           | `api/notes`           | Proxy direct            |
| Utilisateurs | `users-api-adapter.php`   | `api/users`           | `users-api-adapter.php` |
| Auth         | `improved-jwt-bridge.php` | `api-auth-login.php`  | JWT Bridge              |

## Utilisation avec le JWT

Pour accéder aux ressources protégées, vous devez inclure le token JWT dans l'en-tête `Authorization` au format `Bearer <token>`.

### Exemple JavaScript

```javascript
// Obtenir le token JWT
const token = localStorage.getItem("jwt_token");

// Accéder aux utilisateurs avec l'adaptateur
fetch("users-api-adapter.php", {
	headers: {
		Authorization: "Bearer " + token
	}
})
	.then((response) => response.json())
	.then((data) => console.log(data));

// Accéder aux notes via le proxy
fetch("optimal-proxy.php?endpoint=api-notes.php", {
	headers: {
		Authorization: "Bearer " + token
	}
})
	.then((response) => response.json())
	.then((data) => console.log(data));
```

## Structure des adaptateurs d'API

Les adaptateurs d'API comme `users-api-adapter.php` servent de traducteurs entre notre frontend et le backend. Ils:

1. Reçoivent la requête du frontend
2. Transforment la requête au format attendu par le backend
3. Transmettent la requête au backend via le proxy optimal
4. Reçoivent la réponse du backend
5. Transforment la réponse au format attendu par le frontend
6. Retournent la réponse au frontend

## Paramètres des adaptateurs

### Adaptateur d'API Utilisateurs

L'adaptateur d'API Utilisateurs (`users-api-adapter.php`) accepte les paramètres suivants:

- `?id=X` - Récupérer un utilisateur spécifique par son ID
- `?classe=Y` - Récupérer tous les utilisateurs d'une classe spécifique

Exemple: `users-api-adapter.php?id=5` pour obtenir l'utilisateur avec l'ID 5.

## Dépannage

### Erreur 401 Unauthorized

Si vous recevez une erreur 401, vérifiez que:

- Votre token JWT est valide et non expiré
- L'en-tête Authorization est correctement formaté (`Bearer <token>`)

### Erreur 404 Not Found

Si vous recevez une erreur 404, vérifiez que:

- Vous utilisez le bon endpoint
- Pour les ressources qui nécessitent un adaptateur, utilisez l'adaptateur approprié
- L'ID de ressource existe dans le backend

### Erreur dans la réponse JSON

Si la réponse contient une erreur, consultez les logs dans:

- `logs/improved-jwt-bridge.log` pour les problèmes d'authentification
- `logs/users-api.log` pour les problèmes avec l'API utilisateurs
- `logs/optimal-proxy.log` pour les problèmes généraux de proxy
