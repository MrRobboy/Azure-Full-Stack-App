// Configuration de l'application
// Version: 3.1 - Azure Edition - Communication POST optimisée

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

	// URL du proxy (sera testé et mis à jour automatiquement sur Azure)
	proxyUrl: "simple-proxy.php",

	// Stratégie de contournement pour les erreurs 404
	bypass404: true,

	// Version de configuration
	version: "3.1"
};

// Configuration pour l'environnement
let appConfig = { ...defaultConfig };

// Fonction pour trouver un chemin de proxy fonctionnel
async function findWorkingProxyPath() {
	if (!isAzure) return; // Seulement exécuter sur Azure

	console.log("Searching for working proxy path...");

	// Liste des chemins proxy à tester, par ordre de préférence
	const possibleProxyPaths = [
		"simple-proxy.php", // Racine
		"api-bridge.php", // Alternative principale
		"local-proxy-fix.php", // Version fixe (recommandée)
		"post-test.php", // Nouveau proxy de test POST optimisé
		"/api/simple-proxy.php", // Sous-dossier API
		"/proxy/simple-proxy.php", // Sous-dossier Proxy
		"/simple-proxy.php", // Chemin absolu
		"/api/local-proxy-fix.php", // Version fixe dans API
		"/proxy/local-proxy-fix.php" // Version fixe dans Proxy
	];

	for (const path of possibleProxyPaths) {
		console.log("Testing proxy path:", path);
		try {
			// Utiliser un signal pour limiter la durée d'attente
			const controller = new AbortController();
			const timeoutId = setTimeout(
				() => controller.abort(),
				5000
			);

			// Ajouter un paramètre unique pour éviter la mise en cache
			const response = await fetch(
				`${path}?endpoint=status.php&_=${Date.now()}`,
				{
					method: "GET",
					signal: controller.signal
				}
			);

			clearTimeout(timeoutId);

			if (response.ok) {
				console.log(`Proxy path ${path} is working!`);
				// Mettre à jour la configuration avec ce chemin
				appConfig.proxyUrl = path;
				console.log("Found working proxy path:", path);
				return true;
			}
		} catch (error) {
			console.warn(
				`Failed to access proxy at ${path}:`,
				error.message
			);
		}
	}

	console.error("No working proxy path found!");
	return false;
}

// Sur Azure, tester les différents chemins proxy possibles
if (isAzure) {
	console.log("Running on Azure, testing various proxy paths");

	// Fonction pour initialiser avec chemin de proxy en cours
	function initConfig() {
		console.log("App config:", appConfig);
		console.log("Is Azure environment:", isAzure);
		console.log("Proxy URL:", appConfig.proxyUrl);
		console.log("API Base URL:", appConfig.apiBaseUrl);

		// Vérifier si le proxy est disponible
		if (appConfig.useProxy) {
			verifyProxyAccess();
		}
	}

	// Fonction pour vérifier l'accessibilité du proxy
	async function verifyProxyAccess() {
		console.log("Testing proxy paths...");

		// Utiliser la fonction pour trouver un proxy qui fonctionne
		await findWorkingProxyPath();
	}

	// Appeler la fonction d'initialisation
	initConfig();
}

// Fonction pour tester la connectivité au backend
async function testBackendConnection() {
	console.log("Trying to find a working backend URL...");

	// D'abord, essayer avec le proxy
	if (appConfig.useProxy) {
		try {
			// Test avec le proxy
			const url = `${
				appConfig.proxyUrl
			}?endpoint=status.php&_=${Date.now()}`;
			const response = await fetch(url);

			if (response.ok) {
				console.log(
					"Proxy is working with current backend URL configuration."
				);
				return true;
			}
		} catch (error) {
			console.warn("Proxy test failed:", error.message);
		}
	}

	// Ensuite, essayer directement (si CORS est configuré)
	try {
		const response = await fetch(`${appConfig.apiBaseUrl}/status`);

		if (response.ok) {
			console.log("Direct backend connection is working.");
			// Désactiver le proxy puisque la connexion directe fonctionne
			appConfig.useProxy = false;
			return true;
		}
	} catch (error) {
		console.warn(
			"Direct backend connection failed:",
			error.message
		);
	}

	return false;
}

