// Configuration de l'application
const appConfig = {
	apiBaseUrl: "https://app-backend-esgi-app.azurewebsites.net/api",
	backendBaseUrl: "https://app-backend-esgi-app.azurewebsites.net",
	useProxy: true, // Enable proxy by default to ensure login works
	proxyUrl: "backend-proxy.php", // URL du proxy local
	version: "1.7"
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
		// First try using the proxy to see if it works
		const proxyResponse = await fetch(
			`${appConfig.proxyUrl}?endpoint=status`,
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
			appConfig.useProxy = true;

			// Now try direct access to see if CORS is fixed
			try {
				const directResponse = await fetch(
					`${appConfig.backendBaseUrl}/azure-cors.php`,
					{
						method: "GET",
						credentials: "include",
						headers: {
							"Content-Type":
								"application/json",
							"X-Requested-With":
								"XMLHttpRequest"
						}
					}
				);

				if (directResponse.ok) {
					console.log(
						"Direct API access is working but keeping proxy for now"
					);
					// Keep using proxy for stability (comment out the line below to use direct access)
					// appConfig.useProxy = false;
				} else {
					console.log(
						"Direct API access failed, using proxy"
					);
					appConfig.useProxy = true;
				}
			} catch (directError) {
				console.log(
					"Direct API access error:",
					directError
				);
				appConfig.useProxy = true;
			}
		} else {
			console.log(
				"Proxy not working correctly, trying direct access"
			);
			try {
				const directResponse = await fetch(
					`${appConfig.backendBaseUrl}/azure-cors.php`,
					{
						method: "GET",
						credentials: "include",
						headers: {
							"Content-Type":
								"application/json",
							"X-Requested-With":
								"XMLHttpRequest"
						}
					}
				);

				if (directResponse.ok) {
					console.log(
						"Direct API access working, using direct access"
					);
					appConfig.useProxy = false;
				} else {
					console.error(
						"Both proxy and direct access failed"
					);
					appConfig.useProxy = true; // Default to proxy
				}
			} catch (directError) {
				console.error(
					"Both proxy and direct access failed with errors"
				);
				appConfig.useProxy = true; // Default to proxy
			}
		}
	} catch (error) {
		console.error("API connection check error:", error);
		appConfig.useProxy = true; // Default to proxy on any error
	}
}

// Vérifier la connexion API au chargement
document.addEventListener("DOMContentLoaded", checkApiAvailability);

// Ajouter à l'objet window pour accessibilité globale
window.appConfig = appConfig;
window.getApiUrl = getApiUrl;
window.getBackendUrl = getBackendUrl;
window.getFullUrl = getFullUrl;
