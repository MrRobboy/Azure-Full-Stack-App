class ErrorHandler {
	static showError(
		message,
		type = "error",
		context = "",
		suggestion = ""
	) {
		// Supprimer les messages existants
		this.clearMessages();

		// Créer le conteneur de message
		const messageContainer = document.createElement("div");
		messageContainer.className = `message-container ${type}`;

		// Créer le message
		const messageElement = document.createElement("div");
		messageElement.className = "message";

		// Ajouter le contexte si présent
		if (context) {
			const contextElement = document.createElement("div");
			contextElement.className = "error-context";
			contextElement.textContent = context;
			messageElement.appendChild(contextElement);
		}

		// Ajouter le message principal
		const messageText = document.createElement("div");
		messageText.className = "error-message";
		messageText.textContent = message;
		messageElement.appendChild(messageText);

		// Ajouter la suggestion si présente
		if (suggestion) {
			const suggestionElement = document.createElement("div");
			suggestionElement.className = "error-suggestion";
			suggestionElement.textContent = suggestion;
			messageElement.appendChild(suggestionElement);
		}

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

	static handleApiError(error, context = "") {
		console.error("Erreur API:", error);

		if (error.response) {
			// Erreur avec réponse du serveur
			const status = error.response.status;
			const data = error.response.data;
			let errorCode = "";
			let suggestion = "";

			switch (status) {
				case 400:
					errorCode = "ERR-400";
					suggestion =
						"Vérifiez les données saisies et réessayez.";
					this.showError(
						data.message ||
							"Données invalides",
						"error",
						`${
							context ||
							"Erreur de validation"
						} [${errorCode}]`,
						suggestion
					);
					break;
				case 401:
					errorCode = "ERR-401";
					suggestion =
						"Veuillez vous reconnecter.";
					this.showError(
						"Vous devez être connecté pour effectuer cette action",
						"error",
						`${
							context ||
							"Erreur d'authentification"
						} [${errorCode}]`,
						suggestion
					);
					break;
				case 403:
					errorCode = "ERR-403";
					suggestion =
						"Contactez votre administrateur pour obtenir les permissions nécessaires.";
					this.showError(
						"Vous n'avez pas les permissions nécessaires",
						"error",
						`${
							context ||
							"Erreur d'autorisation"
						} [${errorCode}]`,
						suggestion
					);
					break;
				case 404:
					errorCode = "ERR-404";
					suggestion =
						"Vérifiez que la ressource existe et que l'URL est correcte.";
					this.showError(
						"Ressource non trouvée",
						"error",
						`${
							context ||
							"Erreur de ressource"
						} [${errorCode}]`,
						suggestion
					);
					break;
				case 500:
					errorCode = "ERR-500";
					suggestion =
						"Veuillez réessayer dans quelques instants. Si le problème persiste, contactez le support.";
					this.showError(
						"Erreur serveur. Veuillez réessayer plus tard",
						"error",
						`${
							context ||
							"Erreur serveur"
						} [${errorCode}]`,
						suggestion
					);
					break;
				default:
					errorCode = "ERR-000";
					suggestion =
						"Contactez le support technique en mentionnant le code d'erreur.";
					this.showError(
						data.message ||
							"Une erreur est survenue",
						"error",
						`${
							context ||
							"Erreur inconnue"
						} [${errorCode}]`,
						suggestion
					);
			}
		} else if (error.request) {
			// Erreur de requête (pas de réponse)
			errorCode = "ERR-NET";
			suggestion =
				"Vérifiez votre connexion internet et réessayez.";
			this.showError(
				"Impossible de se connecter au serveur",
				"error",
				`${
					context || "Erreur de connexion"
				} [${errorCode}]`,
				suggestion
			);
		} else {
			// Erreur lors de la configuration de la requête
			errorCode = "ERR-CFG";
			suggestion =
				"Veuillez rafraîchir la page. Si le problème persiste, contactez le support.";
			this.showError(
				"Erreur lors de la préparation de la requête",
				"error",
				`${
					context || "Erreur de configuration"
				} [${errorCode}]`,
				suggestion
			);
		}
	}

	static showSuccess(message, context = "") {
		this.showError(message, "success", context);
	}

	static showWarning(message, context = "") {
		this.showError(message, "warning", context);
	}

	// Méthodes spécifiques pour la gestion des classes
	static handleClasseError(error, action) {
		const context = `Gestion des classes - ${action}`;
		this.handleApiError(error, context);
	}

	static showClasseSuccess(message, action) {
		const context = `Gestion des classes - ${action}`;
		this.showSuccess(message, context);
	}
}