// Fonction optimisée pour gérer les requêtes POST, basée sur les tests réussis
async function handlePostRequest(endpoint, data, options = {}) {
	console.log(`Handling POST request to ${endpoint}`);

	// Si c'est une tentative d'authentification, essayer plusieurs chemins possibles
	if (endpoint.includes("auth/login")) {
		console.log(
			"Authentification détectée, essai avec plusieurs chemins d'API"
		);

		// Liste des endpoints d'authentification potentiels à essayer
		const authEndpoints = [
			// Essayer d'abord avec nos nouveaux points d'entrée directs
			"api-auth-login.php", // Notre nouveau endpoint direct
			"api/auth/login", // Endpoint standard
			"api/auth/check-credentials" // Endpoint GET alternatif
		];

		// Essayer d'abord api-auth-login.php (point d'entrée direct)
		try {
			console.log("Essai avec api-auth-login.php...");
			const authResponse = await fetch(
				`${appConfig.backendBaseUrl}/api-auth-login.php`,
				{
					method: "POST",
					credentials: "include", // Important pour CORS avec cookies
					headers: {
						"Content-Type":
							"application/json",
						Accept: "application/json",
						"X-Requested-With":
							"XMLHttpRequest",
						Origin: window.location.origin
					},
					body: JSON.stringify(data)
				}
			);

			if (authResponse.ok) {
				console.log("api-auth-login.php a fonctionné!");
				return authResponse;
			}
		} catch (e) {
			console.warn("Échec avec api-auth-login.php:", e);
		}

		// Essayer avec GET auth/check-credentials, qui est connu pour fonctionner
		try {
			console.log("Essai avec GET api-auth-login.php...");
			const params = new URLSearchParams({
				email: data.email || "",
				password: data.password || ""
			});

			const checkCredsResponse = await fetch(
				`${
					appConfig.backendBaseUrl
				}/api-auth-login.php?${params.toString()}`,
				{
					method: "GET",
					credentials: "include", // Important pour CORS avec cookies
					headers: {
						Accept: "application/json",
						"X-Requested-With":
							"XMLHttpRequest",
						Origin: window.location.origin
					}
				}
			);

			if (checkCredsResponse.ok) {
				console.log(
					"GET api-auth-login.php a fonctionné!"
				);
				return checkCredsResponse;
			}
		} catch (e) {
			console.warn("Échec avec GET api-auth-login.php:", e);
		}

		// Continuer avec direct-login.php (solution existante)
		try {
			console.log("Essai avec direct-login.php...");
			const directLoginResponse = await fetch(
				"direct-login.php",
				{
					method: "POST",
					headers: {
						"Content-Type":
							"application/json"
					},
					body: JSON.stringify(data)
				}
			);

			if (directLoginResponse.ok) {
				console.log("direct-login.php a fonctionné!");
				return directLoginResponse;
			}
		} catch (e) {
			console.warn("Échec avec direct-login.php:", e);
		}

		// Essayer avec simple-login.php
		try {
			console.log("Essai avec simple-login.php...");
			const params = new URLSearchParams({
				email: data.email || "",
				password: data.password || "",
				json: "true"
			});

			const simpleLoginResponse = await fetch(
				`simple-login.php?${params.toString()}`
			);
			if (simpleLoginResponse.ok) {
				console.log("simple-login.php a fonctionné!");
				return simpleLoginResponse;
			}
		} catch (e) {
			console.warn("Échec avec simple-login.php:", e);
		}

		// Essayer les différents endpoints directement
		for (const authEndpoint of authEndpoints) {
			try {
				console.log(
					`Essai direct avec ${authEndpoint}...`
				);
				const directResponse = await fetch(
					`${appConfig.backendBaseUrl}/${authEndpoint}`,
					{
						method: "POST",
						headers: {
							"Content-Type":
								"application/json",
							"X-MS-REQUEST-ID": `${Date.now()}-${Math.random()
								.toString(36)
								.substr(2, 9)}`,
							"X-Original-URL": `/${authEndpoint}`
						},
						body: JSON.stringify(data)
					}
				);

				if (directResponse.ok) {
					console.log(
						`Endpoint direct ${authEndpoint} a fonctionné!`
					);
					return directResponse;
				}
			} catch (e) {
				console.warn(`Échec avec ${authEndpoint}:`, e);
			}
		}

		// Si toutes les méthodes ont échoué, poursuivre avec le comportement normal
		console.warn(
			"Toutes les méthodes d'authentification ont échoué, retour à la méthode standard"
		);
	}

	// Si l'URL du proxy est 404, essayer d'en trouver un nouveau avant de continuer
	try {
		// Test préalable du proxy pour éviter les redirections 404
		if (appConfig.useProxy) {
			const testResponse = await fetch(
				`${appConfig.proxyUrl}?_=${Date.now()}`
			);
			if (testResponse.status === 404) {
				console.warn(
					"Current proxy returns 404, trying to find a working one..."
				);
				await findWorkingProxyPath();
			}
		}
	} catch (e) {
		console.warn("Error checking proxy:", e);
	}

	// Mode direct - Communication directe avec le backend (confirmé fonctionnel par post-test.php)
	if (!appConfig.useProxy) {
		console.log("Using direct backend connection for POST");
		const url = `${appConfig.apiBaseUrl}/${endpoint}`;

		// Ajout des en-têtes spécifiques Azure qui ont fonctionné dans les tests
		const headers = {
			"Content-Type": "application/json",
			"X-MS-REQUEST-ID": `${Date.now()}-${Math.random()
				.toString(36)
				.substr(2, 9)}`,
			"X-Original-URL": `/${endpoint}`
		};

		// Fusionner avec les en-têtes personnalisés si fournis
		if (options.headers) {
			Object.assign(headers, options.headers);
		}

		try {
			return await fetch(url, {
				method: "POST",
				headers,
				body: JSON.stringify(data),
				...options,
				headers // Écrase options.headers pour s'assurer que nos en-têtes spécifiques sont inclus
			});
		} catch (error) {
			console.error("Direct POST failed:", error);
			// Si échec, essayer avec le proxy
			console.log("Falling back to proxy for POST");
			return await proxyPostRequest(endpoint, data, options);
		}
	}

	// Mode proxy - Utiliser le proxy PHP pour les requêtes POST
	return await proxyPostRequest(endpoint, data, options);
}

