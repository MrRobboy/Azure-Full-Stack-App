// Configuration de l'application
// Version: 4.2 - Azure Edition - Proxy Fix

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

	// URL des proxies - avec priorité
	proxyUrls: [
		"api-bridge.php", // Proxy principal
		"matieres-proxy.php", // Proxy spécifique pour les matières
		"simple-proxy.php", // Alternative
		"unified-proxy.php" // Original
	],

	// URL du proxy par défaut
	proxyUrl: "api-bridge.php",

	// Pour les matières spécifiquement
	matieresProxyUrl: "matieres-proxy.php",

	// Stratégie de contournement pour les erreurs 404
	bypass404: true,

	// Version de configuration
	version: "4.2"
};

// Configuration pour l'environnement
let appConfig = { ...defaultConfig };

// Fonction pour vérifier l'accessibilité du proxy
async function verifyProxyAccess() {
	if (!isAzure) return true; // Seulement exécuter sur Azure

	console.log("Verifying access to proxy...");

	// D'abord vérifier test-proxy.php
	try {
		const testResponse = await fetch(
			`test-proxy.php?_=${Date.now()}`
		);
		if (testResponse.ok) {
			const testData = await testResponse.json();
			console.log("Test proxy response:", testData);
		}
	} catch (error) {
		console.warn("Test proxy check failed:", error.message);
	}

	// Essayer chaque proxy dans l'ordre jusqu'à ce que l'un fonctionne
	for (const proxyUrl of appConfig.proxyUrls) {
		try {
			const response = await fetch(
				`${proxyUrl}?endpoint=status.php&_=${Date.now()}`,
				{
					method: "GET",
					headers: {
						Accept: "application/json",
						"Content-Type":
							"application/json"
					},
					timeout: 5000
				}
			);

			if (response.ok) {
				console.log(
					`Proxy ${proxyUrl} is working correctly!`
				);
				appConfig.proxyUrl = proxyUrl;
				return true;
			}
		} catch (error) {
			console.warn(
				`Error accessing proxy ${proxyUrl}:`,
				error.message
			);
		}
	}

	console.error("All proxies failed. Using fallback.");
	return false;
}

// Sur Azure, initialiser avec notre configuration
if (isAzure) {
	console.log("Running on Azure with proxy");
	console.log("App config:", appConfig);
	console.log("Default Proxy URL:", appConfig.proxyUrl);
	console.log("API Base URL:", appConfig.apiBaseUrl);

	// Vérifier l'accès au proxy
	verifyProxyAccess().then((success) => {
		if (!success) {
			console.warn("Using fallback proxy configuration");
		}
	});
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
		// Traitement spécial pour les matières
		if (fullPath.includes("matieres")) {
			return `${
				appConfig.matieresProxyUrl
			}?endpoint=${encodeURIComponent(fullPath)}`;
		}

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
