# Guide de migration vers le proxy unifié avec données simulées

## Introduction

Ce guide explique comment adapter les pages de gestion existantes (`gestion_classes.php`, `gestion_matieres.php`, etc.) pour utiliser le nouveau proxy unifié et tirer parti des données simulées en cas d'indisponibilité du backend.

## Avantages de la migration

1. **Robustesse** : Les pages continuent de fonctionner même si le backend est partiellement indisponible.
2. **Maintenance simplifiée** : Un seul point d'entrée pour toutes les requêtes API.
3. **Débogage facilité** : Journal détaillé des requêtes et des réponses.
4. **Performances améliorées** : Traitement optimisé des requêtes.

## Étapes de migration

### 1. Mise à jour des références de scripts

Dans chaque fichier de gestion, assurez-vous d'inclure les scripts nécessaires :

```html
<script src="js/config.js?v=5.0"></script>
<script src="js/api-service.js?v=2.0"></script>
<script src="js/notification-system.js?v=1.1"></script>
```

### 2. Adaptation des fonctions de chargement

#### Exemple : Chargement des matières

**Avant** :

```javascript
async function loadMatieres() {
	try {
		const response = await fetch(getApiUrl("matieres"), {
			headers: {
				Accept: "application/json",
				"Content-Type": "application/json"
			},
			credentials: "include"
		});

		if (!response.ok) {
			throw new Error(`Erreur HTTP: ${response.status}`);
		}

		const result = await response.json();
		if (!result.success) {
			throw new Error(
				result.message ||
					"Erreur lors du chargement des matières"
			);
		}

		// Traitement des données...
	} catch (error) {
		console.error("Erreur:", error);
		NotificationSystem.error(error.message);
	}
}
```

**Après** :

```javascript
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

		// Appel API via notre service
		const result = await ApiService.request("matieres");

		if (!result.success) {
			throw new Error(
				result.error ||
					result.data.message ||
					"Erreur lors du chargement des matières"
			);
		}

		const data = result.data;

		// Vérifier si les données sont simulées
		if (data.simulated) {
			console.warn(
				"Utilisation de données simulées pour les matières"
			);
			NotificationSystem.warning(
				"Utilisation de données simulées (backend indisponible)"
			);
		}

		// Traitement des données...
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

### 3. Adaptation des fonctions de création

#### Exemple : Création d'une matière

**Avant** :

```javascript
async function createMatiere(data) {
	try {
		const response = await fetch(getApiUrl("matieres"), {
			method: "POST",
			headers: {
				"Content-Type": "application/json",
				Accept: "application/json"
			},
			credentials: "include",
			body: JSON.stringify(data)
		});

		if (!response.ok) {
			throw new Error(`Erreur HTTP: ${response.status}`);
		}

		const result = await response.json();
		// Traitement de la réponse...
	} catch (error) {
		// Gestion des erreurs...
	}
}
```

**Après** :

```javascript
async function createMatiere(data) {
	try {
		console.log("Création d'une matière:", data);

		// Appel API via notre service
		const result = await ApiService.request(
			"matieres",
			"POST",
			data
		);

		if (!result.success) {
			throw new Error(
				result.error ||
					result.data.message ||
					"Erreur lors de la création de la matière"
			);
		}

		// Vérifier si les données sont simulées
		if (result.data.simulated) {
			console.warn(
				"Réponse simulée pour la création de matière"
			);
			NotificationSystem.warning(
				"Création simulée (backend indisponible)"
			);
			// Dans le cas des données simulées, on peut générer un ID fictif
			return {
				...data,
				id_matiere: Date.now(), // ID temporaire basé sur le timestamp
				simulated: true
			};
		}

		return result.data;
	} catch (error) {
		console.error(
			"Erreur lors de la création de la matière:",
			error
		);
		NotificationSystem.error(error.message);
		throw error;
	}
}
```

### 4. Adaptation des fonctions de mise à jour

#### Exemple : Mise à jour d'une matière

**Avant** :

```javascript
async function updateMatiere(id, data) {
	try {
		const response = await fetch(`${getApiUrl("matieres")}/${id}`, {
			method: "PUT",
			headers: {
				"Content-Type": "application/json",
				Accept: "application/json"
			},
			credentials: "include",
			body: JSON.stringify(data)
		});

		if (!response.ok) {
			throw new Error(`Erreur HTTP: ${response.status}`);
		}

		const result = await response.json();
		// Traitement de la réponse...
	} catch (error) {
		// Gestion des erreurs...
	}
}
```

**Après** :

```javascript
async function updateMatiere(id, data) {
	try {
		console.log(`Mise à jour de la matière #${id}:`, data);

		// Appel API via notre service
		const result = await ApiService.request(
			`matieres/${id}`,
			"PUT",
			data
		);

		if (!result.success) {
			throw new Error(
				result.error ||
					result.data.message ||
					"Erreur lors de la mise à jour de la matière"
			);
		}

		// Vérifier si les données sont simulées
		if (result.data.simulated) {
			console.warn(
				"Réponse simulée pour la mise à jour de matière"
			);
			NotificationSystem.warning(
				"Mise à jour simulée (backend indisponible)"
			);
		}

		return result.data;
	} catch (error) {
		console.error(
			"Erreur lors de la mise à jour de la matière:",
			error
		);
		NotificationSystem.error(error.message);
		throw error;
	}
}
```

### 5. Adaptation des fonctions de suppression

#### Exemple : Suppression d'une matière

**Avant** :

```javascript
async function deleteMatiere(id) {
	try {
		const response = await fetch(`${getApiUrl("matieres")}/${id}`, {
			method: "DELETE",
			headers: {
				Accept: "application/json"
			},
			credentials: "include"
		});

		if (!response.ok) {
			throw new Error(`Erreur HTTP: ${response.status}`);
		}

		const result = await response.json();
		// Traitement de la réponse...
	} catch (error) {
		// Gestion des erreurs...
	}
}
```

**Après** :

```javascript
async function deleteMatiere(id) {
	try {
		console.log(`Suppression de la matière #${id}`);

		// Appel API via notre service
		const result = await ApiService.request(
			`matieres/${id}`,
			"DELETE"
		);

		if (!result.success) {
			throw new Error(
				result.error ||
					result.data.message ||
					"Erreur lors de la suppression de la matière"
			);
		}

		// Vérifier si les données sont simulées
		if (result.data.simulated) {
			console.warn(
				"Réponse simulée pour la suppression de matière"
			);
			NotificationSystem.warning(
				"Suppression simulée (backend indisponible)"
			);
		}

		return result.data;
	} catch (error) {
		console.error(
			"Erreur lors de la suppression de la matière:",
			error
		);
		NotificationSystem.error(error.message);
		throw error;
	}
}
```

## Gestion des données simulées

Le proxy unifié renvoie des données simulées dans les cas suivants :

1. L'endpoint de statut est inaccessible.
2. Les endpoints principaux (matières, classes, etc.) retournent une erreur 404.

Ces données simulées sont marquées avec `simulated: true` dans la réponse. Vous pouvez utiliser cette information pour afficher un avertissement à l'utilisateur et adapter le comportement de l'application.

### Exemple : Vérification des données simulées

```javascript
function processData(result) {
	// Vérifier si les données sont simulées
	if (result.data.simulated) {
		console.warn("Utilisation de données simulées");
		NotificationSystem.warning(
			"Mode hors ligne - Données simulées"
		);

		// Adapter l'interface pour indiquer le mode hors ligne
		document.getElementById("offline-indicator").style.display =
			"block";
	} else {
		// Mode normal - données réelles
		document.getElementById("offline-indicator").style.display =
			"none";
	}

	// Continuer le traitement normal des données...
}
```

## Exemple complet : Adaptation de gestion_matieres.php

Voici un exemple complet de migration pour `gestion_matieres.php` :

```javascript
document.addEventListener("DOMContentLoaded", async function () {
	// Initialisation
	console.log("Initialisation de la page de gestion des matières");

	// Vérifier que le système de notification est chargé
	if (typeof NotificationSystem === "undefined") {
		console.error("Le système de notification n'est pas chargé !");
		alert("Erreur de chargement du système de notification");
	}

	// Charger les matières initiales
	await loadMatieres();

	// Ajouter les gestionnaires d'événements
	document.getElementById("matiere-form").addEventListener(
		"submit",
		handleMatiereSubmit
	);
});

