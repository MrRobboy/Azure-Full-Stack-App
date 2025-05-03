// Fonction pour afficher les messages d'erreur
function showError(message) {
	const errorDiv = document.createElement("div");
	errorDiv.className = "error-message";
	errorDiv.innerHTML = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Erreur :</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;

	// Insérer le message d'erreur en haut de la page
	const mainContent = document.querySelector(".main-content");
	if (mainContent) {
		mainContent.insertBefore(errorDiv, mainContent.firstChild);
	}
}

// Fonction pour afficher les messages de succès
function showSuccess(message) {
	const successDiv = document.createElement("div");
	successDiv.className = "success-message";
	successDiv.innerHTML = `
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Succès :</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;

	// Insérer le message de succès en haut de la page
	const mainContent = document.querySelector(".main-content");
	if (mainContent) {
		mainContent.insertBefore(successDiv, mainContent.firstChild);
	}
}

// Fonction pour gérer les erreurs d'API
function handleApiError(error) {
	console.error("Erreur API:", error);

	let errorMessage = "Une erreur est survenue";

	if (
		error instanceof TypeError &&
		error.message.includes("Failed to fetch")
	) {
		errorMessage =
			"Impossible de se connecter au serveur. Veuillez vérifier votre connexion internet.";
	} else if (error.message === "Réponse invalide du serveur") {
		errorMessage =
			"Le serveur a renvoyé une réponse invalide. Veuillez réessayer plus tard.";
	} else if (error.message) {
		errorMessage = error.message;
	}

	showError(errorMessage);
}
