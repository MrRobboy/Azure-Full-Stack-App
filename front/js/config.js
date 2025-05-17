// Configuration de l'application
// Version: 5.0 - Unified Proxy Edition

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
	useProxy: true, // Toujours utiliser le proxy pour éviter les problèmes CORS

	// URL du proxy par défaut
	proxyUrl: "unified-proxy.php",

	// Proxies disponibles avec priorité
	proxyUrls: [
		"unified-proxy.php", // Nouveau proxy unifié (prioritaire)
		"azure-proxy.php", // Alternative Azure
		"simple-proxy.php", // Alternative simple
		"api-bridge.php" // Fallback
	],

	// Version de configuration
	version: "5.0"
};

// Configuration pour l'environnement
let appConfig = { ...defaultConfig };

// Fonction pour vérifier l'accessibilité du proxy
async function verifyProxyAccess() {
	console.log("Verifying access to unified proxy...");

	try {
		const response = await fetch(
			`${appConfig.proxyUrl}?endpoint=status&_=${Date.now()}`,
			{
				method: "GET",
				headers: {
					Accept: "application/json",
					"Content-Type": "application/json"
				},
				timeout: 5000
			}
		);

		if (response.ok) {
			console.log(`Unified proxy is working correctly!`);
			return true;
		}
	} catch (error) {
		console.warn(`Error accessing unified proxy:`, error.message);
	}

	// Si le proxy unifié échoue, essayer les alternatives
	console.log("Fallback to alternative proxies...");

	// Essayer chaque proxy alternatif dans l'ordre
	for (let i = 1; i < appConfig.proxyUrls.length; i++) {
		const proxyUrl = appConfig.proxyUrls[i];

		try {
			const response = await fetch(
				`${proxyUrl}?endpoint=status&_=${Date.now()}`,
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
					`Alternative proxy ${proxyUrl} is working, using it instead`
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

	console.error("All proxies failed. Using first one anyway.");
	return false;
}

// Initialiser avec notre configuration
console.log("App config initialized with:", appConfig);
console.log("Default Proxy URL:", appConfig.proxyUrl);
console.log("API Base URL:", appConfig.apiBaseUrl);

// Vérifier l'accès au proxy
verifyProxyAccess().then((success) => {
	if (!success) {
		console.warn("Using fallback proxy configuration");
	}
});

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

// Fonction pour obtenir l'URL du proxy avec l'endpoint encodé
function getProxyUrl(endpoint) {
	// Nettoyer l'endpoint
	const cleanEndpoint = endpoint.startsWith("/")
		? endpoint.substring(1)
		: endpoint;

	// Construire l'URL complète du proxy
	return `${appConfig.proxyUrl}?endpoint=${encodeURIComponent(
		cleanEndpoint
	)}`;
}

// Exposer les fonctions utilitaires globalement
window.appConfig = appConfig;
window.getApiUrl = getApiUrl;
window.getProxyUrl = getProxyUrl;

// Exporter la configuration pour les modules
if (typeof module !== "undefined" && module.exports) {
	module.exports = {
		appConfig,
		getApiUrl,
		getProxyUrl
	};
}
