// Configuration de l'application
const appConfig = {
	apiBaseUrl: "https://app-backend-esgi-app.azurewebsites.net/api",
	useProxy: true, // Activer le proxy par défaut pour éviter les problèmes CORS
	proxyUrl: "backend-proxy.php", // URL du proxy local
	version: "1.3"
};

// Fonction pour obtenir l'URL de l'API
function getApiUrl(endpoint) {
	if (appConfig.useProxy) {
		// Utiliser le proxy local
		return `${appConfig.proxyUrl}?endpoint=api/${endpoint}`;
	} else {
		// Appel direct à l'API
		return `${appConfig.apiBaseUrl}/${endpoint}`;
	}
}

// Fonction pour obtenir l'URL d'un endpoint spécifique (hors API)
function getBackendUrl(endpoint) {
	if (appConfig.useProxy) {
		// Utiliser le proxy local
		return `${appConfig.proxyUrl}?endpoint=${endpoint}`;
	} else {
		// Appel direct au backend
		const baseUrl = appConfig.apiBaseUrl.replace("/api", "");
		return `${baseUrl}/${endpoint}`;
	}
}

// Ajouter à l'objet window pour accessibilité globale
window.appConfig = appConfig;
window.getApiUrl = getApiUrl;
window.getBackendUrl = getBackendUrl;
