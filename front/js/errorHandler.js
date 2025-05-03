class ErrorHandler {
	static showError(message, type = "error") {
		// Supprimer les messages existants
		this.clearMessages();

		// Créer le conteneur de message
		const messageContainer = document.createElement("div");
		messageContainer.className = `message-container ${type}`;

		// Créer le message
		const messageElement = document.createElement("div");
		messageElement.className = "message";
		messageElement.textContent = message;

		// Ajouter le bouton de fermeture
		const closeButton = document.createElement("button");
		closeButton.className = "close-button";
		closeButton.innerHTML = "&times;";
		closeButton.onclick = () => this.clearMessages();

		// Assembler les éléments
		messageContainer.appendChild(messageElement);
		messageContainer.appendChild(closeButton);

		// Ajouter le conteneur au début du main-content
		const mainContent = document.querySelector(".main-content");
		mainContent.insertBefore(
			messageContainer,
			mainContent.firstChild
		);

		// Supprimer automatiquement après 5 secondes
		setTimeout(() => this.clearMessages(), 5000);
	}

	static clearMessages() {
		const existingMessages =
			document.querySelectorAll(".message-container");
		existingMessages.forEach((message) => message.remove());
	}

	static handleApiError(error) {
		console.error("Erreur API:", error);

		if (error.response) {
			// Erreur avec réponse du serveur
			const status = error.response.status;
			const data = error.response.data;

			switch (status) {
				case 400:
					this.showError(
						data.message ||
							"Données invalides",
						"error"
					);
					break;
				case 401:
					this.showError(
						"Vous devez être connecté pour effectuer cette action",
						"error"
					);
					break;
				case 403:
					this.showError(
						"Vous n'avez pas les permissions nécessaires",
						"error"
					);
					break;
				case 404:
					this.showError(
						"Ressource non trouvée",
						"error"
					);
					break;
				case 500:
					this.showError(
						"Erreur serveur. Veuillez réessayer plus tard",
						"error"
					);
					break;
				default:
					this.showError(
						data.message ||
							"Une erreur est survenue",
						"error"
					);
			}
		} else if (error.request) {
			// Erreur de requête (pas de réponse)
			this.showError(
				"Impossible de se connecter au serveur",
				"error"
			);
		} else {
			// Erreur lors de la configuration de la requête
			this.showError(
				"Erreur lors de la préparation de la requête",
				"error"
			);
		}
	}

	static showSuccess(message) {
		this.showError(message, "success");
	}

	static showWarning(message) {
		this.showError(message, "warning");
	}
}
