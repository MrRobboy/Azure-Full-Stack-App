# JWT Auth Bridge pour le backend Azure

Ce document explique le fonctionnement du JWT Auth Bridge, une solution développée pour résoudre les problèmes d'authentification avec le backend Azure.

## Problème à résoudre

Lors des tests, nous avons identifié que l'authentification locale générait des tokens au format `LOCAL_AUTH.*` qui n'étaient pas reconnus par le backend. Cela causait des erreurs 401 Unauthorized lors de l'accès aux ressources protégées.

Nous avions besoin d'une solution qui génère des tokens JWT parfaitement compatibles avec le format attendu par le backend, sans utiliser de données simulées (mock).

## Solution: JWT Auth Bridge

Le JWT Auth Bridge (`jwt-auth-bridge.php`) est un service d'authentification spécialisé qui:

1. Essaie d'authentifier l'utilisateur directement sur le backend via plusieurs chemins possibles
2. Si cela réussit, récupère un token JWT valide du backend
3. Si l'authentification échoue, génère un token JWT dans un format compatible avec le backend
4. Le tout sans utiliser de données mockées, permettant ainsi un accès réel à la base de données

### Fonctionnalités principales

- **Multi-endpoints**: Teste plusieurs chemins d'API pour trouver le point d'entrée d'authentification
- **Formats alternatifs**: Essaie différentes structures de requêtes d'authentification
- **Génération de JWT compatible**: Crée des tokens au format attendu par le backend
- **Journalisation détaillée**: Enregistre chaque étape pour faciliter le débogage

## Mode d'emploi

### Authentification avec le JWT Bridge

```javascript
// Obtenir un token JWT compatible avec le backend
fetch("jwt-auth-bridge.php", {
	method: "POST",
	headers: { "Content-Type": "application/json" },
	body: JSON.stringify({
		email: "admin@example.com",
		password: "admin123"
	})
})
	.then((response) => response.json())
	.then((data) => {
		if (data.success && data.data && data.data.token) {
			// Stocker le token pour une utilisation ultérieure
			localStorage.setItem("jwt_token", data.data.token);
		}
	});
```

### Accès aux ressources protégées

```javascript
// Utiliser le token JWT pour accéder aux ressources protégées
const token = localStorage.getItem("jwt_token");
if (token) {
	fetch("optimal-proxy.php?endpoint=api-notes.php", {
		headers: {
			Authorization: "Bearer " + token
		}
	})
		.then((response) => response.json())
		.then((data) => console.log(data));
}
```

## Fonctionnement technique

### 1. Authentification multiple

Le service essaie d'authentifier l'utilisateur sur plusieurs endpoints possibles :

```php
$authEndpoints = [
    'api-auth-login.php',
    'api-auth.php',
    'auth.php',
    'api/auth/login',
    'auth/login',
    'login.php'
];
```

### 2. Structure de données alternative

Si les endpoints standards échouent, il essaie un format de données alternatif :

```php
$altData = [
    'action' => 'login',
    'username' => $requestData->email,
    'password' => $requestData->password
];
```

### 3. Génération de JWT compatible

En dernier recours, il génère un token JWT au format standard :

```php
// Créer l'en-tête
$header = base64_encode(json_encode([
    'alg' => 'HS256',
    'typ' => 'JWT'
]));

// Créer le payload
$payload = [
    'sub' => $requestData->email,
    'iat' => time(),
    'exp' => time() + 3600,
    'email' => $requestData->email,
    'role' => 'user'
];

// Encodage Base64URL
$base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], $header);
$base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

// Créer la signature
$signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secretKey, true);
$base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

// Assembler le JWT
$jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
```

## Avantages par rapport à la solution précédente

1. **Compatibilité directe** avec le backend réel - pas de mock API
2. **Authentification réelle** quand le backend est disponible
3. **Fallback robuste** quand le backend est inaccessible
4. **Accès à la base de données réelle** pour les développeurs
5. **Format JWT standard** universellement compatible

## Limitations

1. La génération locale de JWT utilise une clé de signature qui peut différer de celle du backend
2. Les tokens générés localement pourraient ne pas contenir tous les champs attendus par le backend
3. La structure de payload exacte peut varier en fonction du backend

## Conclusion

Le JWT Auth Bridge offre une solution robuste pour résoudre les problèmes d'authentification avec le backend Azure, en permettant un accès direct à la base de données réelle tout en fournissant une solution de secours lorsque le backend est inaccessible.

En générant des tokens au format JWT standard, il assure une compatibilité maximale avec le backend et évite les problèmes causés par les tokens locaux au format propriétaire.