// Fonction dédiée pour les requêtes via proxy
async function proxyPostRequest(endpoint, data, options = {}) {
	try {
		console.log(`Using proxy for POST to ${endpoint}`);
		// Utiliser direct-login.php comme proxy principal s'il s'agit d'une demande de connexion
		if (endpoint.includes("auth/login")) {
			console.log(
				"Authentication request detected, using direct-login.php"
			);
			try {
				const response = await fetch(
					"direct-login.php",
					{
						method: "POST",
						headers: {
							"Content-Type":
								"application/json"
						},
						body: JSON.stringify(data)
					}
				);

				if (response.ok) {
					return response;
				}
				console.warn(
					"direct-login.php failed, trying standard proxy"
				);
			} catch (e) {
				console.warn("direct-login.php error:", e);
			}
		}

		// Proxy standard
		const proxyUrl = `${
			appConfig.proxyUrl
		}?endpoint=${encodeURIComponent(endpoint)}`;

		const response = await fetch(proxyUrl, {
			method: "POST",
			headers: {
				"Content-Type": "application/json"
			},
			body: JSON.stringify(data),
			...options
		});

		// Si ça fonctionne, excellent
		if (response.ok) {
			return response;
		}

		// Si c'est un 404 et que bypass404 est activé
		if (response.status === 404 && appConfig.bypass404) {
			console.warn(
				"Proxy returned 404, trying alternative method"
			);
			// Utiliser des méthodes alternatives
			return await alternativePostMethod(endpoint, data);
		}

		return response;
	} catch (error) {
		console.error("Proxy POST error:", error);

		// Si bypass404 est activé, essayer la méthode de contournement
		if (appConfig.bypass404) {
			console.warn(
				"Error with proxy, trying alternative method"
			);
			return await alternativePostMethod(endpoint, data);
		}

		throw error;
	}
}

