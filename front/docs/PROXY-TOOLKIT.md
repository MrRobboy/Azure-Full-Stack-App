# Boîte à outils Proxy pour Azure

Ce document présente les différents outils développés pour résoudre les problèmes de communication entre le frontend et le backend sur Azure, en particulier les problèmes CORS et d'authentification.

## Problèmes identifiés

1. Les requêtes cross-origin entre le frontend et le backend échouent avec des erreurs CORS
2. Les chemins d'API ne sont pas clairement définis ou accessibles
3. L'authentification pose des problèmes particuliers

## Solutions disponibles

### 1. Proxy générique optimisé

- **Fichier**: `new-proxy.php`
- **Description**: Proxy complet optimisé pour Azure avec gestion des erreurs et journalisation
- **Utilisation**: `fetch('new-proxy.php?endpoint=status.php')`
- **Fonctionnalités**:
     - Gestion CORS complète
     - Décompression des réponses
     - Transmission des cookies
     - Masquage des informations sensibles

### 2. Solution d'authentification locale

- **Fichier**: `auth-api-fix.php`
- **Description**: Service d'authentification local pour développement indépendant du backend
- **Utilisation**: `fetch('auth-api-fix.php', {method: 'POST', ...})`
- **Fonctionnalités**:
     - Utilisateurs de test prédéfinis
     - Génération de tokens JWT
     - Gestion de session
     - Indépendant du backend

### 3. Proxy POST spécialisé

- **Fichier**: `proxy-post.php`
- **Description**: Proxy spécialisé pour les requêtes POST avec log détaillé
- **Utilisation**: `fetch('proxy-post.php?target=api-auth-login.php', {method: 'POST', ...})`
- **Fonctionnalités**:
     - Journalisation améliorée
     - Focus sur les requêtes POST
     - Affichage des détails des erreurs

### 4. Explorateur de Backend

- **Fichier**: `backend-explorer.php`
- **Description**: Outil pour tester et découvrir les endpoints du backend
- **Utilisation**: Ouvrir `backend-explorer.php` dans le navigateur
- **Fonctionnalités**:
     - Test automatisé de différentes combinaisons d'URL
     - Affichage détaillé des résultats
     - Identification des endpoints valides

### 5. Générateur de Proxy

- **Fichier**: `proxy-generator.php`
- **Description**: Outil pour générer un proxy personnalisé selon les besoins
- **Utilisation**: Ouvrir `proxy-generator.php` dans le navigateur
- **Fonctionnalités**:
     - Interface graphique
     - Configuration personnalisée
     - Génération de code adaptée

## Guides pratiques

### Comment identifier les bons chemins d'API

1. Ouvrez l'**Explorateur de Backend** (`backend-explorer.php`)
2. Cliquez sur "Tester tous les chemins"
3. Notez les URL qui retournent un statut 200 ou 405 (elles existent)
4. Utilisez ces informations pour configurer le **Générateur de Proxy**

### Comment tester l'authentification

#### Option 1: Authentication locale (développement)

```javascript
fetch("auth-api-fix.php", {
	method: "POST",
	headers: { "Content-Type": "application/json" },
	body: JSON.stringify({
		email: "admin@example.com",
		password: "admin123"
	})
})
	.then((response) => response.json())
	.then((data) => console.log(data));
```

#### Option 2: Proxy POST vers le backend

```javascript
fetch("proxy-post.php?target=api-auth-login.php", {
	method: "POST",
	headers: { "Content-Type": "application/json" },
	body: JSON.stringify({
		email: "admin@example.com",
		password: "admin123"
	})
})
	.then((response) => response.json())
	.then((data) => console.log(data));
```

### Comment générer un proxy personnalisé

1. Utilisez l'**Explorateur de Backend** pour identifier les bons chemins
2. Ouvrez le **Générateur de Proxy** (`proxy-generator.php`)
3. Configurez les différents chemins en fonction des résultats
4. Cliquez sur "Générer le Proxy"
5. Utilisez le nouveau proxy comme point d'entrée pour toutes vos API

## Analyse des problèmes CORS

Les problèmes CORS sur Azure peuvent provenir de plusieurs sources:

1. **Configuration IIS/Nginx**: Les serveurs web Azure peuvent supprimer ou modifier certains en-têtes
2. **Routage**: Les requêtes OPTIONS ne sont pas correctement gérées
3. **En-têtes PHP**: Les en-têtes CORS sont appliqués trop tard ou incorrectement
4. **Specificités d'Azure**: Le Reverse Proxy d'Azure App Service a des comportements particuliers

Notre solution adopte une approche "server-side proxy" qui contourne complètement ces problèmes en:

1. Faisant les requêtes côté serveur (PHP) plutôt que côté client
2. Transmettant fidèlement les en-têtes et données
3. Évitant le besoin de configurer CORS sur le backend

## Intégration avec le frontend

Pour intégrer nos solutions dans l'application frontend, modifiez le fichier de configuration pour utiliser le proxy généré:

```javascript
// Dans config.js ou équivalent
const config = {
	api: {
		baseUrl: "https://app-backend-esgi-app.azurewebsites.net",
		proxyUrls: [
			"custom-proxy.php", // Proxy personnalisé généré
			"new-proxy.php", // Proxy générique optimisé
			"simple-proxy.php" // Fallback
		],
		useProxy: true
	}
};
```

## Résolution des problèmes

| Problème                     | Solution                          | Outil à utiliser       |
| ---------------------------- | --------------------------------- | ---------------------- |
| Erreur CORS                  | Utiliser un proxy                 | `new-proxy.php`        |
| Authentification échoue      | Utiliser authentification locale  | `auth-api-fix.php`     |
| Besoin d'explorer le backend | Explorer les endpoints            | `backend-explorer.php` |
| Configuration personnalisée  | Générer un proxy adapté           | `proxy-generator.php`  |
| Problème avec POST           | Utiliser le proxy POST spécialisé | `proxy-post.php`       |

## Conclusion

Cette boîte à outils offre une solution complète pour diagnostiquer et résoudre les problèmes de communication entre le frontend et le backend sur Azure. Utilisez les différents outils selon vos besoins et n'hésitez pas à les adapter pour répondre à des cas spécifiques.
