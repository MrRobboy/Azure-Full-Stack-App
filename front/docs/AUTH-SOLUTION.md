# Solution d'authentification pour le frontend Azure

Ce document explique la solution mise en place pour résoudre les problèmes d'authentification dans l'application frontend Azure.

## Problème identifié

Lors des tests, nous avons constaté que:

1. Le statut du backend est accessible (200 OK)
2. Les notes requièrent une authentification (401 Unauthorized)
3. L'endpoint d'authentification retourne une erreur 404 Not Found

Bien que le Backend Explorer ait identifié l'existence de l'endpoint `/api-auth-login.php` (avec un code 405 Method Not Allowed), les tentatives de connexion à cet endpoint échouent avec des erreurs 404.

## Solution mise en œuvre

### 1. Authentification améliorée

Nous avons développé un proxy d'authentification amélioré (`auth-proxy.php`) qui:

1. Tente de s'authentifier sur plusieurs endpoints potentiels du backend
2. Fournit un système d'authentification locale comme solution de repli
3. Gère les erreurs de manière élégante avec des messages clairs

### 2. Mock API local pour le développement

Pour résoudre le problème des tokens locaux qui ne sont pas reconnus par le backend, nous avons créé un Mock API local (`local-api-mock.php`) qui:

1. Détecte automatiquement les tokens locaux et les valide
2. Simule des réponses backend avec des données de développement
3. Permet un développement frontend totalement indépendant du backend

#### Stratégie multi-endpoints

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

#### Authentification locale

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

### 3. Utilisation du token avec le Mock API

Une fois authentifié localement, vous pouvez accéder aux ressources protégées via le Mock API qui simulera les réponses du backend:

```javascript
// Récupérer le token du localStorage
const token = localStorage.getItem("auth_token");
const isLocalToken = token.startsWith("LOCAL_AUTH.");

// Déterminer quelle API utiliser (mock pour le développement ou proxy pour la production)
const apiUrl = isLocalToken
	? "local-api-mock.php?endpoint=api-notes.php"
	: "optimal-proxy.php?endpoint=api-notes.php";

// Requête avec authentification
fetch(apiUrl, {
	headers: {
		Authorization: "Bearer " + token
	}
});
```

### 4. Données mockées disponibles

Le Mock API fournit plusieurs jeux de données simulées:

- **Notes et matières**: `local-api-mock.php?endpoint=api-notes.php`
- **Profil utilisateur**: `local-api-mock.php?endpoint=user-profile.php`
- **Données génériques**: `local-api-mock.php?endpoint=any-other-endpoint`

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

### Détection des tokens locaux

```php
// Extraire le token d'autorisation
$token = null;
$headers = getallheaders();
foreach ($headers as $name => $value) {
    if (strtolower($name) === 'authorization') {
        if (preg_match('/^Bearer\s+(.*)$/i', $value, $matches)) {
            $token = $matches[1];
        }
        break;
    }
}

// Vérifier si le token est un token local
$isLocalToken = strpos($token, 'LOCAL_AUTH.') === 0;
```

## Avantages de cette approche

1. **Robustesse**: Essai de multiples chemins pour trouver le bon endpoint
2. **Indépendance**: Développement frontend totalement découplé du backend
3. **Clarté**: Messages d'erreur explicites et logs détaillés
4. **Facilité**: Utilisateurs de test prédéfinis pour les différents rôles
5. **Transition fluide**: Le code frontend détecte automatiquement s'il doit utiliser le Mock API ou le backend réel
6. **Données cohérentes**: Le Mock API renvoie des données structurées qui simulent celles du backend

## Limitations

1. Les tokens locaux ne sont pas cryptés de façon sécurisée (uniquement pour le développement)
2. L'authentification locale ne vérifie pas les permissions réelles du backend
3. Les données mockées peuvent différer de celles du backend réel

## Conclusion

Cette solution hybride permet de poursuivre le développement du frontend même lorsque le backend rencontre des problèmes d'accessibilité ou que les endpoints d'authentification ne sont pas correctement configurés. Avec l'ajout du Mock API, vous pouvez désormais tester l'ensemble du flux utilisateur sans dépendre du backend.
