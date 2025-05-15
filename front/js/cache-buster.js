// Script pour forcer le rechargement des scripts en cas de mises à jour
(function () {
	console.log("Initialisation du cache-buster...");

	// Version actuelle de l'application
	const APP_VERSION = "1.1.0";

	// Vérifier la version précédemment chargée
	const previousVersion = localStorage.getItem("app_version");

	// Si la version a changé ou n'existe pas encore, forcer le rechargement des caches
	if (!previousVersion || previousVersion !== APP_VERSION) {
		console.log(
			`Nouvelle version détectée: ${APP_VERSION} (précédente: ${
				previousVersion || "aucune"
			})`
		);

		// Essayer de vider le cache des applications
		if ("caches" in window) {
			caches.keys().then((cacheNames) => {
				cacheNames.forEach((cacheName) => {
					caches.delete(cacheName).then(() => {
						console.log(
							`Cache '${cacheName}' supprimé`
						);
					});
				});
			});
		}

		// Stocker la nouvelle version
		localStorage.setItem("app_version", APP_VERSION);

		// Forcer un rechargement de la page sans cache
		if (!sessionStorage.getItem("cache_cleared")) {
			sessionStorage.setItem("cache_cleared", "true");
			console.log(
				"Rechargement de la page pour vider le cache..."
			);
			window.location.reload(true);
		}
	} else {
		console.log(`Application à jour: version ${APP_VERSION}`);
	}

	// Fonction utilitaire pour charger un script avec un paramètre de version
	window.loadScript = function (src, callback) {
		// Ajouter un paramètre de version pour éviter la mise en cache
		const timestamp = new Date().getTime();
		const fullSrc = src.includes("?")
			? `${src}&v=${timestamp}`
			: `${src}?v=${timestamp}`;

		const script = document.createElement("script");
		script.src = fullSrc;
		script.onload = callback || function () {};
		script.onerror = function () {
			console.error(
				`Erreur lors du chargement du script: ${src}`
			);
		};

		document.head.appendChild(script);
		return script;
	};
})();
