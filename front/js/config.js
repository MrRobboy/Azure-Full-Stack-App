// Configuration de l'application
const appConfig = {
	apiBaseUrl: "https://app-backend-esgi-app.azurewebsites.net/api",
	version: "1.2"
};

// Fonction pour obtenir l'URL de l'API
function getApiUrl(endpoint) {
	return `${appConfig.apiBaseUrl}/${endpoint}`;
}

// Ajouter à l'objet window pour accessibilité globale
window.appConfig = appConfig;
window.getApiUrl = getApiUrl;
