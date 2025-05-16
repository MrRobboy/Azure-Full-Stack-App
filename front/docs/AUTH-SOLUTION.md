# Solution d'authentification pour le frontend Azure

Ce document explique la solution mise en place pour résoudre les problèmes d'authentification dans l'application frontend Azure.

## Problème identifié

Lors des tests, nous avons constaté que:

1. Le statut du backend est accessible (200 OK)
2. Les notes requièrent une authentification (401 Unauthorized)
3. L'endpoint d'authentification retourne une erreur 404 Not Found

Bien que le Backend Explorer ait identifié l'existence de l'endpoint `/api-auth-login.php` (avec un code 405 Method Not Allowed), les tentatives de connexion à cet endpoint échouent avec des erreurs 404.

## Solution mise en œuvre

Nous avons développé un proxy d'authentification amélioré (`auth-proxy.php`) qui:

1. Tente de s'authentifier sur plusieurs endpoints potentiels du backend
2. Fournit un système d'authentification locale comme solution de repli
3. Gère les erreurs de manière élégante avec des messages clairs

### Stratégie multi-endpoints

Le proxy essaie plusieurs chemins d'authentification potentiels:

```php
$AUTH_ENDPOINTS = [
    'api-auth-login.php',   // Endpoint principal testé
    'api-auth.php',         // Alternative possible
    'auth.php',             // Alternative simplifiée
    'api/auth/login',       // Style REST API
    'auth/login',           // Style REST API simplifié
    'login.php',            // Très simple
];
```

Si l'un de ces endpoints répond avec un code autre que 404, le proxy utilise cette réponse.

### Authentification locale

Si tous les endpoints du backend échouent, le proxy bascule automatiquement vers une authentification locale qui:

1. Fonctionne entièrement sur le frontend
2. Accepte des utilisateurs prédéfinis pour le développement
3. Génère des tokens JWT simulés
4. Permet de continuer le développement sans dépendre du backend

#### Utilisateurs locaux disponibles:

| Email             | Mot de passe | Rôle  |
| ----------------- | ------------ | ----- |
| admin@example.com | admin123     | admin |
| user@example.com  | user123      | user  |
| test@example.com  | test123      | guest |

## Mode d'emploi

### 1. Authentification backend (mode par défaut)

```javascript
// Tente de s'authentifier sur le backend
fetch("auth-proxy.php", {
	method: "POST",
	headers: { "Content-Type": "application/json" },
	body: JSON.stringify({
		email: "admin@example.com",
		password: "admin123"
	})
});
```

### 2. Authentification locale (mode développement)

```javascript
// Force l'utilisation de l'authentification locale
fetch("auth-proxy.php?local=true", {
	method: "POST",
	headers: { "Content-Type": "application/json" },
	body: JSON.stringify({
		email: "admin@example.com",
		password: "admin123"
	})
});
```

### 3. Utilisation du token

Une fois l'authentification réussie (locale ou backend), utilisez le token obtenu pour accéder aux ressources protégées:

```javascript
// Récupérer le token du localStorage
const token = localStorage.getItem("auth_token");

// Utiliser le token pour accéder aux ressources protégées
fetch("optimal-proxy.php?endpoint=api-notes.php", {
	headers: {
		Authorization: "Bearer " + token
	}
});
```

## Fonctionnement technique

### Génération du token local

```php
// Générer un token simple basé sur le temps
$now = time();
$expiresAt = $now + 3600; // 1 heure

$payload = [
    'sub' => $email,
    'name' => $localUsers[$email]['name'],
    'role' => $localUsers[$email]['role'],
    'iat' => $now,
    'exp' => $expiresAt
];

// Encoder en base64 pour simuler un JWT
$encodedPayload = base64_encode(json_encode($payload));
$token = 'LOCAL_AUTH.' . $encodedPayload . '.SIGNATURE';
```

### Structure de la réponse

```json
{
	"success": true,
	"message": "Authentification locale réussie",
	"data": {
		"token": "LOCAL_AUTH.eyJzdWIiOiJhZG1pbkBleGFtcGxlLmNvbSIsIm5hbWUiOiJBZG1pbiIsInJvbGUiOiJhZG1pbiIsImlhdCI6MTYyNjQzODc2OSwiZXhwIjoxNjI2NDQyMzY5fQ==.SIGNATURE",
		"user": {
			"email": "admin@example.com",
			"name": "Admin",
			"role": "admin"
		},
		"expiresAt": 1626442369
	}
}
```

## Avantages de cette approche

1. **Robustesse**: Essai de multiples chemins pour trouver le bon endpoint
2. **Indépendance**: Possibilité de développer sans accès au backend
3. **Clarté**: Messages d'erreur explicites et logs détaillés
4. **Facilité**: Utilisateurs de test prédéfinis pour les différents rôles
5. **Transition fluide**: Le code frontend reste identique qu'on utilise l'authentification locale ou backend

## Limitations

1. Les tokens locaux ne sont pas cryptés de façon sécurisée (uniquement pour le développement)
2. L'authentification locale ne vérifie pas les permissions réelles du backend
3. Le système ne gère pas la synchronisation des données utilisateur entre le backend et l'auth locale

## Conclusion

Cette solution hybride permet de poursuivre le développement du frontend même lorsque le backend rencontre des problèmes d'accessibilité ou que les endpoints d'authentification ne sont pas correctement configurés.
