// Configuration de l'application
const appConfig = {
	apiBaseUrl: "https://app-backend-esgi-app.azurewebsites.net/api",
	backendBaseUrl: "https://app-backend-esgi-app.azurewebsites.net",
	useProxy: true, // Always use proxy since direct communication has CORS issues
	proxyUrl: "simple-proxy.php", // Default path for local development
	version: "1.9"
};

// Detect if we're on Azure and adjust paths
const isAzure =
	typeof window !== "undefined" &&
	window.location &&
	window.location.hostname.includes("azurewebsites.net");
if (isAzure) {
	// On Azure, use absolute paths from root
	appConfig.proxyUrl = "/simple-proxy.php";
	console.log("Running on Azure, using absolute paths");
} else {
	console.log("Running locally, using relative paths");
}

// Fonction pour obtenir l'URL de l'API
function getApiUrl(endpoint) {
	if (appConfig.useProxy) {
		// Utiliser le proxy local - essayer les deux formats avec et sans "api/"
		// Le proxy tentera les deux formats
		return `${appConfig.proxyUrl}?endpoint=${encodeURIComponent(
			endpoint.startsWith("api/")
				? endpoint
				: "api/" + endpoint
		)}`;
	} else {
		// Appel direct à l'API - vérifier si endpoint commence déjà par "api/"
		if (endpoint.startsWith("api/")) {
			return `${appConfig.backendBaseUrl}/${endpoint}`;
		} else {
			return `${appConfig.apiBaseUrl}/${endpoint}`;
		}
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

// Fonction pour vérifier si l'API est disponible
async function checkApiAvailability() {
	try {
		// Always force proxy usage for reliability
		appConfig.useProxy = true;

		// Test if proxy works
		try {
			const proxyResponse = await fetch(
				`${appConfig.proxyUrl}?endpoint=status.php`,
				{
					method: "GET",
					credentials: "include",
					headers: {
						Accept: "application/json"
					}
				}
			);

			if (proxyResponse.ok) {
				console.log("Proxy is working correctly");
				// Leave proxy enabled
			} else {
				console.log(
					"Primary proxy not working, status:",
					proxyResponse.status
				);
			}
		} catch (proxyError) {
			console.error("Error with proxy:", proxyError);

			// If the proxy fails, try falling back to direct if absolutely necessary
			try {
				const directResponse = await fetch(
					`${appConfig.backendBaseUrl}/status.php`,
					{
						method: "GET"
					}
				);

				if (directResponse.ok) {
					console.log(
						"Direct communication working as fallback"
					);
					// Only disable proxy if absolutely necessary
					// appConfig.useProxy = false;
				} else {
					console.error(
						"Both proxy and direct communication failing"
					);
				}
			} catch (directError) {
				console.error(
					"Both proxy and direct access failed with errors"
				);
			}
		}
	} catch (error) {
		console.error("API connection check error:", error);
	}
}

// Vérifier la connexion API au chargement
document.addEventListener("DOMContentLoaded", checkApiAvailability);

// Ajouter à l'objet window pour accessibilité globale
window.appConfig = appConfig;
window.getApiUrl = getApiUrl;
window.getBackendUrl = getBackendUrl;
window.getFullUrl = getFullUrl;
