# Guide du Proxy Unifié

## Introduction

Ce document explique le fonctionnement du nouveau système de proxy unifié, qui a été implémenté pour résoudre les problèmes de communication entre le front-end et le back-end, particulièrement les problèmes CORS sur Azure.

## Architecture

### 1. Composants principaux

![Architecture du Proxy](https://i.imgur.com/h8Yg92T.png)

Le système de proxy se compose de trois éléments clés :

1. **Proxy PHP (`unified-proxy.php`)** : Composant serveur qui relaye les requêtes du front-end vers le back-end.
2. **Configuration JavaScript (`config.js`)** : Définit les paramètres et les URL du proxy.
3. **Service API JavaScript (`api-service.js`)** : Facilite les appels API côté client.

### 2. Flux de données

1. Le client JavaScript fait une requête via `ApiService.js`
2. La requête est envoyée à `unified-proxy.php` avec l'endpoint souhaité
3. Le proxy transforme la requête et la transmet au back-end
4. Le back-end traite la requête et renvoie une réponse
5. Le proxy relaye la réponse au client JavaScript
6. Le client traite la réponse

## Composants détaillés

### 1. Proxy PHP (`unified-proxy.php`)

Le proxy PHP est le cœur du système. Il :

- Reçoit les requêtes du front-end
- Gère les en-têtes CORS
- Journalise les requêtes et les réponses
- Traite différemment les endpoints d'authentification et d'utilisateur
- Relaye les requêtes au back-end avec les bons en-têtes et méthodes HTTP
- Renvoie les réponses au front-end

#### Points importants :

- La configuration est définie en haut du fichier (URL de l'API, endpoints spéciaux, etc.)
- Les requêtes OPTIONS sont traitées spécialement pour le preflight CORS
- Chaque requête est journalisée dans un fichier de log daté
- Les méthodes HTTP (GET, POST, PUT, DELETE) sont toutes supportées

### 2. Configuration JavaScript (`config.js`)

Ce fichier définit :

- L'URL du proxy principal
- Les proxies alternatifs (fallback)
- Les fonctions utilitaires pour construire les URLs
- Une fonction pour vérifier l'accessibilité du proxy

#### Fonctions clés :

- `getApiUrl(endpoint)` : Construit l'URL complète de l'API
- `getProxyUrl(endpoint)` : Construit l'URL du proxy avec l'endpoint encodé
- `verifyProxyAccess()` : Vérifie que le proxy est accessible et bascule sur une alternative si nécessaire

### 3. Service API JavaScript (`api-service.js`)

Ce service fournit une interface simple pour communiquer avec l'API via le proxy.

#### Fonctions clés :

- `request(endpoint, method, data, options)` : Fonction générique pour toutes les requêtes API
- `login(email, password)` : Authentification
- `logout()` : Déconnexion
- `getCurrentUser()` : Récupération du profil utilisateur

## Comment utiliser le système

### 1. Page de login

La page de login est déjà configurée pour utiliser le nouveau système. Elle :

- Charge les scripts nécessaires (`config.js` et `api-service.js`)
- Utilise `ApiService.login()` pour l'authentification
- Gère les erreurs et les états de chargement
- Stocke les données de session via `session-handler.php`
- Redirige vers le dashboard après connexion réussie

### 2. Adapter les pages de gestion

Pour adapter les autres pages de gestion (`gestion_classes.php`, `gestion_matieres.php`, etc.), suivez ces étapes :

#### Étape 1 : Inclure les scripts nécessaires

```html
<script src="js/config.js?v=5.0"></script>
<script src="js/api-service.js?v=2.0"></script>
<script src="js/notification-system.js?v=1.1"></script>
```

#### Étape 2 : Adapter les fonctions de chargement de données

Remplacez les appels fetch existants par des appels à ApiService.

**Avant :**

```javascript
const response = await fetch(`matieres-proxy.php`);
const result = await response.json();
```

**Après :**

```javascript
const result = await ApiService.request("matieres");
// Les données sont dans result.data
```

#### Étape 3 : Adapter les fonctions de modification

**Avant :**

```javascript
const response = await fetch(`${endpoint}/${id}`, {
	method: "PUT",
	headers: { "Content-Type": "application/json" },
	body: JSON.stringify(formData)
});
```

**Après :**

```javascript
const result = await ApiService.request(`${endpoint}/${id}`, "PUT", formData);
```

#### Étape 4 : Adapter la gestion des erreurs

**Avant :**

```javascript
if (!response.ok) {
	throw new Error(`HTTP error ${response.status}`);
}
```

**Après :**

```javascript
if (!result.success) {
	throw new Error(result.error || "Erreur lors de l'opération");
}
```

### 3. Exemple concret : Adaptation de gestion_matieres.php

```javascript
// Fonction pour charger les matières
async function loadMatieres() {
	try {
		console.log("Chargement des matières...");

		// Afficher un loader si disponible
		if (typeof NotificationSystem.startLoader === "function") {
			NotificationSystem.startLoader(
				"loading-matieres",
				"Chargement des matières..."
			);
		}

		// Appel API via le service
		const result = await ApiService.request("matieres");

		if (!result.success) {
			throw new Error(
				result.error ||
					"Erreur lors du chargement des matières"
			);
		}

		// Traitement des données
		const tbody = document.querySelector("#matieresTable tbody");
		tbody.innerHTML = "";

		if (!result.data.data || result.data.data.length === 0) {
			tbody.innerHTML =
				'<tr><td colspan="2">Aucune matière trouvée</td></tr>';
			return;
		}

		// Afficher les matières
		result.data.data.forEach((matiere) => {
			const tr = document.createElement("tr");
			tr.innerHTML = `
                <td>${matiere.nom}</td>
                <td>
                    <button class="btn btn-edit" onclick="editMatiere(${matiere.id_matiere}, '${matiere.nom}')">Modifier</button>
                    <button class="btn btn-danger" onclick="deleteMatiere(${matiere.id_matiere})">Supprimer</button>
                </td>
            `;
			tbody.appendChild(tr);
		});

		// Notification de succès
		NotificationSystem.success("Matières chargées avec succès");
	} catch (error) {
		console.error("Erreur lors du chargement des matières:", error);
		NotificationSystem.error(error.message);
	} finally {
		// Masquer le loader si disponible
		if (typeof NotificationSystem.stopLoader === "function") {
			NotificationSystem.stopLoader("loading-matieres");
		}
	}
}
```

## Dépannage

### Problèmes courants

1. **Le proxy ne répond pas**

      - Vérifiez que le serveur Apache/PHP fonctionne
      - Vérifiez les permissions du répertoire de logs
      - Consultez les logs pour identifier les erreurs

2. **Erreurs CORS**

      - Vérifiez que tous les en-têtes CORS sont correctement définis dans `unified-proxy.php`
      - Assurez-vous que les requêtes OPTIONS sont correctement traitées

3. **Erreurs d'authentification**

      - Vérifiez que l'endpoint d'authentification est correctement configuré dans le proxy
      - Consultez les logs du proxy pour voir les détails de la requête

4. **Les requêtes POST/PUT échouent**
      - Vérifiez que les données sont correctement formatées en JSON
      - Assurez-vous que le Content-Type est bien défini à application/json

### Logs

Les logs du proxy sont stockés dans le répertoire `/front/logs/` avec un fichier par jour.

Pour consulter les logs :

```
tail -f /front/logs/proxy-YYYY-MM-DD.log
```

## Futures améliorations

1. **Mise en cache** : Ajouter un système de cache pour les requêtes fréquentes
2. **Compression** : Compresser les réponses pour réduire la bande passante
3. **Rate limiting** : Limiter le nombre de requêtes par IP pour éviter les abus
4. **Monitoring** : Ajouter des métriques pour surveiller les performances

## Conclusion

Ce nouveau système de proxy offre une solution robuste et maintenable pour les communications entre le front-end et le back-end. En adoptant une approche centralisée, il simplifie la gestion des requêtes API et résout les problèmes CORS courants sur Azure.

En suivant ce guide, vous devriez pouvoir adapter facilement toutes les pages de gestion pour utiliser ce nouveau système.
