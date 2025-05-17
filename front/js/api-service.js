/**
 * API Service - Utilitaire pour toutes les communications API
 * Version 2.0 - Compatible avec le proxy unifié
 */

// Singleton API Service
const ApiService = (function () {
	// Génère un identifiant unique pour chaque requête (pour le debug)
	function generateRequestId() {
		return (
			Date.now().toString(36) +
			Math.random().toString(36).substr(2, 5)
		);
	}

	/**
	 * Effectue une requête API
	 * @param {string} endpoint - Endpoint API (sans le / initial)
	 * @param {string} method - Méthode HTTP (GET, POST, PUT, DELETE)
	 * @param {Object} data - Données à envoyer (null pour GET/DELETE)
	 * @param {Object} options - Options supplémentaires
	 * @returns {Promise<Object>} - Promesse avec la réponse
	 */
	async function request(
		endpoint,
		method = "GET",
		data = null,
		options = {}
	) {
		const requestId = generateRequestId();
		console.log(
			`[${requestId}] 🌐 API Request: ${method} ${endpoint}`
		);

		try {
			// S'assurer que l'endpoint ne commence pas par un slash
			const cleanEndpoint = endpoint.startsWith("/")
				? endpoint.substring(1)
				: endpoint;

			// Utiliser la fonction globale pour obtenir l'URL du proxy
			const url = window.getProxyUrl(cleanEndpoint);

			// Configuration de la requête
			const requestOptions = {
				method: method,
				headers: {
					Accept: "application/json",
					"Content-Type": "application/json",
					"X-Request-ID": requestId
				},
				...options
			};

			// Ajouter le corps de la requête pour les méthodes qui le supportent
			if (
				data &&
				["POST", "PUT", "PATCH", "DELETE"].includes(
					method
				)
			) {
				requestOptions.body = JSON.stringify(data);
				console.log(
					`[${requestId}] Request data:`,
					data
				);
			}

			// Exécuter la requête
			console.log(`[${requestId}] Fetching: ${url}`);
			const response = await fetch(url, requestOptions);

			// Analyser la réponse
			const contentType =
				response.headers.get("content-type");
			let responseData;

			// Déterminer si la réponse est du JSON
			if (
				contentType &&
				contentType.includes("application/json")
			) {
				responseData = await response.json();
			} else {
				// Si ce n'est pas du JSON, retourner le texte brut
				const textData = await response.text();
				responseData = { raw: textData };
			}

			console.log(
				`[${requestId}] Response status: ${response.status}`
			);

			// Retourner un objet standardisé
			return {
				success: response.ok,
				status: response.status,
				data: responseData
			};
		} catch (error) {
			console.error(`[${requestId}] Request failed:`, error);
			return {
				success: false,
				status: 0,
				error: error.message,
				data: null
			};
		}
	}

	/**
	 * Authentifie un utilisateur
	 * @param {string} email - Email de l'utilisateur
	 * @param {string} password - Mot de passe
	 * @returns {Promise<Object>} - Résultat de l'authentification
	 */
	async function login(email, password) {
		console.log(`🔒 Login attempt for: ${email}`);
		return request("auth/login", "POST", { email, password });
	}

	/**
	 * Déconnecte l'utilisateur courant
	 * @returns {Promise<Object>} - Résultat de la déconnexion
	 */
	async function logout() {
		console.log("🚪 Logout requested");
		return request("auth/logout", "POST");
	}

	/**
	 * Récupère le profil de l'utilisateur courant
	 * @returns {Promise<Object>} - Données du profil utilisateur
	 */
	async function getCurrentUser() {
		console.log("👤 Fetching current user profile");
		return request("user/profile", "GET");
	}

	/**
	 * Récupère des identifiants de test depuis la base de données
	 * @returns {Promise<Object>} - Identifiants de test
	 */
	async function getTestCredentials() {
		console.log("🔑 Fetching test credentials from database");
		return request("auth/test-credentials", "GET");
	}

	// API publique
	return {
		request,
		login,
		logout,
		getCurrentUser,
		getTestCredentials
	};
})();

// Rendre disponible globalement
window.ApiService = ApiService;
