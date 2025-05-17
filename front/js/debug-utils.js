/**
 * Utilitaires de débogage pour le frontend
 * Aide à diagnostiquer les problèmes de communication avec le backend
 */

const DebugUtils = {
	// Stockage des requêtes
	requests: [],

	// Configuration
	config: {
		maxRequests: 50, // Nombre maximum de requêtes à conserver
		logToConsole: true, // Afficher les logs dans la console
		detailedLogging: true // Logging détaillé
	},

	/**
	 * Initialisation des utilitaires de débogage
	 */
	init() {
		console.log("🔍 Debug utilities initialized");

		// Interception des requêtes fetch pour logging
		const originalFetch = window.fetch;
		window.fetch = async (url, options = {}) => {
			const startTime = performance.now();
			const requestId = this.generateRequestId();

			// Logging de la requête
			this.logRequest({
				id: requestId,
				url,
				options,
				timestamp: new Date().toISOString(),
				status: "pending"
			});

			try {
				const response = await originalFetch(
					url,
					options
				);

				// Cloner la réponse pour pouvoir la lire sans la consommer
				const clonedResponse = response.clone();
				let responseData;

				try {
					responseData =
						await clonedResponse.json();
				} catch (e) {
					responseData = {
						error: "Could not parse response as JSON"
					};
				}

				// Logging de la réponse
				this.logResponse({
					id: requestId,
					url,
					status: response.status,
					statusText: response.statusText,
					headers: Array.from(
						response.headers.entries()
					),
					data: responseData,
					duration: performance.now() - startTime
				});

				return response;
			} catch (error) {
				// Logging de l'erreur
				this.logError({
					id: requestId,
					url,
					error: error.toString(),
					duration: performance.now() - startTime
				});

				throw error;
			}
		};

		// Ajouter un bouton de diagnostic dans le coin en bas à droite
		this.addDebugButton();
	},

	/**
	 * Génère un ID unique pour une requête
	 */
	generateRequestId() {
		return "req_" + Math.random().toString(36).substr(2, 9);
	},

	/**
	 * Enregistre une requête
	 */
	logRequest(requestData) {
		// Ajouter la requête au stockage
		this.requests.push(requestData);

		// Limiter le nombre de requêtes stockées
		if (this.requests.length > this.config.maxRequests) {
			this.requests.shift();
		}

		// Afficher dans la console si activé
		if (this.config.logToConsole) {
			console.group(
				`📤 Request [${requestData.id}]: ${requestData.url}`
			);
			console.log(
				"Method:",
				requestData.options?.method || "GET"
			);
			if (requestData.options?.body) {
				try {
					console.log(
						"Body:",
						JSON.parse(
							requestData.options.body
						)
					);
				} catch (e) {
					console.log(
						"Body:",
						requestData.options.body
					);
				}
			}
			console.groupEnd();
		}
	},

	/**
	 * Enregistre une réponse
	 */
	logResponse(responseData) {
		// Mettre à jour la requête correspondante
		const requestIndex = this.requests.findIndex(
			(r) => r.id === responseData.id
		);
		if (requestIndex >= 0) {
			this.requests[requestIndex] = {
				...this.requests[requestIndex],
				response: responseData,
				status: "completed",
				successful:
					responseData.status >= 200 &&
					responseData.status < 300
			};
		}

		// Afficher dans la console si activé
		if (this.config.logToConsole) {
			if (
				responseData.status >= 200 &&
				responseData.status < 300
			) {
				console.group(
					`✅ Response [${responseData.id}]: ${responseData.url}`
				);
			} else {
				console.group(
					`❌ Response [${responseData.id}]: ${responseData.url}`
				);
			}

			console.log(
				"Status:",
				responseData.status,
				responseData.statusText
			);
			console.log(
				"Duration:",
				Math.round(responseData.duration),
				"ms"
			);

			if (this.config.detailedLogging) {
				console.log(
					"Headers:",
					Object.fromEntries(responseData.headers)
				);
				console.log("Data:", responseData.data);
			}

			console.groupEnd();
		}
	},

	/**
	 * Enregistre une erreur
	 */
	logError(errorData) {
		// Mettre à jour la requête correspondante
		const requestIndex = this.requests.findIndex(
			(r) => r.id === errorData.id
		);
		if (requestIndex >= 0) {
			this.requests[requestIndex] = {
				...this.requests[requestIndex],
				error: errorData.error,
				status: "failed"
			};
		}

		// Afficher dans la console si activé
		if (this.config.logToConsole) {
			console.group(
				`❌ Error [${errorData.id}]: ${errorData.url}`
			);
			console.error("Error:", errorData.error);
			console.log(
				"Duration:",
				Math.round(errorData.duration),
				"ms"
			);
			console.groupEnd();
		}
	},

	/**
	 * Ajoute un bouton de diagnostic flottant
	 */
	addDebugButton() {
		const button = document.createElement("button");
		button.innerText = "🔍 Debug";
		button.style.position = "fixed";
		button.style.bottom = "10px";
		button.style.right = "10px";
		button.style.zIndex = "9999";
		button.style.padding = "5px 10px";
		button.style.backgroundColor = "#007bff";
		button.style.color = "white";
		button.style.border = "none";
		button.style.borderRadius = "4px";
		button.style.cursor = "pointer";

		button.addEventListener("click", () => this.showDebugPanel());

		document.body.appendChild(button);
	},

	/**
	 * Affiche le panneau de débogage
	 */
	showDebugPanel() {
		// Créer le panneau s'il n'existe pas
		let panel = document.getElementById("debug-panel");

		if (!panel) {
			panel = document.createElement("div");
			panel.id = "debug-panel";
			panel.style.position = "fixed";
			panel.style.top = "10%";
			panel.style.left = "10%";
			panel.style.width = "80%";
			panel.style.height = "80%";
			panel.style.backgroundColor = "white";
			panel.style.boxShadow = "0 0 10px rgba(0, 0, 0, 0.5)";
			panel.style.zIndex = "10000";
			panel.style.overflowY = "auto";
			panel.style.padding = "20px";
			panel.style.borderRadius = "5px";

			// Bouton de fermeture
			const closeButton = document.createElement("button");
			closeButton.innerText = "X";
			closeButton.style.position = "absolute";
			closeButton.style.top = "10px";
			closeButton.style.right = "10px";
			closeButton.style.backgroundColor = "#dc3545";
			closeButton.style.color = "white";
			closeButton.style.border = "none";
			closeButton.style.borderRadius = "4px";
			closeButton.style.cursor = "pointer";
			closeButton.style.padding = "5px 10px";

			closeButton.addEventListener("click", () => {
				document.body.removeChild(panel);
			});

			panel.appendChild(closeButton);

			// Titre
			const title = document.createElement("h2");
			title.innerText = "Debug Panel";
			panel.appendChild(title);

			// Conteneur des requêtes
			const requestsContainer = document.createElement("div");
			requestsContainer.id = "requests-container";
			panel.appendChild(requestsContainer);

			document.body.appendChild(panel);
		}

		// Mettre à jour le contenu du panneau
		this.updateDebugPanel();
	},

	/**
	 * Met à jour le contenu du panneau de débogage
	 */
	updateDebugPanel() {
		const container = document.getElementById("requests-container");
		if (!container) return;

		// Vider le conteneur
		container.innerHTML = "";

		// Afficher les statistiques
		const stats = document.createElement("div");
		stats.style.marginBottom = "20px";
		stats.style.padding = "10px";
		stats.style.backgroundColor = "#f8f9fa";
		stats.style.borderRadius = "4px";

		const successCount = this.requests.filter(
			(r) => r.status === "completed" && r.successful
		).length;
		const failCount = this.requests.filter(
			(r) =>
				r.status === "failed" ||
				(r.status === "completed" && !r.successful)
		).length;
		const pendingCount = this.requests.filter(
			(r) => r.status === "pending"
		).length;

		stats.innerHTML = `
            <h3>Statistics</h3>
            <p>
                <span style="color: green">✓ Success: ${successCount}</span> |
                <span style="color: red">✗ Failed: ${failCount}</span> |
                <span style="color: orange">⟳ Pending: ${pendingCount}</span>
            </p>
        `;

		container.appendChild(stats);

		// Afficher les requêtes
		const requestsList = document.createElement("div");

		// Trier les requêtes par timestamp (les plus récentes d'abord)
		const sortedRequests = [...this.requests].sort((a, b) => {
			return new Date(b.timestamp) - new Date(a.timestamp);
		});

		sortedRequests.forEach((request) => {
			const requestItem = document.createElement("div");
			requestItem.style.marginBottom = "10px";
			requestItem.style.padding = "10px";
			requestItem.style.borderRadius = "4px";
			requestItem.style.cursor = "pointer";

			// Couleur en fonction du statut
			if (request.status === "pending") {
				requestItem.style.backgroundColor = "#fff3cd";
				requestItem.style.border = "1px solid #ffeeba";
			} else if (
				request.status === "completed" &&
				request.successful
			) {
				requestItem.style.backgroundColor = "#d4edda";
				requestItem.style.border = "1px solid #c3e6cb";
			} else {
				requestItem.style.backgroundColor = "#f8d7da";
				requestItem.style.border = "1px solid #f5c6cb";
			}

			// Informations sur la requête
			const method = request.options?.method || "GET";
			const url = new URL(
				request.url,
				window.location.origin
			);
			const endpoint = url.pathname + url.search;

			requestItem.innerHTML = `
                <div>
                    <strong>${method}</strong> ${endpoint}
                </div>
                <div style="font-size: 0.8em; color: #6c757d;">
                    ID: ${request.id} | Time: ${new Date(
				request.timestamp
			).toLocaleTimeString()}
                </div>
            `;

			// Afficher les détails au clic
			requestItem.addEventListener("click", () => {
				let detailsElem = document.getElementById(
					`request-details-${request.id}`
				);

				if (detailsElem) {
					detailsElem.remove();
				} else {
					detailsElem =
						document.createElement("pre");
					detailsElem.id = `request-details-${request.id}`;
					detailsElem.style.backgroundColor =
						"#f8f9fa";
					detailsElem.style.padding = "10px";
					detailsElem.style.borderRadius = "4px";
					detailsElem.style.overflow = "auto";
					detailsElem.style.maxHeight = "300px";
					detailsElem.style.marginTop = "10px";

					try {
						detailsElem.textContent =
							JSON.stringify(
								request,
								null,
								2
							);
					} catch (e) {
						detailsElem.textContent =
							"Error displaying details: " +
							e.toString();
					}

					requestItem.appendChild(detailsElem);
				}
			});

			requestsList.appendChild(requestItem);
		});

		container.appendChild(requestsList);
	}
};

// Initialiser les utilitaires de débogage au chargement de la page
document.addEventListener("DOMContentLoaded", () => {
	DebugUtils.init();
});
