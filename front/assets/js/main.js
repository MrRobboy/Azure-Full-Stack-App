// Fonction pour afficher les messages d'erreur
function showError(message) {
	const alert = document.createElement("div");
	alert.className = "alert alert-danger";
	alert.innerHTML = `
        <div class="error-header">
            <i class="fas fa-exclamation-circle"></i>
            <span>Erreur</span>
        </div>
        <div class="error-content">${message}</div>
    `;
	document.querySelector("main.container").insertBefore(
		alert,
		document.querySelector("main.container").firstChild
	);
	setTimeout(() => alert.remove(), 5000);
}

// Fonction pour afficher les messages de succès
function showSuccess(message) {
	const alert = document.createElement("div");
	alert.className = "alert alert-success";
	alert.innerHTML = `
        <div class="error-header">
            <i class="fas fa-check-circle"></i>
            <span>Succès</span>
        </div>
        <div class="error-content">${message}</div>
    `;
	document.querySelector("main.container").insertBefore(
		alert,
		document.querySelector("main.container").firstChild
	);
	setTimeout(() => alert.remove(), 5000);
}

// Fonction pour gérer les erreurs de l'API
function handleApiError(error) {
	console.error("Erreur API:", error);
	showError(
		error.message ||
			"Une erreur est survenue lors de la communication avec le serveur"
	);
}

// Fonction pour vérifier si l'utilisateur est connecté
function checkAuth() {
	if (!document.cookie.includes("PHPSESSID")) {
		window.location.href = "login.php";
	}
}

// Vérifier l'authentification au chargement de la page
document.addEventListener("DOMContentLoaded", checkAuth);
