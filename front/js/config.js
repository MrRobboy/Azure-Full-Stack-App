// Configuration de l'application
const appConfig = {
	apiBaseUrl: "https://app-backend-esgi-app.azurewebsites.net/api",
	backendBaseUrl: "https://app-backend-esgi-app.azurewebsites.net",
	useProxy: true, // Activer le proxy par défaut pour éviter les problèmes CORS
	proxyUrl: "backend-proxy.php", // URL du proxy local
	version: "1.5"
};

// Fonction pour obtenir l'URL de l'API
function getApiUrl(endpoint) {
	if (appConfig.useProxy) {
		// Utiliser le proxy local - essayer les deux formats avec et sans "api/"
		// Le proxy tentera les deux formats
		return `${appConfig.proxyUrl}?endpoint=${encodeURIComponent(
			endpoint
		)}`;
	} else {
		// Appel direct à l'API - utiliser l'URL complète sans api/ préfixe
		// puisque l'API semble ne pas utiliser ce préfixe
		return `${appConfig.backendBaseUrl}/${endpoint}`;
	}
}

// Fonction pour obtenir l'URL d'un endpoint spécifique (hors API)
function getBackendUrl(endpoint) {
	if (appConfig.useProxy) {
		// Utiliser le proxy local
		return `${appConfig.proxyUrl}?endpoint=${encodeURIComponent(
			endpoint
		)}`;
	} else {
		// Appel direct au backend
		return `${appConfig.backendBaseUrl}/${endpoint}`;
	}
}

// Fonction pour obtenir l'URL basée sur un chemin complet
function getFullUrl(fullPath) {
	if (appConfig.useProxy) {
		// Extraire le chemin relatif (sans le domaine)
		let relativePath = fullPath;

		// Si l'URL contient le domaine backend, le retirer
		if (fullPath.startsWith(appConfig.backendBaseUrl)) {
			relativePath = fullPath.substring(
				appConfig.backendBaseUrl.length + 1
			); // +1 pour le slash
		} else if (fullPath.startsWith(appConfig.apiBaseUrl)) {
			relativePath = fullPath.substring(
				appConfig.apiBaseUrl.length - 4
			); // -4 pour garder "api/"
		}

		// Utiliser le proxy
		return `${appConfig.proxyUrl}?endpoint=${encodeURIComponent(
			relativePath
		)}`;
	} else {
		// Retourner l'URL complète telle quelle
		return fullPath;
	}
}

// Ajouter à l'objet window pour accessibilité globale
window.appConfig = appConfig;
window.getApiUrl = getApiUrl;
window.getBackendUrl = getBackendUrl;
window.getFullUrl = getFullUrl;