// Méthodes alternatives pour POST (utilise les méthodes qui ont fonctionné lors du test)
async function alternativePostMethod(endpoint, data) {
	console.log("Using alternative POST method for", endpoint);

	// Méthode spéciale pour l'authentification
	if (endpoint.includes("auth/login")) {
		try {
			console.log("Trying simple-login.php for auth...");
			const params = new URLSearchParams({
				email: data.email || "",
				password: data.password || "",
				json: "true" // Demander une réponse JSON
			});

			const response = await fetch(
				`simple-login.php?${params.toString()}`
			);
			if (response.ok) {
				// Vérifier si la réponse est du JSON
				const text = await response.text();
				try {
					const json = JSON.parse(text);
					// Simuler une réponse fetch
					return {
						ok: true,
						status: 200,
						json: () =>
							Promise.resolve(json),
						text: () =>
							Promise.resolve(text)
					};
				} catch (e) {
					console.warn(
						"simple-login.php did not return JSON:",
						e
					);
				}
			}
		} catch (e) {
			console.warn("Error with simple-login.php:", e);
		}
	}

	// Méthode 1: Utiliser fetch avec en-têtes Azure spécifiques (méthode ayant fonctionné dans les tests)
	try {
		const url = `${appConfig.backendBaseUrl}/${endpoint}`;

		const response = await fetch(url, {
			method: "POST",
			headers: {
				"Content-Type": "application/json",
				"X-MS-SITE-RESTRICTED-TOKEN": "true",
				"X-ARR-SSL": "true",
				"X-MS-REQUEST-ID": `${Date.now()}-${Math.random()
					.toString(36)
					.substr(2, 9)}`,
				"X-Original-URL": `/${endpoint}`
			},
			body: JSON.stringify(data)
		});

		if (response.ok) {
			console.log("Alternative method 1 succeeded");
			return response;
		}
	} catch (error) {
		console.warn("Alternative method 1 failed:", error);
	}

	// Méthode 2: Utiliser fetch avec en-têtes alternatifs (méthode ayant aussi fonctionné)
	try {
		const url = `${appConfig.backendBaseUrl}/${endpoint}`;

		const response = await fetch(url, {
			method: "POST",
			headers: {
				"X-HTTP-Method-Override": "POST",
				"User-Agent": "AzureWebApp/1.0",
				Accept: "application/json"
			},
			body: JSON.stringify(data)
		});

		if (response.ok) {
			console.log("Alternative method 2 succeeded");
			return response;
		}
	} catch (error) {
		console.warn("Alternative method 2 failed:", error);
	}

	// Méthode 3: Fallback iframe si tout échoue
	return await iframePostFallback(endpoint, data);
}

// Fonction de contournement utilisant un iframe pour POST (évite les problèmes de CORS/404)
async function iframePostFallback(endpoint, data) {
	return new Promise((resolve, reject) => {
		const uniqueId = `iframe_fallback_${Date.now()}_${Math.random()
			.toString(36)
			.substr(2, 9)}`;

		// Créer un iframe caché
		const iframe = document.createElement("iframe");
		iframe.name = uniqueId;
		iframe.style.display = "none";
		document.body.appendChild(iframe);

		// Créer un formulaire qui cible l'iframe
		const form = document.createElement("form");
		form.action = `${appConfig.backendBaseUrl}/${endpoint}`;
		form.method = "POST";
		form.target = uniqueId;
		form.enctype = "application/json";

		// Ajouter les données au formulaire
		const input = document.createElement("input");
		input.type = "hidden";
		input.name = "data";
		input.value = JSON.stringify(data);
		form.appendChild(input);

		// Ajouter une fonction de callback
		window[uniqueId] = function (responseData) {
			resolve(responseData);
			cleanup();
		};

		// Fonction pour nettoyer les éléments DOM
		function cleanup() {
			try {
				if (iframe.parentNode)
					document.body.removeChild(iframe);
				if (form.parentNode)
					document.body.removeChild(form);
				delete window[uniqueId];
			} catch (e) {
				console.warn("Cleanup error:", e);
			}
		}

		// Gérer les erreurs
		iframe.onerror = function (error) {
			console.error("Iframe error:", error);
			cleanup();
			reject(new Error("Iframe communication failed"));
		};

		// Ajouter des en-têtes supplémentaires via input hidden
		const headersInput = document.createElement("input");
		headersInput.type = "hidden";
		headersInput.name = "headers";
		headersInput.value = JSON.stringify({
			"X-MS-SITE-RESTRICTED-TOKEN": "true",
			"X-ARR-SSL": "true",
			"X-HTTP-Method-Override": "POST"
		});
		form.appendChild(headersInput);

		// Soumettre le formulaire
		document.body.appendChild(form);
		form.submit();

		// Définir un timeout plus court (10 secondes au lieu de 30)
		setTimeout(() => {
			if (iframe.parentNode) {
				cleanup();
				reject(
					new Error(
						"Iframe communication timed out"
					)
				);
			}
		}, 10000);
	});
}

