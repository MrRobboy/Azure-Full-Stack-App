# Vue d'ensemble du système d'intégration frontend-backend

Ce document présente une vue d'ensemble complète du système d'intégration entre le frontend et le backend Azure. Il explique comment les différents composants travaillent ensemble pour fournir une solution robuste d'authentification et d'accès aux données.

## Architecture globale

```
┌─────────────┐       ┌────────────────┐       ┌──────────────┐
│  Frontend   │──────▶│ Enhanced Proxy │──────▶│   Backend    │
│  (Browser)  │◀──────│     Layer      │◀──────│   (Azure)    │
└─────────────┘       └────────────────┘       └──────────────┘
```

Le système est composé de trois couches principales:

1. **Frontend**: Interface utilisateur (navigateur)
2. **Couche Proxy**: Gère la communication et la transformation des requêtes
3. **Backend**: Serveur Azure avec API et base de données

## Composants clés

### 1. JWT Authentication Bridge (`improved-jwt-bridge.php`)

- **Rôle**: Fournir une authentification fiable compatible avec le backend
- **Fonctionnalités**:
     - Tente l'authentification directe avec le backend
     - Génère des JWT compatibles en cas d'échec
     - Utilise le même format et la même clé que le backend (`esgi_azure_secret_key`)
- **Avantages**:
     - Garantit un accès continu même si le backend est temporairement indisponible
     - Génère des tokens avec la même structure que ceux du backend

### 2. Proxy Amélioré (`enhanced-proxy.php`)

- **Rôle**: Servir de point d'entrée unique pour toutes les requêtes API
- **Fonctionnalités**:
     - Supporte les formats d'API traditionnels (`fichier.php`) et REST (`api/ressource`)
     - Gère différents formats de paramètres et d'identifiants
     - Transforme les requêtes au format attendu par le backend
     - Journalise les requêtes et les réponses pour le débogage
- **Avantages**:
     - Simplifie le code frontend (un seul point d'entrée)
     - Améliore la compatibilité avec différents formats d'API
     - Fournit des messages d'erreur clairs et formatés

### 3. Outil de diagnostic (`api-diagnostic.php`)

- **Rôle**: Tester et valider la communication avec tous les endpoints du backend
- **Fonctionnalités**:
     - Interface visuelle pour tester les endpoints
     - Affichage des résultats détaillés
     - Gestion des tokens JWT pour les endpoints protégés
- **Avantages**:
     - Permet d'identifier rapidement les problèmes
     - Fournit des informations détaillées sur les réponses du backend

## Flux d'authentification

1. **Obtention du token**:

      ```javascript
      fetch("improved-jwt-bridge.php", {
      	method: "POST",
      	headers: { "Content-Type": "application/json" },
      	body: JSON.stringify({
      		email: "admin@example.com",
      		password: "password123"
      	})
      })
      	.then((response) => response.json())
      	.then((data) => {
      		if (data.success && data.data && data.data.token) {
      			localStorage.setItem(
      				"jwt_token",
      				data.data.token
      			);
      		}
      	});
      ```

2. **Utilisation du token pour accéder aux ressources**:
      ```javascript
      const token = localStorage.getItem("jwt_token");
      fetch("enhanced-proxy.php?endpoint=api/users", {
      	headers: {
      		Authorization: "Bearer " + token
      	}
      })
      	.then((response) => response.json())
      	.then((data) => console.log(data));
      ```

## Mappages des endpoints

Le proxy amélioré gère automatiquement le routage des requêtes vers les bons endpoints:

| Frontend Request                          | Backend Endpoint                                             |
| ----------------------------------------- | ------------------------------------------------------------ |
| `enhanced-proxy.php?endpoint=status.php`  | `https://app-backend-esgi-app.azurewebsites.net/status.php`  |
| `enhanced-proxy.php?endpoint=api/users`   | `https://app-backend-esgi-app.azurewebsites.net/api/users`   |
| `enhanced-proxy.php?endpoint=api/notes/1` | `https://app-backend-esgi-app.azurewebsites.net/api/notes/1` |

## Journalisation et dépannage

Le système maintient plusieurs fichiers de logs pour faciliter le dépannage:

- `logs/improved-jwt-bridge.log`: Logs d'authentification
- `logs/enhanced-proxy.log`: Logs de toutes les requêtes API
- `logs/users-api.log`: Logs spécifiques à l'API utilisateurs (si utilisé)

## Considérations de sécurité

1. **Clé de signature JWT**: La clé `esgi_azure_secret_key` est utilisée localement et doit correspondre à celle du backend
2. **Contrôle des CORS**: Les en-têtes CORS sont configurés pour permettre un accès sécurisé depuis différentes origines
3. **Validation des entrées**: Toutes les entrées utilisateur sont validées avant utilisation

## Conclusion

Cette architecture offre une solution robuste pour intégrer le frontend avec le backend Azure, tout en gérant les cas où le backend pourrait être temporairement indisponible. Le proxy amélioré simplifie considérablement le développement frontend en fournissant un point d'entrée unique pour toutes les requêtes API.
