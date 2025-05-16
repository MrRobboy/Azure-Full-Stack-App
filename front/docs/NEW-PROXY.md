# Documentation du Nouveau Proxy

## Introduction

Ce document détaille le fonctionnement et l'utilisation du nouveau proxy créé pour faciliter la communication entre le frontend et le backend de l'application Azure-Full-Stack-App.

## Fichiers Principaux

1. **new-proxy.php**: Proxy principal optimisé pour Azure
2. **test-new-proxy.php**: Page de test pour vérifier le fonctionnement du proxy

## Objectifs et Fonctionnalités

### Objectifs principaux

- Contourner les problèmes CORS entre le frontend et le backend
- Assurer une transmission fiable des requêtes et des réponses
- Gérer correctement les cookies de session pour l'authentification
- Faciliter le débogage et le diagnostic des problèmes

### Fonctionnalités clés

1. **Gestion CORS optimisée**:

      - Détection automatique de l'origine
      - Support complet des requêtes préflight OPTIONS
      - Transmission des cookies d'authentification

2. **Transmission des données**:

      - Transfert fidèle des en-têtes HTTP
      - Transmission du corps des requêtes (GET, POST, PUT, etc.)
      - Préservation des cookies entre le client et le backend

3. **Sécurité**:

      - En-têtes de sécurité essentiels
      - Validation basique des entrées
      - Journalisation des événements importants

4. **Robustesse**:
      - Gestion des erreurs avec messages informatifs
      - Création automatique des répertoires de logs
      - Timeouts pour éviter les requêtes bloquées

## Utilisation

### Configuration requise

Aucune configuration particulière n'est nécessaire. Le proxy fonctionne immédiatement après son déploiement sur Azure.

### Intégration dans le JavaScript

Pour utiliser le proxy dans votre code JavaScript:

```javascript
async function callApi(endpoint, options = {}) {
	const url = `new-proxy.php?endpoint=${encodeURIComponent(endpoint)}`;

	const defaultOptions = {
		credentials: "include",
		headers: {
			"Content-Type": "application/json"
		}
	};

	const response = await fetch(url, { ...defaultOptions, ...options });
	return response.json();
}

// Exemple d'utilisation
const data = await callApi("api-notes.php?action=matieres");
```

### Configuration dans config.js

Pour intégrer le proxy dans le système existant, ajoutez-le à la liste des proxys dans `config.js`:

```javascript
const config = {
	api: {
		baseUrl: "https://app-backend-esgi-app.azurewebsites.net",
		proxyUrls: [
			"new-proxy.php", // Nouveau proxy en priorité
			"azure-proxy.php",
			"simple-proxy.php"
		],
		useProxy: true // Activer l'utilisation du proxy
	}
	// autres configurations...
};
```

## Diagnostic et Tests

### Page de test intégrée

Une page de test est disponible à l'adresse `/test-new-proxy.php`. Elle permet de tester:

- La connectivité de base (statut)
- L'accès aux données (matières)
- L'authentification (login)
- Les en-têtes CORS

### Journalisation

Les logs sont stockés dans le répertoire `/logs/new-proxy.log`. Consultez ces logs pour diagnostiquer les problèmes.

Informations journalisées:

- URL des requêtes
- Méthodes HTTP
- Codes de statut
- Erreurs cURL
- Problèmes d'authentification

## Différences avec les autres proxys

### Par rapport à azure-proxy.php

- Plus simple et plus léger, sans sacrifier les fonctionnalités essentielles
- Focus sur les composants critiques uniquement
- Amélioration de la gestion des erreurs

### Par rapport à simple-proxy.php

- Meilleure gestion des origines CORS
- Support plus robuste des différentes méthodes HTTP
- Structure de code plus modulaire

## Dépannage

### Problèmes courants et solutions

1. **Erreur "Endpoint manquant ou invalide"**:

      - Vérifiez que vous avez bien spécifié le paramètre `endpoint` dans l'URL

2. **Problèmes d'authentification**:

      - Assurez-vous d'utiliser `credentials: 'include'` dans vos requêtes fetch
      - Vérifiez que les cookies sont correctement transmis

3. **Erreurs cURL**:

      - Vérifiez la connectivité réseau
      - Confirmez que l'URL du backend est correcte et accessible

4. **Problèmes CORS persistants**:
      - Vérifiez les logs pour identifier les en-têtes manquants
      - Assurez-vous que le navigateur accepte les cookies tiers si nécessaire

## Conclusion

Ce nouveau proxy offre une solution robuste et légère pour surmonter les défis CORS et de communication entre le frontend et le backend sur Azure. Sa conception met l'accent sur la fiabilité, la simplicité et la facilité de débogage.
