const ErrorMessages = {
	// Messages généraux
	GENERAL: {
		REQUIRED_FIELDS:
			"Veuillez remplir tous les champs obligatoires",
		INVALID_DATA: "Les données fournies sont invalides",
		SERVER_ERROR: "Une erreur est survenue sur le serveur",
		NETWORK_ERROR: "Erreur de connexion au serveur",
		UNKNOWN_ERROR: "Une erreur inattendue est survenue"
	},

	// Messages pour les examens
	EXAMS: {
		CREATE: {
			SUCCESS: "L'examen a été créé avec succès",
			ERROR: "Erreur lors de la création de l'examen",
			INVALID_TITLE: "Le titre de l'examen est invalide",
			INVALID_SUBJECT: "La matière sélectionnée est invalide",
			INVALID_CLASS: "La classe sélectionnée est invalide",
			DUPLICATE: "Un examen avec ce titre existe déjà pour cette matière et cette classe"
		},
		UPDATE: {
			SUCCESS: "L'examen a été modifié avec succès",
			ERROR: "Erreur lors de la modification de l'examen",
			NOT_FOUND: "L'examen à modifier n'a pas été trouvé"
		},
		DELETE: {
			SUCCESS: "L'examen a été supprimé avec succès",
			ERROR: "Erreur lors de la suppression de l'examen",
			NOT_FOUND: "L'examen à supprimer n'a pas été trouvé",
			HAS_GRADES: "Impossible de supprimer l'examen car il contient des notes"
		}
	},

	// Messages pour les matières
	SUBJECTS: {
		CREATE: {
			SUCCESS: "La matière a été créée avec succès",
			ERROR: "Erreur lors de la création de la matière",
			INVALID_NAME: "Le nom de la matière est invalide",
			DUPLICATE: "Une matière avec ce nom existe déjà"
		},
		UPDATE: {
			SUCCESS: "La matière a été modifiée avec succès",
			ERROR: "Erreur lors de la modification de la matière",
			NOT_FOUND: "La matière à modifier n'a pas été trouvée"
		},
		DELETE: {
			SUCCESS: "La matière a été supprimée avec succès",
			ERROR: "Erreur lors de la suppression de la matière",
			NOT_FOUND: "La matière à supprimer n'a pas été trouvée",
			HAS_EXAMS: "Impossible de supprimer la matière car elle contient des examens"
		}
	},

	// Messages pour les classes
	CLASSES: {
		CREATE: {
			SUCCESS: "La classe a été créée avec succès",
			ERROR: "Erreur lors de la création de la classe",
			INVALID_NAME: "Le nom de la classe est invalide",
			INVALID_LEVEL: "Le niveau de la classe est invalide",
			INVALID_RHYTHM: "Le rythme de la classe est invalide",
			DUPLICATE: "Une classe avec ce nom existe déjà"
		},
		UPDATE: {
			SUCCESS: "La classe a été modifiée avec succès",
			ERROR: "Erreur lors de la modification de la classe",
			NOT_FOUND: "La classe à modifier n'a pas été trouvée"
		},
		DELETE: {
			SUCCESS: "La classe a été supprimée avec succès",
			ERROR: "Erreur lors de la suppression de la classe",
			NOT_FOUND: "La classe à supprimer n'a pas été trouvée",
			HAS_STUDENTS:
				"Impossible de supprimer la classe car elle contient des élèves"
		}
	}
};

// Fonction pour afficher une notification
function showNotification(message, type = "error") {
	const notification = document.createElement("div");
	notification.className = `notification ${type}`;
	notification.innerHTML = `
        <span class="close">&times;</span>
        <p>${message}</p>
    `;
	document.body.appendChild(notification);

	// Supprimer les anciennes notifications du même type
	const oldNotifications = document.querySelectorAll(
		`.notification.${type}`
	);
	oldNotifications.forEach((old, index) => {
		if (index < oldNotifications.length - 1) {
			old.remove();
		}
	});

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
	showNotification(message, "error");
}

// Fonction pour afficher un succès
function showSuccess(message) {
	showNotification(message, "success");
}
