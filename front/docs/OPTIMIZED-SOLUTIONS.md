# Solutions optimisées pour la communication frontend-backend sur Azure

Ce document présente les solutions optimisées mises en place pour résoudre les problèmes de communication entre le frontend et le backend sur Azure, basées sur les résultats de l'exploration du backend.

## Résultats de l'analyse du Backend Explorer

L'exploration du backend a révélé la structure réelle de l'API:

| Endpoint              | Statut                 | Méthode | Description                                               |
| --------------------- | ---------------------- | ------- | --------------------------------------------------------- |
| `/status.php`         | 200 OK                 | GET     | Fonctionne correctement, accessible sans authentification |
| `/api-auth-login.php` | 405 Method Not Allowed | POST    | Existe mais n'accepte que les requêtes POST               |
| `/api-notes.php`      | 401 Unauthorized       | GET     | Existe mais nécessite une authentification                |

Points importants à noter:

1. L'API utilise des fichiers PHP à plat à la racine (pas dans un dossier `/api/`)
2. Les tentatives d'accès avec des chemins comme `/api/auth/login` donnent toutes des erreurs 404
3. Les tentatives via `/index.php/...` ou autres routeurs alternatifs échouent également

## Solutions optimisées mises en place

### 1. Proxy général optimisé (`optimal-proxy.php`)

Ce proxy est configuré pour diriger automatiquement les requêtes vers les bons endpoints, en se basant sur les résultats de l'exploration:

```php
// Configuration des endpoints confirmés
$API_BASE = 'https://app-backend-esgi-app.azurewebsites.net';
$AUTH_PATH = 'api-auth-login.php';  // 405 = existe mais méthode GET non autorisée
$STATUS_PATH = 'status.php';        // 200 = existe et fonctionne
$NOTES_PATH = 'api-notes.php';      // 401 = existe mais requiert authentification
```

**Caractéristiques:**

- Routage intelligent des requêtes basé sur le contenu de l'endpoint demandé
- Gestion complète des en-têtes CORS
- Décompression automatique des réponses
- Journalisation détaillée
- Détection des erreurs 404 et formatage des réponses

**Utilisation:**

```javascript
// Accès au statut
fetch("optimal-proxy.php?endpoint=status.php")
	.then((response) => response.json())
	.then((data) => console.log(data));

// Accès aux notes (authentifié)
fetch("optimal-proxy.php?endpoint=notes", {
	headers: { Authorization: "Bearer " + token }
})
	.then((response) => response.json())
	.then((data) => console.log(data));
```

### 2. Proxy d'authentification spécialisé (`auth-proxy.php`)

Ce proxy est spécialement optimisé pour l'endpoint d'authentification, qui doit être appelé en POST:

**Caractéristiques:**

- Restreint à la méthode POST uniquement
- Vérification préalable de la validité du JSON
- Gestion améliorée des erreurs
- Journalisation détaillée de chaque étape
- Optimisé pour la manipulation des jetons d'authentification

**Utilisation:**

```javascript
fetch("auth-proxy.php", {
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
			localStorage.setItem("auth_token", data.data.token);
		}
	});
```

### 3. Interface de test complète (`test-optimal-solution.php`)

Une interface de test pour valider les solutions optimisées:

**Fonctionnalités:**

- Test individuel de chaque composant
- Test du flux complet (authentification puis accès aux ressources protégées)
- Affichage détaillé des résultats
- Stockage automatique du jeton d'authentification dans le localStorage

## Guide d'intégration

Pour intégrer ces solutions dans votre application frontend:

1. **Configuration de l'API**

Mettez à jour votre configuration pour utiliser les proxys optimisés:

```javascript
// Dans votre config.js ou équivalent
const API_CONFIG = {
	// Proxy général pour toutes les requêtes sauf l'authentification
	baseUrl: "optimal-proxy.php?endpoint=",

	// Endpoint d'authentification spécialisé
	authUrl: "auth-proxy.php",

	// Endpoints spécifiques
	endpoints: {
		status: "status.php",
		notes: "api-notes.php",
		login: "api-auth-login.php"
	}
};
```

2. **Service d'authentification**

```javascript
async function login(email, password) {
	try {
		const response = await fetch(API_CONFIG.authUrl, {
			method: "POST",
			headers: { "Content-Type": "application/json" },
			body: JSON.stringify({ email, password })
		});

		const data = await response.json();

		if (data.success && data.data && data.data.token) {
			localStorage.setItem("auth_token", data.data.token);
			return true;
		} else {
			console.error("Authentication failed:", data);
			return false;
		}
	} catch (error) {
		console.error("Login error:", error);
		return false;
	}
}
```

3. **Service de données**

```javascript
async function fetchData(endpoint, options = {}) {
	// Ajouter le token d'authentification si disponible
	const token = localStorage.getItem("auth_token");
	const headers = options.headers || {};

	if (token) {
		headers["Authorization"] = `Bearer ${token}`;
	}

	try {
		const response = await fetch(
			`${API_CONFIG.baseUrl}${endpoint}`,
			{
				...options,
				headers
			}
		);

		return await response.json();
	} catch (error) {
		console.error(`Error fetching ${endpoint}:`, error);
		throw error;
	}
}
```

## Conclusion

Cette approche optimisée résout les problèmes de communication avec le backend Azure en:

1. **Identifiant correctement les vrais endpoints** grâce à l'exploration du backend
2. **Utilisant des proxys spécialisés** pour les différents types de requêtes
3. **Gérant correctement les en-têtes CORS** et autres paramètres de requête
4. **Fournissant une journalisation détaillée** pour faciliter le débogage

L'architecture proposée est robuste et adaptable, permettant d'ajouter facilement de nouveaux endpoints à mesure que le backend évolue.
