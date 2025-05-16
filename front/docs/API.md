# Guide des Endpoints API

## Vue d'ensemble

Ce guide documente tous les endpoints disponibles dans l'API, leur utilisation et les formats de données attendus.

## Base URL

```
https://app-backend-esgi-app.azurewebsites.net/api
```

## Authentification

### Login

```http
POST /auth/login
Content-Type: application/json

{
    "email": "string",
    "password": "string"
}
```

**Réponse**

```json
{
	"success": true,
	"data": {
		"user": {
			"id": "integer",
			"email": "string",
			"nom": "string",
			"prenom": "string",
			"role": "string"
		},
		"token": "string",
		"expires_in": "integer"
	}
}
```

### Logout

```http
POST /auth/logout
Authorization: Bearer <token>
```

**Réponse**

```json
{
	"success": true,
	"message": "Déconnexion réussie"
}
```

### Vérification du statut

```http
GET /auth/status
Authorization: Bearer <token>
```

**Réponse**

```json
{
	"success": true,
	"data": {
		"authenticated": true,
		"user": {
			"id": "integer",
			"email": "string",
			"role": "string"
		}
	}
}
```

## Matières

### Liste des matières

```http
GET /matieres
Authorization: Bearer <token>
```

**Réponse**

```json
{
	"success": true,
	"data": [
		{
			"id": "integer",
			"nom": "string",
			"description": "string",
			"coefficient": "float",
			"professeur_id": "integer"
		}
	]
}
```

### Détails d'une matière

```http
GET /matieres/{id}
Authorization: Bearer <token>
```

**Réponse**

```json
{
	"success": true,
	"data": {
		"id": "integer",
		"nom": "string",
		"description": "string",
		"coefficient": "float",
		"professeur": {
			"id": "integer",
			"nom": "string",
			"prenom": "string"
		},
		"classes": [
			{
				"id": "integer",
				"nom": "string"
			}
		]
	}
}
```

### Création d'une matière

```http
POST /matieres
Authorization: Bearer <token>
Content-Type: application/json

{
    "nom": "string",
    "description": "string",
    "coefficient": "float",
    "professeur_id": "integer"
}
```

**Réponse**

```json
{
	"success": true,
	"data": {
		"id": "integer",
		"nom": "string",
		"description": "string",
		"coefficient": "float",
		"professeur_id": "integer"
	}
}
```

## Notes

### Liste des notes

```http
GET /notes
Authorization: Bearer <token>
```

**Réponse**

```json
{
	"success": true,
	"data": [
		{
			"id": "integer",
			"eleve_id": "integer",
			"matiere_id": "integer",
			"valeur": "float",
			"date": "string",
			"type": "string"
		}
	]
}
```

### Ajout d'une note

```http
POST /notes
Authorization: Bearer <token>
Content-Type: application/json

{
    "eleve_id": "integer",
    "matiere_id": "integer",
    "valeur": "float",
    "date": "string",
    "type": "string"
}
```

**Réponse**

```json
{
	"success": true,
	"data": {
		"id": "integer",
		"eleve_id": "integer",
		"matiere_id": "integer",
		"valeur": "float",
		"date": "string",
		"type": "string"
	}
}
```

## Classes

### Liste des classes

```http
GET /classes
Authorization: Bearer <token>
```

**Réponse**

```json
{
	"success": true,
	"data": [
		{
			"id": "integer",
			"nom": "string",
			"niveau": "string",
			"effectif": "integer"
		}
	]
}
```

### Détails d'une classe

```http
GET /classes/{id}
Authorization: Bearer <token>
```

**Réponse**

```json
{
	"success": true,
	"data": {
		"id": "integer",
		"nom": "string",
		"niveau": "string",
		"effectif": "integer",
		"eleves": [
			{
				"id": "integer",
				"nom": "string",
				"prenom": "string"
			}
		],
		"matieres": [
			{
				"id": "integer",
				"nom": "string"
			}
		]
	}
}
```

## Élèves

### Liste des élèves

