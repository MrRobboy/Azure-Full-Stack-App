// Startup script pour les applications Azure
// Ce script est exécuté au démarrage de l'application sur Azure App Service

var fs = require("fs");
var http = require("http");
var path = require("path");

// Log les informations du site au démarrage
console.log("Starting CORS configuration...");
console.log("Site root: " + process.env.HOME_SITE || "unknown");
console.log("Node version: " + process.version);

// Créer un middleware simple si nécessaire
function createCorsMiddleware() {
	return function (req, res, next) {
		// Ajouter les en-têtes CORS à toutes les réponses
		res.setHeader("Access-Control-Allow-Origin", "*");
		res.setHeader(
			"Access-Control-Allow-Methods",
			"GET, POST, PUT, DELETE, OPTIONS"
		);
		res.setHeader(
			"Access-Control-Allow-Headers",
			"Content-Type, Authorization, X-Requested-With"
		);
		res.setHeader("Access-Control-Max-Age", "86400");

		// Répondre immédiatement aux requêtes OPTIONS
		if (req.method === "OPTIONS") {
			res.statusCode = 204;
			res.end();
			return;
		}

		// Passer au middleware suivant
		if (next) next();
	};
}

// Créer un petit serveur pour le test CORS si nécessaire
function startCorsTestServer() {
	const port = process.env.PORT || 8080;

	const server = http.createServer((req, res) => {
		// Appliquer les en-têtes CORS
		res.setHeader("Access-Control-Allow-Origin", "*");
		res.setHeader(
			"Access-Control-Allow-Methods",
			"GET, POST, PUT, DELETE, OPTIONS"
		);
		res.setHeader(
			"Access-Control-Allow-Headers",
			"Content-Type, Authorization, X-Requested-With"
		);
		res.setHeader("Access-Control-Max-Age", "86400");

		// Gérer les requêtes OPTIONS
		if (req.method === "OPTIONS") {
			res.statusCode = 204;
			res.end();
			return;
		}

		// Pour les requêtes autres qu'OPTIONS
		const url = req.url;

		if (url === "/cors-test") {
			res.statusCode = 200;
			res.setHeader("Content-Type", "application/json");
			res.end(
				JSON.stringify({
					success: true,
					message: "CORS is working correctly",
					method: req.method,
					headers: req.headers,
					timestamp: new Date().toISOString()
				})
			);
		} else {
			// Rediriger les autres requêtes vers le gestionnaire PHP
			res.statusCode = 200;
			res.setHeader("Content-Type", "text/html");
			res.end(
				'<html><body><h1>CORS Test Server</h1><p>Try <a href="/cors-test">/cors-test</a> endpoint.</p></body></html>'
			);
		}
	});

	server.listen(port, () => {
		console.log(
			`CORS test server running at http://localhost:${port}/`
		);
	});
}

// Fonction principale
function main() {
	try {
		console.log("CORS configuration complete.");
		// startCorsTestServer(); // Décommenter pour tester
	} catch (err) {
		console.error("Error in startup script:", err);
	}
}

// Exécuter la fonction principale
main();
