// Fonction pour afficher un message d'erreur
function showError(message) {
	const errorContainer = document.getElementById("error-container");
	if (!errorContainer) {
		const container = document.createElement("div");
		container.id = "error-container";
		container.className = "alert alert-error";
		document.querySelector(".main-content").insertBefore(
			container,
			document.querySelector(".main-content").firstChild
		);
	}

	const errorDiv = document.getElementById("error-container");
	errorDiv.textContent = message;
	errorDiv.style.display = "block";

	// Faire défiler jusqu'au message d'erreur
	errorDiv.scrollIntoView({ behavior: "smooth", block: "start" });

	// Masquer le message après 5 secondes
	setTimeout(() => {
		errorDiv.style.display = "none";
	}, 5000);
}

// Fonction pour afficher un message de succès
function showSuccess(message) {
	const successContainer = document.getElementById("success-container");
	if (!successContainer) {
		const container = document.createElement("div");
		container.id = "success-container";
		container.className = "alert alert-success";
		document.querySelector(".main-content").insertBefore(
			container,
			document.querySelector(".main-content").firstChild
		);
	}

	const successDiv = document.getElementById("success-container");
	successDiv.textContent = message;
	successDiv.style.display = "block";

	// Faire défiler jusqu'au message de succès
	successDiv.scrollIntoView({ behavior: "smooth", block: "start" });

	// Masquer le message après 5 secondes
	setTimeout(() => {
		successDiv.style.display = "none";
	}, 5000);
}

// Fonction pour gérer les erreurs d'API
function handleApiError(error) {
	console.error("Erreur API:", error);

	// Si l'erreur est une réponse du serveur
	if (error.response) {
		showError(
			`Erreur serveur: ${error.response.status} - ${error.response.statusText}`
		);
	}
	// Si l'erreur est un message personnalisé
	else if (error.message) {
		showError(error.message);
	}
	// Si c'est une autre type d'erreur
	else {
		showError(
			"Une erreur inattendue est survenue. Veuillez réessayer."
		);
	}
}

// Fonction pour gérer les erreurs de validation
function handleValidationError(errors) {
	if (Array.isArray(errors)) {
		showError(errors.join("\n"));
	} else if (typeof errors === "object") {
		const errorMessages = Object.values(errors).flat();
		showError(errorMessages.join("\n"));
	} else {
		showError(errors.toString());
	}
}

// Fonction pour logger les erreurs côté client
function logClientError(context, error) {
	console.error(`[${context}] Erreur:`, error);

	// On pourrait envoyer les erreurs à un service de logging côté serveur
	/*
	fetch('/api/log-error', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json'
		},
		body: JSON.stringify({
			context,
			error: error.message,
			stack: error.stack,
			timestamp: new Date().toISOString()
		})
	}).catch(console.error);
	*/
}
