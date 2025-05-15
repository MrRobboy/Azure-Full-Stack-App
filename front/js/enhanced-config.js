// Enhanced Configuration for Azure Full-Stack Application
// This file has improved proxy detection and fallback mechanisms

const appConfig = {
	apiBaseUrl: "https://app-backend-esgi-app.azurewebsites.net/api",
	backendBaseUrl: "https://app-backend-esgi-app.azurewebsites.net",
	useProxy: true, // Always use proxy for CORS handling
	proxyUrls: [], // Will be populated with working proxy paths
	version: "2.0",
	currentProxyIndex: 0,
	proxyTestsComplete: false,
	lastProxyError: null
};

// Detect if we're on Azure or local environment
const isAzure =
	typeof window !== "undefined" &&
	window.location &&
	(window.location.hostname.includes("azurewebsites.net") ||
		window.location.hostname ===
			"app-frontend-esgi-app.azurewebsites.net");

// Used to enable verbose logging on Azure for troubleshooting
const enableVerboseLogging =
	isAzure ||
	(localStorage && localStorage.getItem("verbose_logging") === "true");

// Create more descriptive logs that reveal environment details
function logInfo(message, data) {
	if (enableVerboseLogging) {
		console.log(`[INFO] ${message}`, data || "");
		if (isAzure && window.fetch) {
			// Log to backend for persistent storage if needed
			try {
				fetch("log-client-error.php", {
					method: "POST",
					headers: {
						"Content-Type":
							"application/json"
					},
					body: JSON.stringify({
						level: "info",
						message: message,
						data: data,
						client_info: {
							url: window.location
								.href,
							user_agent: navigator.userAgent,
							timestamp: new Date().toISOString()
						}
					})
				}).catch((e) =>
					console.error(
						"Error logging to server:",
						e
					)
				);
			} catch (e) {
				// Ignore logging errors
			}
		}
	}
}

function logError(message, error) {
	console.error(`[ERROR] ${message}`, error || "");

	// Save last proxy error for diagnostics
	appConfig.lastProxyError = {
		message: message,
		error: error,
		timestamp: new Date().toISOString(),
		url: window.location.href
	};

	if (isAzure && window.fetch) {
		// Log to backend for persistent storage
		try {
			fetch("log-client-error.php", {
				method: "POST",
				headers: { "Content-Type": "application/json" },
				body: JSON.stringify({
					level: "error",
					message: message,
					error: error,
					stack: error && error.stack,
					client_info: {
						url: window.location.href,
						user_agent: navigator.userAgent,
						timestamp: new Date().toISOString()
					}
				})
			}).catch((e) =>
				console.error("Error logging to server:", e)
			);
		} catch (e) {
			// Ignore logging errors
		}
	}
}

// Generate a list of possible proxy paths to test based on environment
function generateProxyPaths() {
	const paths = [];

	// Start with the standard paths that should work in most cases
	paths.push("simple-proxy.php");
	paths.push("/simple-proxy.php");

	// Add paths specific to folder structure
	if (window.location.pathname.includes("/")) {
		const pathParts = window.location.pathname
			.split("/")
			.filter(Boolean);

		// Try in current directory
		paths.push(
			window.location.pathname.substring(
				0,
				window.location.pathname.lastIndexOf("/") + 1
			) + "simple-proxy.php"
		);

		// Try one level up
		if (pathParts.length > 0) {
			paths.push("../simple-proxy.php");
		}

		// Try two levels up
		if (pathParts.length > 1) {
			paths.push("../../simple-proxy.php");
		}
	}

	// Try in special subdirectories
	paths.push("proxy/simple-proxy.php");
	paths.push("/proxy/simple-proxy.php");
	paths.push("api/simple-proxy.php");
	paths.push("/api/simple-proxy.php");

	// For really difficult Azure environments, try absolute URLs with the same host
	if (isAzure) {
		paths.push(window.location.origin + "/simple-proxy.php");
		paths.push(window.location.origin + "/proxy/simple-proxy.php");
		paths.push(window.location.origin + "/api/simple-proxy.php");
	}

	// Add alternative proxy implementations as fallbacks
	paths.push("local-proxy.php");
	paths.push("/local-proxy.php");

	// Final fallback to direct API calls (which will likely fail due to CORS)
	paths.push("direct-login.php");

	return paths;
}

