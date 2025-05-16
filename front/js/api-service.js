/**
 * API Service - Utility for all API communications
 * This service handles all communications with the backend API through our CORS proxy solution
 */

// Singleton API Service
const ApiService = (function () {
	// Private properties
	const _corsProxy = "matieres-proxy.php";

	/**
	 * Test the proxy connection
	 * @returns {Promise} - Test result
	 */
	async function testProxy() {
		console.log("Testing proxy connection...");
		try {
			const response = await fetch("test-proxy.php");
			const data = await response.json();
			console.log("Proxy test response:", data);
			return data;
		} catch (error) {
			console.error("Proxy test failed:", error);
			throw error;
		}
	}

	/**
	 * Make an API request using our CORS proxy
	 * @param {string} endpoint - API endpoint path
	 * @param {string} method - HTTP method (GET, POST, PUT, DELETE)
	 * @param {Object} data - Request body data
	 * @param {Object} options - Additional options
	 * @returns {Promise} - Response promise
	 */
	async function makeRequest(
		endpoint,
		method = "GET",
		data = null,
		options = {}
	) {
		console.log(`Making ${method} request to: ${endpoint}`);
		if (data) {
			console.log("Request data:", data);
		}

		const requestOptions = {
			method: method,
			headers: {
				Accept: "application/json",
				"X-Requested-With": "XMLHttpRequest"
			},
			...options
		};

		// Add content-type for requests with body
		if (data && ["POST", "PUT", "PATCH"].includes(method)) {
			requestOptions.headers["Content-Type"] =
				"application/json";
			requestOptions.body = JSON.stringify(data);
		}

		try {
			// First test the proxy
			await testProxy();

			// Ensure endpoint doesn't start with a slash
			const cleanEndpoint = endpoint.startsWith("/")
				? endpoint.substring(1)
				: endpoint;
			const proxyUrl = `${_corsProxy}?endpoint=${encodeURIComponent(
				cleanEndpoint
			)}`;
			console.log(`Using proxy URL: ${proxyUrl}`);

			const response = await fetch(proxyUrl, requestOptions);
			console.log(
				`Response status: ${response.status} for ${endpoint}`
			);

			const contentType =
				response.headers.get("content-type");
			console.log(`Response content-type: ${contentType}`);

			if (
				contentType &&
				contentType.includes("application/json")
			) {
				const jsonData = await response.json();
				console.log(
					`Response data for ${endpoint}:`,
					jsonData
				);
				return {
					success: response.ok,
					status: response.status,
					data: jsonData
				};
			} else {
				const textData = await response.text();
				console.log(
					`Non-JSON response from ${endpoint}:`,
					textData
				);
				return {
					success: response.ok,
					status: response.status,
					data: textData
				};
			}
		} catch (error) {
			console.error(`Request failed for ${endpoint}:`, error);
			throw error;
		}
	}

	/**
	 * Login using the API
	 * @param {string} email - User email
	 * @param {string} password - User password
	 * @returns {Promise} - Authentication result
	 */
	async function login(email, password) {
		console.log(`Attempting login for: ${email}`);
		return makeRequest("auth/login", "POST", {
			email,
			password
		});
	}

	/**
	 * Logout current user
	 * @returns {Promise} - Logout result
	 */
	async function logout() {
		return makeRequest("auth/logout", "POST");
	}

	/**
	 * Get current user profile
	 * @returns {Promise} - User profile data
	 */
	async function getCurrentUser() {
		console.log("Getting current user profile...");
		return makeRequest("user/profile", "GET");
	}

	// Public API
	return {
		login,
		logout,
		getCurrentUser,
		request: makeRequest,
		testProxy
	};
})();

// Make available globally
window.ApiService = ApiService;
