const ErrorMessages = {
	// Messages généraux
	GENERAL: {
		REQUIRED_FIELDS:
			"Veuillez remplir tous les champs obligatoires",
		INVALID_DATA: "Les données fournies sont invalides",
		SERVER_ERROR: "Une erreur est survenue sur le serveur",
		NETWORK_ERROR: "Erreur de connexion au serveur",
		UNKNOWN_ERROR: "Une erreur inconnue est survenue"
	},

	// Messages pour les examens
	EXAMS: {
		CREATE: {
			SUCCESS: "L'examen a été créé avec succès",
			ERROR: "Erreur lors de la création de l'examen",
			DUPLICATE: "Un examen avec ce titre existe déjà",
			INVALID_SUBJECT: "La matière sélectionnée n'existe pas",
			INVALID_CLASS: "La classe sélectionnée n'existe pas"
		},
		UPDATE: {
			SUCCESS: "L'examen a été mis à jour avec succès",
			ERROR: "Erreur lors de la mise à jour de l'examen",
			NOT_FOUND: "L'examen à mettre à jour n'existe pas"
		},
		DELETE: {
			SUCCESS: "L'examen a été supprimé avec succès",
			ERROR: "Erreur lors de la suppression de l'examen",
			NOT_FOUND: "L'examen à supprimer n'existe pas"
		}
	},

	// Messages pour les matières
	SUBJECTS: {
		CREATE: {
			SUCCESS: "La matière a été créée avec succès",
			ERROR: "Erreur lors de la création de la matière",
			DUPLICATE: "Une matière avec ce nom existe déjà"
		},
		UPDATE: {
			SUCCESS: "La matière a été mise à jour avec succès",
			ERROR: "Erreur lors de la mise à jour de la matière",
			NOT_FOUND: "La matière à mettre à jour n'existe pas"
		},
		DELETE: {
			SUCCESS: "La matière a été supprimée avec succès",
			ERROR: "Erreur lors de la suppression de la matière",
			NOT_FOUND: "La matière à supprimer n'existe pas"
		}
	},

	// Messages pour les classes
	CLASSES: {
		CREATE: {
			SUCCESS: "La classe a été créée avec succès",
			ERROR: "Erreur lors de la création de la classe",
			DUPLICATE: "Une classe avec ce nom existe déjà"
		},
		UPDATE: {
			SUCCESS: "La classe a été mise à jour avec succès",
			ERROR: "Erreur lors de la mise à jour de la classe",
			NOT_FOUND: "La classe à mettre à jour n'existe pas"
		},
		DELETE: {
			SUCCESS: "La classe a été supprimée avec succès",
			ERROR: "Erreur lors de la suppression de la classe",
			NOT_FOUND: "La classe à supprimer n'existe pas"
		}
	}
};

// Fonction pour afficher une notification
function showNotification(message, type = "error") {
	console.log(`Affichage d'une notification de type ${type}: ${message}`);

	// Créer le conteneur s'il n'existe pas
	let container = document.querySelector(".notification-container");
	if (!container) {
		container = document.createElement("div");
		container.className = "notification-container";
		document.body.appendChild(container);
	}

	// Supprimer les anciennes notifications du même type
	const oldNotifications = container.querySelectorAll(
		`.notification.${type}`
	);
	oldNotifications.forEach((notification) => {
		notification.classList.add("slideOut");
		setTimeout(() => notification.remove(), 500);
	});

	const notification = document.createElement("div");
	notification.className = `notification ${type}`;
	notification.innerHTML = `
		<span class="close">&times;</span>
		<p>${message}</p>
	`;

	// Ajouter la notification au conteneur
	container.appendChild(notification);

	// Fermer la notification après 5 secondes
	setTimeout(() => {
		notification.classList.add("slideOut");
		setTimeout(() => notification.remove(), 500);
	}, 5000);

	// Fermer la notification au clic sur le bouton
	notification.querySelector(".close").addEventListener("click", () => {
		notification.classList.add("slideOut");
		setTimeout(() => notification.remove(), 500);
	});
}

// Fonction pour afficher une erreur
function showError(message) {
	console.error("Erreur:", message);
	showNotification(message, "error");
}

// Fonction pour afficher un succès
function showSuccess(message) {
	console.log("Succès:", message);
	showNotification(message, "success");
}
