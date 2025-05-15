// Gestionnaire d'erreurs global
const ErrorHandler = {
	// Initialiser le gestionnaire d'erreurs
	init() {
		console.log("Initialisation du gestionnaire d'erreurs...");
		this.setupGlobalErrorHandling();
	},

	// Configurer la gestion globale des erreurs
	setupGlobalErrorHandling() {
		// Intercepter les erreurs non gérées
		window.addEventListener("error", (event) => {
			console.error("Erreur non gérée:", event.error);
			this.displayError({
				type: "Erreur JavaScript",
				message: event.message,
				details: {
					filename: event.filename,
					lineno: event.lineno,
					colno: event.colno,
					stack: event.error?.stack
				}
			});

			// Afficher la notification si le système est disponible
			if (window.NotificationSystem) {
				NotificationSystem.error(
					`Erreur: ${event.message}`
				);
			}
		});

		// Intercepter les rejets de promesses non gérés
		window.addEventListener("unhandledrejection", (event) => {
			console.error(
				"Promesse rejetée non gérée:",
				event.reason
			);
			this.displayError({
				type: "Promesse rejetée",
				message:
					event.reason?.message ||
					"Erreur asynchrone non gérée",
				details: {
					stack: event.reason?.stack,
					reason: event.reason
				}
			});

			// Afficher la notification si le système est disponible
			if (window.NotificationSystem) {
				NotificationSystem.error(
					`Erreur asynchrone: ${
						event.reason?.message ||
						"Erreur inconnue"
					}`
				);
			}
		});
	},

	// Gérer les erreurs réseau (fetch)
	async handleFetchError(response, url) {
		console.error(
			`Erreur HTTP: ${response.status} - ${response.statusText}`,
			url
		);

		let errorData = {
			type: `Erreur HTTP ${response.status}`,
			message: response.statusText,
			details: {
				status: response.status,
				url: url
			}
		};

		// Tenter de récupérer les détails de l'erreur depuis la réponse JSON
		try {
			const contentType =
				response.headers.get("content-type");
			if (
				contentType &&
				contentType.includes("application/json")
			) {
				const data = await response.json();
				errorData.message =
					data.message || errorData.message;
				errorData.details.response = data;
			} else {
				const text = await response.text();
				errorData.details.response = text;
			}
		} catch (e) {
			console.error(
				"Erreur lors de la lecture de la réponse:",
				e
			);
		}

		this.displayError(errorData);

		// Afficher la notification si le système est disponible
		if (window.NotificationSystem) {
			NotificationSystem.error(
				`Erreur serveur: ${errorData.message}`
			);
		}

		return errorData;
	},

	// Gérer les erreurs de connexion réseau
	handleNetworkError(error, url) {
		console.error("Erreur réseau:", error, url);

		const errorData = {
			type: "Erreur réseau",
			message:
				error.message ||
				"Impossible de contacter le serveur",
			details: {
				name: error.name,
				stack: error.stack,
				url: url
			}
		};

		this.displayError(errorData);

		// Afficher la notification si le système est disponible
		if (window.NotificationSystem) {
			NotificationSystem.error(
				`Erreur réseau: ${errorData.message}`
			);
		}

		return errorData;
	},

	// Afficher l'erreur dans l'interface
	displayError(error) {
		// Chercher un conteneur d'erreur existant, ou en créer un nouveau
		let errorContainer = document.getElementById("error-container");

		if (!errorContainer) {
			errorContainer = document.createElement("div");
			errorContainer.id = "error-container";
			errorContainer.className = "error-container";
			document.body.appendChild(errorContainer);
		}

		// Créer l'élément d'erreur
		const errorElement = document.createElement("div");
		errorElement.className = "error-message";

		errorElement.innerHTML = `
			<div class="error-header">
				<i class="fas fa-exclamation-circle"></i>
				<span>${error.type || "Erreur"}</span>
				<button class="close-btn">&times;</button>
			</div>
			<div class="error-content">
				<p>${error.message || "Une erreur inconnue est survenue"}</p>
				<div class="error-details">
					<pre>${JSON.stringify(error.details, null, 2)}</pre>
				</div>
			</div>
		`;

		// Ajouter l'erreur au conteneur
		errorContainer.appendChild(errorElement);

		// Configurer le bouton de fermeture
		errorElement
			.querySelector(".close-btn")
			.addEventListener("click", () => {
				errorElement.remove();
				if (errorContainer.children.length === 0) {
					errorContainer.remove();
				}
			});
	},

	// Fonction utilitaire pour effectuer des requêtes fetch avec gestion des erreurs
	async fetchWithErrorHandling(url, options = {}) {
		try {
			const response = await fetch(url, options);

			if (!response.ok) {
				// Gérer les erreurs HTTP
				const errorData = await this.handleFetchError(
					response,
					url
				);
				throw new Error(errorData.message);
			}

			return response;
		} catch (error) {
			// Vérifier si c'est une erreur réseau (non-HTTP)
			if (error.name === "TypeError" || !error.status) {
				this.handleNetworkError(error, url);
			}
			throw error;
		}
	}
};

// Initialiser le gestionnaire d'erreurs
ErrorHandler.init();

// Ajouter des styles CSS pour l'affichage des erreurs
const style = document.createElement("style");
style.textContent = `
	.error-container {
		position: fixed;
		right: 20px;
		bottom: 20px;
		max-width: 400px;
		z-index: 9999;
	}

	.error-message {
		background-color: #fff;
		border: 1px solid #dc3545;
		border-left: 5px solid #dc3545;
		border-radius: 4px;
		box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
		margin-bottom: 10px;
		overflow: hidden;
	}

	.error-header {
		background-color: #dc3545;
		color: white;
		padding: 10px 15px;
		display: flex;
		align-items: center;
	}

	.error-header i {
		margin-right: 10px;
	}

	.error-header span {
		flex-grow: 1;
		font-weight: bold;
	}

	.close-btn {
		background: none;
		border: none;
		color: white;
		font-size: 18px;
		cursor: pointer;
	}

	.error-content {
		padding: 15px;
	}

	.error-content p {
		margin-top: 0;
	}

	.error-details {
		margin-top: 10px;
		background-color: #f8f9fa;
		padding: 10px;
		border-radius: 4px;
		font-size: 12px;
		overflow: auto;
		max-height: 200px;
	}

	.error-details pre {
		margin: 0;
		white-space: pre-wrap;
	}
`;
document.head.appendChild(style);

// Exposer le gestionnaire d'erreurs globalement
window.ErrorHandler = ErrorHandler;
