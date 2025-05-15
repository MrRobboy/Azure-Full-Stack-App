/**
 * API Client centralisé pour la communication avec le backend
 * Implémente des mécanismes de détection de chemin de proxy et de retry
 * Version 1.0
 */

// Initialisation du client API
const ApiClient = {
	/**
	 * Envoie une requête API avec détection de chemin et retry automatique
	 *
	 * @param {string} endpoint - Le endpoint API à appeler (ex: 'auth/login')
	 * @param {Object} options - Options de la requête fetch
	 * @param {boolean} useProxy - Si true, utilise le proxy (par défaut: true)
	 * @returns {Promise<Object>} Réponse JSON
	 */
	async request(endpoint, options = {}, useProxy = true) {
		// Si l'endpoint commence déjà par 'api/', on ne l'ajoute pas
		const apiEndpoint = endpoint.startsWith("api/")
			? endpoint
			: `api/${endpoint}`;

		// Préparation des options par défaut
		const defaultOptions = {
			method: "GET",
			headers: {
				"Content-Type": "application/json",
				Accept: "application/json"
			},
			credentials: "include"
		};

		// Fusion des options par défaut avec les options fournies
		const fetchOptions = { ...defaultOptions, ...options };

		// Si useProxy est false, on fait un appel direct à l'API
		if (!useProxy) {
			const directUrl = `${appConfig.backendBaseUrl}/${apiEndpoint}`;
			console.log(
				`[ApiClient] Direct API call to: ${directUrl}`
			);
			const response = await fetch(directUrl, fetchOptions);
			return this._handleResponse(response);
		}

		// Sinon, on utilise le proxy avec stratégie de retry
		return this._requestWithProxy(apiEndpoint, fetchOptions);
	},

	/**
	 * Requête utilisant le proxy avec retry automatique
	 *
	 * @private
	 * @param {string} endpoint - Endpoint API
	 * @param {Object} options - Options fetch
	 * @returns {Promise<Object>} Réponse JSON
	 */
	async _requestWithProxy(endpoint, options) {
		// Chemins de proxy possibles, par ordre de priorité
		const proxyPaths = [
			appConfig.proxyUrl, // Chemin configuré
			"simple-proxy.php", // Même répertoire
			"/simple-proxy.php", // Racine
			window.location.pathname.substring(
				0,
				window.location.pathname.lastIndexOf("/")
			) + "/simple-proxy.php" // Chemin relatif
		];

		let lastError = null;

		// Essayer chaque chemin de proxy jusqu'à ce qu'un fonctionne
		for (const proxyPath of proxyPaths) {
			try {
				const proxyUrl = `${proxyPath}?endpoint=${encodeURIComponent(
					endpoint
				)}`;
				console.log(
					`[ApiClient] Trying proxy: ${proxyUrl}`
				);

				const response = await fetch(proxyUrl, options);

				// Si on obtient une 404, c'est que le proxy n'existe pas à ce chemin
				if (response.status === 404) {
					console.log(
						`[ApiClient] Proxy not found at: ${proxyPath}`
					);
					continue;
				}

				// Si on obtient une réponse, même erreur, on la traite
				return this._handleResponse(response);
			} catch (error) {
				console.error(
					`[ApiClient] Error with proxy ${proxyPath}:`,
					error
				);
				lastError = error;
			}
		}

		// Si tous les chemins ont échoué
		throw new Error(
			lastError
				? `Impossible de contacter le serveur: ${lastError.message}`
				: "Tous les chemins de proxy ont échoué"
		);
	},

	/**
	 * Traitement standard des réponses
	 *
	 * @private
	 * @param {Response} response - Réponse fetch
	 * @returns {Promise<Object>} Données JSON
	 */
	async _handleResponse(response) {
		// Log de la réponse
		console.log(`[ApiClient] Response status: ${response.status}`);

		// Lecture du corps de la réponse sous forme de texte
		const responseText = await response.text();

		// Si la réponse est vide, on retourne un objet vide
		if (!responseText.trim()) {
			console.warn("[ApiClient] Empty response received");
			return {};
		}

		// Tentative de parsing JSON
		try {
			const data = JSON.parse(responseText);

			// Si la réponse n'est pas OK, on lance une erreur
			if (!response.ok) {
				const error = new Error(
					data.message ||
						"Une erreur est survenue"
				);
				error.status = response.status;
				error.data = data;
				throw error;
			}

			return data;
		} catch (error) {
			console.error(
				"[ApiClient] JSON parsing error:",
				error,
				"Raw response:",
				responseText
			);

			// Si c'est une erreur de parsing, on crée une nouvelle erreur
			if (error instanceof SyntaxError) {
				const parseError = new Error(
					"Réponse invalide du serveur"
				);
				parseError.status = response.status;
				parseError.rawResponse = responseText;
				throw parseError;
			}

			// Sinon on propage l'erreur
			throw error;
		}
	},

	/**
	 * Raccourcis pour les méthodes HTTP communes
	 */

	// GET
	async get(endpoint, params = {}, useProxy = true) {
		const queryParams = new URLSearchParams(params).toString();
		const url = queryParams
			? `${endpoint}?${queryParams}`
			: endpoint;
		return this.request(url, { method: "GET" }, useProxy);
	},

	// POST
	async post(endpoint, data = {}, useProxy = true) {
		return this.request(
			endpoint,
			{
				method: "POST",
				body: JSON.stringify(data)
			},
			useProxy
		);
	},

	// PUT
	async put(endpoint, data = {}, useProxy = true) {
		return this.request(
			endpoint,
			{
				method: "PUT",
				body: JSON.stringify(data)
			},
			useProxy
		);
	},

	// DELETE
	async delete(endpoint, useProxy = true) {
		return this.request(endpoint, { method: "DELETE" }, useProxy);
	}
};

// Exposer l'API Client globalement
window.ApiClient = ApiClient;
