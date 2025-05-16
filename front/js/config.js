// Configuration de l'application
// Version: 4.0 - Azure Edition - Unified CORS Solution

// Détecter l'environnement
const isAzure = window.location.hostname.includes("azurewebsites.net");
const isDevelopment =
	!isAzure &&
	(window.location.hostname === "localhost" ||
		window.location.hostname === "127.0.0.1");

// Configuration par défaut
const defaultConfig = {
	// URL de base de l'API backend
	apiBaseUrl: "https://app-backend-esgi-app.azurewebsites.net/api",

	// URL de base du backend
	backendBaseUrl: "https://app-backend-esgi-app.azurewebsites.net",

	// Utiliser le proxy ou non
	useProxy: isAzure, // Activer par défaut sur Azure

	// URL du proxy unifié
	proxyUrl: "unified-proxy.php", // Proxy CORS consolidé

	// Stratégie de contournement pour les erreurs 404
	bypass404: true,

	// Version de configuration
	version: "4.0"
};

// Configuration pour l'environnement
let appConfig = { ...defaultConfig };

// Fonction pour vérifier l'accessibilité du proxy
async function verifyProxyAccess() {
	if (!isAzure) return true; // Seulement exécuter sur Azure

	console.log("Verifying access to unified proxy...");

	try {
		// Ajouter un paramètre unique pour éviter la mise en cache
		const response = await fetch(
			`${
				appConfig.proxyUrl
			}?endpoint=status.php&_=${Date.now()}`,
			{
				method: "GET",
				timeout: 5000
			}
		);

		if (response.ok) {
			console.log("Unified proxy is working correctly!");
			return true;
		} else {
			console.error(
				"Unified proxy returned error status:",
				response.status
			);
			return false;
		}
	} catch (error) {
		console.error("Error accessing unified proxy:", error.message);
		return false;
	}
}

// Sur Azure, initialiser avec notre configuration
if (isAzure) {
	console.log("Running on Azure with unified proxy");
	console.log("App config:", appConfig);
	console.log("Proxy URL:", appConfig.proxyUrl);
	console.log("API Base URL:", appConfig.apiBaseUrl);

	// Vérifier l'accès au proxy
	verifyProxyAccess();
}

// Fonction pour obtenir l'URL complète de l'API
function getApiUrl(endpoint) {
	// Si l'endpoint est déjà une URL complète, la retourner
	if (endpoint.startsWith("http")) {
		return endpoint;
	}

	// Assurer qu'il y a un slash entre l'URL de base et l'endpoint si nécessaire
	if (!endpoint.startsWith("/") && !appConfig.apiBaseUrl.endsWith("/")) {
		return `${appConfig.apiBaseUrl}/${endpoint}`;
	}

	return `${appConfig.apiBaseUrl}${endpoint}`;
}

// Fonction pour obtenir l'URL complète du backend
function getBackendUrl(endpoint) {
	// Si l'endpoint est déjà une URL complète, la retourner
	if (endpoint.startsWith("http")) {
		return endpoint;
	}

	// Assurer qu'il y a un slash entre l'URL de base et l'endpoint si nécessaire
	if (
		!endpoint.startsWith("/") &&
		!appConfig.backendBaseUrl.endsWith("/")
	) {
		return `${appConfig.backendBaseUrl}/${endpoint}`;
	}

	return `${appConfig.backendBaseUrl}${endpoint}`;
}

// Fonction pour obtenir l'URL complète (avec ou sans proxy)
function getFullUrl(fullPath) {
	// Si nous utilisons le proxy sur Azure, construire l'URL du proxy
	if (isAzure && appConfig.useProxy) {
		return `${appConfig.proxyUrl}?endpoint=${encodeURIComponent(
			fullPath
		)}`;
	}

	// Sinon, retourner l'URL directe
	return fullPath;
}

// Exposer les fonctions utilitaires globalement
window.appConfig = appConfig;
window.getApiUrl = getApiUrl;
window.getBackendUrl = getBackendUrl;
window.getFullUrl = getFullUrl;

// Exporter la configuration pour les modules
if (typeof module !== "undefined" && module.exports) {
	module.exports = {
		appConfig,
		getApiUrl,
		getBackendUrl,
		getFullUrl
	};
}