// Fonction pour charger les matières
async function loadMatieres() {
	try {
		console.log("Chargement des matières...");

		// Afficher l'indicateur de chargement
		document.getElementById("matieres-loading").style.display =
			"block";
		document.getElementById("matieres-list").style.display = "none";

		// Appel API via notre service
		const result = await ApiService.request("matieres");

		if (!result.success) {
			throw new Error(
				result.error ||
					result.data.message ||
					"Erreur lors du chargement des matières"
			);
		}

		// Vérifier les données simulées
		if (result.data.simulated) {
			console.warn(
				"Utilisation de données simulées pour les matières"
			);
			NotificationSystem.warning(
				"Mode hors ligne - Données simulées"
			);
			document.getElementById("offline-badge").style.display =
				"inline-block";
		} else {
			document.getElementById("offline-badge").style.display =
				"none";
		}

		// Afficher les matières
		displayMatieres(result.data.data || []);

		// Notification de succès
		NotificationSystem.success("Matières chargées avec succès");
	} catch (error) {
		console.error("Erreur lors du chargement des matières:", error);
		NotificationSystem.error(error.message);
		document.getElementById("matieres-error").textContent =
			error.message;
		document.getElementById("matieres-error").style.display =
			"block";
	} finally {
		// Masquer l'indicateur de chargement
		document.getElementById("matieres-loading").style.display =
			"none";
		document.getElementById("matieres-list").style.display =
			"block";
	}
}

// Autres fonctions adaptées...
```

## Conclusion

En suivant ce guide, vous pourrez adapter facilement toutes les pages de gestion pour utiliser le nouveau proxy unifié. Cette migration apporte plusieurs avantages :

1. **Robustesse** : Les pages continuent de fonctionner même en cas de problèmes avec le backend.
2. **Expérience utilisateur améliorée** : Les utilisateurs sont informés de l'état de la connexion.
3. **Maintenance simplifiée** : Code plus clair et plus facile à maintenir.
4. **Débogage facilité** : Journal détaillé des requêtes et des réponses.

Pour toute question ou problème, consultez le fichier `TROUBLESHOOTING.md` ou contactez l'équipe de développement.
