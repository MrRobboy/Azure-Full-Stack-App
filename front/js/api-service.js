/**
 * API Service - Utility for all API communications
 * This service handles all communications with the backend API through our CORS proxy solution
 */

// Singleton API Service
const ApiService = (function () {
	// Private properties
	const _corsProxy = "azure-cors-proxy.php";
	const _directLoginPath = "direct-login.php";

	// Detect environment
	const _isAzure = window.location.hostname.includes("azurewebsites.net");

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
			// Use our CORS proxy
			const proxyUrl = `${_corsProxy}?endpoint=${encodeURIComponent(
				endpoint
			)}`;
			console.log(`Using proxy URL: ${proxyUrl}`);

			const response = await fetch(proxyUrl, requestOptions);

			console.log(
				`Response status: ${response.status} for ${endpoint}`
			);

			// Handle non-JSON responses
			const contentType =
				response.headers.get("content-type");

			// Debug response headers
			console.log(`Response content-type: ${contentType}`);

			if (
				contentType &&
				contentType.includes("application/json")
			) {
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
					const textData = await response
						.clone()
						.text();
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
						rawResponse: textData.slice(
							0,
							500
						)
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
		} catch (error) {
			console.error(
				`API Request Error (${endpoint}):`,
				error
			);

			// Create a more detailed error response
			return {
				success: false,
				status: 0,
				data: null,
				error: error.message,
				errorType: error.name,
				errorStack: error.stack
			};
		}
	}

	/**
	 * Login using direct-login.php for authentication
	 * @param {string} email - User email
	 * @param {string} password - User password
	 * @returns {Promise} - Authentication result
	 */
	async function login(email, password) {
		console.log(`Attempting login for: ${email}`);

		try {
			const response = await fetch(_directLoginPath, {
				method: "POST",
				headers: {
					"Content-Type": "application/json",
					Accept: "application/json"
				},
				body: JSON.stringify({ email, password })
			});

			const data = await response.json();
			return {
				success: data.success,
				data: data,
				status: response.status
			};
		} catch (error) {
			console.error("Login error:", error);
			return {
				success: false,
				error: error.message,
				status: 0
			};
		}
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
		getByStudent: (eleveId) =>
			makeRequest(`api-notes.php?eleve_id=${eleveId}`, "GET"),

		/**
		 * Get specific note
		 * @param {number} noteId - Note ID
		 * @returns {Promise} - Note details
		 */
		getById: (noteId) =>
			makeRequest(`api-notes.php?id=${noteId}`, "GET"),

		/**
		 * Create new note
		 * @param {Object} noteData - Note data
		 * @returns {Promise} - Creation result
		 */
		create: (noteData) =>
			makeRequest("api-notes.php", "POST", noteData),

		/**
		 * Update existing note
		 * @param {number} noteId - Note ID
		 * @param {number} value - New note value
		 * @returns {Promise} - Update result
		 */
		update: (noteId, value) =>
			makeRequest("api-notes.php", "PUT", {
				id: noteId,
				valeur: value
			}),

		/**
		 * Delete a note
		 * @param {number} noteId - Note ID
		 * @returns {Promise} - Delete result
		 */
		delete: (noteId) =>
			makeRequest("api-notes.php", "DELETE", { id: noteId })
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
		request: makeRequest
	};
})();

// Make available globally
window.ApiService = ApiService;
