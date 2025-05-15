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

// List of possible backend URLs to try
const possibleBackendUrls = [
	// Current URL
	"https://app-backend-esgi-app.azurewebsites.net",

	// Try with the same hostname as the frontend
	window.location.protocol + "//" + window.location.hostname,

	// Common variations
	"https://api-backend-esgi-app.azurewebsites.net",
	"https://backend-esgi-app.azurewebsites.net",
	"https://esgi-app-backend.azurewebsites.net"
];

// Function to check if a backend URL is accessible
async function checkBackendUrl(url) {
	try {
		const statusUrl = url + "/status.php";
		const response = await fetch(statusUrl, {
			method: "GET",
			headers: {
				Accept: "application/json"
			},
			mode: "no-cors" // Try with no-cors to see if the URL exists
		});

		// No-cors always returns opaque response, so we just check if we got a response at all
		return {
			url: url,
			status: response.status,
			ok: response.status === 0 || response.ok
		};
	} catch (error) {
		return {
			url: url,
			status: 0,
			ok: false,
			error: error.message
		};
	}
}

// Fonction pour obtenir l'URL de l'API
function getApiUrl(endpoint) {
	if (endpoint === "privileges") {
		// Cas spécial pour l'API de privilèges
		const baseUrl = window.location.hostname.includes(
			"azurewebsites.net"
		)
			? "https://app-backend-esgi-app.azurewebsites.net"
			: window.location.origin;
		return `${baseUrl}/api/privileges`;
	}

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

// Try checking all possible backend URLs
async function tryFindWorkingBackend() {
	try {
		console.log("Trying to find a working backend URL...");

		let workingUrl = null;

		// First try using the proxy to test status.php
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
			console.log(
				"Proxy is working with current backend URL configuration."
			);
			return;
		}

		// If proxy didn't work, try all possible backend URLs
		for (const url of possibleBackendUrls) {
			console.log("Trying backend URL:", url);
			const result = await checkBackendUrl(url);

			if (result.ok) {
				console.log("Found working backend URL:", url);
				workingUrl = url;
				break;
			}
		}

		if (workingUrl) {
			// Update configuration with working URL
			appConfig.backendBaseUrl = workingUrl;
			appConfig.apiBaseUrl = workingUrl + "/api";
			console.log("Updated backend URL to:", workingUrl);
		} else {
			console.error(
				"No working backend URL found among the tested options."
			);
		}
	} catch (error) {
		console.error("Error while finding backend URL:", error);
	}
}

// Vérifier la connexion API au chargement
document.addEventListener("DOMContentLoaded", async function () {
	// First try to find a working backend
	await tryFindWorkingBackend();

	// Then check API availability with potentially updated URL
	await checkApiAvailability();
});

// Ajouter à l'objet window pour accessibilité globale
window.appConfig = appConfig;
window.getApiUrl = getApiUrl;
window.getBackendUrl = getBackendUrl;
window.getFullUrl = getFullUrl;
