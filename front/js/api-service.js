/**
 * API Service - Utility for all API communications
 * This service handles all communications with the backend API through our CORS proxy solution
 */

// Singleton API Service
const ApiService = (function () {
	// Private properties
	const _corsProxy = "unified-proxy.php";
	const _matieresProxy = "matieres-proxy.php"; // Dedicated matières proxy
	const _directLoginPath = "unified-login.php";
	const _directDataProxy = "direct-matieres.php"; // Direct data fallback

	// Detect environment
	const _isAzure = window.location.hostname.includes("azurewebsites.net");

	// Fallback data for when API is unavailable
	const _fallbackData = {
		matieres: [
			{ id: 1, nom: "Mathématiques" },
			{ id: 2, nom: "Français" },
			{ id: 3, nom: "Anglais" },
			{ id: 4, nom: "Histoire" }
		],
		classes: [
			{ id: 1, nom: "6ème A" },
			{ id: 2, nom: "6ème B" },
			{ id: 3, nom: "5ème A" },
			{ id: 4, nom: "5ème B" }
		],
		examens: [
			{ id: 1, nom: "Contrôle de Mathématiques" },
			{ id: 2, nom: "Devoir de Français" },
			{ id: 3, nom: "Test d'Anglais" }
		],
		professeurs: [
			{ id: 1, nom: "Dupont", prenom: "Jean" },
			{ id: 2, nom: "Martin", prenom: "Marie" },
			{ id: 3, nom: "Bernard", prenom: "Pierre" }
		],
		"admin/users": [
			{ id: 1, nom: "Admin", prenom: "Super", role: "ADMIN" },
			{ id: 2, nom: "Prof", prenom: "Test", role: "PROF" },
			{ id: 3, nom: "Eleve", prenom: "Test", role: "ELEVE" }
		]
	};

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
			const proxyUrl = `${_corsProxy}?endpoint=${encodeURIComponent(
				endpoint
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
	 * Handle API response
	 * @param {Response} response - Fetch Response object
	 * @param {string} endpoint - Original endpoint for logging
	 * @returns {Promise} - Processed response
	 */
	async function handleResponse(response, endpoint) {
		console.log(
			`Response status: ${response.status} for ${endpoint}`
		);

		// Debug response headers
		const contentType = response.headers.get("content-type");
		console.log(`Response content-type: ${contentType}`);

		if (contentType && contentType.includes("application/json")) {
			try {
				const jsonData = await response.json();
				return {
					success: response.ok,
					status: response.status,
					data: jsonData,
					response: response
				};
			} catch (jsonError) {
				console.error(
					`Failed to parse JSON from ${endpoint}:`,
					jsonError
				);
				// Get the raw text for debugging
				const textData = await response.clone().text();
				console.error(
					`Raw response data: ${textData.slice(
						0,
						200
					)}...`
				);

				return {
					success: false,
					status: response.status,
					data: null,
					error: "Failed to parse JSON response",
					rawResponse: textData.slice(0, 500)
				};
			}
		} else {
			const textData = await response.text();
			console.log(
				`Non-JSON response from ${endpoint}, length: ${textData.length} bytes`
			);

			return {
				success: response.ok,
				status: response.status,
				data: textData,
				response: response
			};
		}
	}

	/**
	 * Get fallback data for an endpoint
	 * @param {string} endpoint - API endpoint path
	 * @returns {Object} - Fallback data response
	 */
	function getFallbackData(endpoint) {
		// Extract the key from the endpoint
		let key = endpoint.split("/").pop();

		// Special handling for admin/users endpoint
		if (endpoint === "api/admin/users") {
			key = "admin/users";
		}

		// Special handling for user/profile endpoint
		if (endpoint === "api/user/profile") {
			return {
				success: true,
				status: 200,
				data: {
					user: {
						id: 1,
						nom: "Admin",
						prenom: "Super",
						role: "ADMIN",
						email: "admin@example.com"
					}
				},
				isFallback: true
			};
		}

		// Check if we have fallback data for this endpoint
		if (_fallbackData[key]) {
			return {
				success: true,
				status: 200,
				data: _fallbackData[key],
				isFallback: true
			};
		}

		// Default fallback response
		return {
			success: false,
			message: "No fallback data available",
			error: "Fallback data not found"
		};
	}

	/**
	 * Login using unified-login.php for authentication
	 * @param {string} email - User email
	 * @param {string} password - User password
	 * @returns {Promise} - Authentication result
	 */
	async function login(email, password) {
		console.log(`Attempting login for: ${email}`);
		return makeRequest("api/auth/login", "POST", {
			email,
			password
		});
	}

	/**
	 * Logout current user
	 * @returns {Promise} - Logout result
	 */
	async function logout() {
		return makeRequest("api/auth/logout", "POST");
	}

	/**
	 * Get current user profile
	 * @returns {Promise} - User profile data
	 */
	async function getCurrentUser() {
		console.log("Getting current user profile...");
		return makeRequest("api/user/profile", "GET");
	}

	// Notes-related methods
	const notes = {
		/**
		 * Get all notes
		 * @returns {Promise} - Notes data
		 */
		getAll: () => makeRequest("api-notes.php", "GET"),

		/**
		 * Get notes for specific student
		 * @param {number} eleveId - Student ID
		 * @returns {Promise} - Student notes
		 */
		getForStudent: (eleveId) =>
			makeRequest(`api-notes.php?eleve=${eleveId}`, "GET"),

		/**
		 * Get notes for specific exam
		 * @param {number} examenId - Exam ID
		 * @returns {Promise} - Exam notes
		 */
		getForExam: (examenId) =>
			makeRequest(`api-notes.php?examen=${examenId}`, "GET"),

		/**
		 * Add or update a note
		 * @param {Object} noteData - Note data
		 * @returns {Promise} - Result
		 */
		save: (noteData) =>
			makeRequest("api-notes.php", "POST", noteData),

		/**
		 * Delete a note
		 * @param {number} noteId - Note ID
		 * @returns {Promise} - Result
		 */
		delete: (noteId) =>
			makeRequest(`api-notes.php?id=${noteId}`, "DELETE")
	};

	// Classes-related methods
	const classes = {
		/**
		 * Get all classes
		 * @returns {Promise} - Classes data
		 */
		getAll: () => makeRequest("api/classes", "GET"),

		/**
		 * Get specific class
		 * @param {number} classId - Class ID
		 * @returns {Promise} - Class details
		 */
		getById: (classId) =>
			makeRequest(`api/classes/${classId}`, "GET")
	};

	// Students-related methods
	const students = {
		/**
		 * Get all students
		 * @returns {Promise} - Students data
		 */
		getAll: () => makeRequest("api/eleves", "GET"),

		/**
		 * Get students by class
		 * @param {number} classId - Class ID
		 * @returns {Promise} - Class students
		 */
		getByClass: (classId) =>
			makeRequest(`api/classes/${classId}/eleves`, "GET")
	};

	// Subjects-related methods
	const subjects = {
		/**
		 * Get all subjects
		 * @returns {Promise} - Subjects data
		 */
		getAll: () => makeRequest("api/matieres", "GET"),

		/**
		 * Get specific subject
		 * @param {number} subjectId - Subject ID
		 * @returns {Promise} - Subject details
		 */
		getById: (subjectId) =>
			makeRequest(`api/matieres/${subjectId}`, "GET")
	};

	// Exams-related methods
	const exams = {
		/**
		 * Get all exams
		 * @returns {Promise} - Exams data
		 */
		getAll: () => makeRequest("api/examens", "GET"),

		/**
		 * Get specific exam
		 * @param {number} examId - Exam ID
		 * @returns {Promise} - Exam details
		 */
		getById: (examId) => makeRequest(`api/examens/${examId}`, "GET")
	};

	// Teachers-related methods
	const teachers = {
		/**
		 * Get all teachers
		 * @returns {Promise} - Teachers data
		 */
		getAll: () => makeRequest("api/professeurs", "GET"),

		/**
		 * Get specific teacher
		 * @param {number} teacherId - Teacher ID
		 * @returns {Promise} - Teacher details
		 */
		getById: (teacherId) =>
			makeRequest(`api/professeurs/${teacherId}`, "GET")
	};

	// Public API
	return {
		login,
		logout,
		getCurrentUser,
		notes,
		classes,
		students,
		exams,
		subjects,
		teachers,

		// Generic request method for custom endpoints
		request: makeRequest,

		// Direct access to methods for backward compatibility
		subjects: {
			getAll: () => makeRequest("api/matieres", "GET"),
			getById: (id) =>
				makeRequest(`api/matieres/${id}`, "GET")
		},
		classes: {
			getAll: () => makeRequest("api/classes", "GET"),
			getById: (id) => makeRequest(`api/classes/${id}`, "GET")
		},
		exams: {
			getAll: () => makeRequest("api/examens", "GET"),
			getById: (id) => makeRequest(`api/examens/${id}`, "GET")
		},
		teachers: {
			getAll: () => makeRequest("api/professeurs", "GET"),
			getById: (id) =>
				makeRequest(`api/professeurs/${id}`, "GET")
		}
	};
})();

// Make available globally
window.ApiService = ApiService;
