# Guide des endpoints d'API avec le Proxy Amélioré

Ce document décrit les différents endpoints d'API disponibles avec le nouveau proxy amélioré.

## Architecture améliorée

Le nouveau proxy unifié `enhanced-proxy.php` simplifie l'accès à tous les types d'endpoints:

- Endpoints traditionnels au format `fichier.php`
- Endpoints REST au format `api/ressource`
- Support des IDs dans les deux formats: `api/ressource/id` ou `api/ressource?id=X`

## Endpoints disponibles

| Ressource    | Endpoint                                         | Type d'accès |
| ------------ | ------------------------------------------------ | ------------ |
| Statut       | `enhanced-proxy.php?endpoint=status.php`         | Public       |
| Auth         | `enhanced-proxy.php?endpoint=api-auth-login.php` | Public       |
| Notes        | `enhanced-proxy.php?endpoint=api/notes`          | Protégé      |
| Utilisateurs | `enhanced-proxy.php?endpoint=api/users`          | Protégé      |
| Classes      | `enhanced-proxy.php?endpoint=api/classes`        | Protégé      |
| Professeurs  | `enhanced-proxy.php?endpoint=api/profs`          | Protégé      |
| Matières     | `enhanced-proxy.php?endpoint=api/matieres`       | Protégé      |
| Examens      | `enhanced-proxy.php?endpoint=api/examens`        | Protégé      |
| Privilèges   | `enhanced-proxy.php?endpoint=api/privileges`     | Protégé      |

## Accès à des ressources spécifiques par ID

Il existe deux façons d'accéder à une ressource spécifique:

1. **Format REST** (recommandé):

      ```
      enhanced-proxy.php?endpoint=api/users/5
      ```

2. **Format query string**:
      ```
      enhanced-proxy.php?endpoint=api/users&id=5
      ```

## Utilisation avec le JWT

Pour accéder aux ressources protégées, incluez le token JWT dans l'en-tête `Authorization`:

```javascript
// Obtenir le token JWT
const token = localStorage.getItem("jwt_token");

// Accéder à la liste des utilisateurs
fetch("enhanced-proxy.php?endpoint=api/users", {
	headers: {
		Authorization: "Bearer " + token
	}
})
	.then((response) => response.json())
	.then((data) => console.log(data));

// Accéder à un utilisateur spécifique (format REST)
fetch("enhanced-proxy.php?endpoint=api/users/5", {
	headers: {
		Authorization: "Bearer " + token
	}
})
	.then((response) => response.json())
	.then((data) => console.log(data));
```

## Filtrage des ressources

Pour les ressources qui supportent le filtrage, ajoutez simplement les paramètres à l'URL:

```javascript
// Obtenir les utilisateurs d'une classe spécifique
fetch("enhanced-proxy.php?endpoint=api/users/classe/3", {
	headers: {
		Authorization: "Bearer " + token
	}
})
	.then((response) => response.json())
	.then((data) => console.log(data));

// Filtrer les notes par matière
fetch("enhanced-proxy.php?endpoint=api/notes&matiere=5", {
	headers: {
		Authorization: "Bearer " + token
	}
})
	.then((response) => response.json())
	.then((data) => console.log(data));
```

## Avantages de l'approche unifiée

Le nouveau proxy unifié offre plusieurs avantages:

1. **Interface uniforme**: Un seul point d'entrée pour tous les types d'API
2. **Compatibilité maximale**: Fonctionne avec les formats REST et traditionnels
3. **Journalisation améliorée**: Toutes les requêtes sont enregistrées avec détails
4. **Gestion d'erreurs robuste**: Messages d'erreur clairs et formatés
5. **Pas besoin d'adaptateurs multiples**: Tout passe par un seul proxy

## Dépannage

### Erreur 401 Unauthorized

Si vous recevez une erreur 401, vérifiez que:

- Votre token JWT est valide et non expiré
- L'en-tête Authorization est correctement formaté (`Bearer <token>`)

### Erreur 404 Not Found

Si vous recevez une erreur 404, vérifiez que:

- Vous utilisez le bon format d'endpoint
- Le chemin d'API est correct (vérifiez l'orthographe)
- Pour les ressources avec ID, vérifiez que l'ID existe

### Consulter les logs

Si vous rencontrez des problèmes, consultez les logs dans:

- `logs/enhanced-proxy.log` - Contient des informations détaillées sur chaque requête
- `logs/improved-jwt-bridge.log` - Pour les problèmes d'authentification
