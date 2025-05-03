// Configuration de l'API
const API_CONFIG = {
	// URL de base de l'API
	baseUrl: window.location.origin + "/api",

	// Endpoints
	endpoints: {
		classes: "/classes",
		matieres: "/matieres",
		notes: "/notes",
		examens: "/examens",
		auth: "/auth"
	}
};

// Fonction utilitaire pour construire les URLs de l'API
function getApiUrl(endpoint) {
	return API_CONFIG.baseUrl + API_CONFIG.endpoints[endpoint];
}