```http
GET /eleves
Authorization: Bearer <token>
```

**Réponse**

```json
{
	"success": true,
	"data": [
		{
			"id": "integer",
			"nom": "string",
			"prenom": "string",
			"classe_id": "integer",
			"date_naissance": "string"
		}
	]
}
```

### Détails d'un élève

```http
GET /eleves/{id}
Authorization: Bearer <token>
```

**Réponse**

```json
{
	"success": true,
	"data": {
		"id": "integer",
		"nom": "string",
		"prenom": "string",
		"classe": {
			"id": "integer",
			"nom": "string"
		},
		"notes": [
			{
				"id": "integer",
				"matiere": "string",
				"valeur": "float",
				"date": "string"
			}
		],
		"moyenne_generale": "float"
	}
}
```

## Examens

### Liste des examens

```http
GET /examens
Authorization: Bearer <token>
```

**Réponse**

```json
{
	"success": true,
	"data": [
		{
			"id": "integer",
			"nom": "string",
			"date": "string",
			"type": "string",
			"matiere_id": "integer"
		}
	]
}
```

### Création d'un examen

```http
POST /examens
Authorization: Bearer <token>
Content-Type: application/json

{
    "nom": "string",
    "date": "string",
    "type": "string",
    "matiere_id": "integer"
}
```

**Réponse**

```json
{
	"success": true,
	"data": {
		"id": "integer",
		"nom": "string",
		"date": "string",
		"type": "string",
		"matiere_id": "integer"
	}
}
```

## Professeurs

### Liste des professeurs

```http
GET /professeurs
Authorization: Bearer <token>
```

**Réponse**

```json
{
	"success": true,
	"data": [
		{
			"id": "integer",
			"nom": "string",
			"prenom": "string",
			"email": "string",
			"matieres": [
				{
					"id": "integer",
					"nom": "string"
				}
			]
		}
	]
}
```

## Gestion des Erreurs

### Format d'Erreur

```json
{
	"success": false,
	"error": {
		"code": "string",
		"message": "string",
		"details": {}
	}
}
```

### Codes d'Erreur

| Code | Description           |
| ---- | --------------------- |
| 400  | Requête invalide      |
| 401  | Non authentifié       |
| 403  | Non autorisé          |
| 404  | Ressource non trouvée |
| 429  | Trop de requêtes      |
| 500  | Erreur serveur        |

## Headers Requis

### Authentification

```
Authorization: Bearer <token>
```

### Content-Type

```
Content-Type: application/json
```

### Accept

```
Accept: application/json
```

## Rate Limiting

- 100 requêtes par heure par IP
- Headers de réponse :
     ```
     X-RateLimit-Limit: 100
     X-RateLimit-Remaining: 99
     X-RateLimit-Reset: 1621166400
     ```

## Exemples d'Utilisation

### cURL

```bash
# Login
curl -X POST "https://app-backend-esgi-app.azurewebsites.net/api/auth/login" \
     -H "Content-Type: application/json" \
     -d '{"email":"user@example.com","password":"password123"}'

# Liste des matières
curl -X GET "https://app-backend-esgi-app.azurewebsites.net/api/matieres" \
     -H "Authorization: Bearer <token>"
```

### JavaScript

```javascript
// Login
fetch("https://app-backend-esgi-app.azurewebsites.net/api/auth/login", {
	method: "POST",
	headers: {
		"Content-Type": "application/json"
	},
	body: JSON.stringify({
		email: "user@example.com",
		password: "password123"
	})
})
	.then((response) => response.json())
	.then((data) => console.log(data));

// Liste des matières
fetch("https://app-backend-esgi-app.azurewebsites.net/api/matieres", {
	headers: {
		Authorization: "Bearer <token>"
	}
})
	.then((response) => response.json())
	.then((data) => console.log(data));
```

## Support

Pour toute question ou problème :

1. Consulter la documentation
2. Vérifier les logs
3. Utiliser l'outil de test
4. Contacter l'équipe de support