// Test a single proxy path to see if it works
async function testProxyPath(path) {
	logInfo(`Testing proxy path: ${path}`);

	try {
		const response = await fetch(`${path}?endpoint=status.php`, {
			method: "GET",
			headers: {
				Accept: "application/json"
			}
		});

		if (response.ok) {
			logInfo(`Proxy path successful: ${path}`);
			return true;
		} else {
			logInfo(
				`Proxy path failed with status ${response.status}: ${path}`
			);
			return false;
		}
	} catch (error) {
		logError(`Proxy path error: ${path}`, error);
		return false;
	}
}

// Test all possible proxy paths and store working ones
async function findWorkingProxyPaths() {
	const paths = generateProxyPaths();
	logInfo("Testing possible proxy paths...", paths);

	appConfig.proxyUrls = []; // Reset working paths
	appConfig.proxyTestsComplete = false;

	for (const path of paths) {
		try {
			if (await testProxyPath(path)) {
				appConfig.proxyUrls.push(path);
				logInfo(`Added working proxy path: ${path}`);
			}
		} catch (error) {
			logError(`Error testing path ${path}:`, error);
		}
	}

	appConfig.proxyTestsComplete = true;

	if (appConfig.proxyUrls.length > 0) {
		logInfo(
			`Found ${appConfig.proxyUrls.length} working proxy paths:`,
			appConfig.proxyUrls
		);
		return true;
	} else {
		logError("No working proxy paths found!");
		return false;
	}
}

// Get the current proxy URL
function getCurrentProxyUrl() {
	if (appConfig.proxyUrls.length === 0) {
		if (!appConfig.proxyTestsComplete) {
			logInfo("Proxy tests not completed yet, using default");
			return "simple-proxy.php";
		}
		logError("No working proxy found, using default path");
		return "simple-proxy.php";
	}

	return appConfig.proxyUrls[appConfig.currentProxyIndex];
}

// Move to the next proxy in the list if the current one fails
function rotateToNextProxy() {
	if (appConfig.proxyUrls.length <= 1) return false;

	appConfig.currentProxyIndex =
		(appConfig.currentProxyIndex + 1) % appConfig.proxyUrls.length;
	logInfo(`Rotated to next proxy: ${getCurrentProxyUrl()}`);
	return true;
}

// Function to get API URL with proper proxy handling
function getApiUrl(endpoint) {
	if (appConfig.useProxy) {
		const proxyUrl = getCurrentProxyUrl();
		return `${proxyUrl}?endpoint=${encodeURIComponent(
			endpoint.startsWith("api/")
				? endpoint
				: "api/" + endpoint
		)}`;
	} else {
		if (endpoint.startsWith("api/")) {
			return `${appConfig.backendBaseUrl}/${endpoint}`;
		} else {
			return `${appConfig.apiBaseUrl}/${endpoint}`;
		}
	}
}

// Function to get backend URL (non-API)
function getBackendUrl(endpoint) {
	if (appConfig.useProxy) {
		const proxyUrl = getCurrentProxyUrl();
		return `${proxyUrl}?endpoint=${encodeURIComponent(endpoint)}`;
	} else {
		return `${appConfig.backendBaseUrl}/${endpoint}`;
	}
}

// Initialize configuration
async function initializeConfig() {
	logInfo("Initializing application configuration");
	logInfo("Environment:", isAzure ? "Azure" : "Local");

	// Initialize proxy paths
	if (isAzure) {
		logInfo("Running on Azure, detecting proxy paths");
		await findWorkingProxyPaths();
	} else {
		logInfo("Running locally, using default proxy path");
		appConfig.proxyUrls = ["simple-proxy.php"];
		appConfig.proxyTestsComplete = true;
	}

	// Log final configuration
	logInfo("Configuration initialized:", {
		environment: isAzure ? "Azure" : "Local",
		proxyEnabled: appConfig.useProxy,
		workingProxies: appConfig.proxyUrls,
		currentProxy: getCurrentProxyUrl(),
		apiBaseUrl: appConfig.apiBaseUrl
	});
}

// Initialize config when loaded
initializeConfig().catch((error) => {
	logError("Failed to initialize config:", error);
});

// Export required functions
window.appConfig = appConfig;
window.getApiUrl = getApiUrl;
window.getBackendUrl = getBackendUrl;
window.initializeConfig = initializeConfig;
window.findWorkingProxyPaths = findWorkingProxyPaths;
window.getCurrentProxyUrl = getCurrentProxyUrl;
window.rotateToNextProxy = rotateToNextProxy;