// Vérifier si le proxy est fonctionnel
(async function () {
	try {
		const proxyTest = await fetch(
			`${
				appConfig.proxyUrl
			}?endpoint=status.php&_=${Date.now()}`
		);
		if (proxyTest.ok) {
			console.log("Proxy is working correctly");
		} else {
			console.warn(
				`Proxy test failed with status: ${proxyTest.status}`
			);

			// Si le proxy ne fonctionne pas correctement, essayer de trouver une alternative
			await findWorkingProxyPath();
		}
	} catch (error) {
		console.error("Error testing proxy:", error);
		// Essayer de trouver un proxy qui fonctionne
		await findWorkingProxyPath();
	} finally {
		console.log("Configuration finale:");
		console.log("Proxy URL:", appConfig.proxyUrl);
		console.log("API Base URL:", appConfig.apiBaseUrl);
	}
})();

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
		console.log(`Testing backend URL: ${url}`);

		// Try first with deployment-complete.php (our new diagnostic endpoint)
		const deploymentResponse = await fetch(
			`${url}/deployment-complete.php`,
			{
				method: "GET",
				headers: { Accept: "application/json" },
				mode: "cors"
			}
		);

		// If the diagnostic endpoint is available, we've found our backend
		if (deploymentResponse.ok) {
			console.log(
				`Found working backend with deployment verification at: ${url}`
			);
			return {
				url: url,
				status: deploymentResponse.status,
				ok: true,
				diagnostic: true
			};
		}

		// Fall back to the status check
		const statusUrl = url + "/test-api.php";
		console.log(`Testing API endpoint: ${statusUrl}`);

		const response = await fetch(statusUrl, {
			method: "GET",
			headers: { Accept: "application/json" },
			mode: "cors"
		});

		if (response.ok) {
			console.log(`Found working API endpoint: ${statusUrl}`);
			return {
				url: url,
				status: response.status,
				ok: true
			};
		}

		// Last resort: try with no-cors to see if the URL exists
		const noCorsResponse = await fetch(`${url}/azure-init.php`, {
			method: "GET",
			mode: "no-cors"
		});

		return {
			url: url,
			status: noCorsResponse.status,
			ok: noCorsResponse.status === 0 || noCorsResponse.ok
		};
	} catch (error) {
		console.warn(
			`Error checking backend URL ${url}:`,
			error.message
		);
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
			`${appConfig.proxyUrl}?endpoint=deployment-complete.php`,
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
	// Priorité 1 : Trouver le chemin du proxy qui fonctionne
	await findWorkingProxyPath();

	// Priorité 2 : Trouver un backend qui fonctionne
	await tryFindWorkingBackend();

	// Priorité 3 : Vérifier la disponibilité de l'API
	await checkApiAvailability();

	// Finalement, mettre à jour les logs de configuration après tous les ajustements
	console.log("Configuration finale:");
	console.log("Proxy URL:", appConfig.proxyUrl);
	console.log("API Base URL:", appConfig.apiBaseUrl);
});

// Ajouter à l'objet window pour accessibilité globale
window.appConfig = appConfig;
window.getApiUrl = getApiUrl;
window.getBackendUrl = getBackendUrl;
window.getFullUrl = getFullUrl;

// Fonction pour détecter l'URL du backend
async function detectBackendUrl() {
	console.log("Détection de l'URL du backend en cours...");

	// Liste des URL de backend potentielles
	const possibleBackends = [
		"https://app-backend-esgi-app.azurewebsites.net",
		"https://api-backend-esgi-app.azurewebsites.net",
		"https://backend-esgi-app.azurewebsites.net",
		"https://esgi-app-backend.azurewebsites.net",

		// Même sous-domaine
		`${
			window.location.protocol
		}//${window.location.hostname.replace("frontend", "backend")}`,

		// Même domaine, sous-domaine différent
		`${window.location.protocol}//api.${window.location.hostname
			.split(".")
			.slice(1)
			.join(".")}`,

		// URL locale pour le développement
		"http://localhost:3000",
		"http://localhost:8000"
	];

	// URL testées
	const testedUrls = {};

	// Tester chaque backend potentiel
	for (const backendUrl of possibleBackends) {
		try {
			console.log(`Test du backend: ${backendUrl}`);

			// Tester avec /status.php d'abord (détecté comme fonctionnel dans les tests)
			const statusResponse = await fetch(
				`${backendUrl}/status.php`,
				{
					method: "GET",
					headers: {
						Accept: "application/json"
					},
					mode: "no-cors" // Pour éviter les erreurs CORS pendant la détection
				}
			);

			testedUrls[backendUrl] = statusResponse.status;

			// Si une réponse est reçue (même no-cors retourne une réponse opaque)
			if (statusResponse) {
				console.log(`Backend trouvé: ${backendUrl}`);
				appConfig.backendBaseUrl = backendUrl;
				appConfig.apiBaseUrl = `${backendUrl}/api`;
				return true;
			}
		} catch (e) {
			console.warn(`Échec avec ${backendUrl}:`, e.message);
			testedUrls[backendUrl] = "error";
		}
	}

	console.error("Aucun backend détecté! URLs testées:", testedUrls);
	return false;
}
