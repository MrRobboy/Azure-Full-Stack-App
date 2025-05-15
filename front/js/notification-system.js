// Système de notification centralisé
const NotificationSystem = {
	// Initialiser le système
	init() {
		console.log("Initialisation du système de notification...");
		this.createContainer();
		this.loaders = {};

		// Marquer le système comme initialisé
		this.initialized = true;

		// Tester si le système est correctement chargé
		this.verifyLoaded();
	},

	// Vérifier que le système est correctement chargé
	verifyLoaded() {
		const requiredFunctions = [
			"show",
			"error",
			"success",
			"info",
			"warning",
			"startLoader",
			"stopLoader"
		];
		const missingFunctions = [];

		requiredFunctions.forEach((func) => {
			if (typeof this[func] !== "function") {
				missingFunctions.push(func);
			}
		});

		if (missingFunctions.length > 0) {
			console.error(
				`NotificationSystem: Fonctions manquantes: ${missingFunctions.join(
					", "
				)}`
			);
			return false;
		}

		console.log(
			"NotificationSystem: Toutes les fonctions requises sont chargées"
		);
		return true;
	},

	// Créer le conteneur de notifications
	createContainer() {
		if (!document.querySelector(".notification-container")) {
			const container = document.createElement("div");
			container.className = "notification-container";
			document.body.appendChild(container);
			console.log("Conteneur de notifications créé");
		}
	},

	// Afficher une notification
	show(message, type = "info") {
		console.log(
			`Affichage d'une notification de type ${type}: ${message}`
		);

		const container = document.querySelector(
			".notification-container"
		);
		if (!container) {
			console.error("Conteneur de notifications non trouvé");
			return;
		}

		// Créer la notification
		const notification = document.createElement("div");
		notification.className = `notification ${type}`;

		let icon = "";
		switch (type) {
			case "success":
				icon = '<i class="fas fa-check-circle"></i>';
				break;
			case "error":
				icon =
					'<i class="fas fa-exclamation-circle"></i>';
				break;
			case "warning":
				icon =
					'<i class="fas fa-exclamation-triangle"></i>';
				break;
			case "info":
			default:
				icon = '<i class="fas fa-info-circle"></i>';
				break;
		}

		notification.innerHTML = `
            <span class="close">&times;</span>
            <div class="notification-content">
                ${icon}
                <p>${message}</p>
            </div>
        `;

		// Ajouter la notification au conteneur
		container.appendChild(notification);

		// Fermer la notification après 5 secondes sauf pour erreurs
		const timeout = type === "error" ? 8000 : 5000;
		setTimeout(() => {
			notification.classList.add("slideOut");
			setTimeout(() => notification.remove(), 300);
		}, timeout);

		// Fermer la notification au clic sur le bouton
		notification
			.querySelector(".close")
			.addEventListener("click", () => {
				notification.classList.add("slideOut");
				setTimeout(() => notification.remove(), 300);
			});

		return notification;
	},

	// Afficher une erreur
	error(message) {
		this.show(message, "error");
	},

	// Afficher un succès
	success(message) {
		this.show(message, "success");
	},

	// Afficher une information
	info(message) {
		this.show(message, "info");
	},

	// Afficher un avertissement
	warning(message) {
		this.show(message, "warning");
	},

	// Afficher un indicateur de chargement
	startLoader(id, message) {
		console.log(`Démarrage du loader: ${id} - ${message}`);

		if (this.loaders[id]) {
			// Si un loader avec cet ID existe déjà, le mettre à jour
			const loaderNotification = this.loaders[id];
			loaderNotification.querySelector("p").textContent =
				message;
		} else {
			// Créer un nouveau loader
			const container = document.querySelector(
				".notification-container"
			);
			if (!container) {
				console.error(
					"Conteneur de notifications non trouvé"
				);
				return;
			}

			const loaderNotification =
				document.createElement("div");
			loaderNotification.className = "notification loading";
			loaderNotification.innerHTML = `
				<div class="notification-content">
					<div class="loader-spinner"></div>
					<p>${message}</p>
				</div>
			`;

			container.appendChild(loaderNotification);
			this.loaders[id] = loaderNotification;
		}

		return id;
	},

	// Arrêter un indicateur de chargement
	stopLoader(id, successMessage = null) {
		console.log(`Arrêt du loader: ${id}`);

		if (this.loaders[id]) {
			const loaderNotification = this.loaders[id];
			loaderNotification.classList.add("slideOut");
			setTimeout(() => loaderNotification.remove(), 300);
			delete this.loaders[id];

			// Afficher un message de succès si fourni
			if (successMessage) {
				this.success(successMessage);
			}
		}
	}
};

// Styles pour les notifications
const style = document.createElement("style");
style.textContent = `
    .notification-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    .notification {
        position: relative;
        padding: 12px 15px;
        margin-bottom: 10px;
        border-radius: 4px;
        color: white;
        width: 300px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        animation: slideIn 0.3s ease-out;
        backdrop-filter: blur(5px);
    }
    
    .notification-content {
        display: flex;
        align-items: center;
    }
    
    .notification-content i {
        margin-right: 10px;
        font-size: 18px;
    }

    .notification.error {
        background-color: rgba(220, 53, 69, 0.95);
        border-left: 4px solid #bd2130;
    }

    .notification.success {
        background-color: rgba(40, 167, 69, 0.95);
        border-left: 4px solid #1e7e34;
    }

    .notification.info {
        background-color: rgba(23, 162, 184, 0.95);
        border-left: 4px solid #117a8b;
    }

    .notification.warning {
        background-color: rgba(255, 193, 7, 0.95);
        color: #212529;
        border-left: 4px solid #d39e00;
    }
    
    .notification.loading {
        background-color: rgba(108, 117, 125, 0.95);
        border-left: 4px solid #5a6268;
    }

    .notification .close {
        position: absolute;
        right: 10px;
        top: 10px;
        cursor: pointer;
        font-size: 20px;
        background: none;
        border: none;
        color: inherit;
        padding: 0;
    }
    
    .loader-spinner {
        width: 20px;
        height: 20px;
        margin-right: 10px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 1s ease-in-out infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .slideOut {
        animation: slideOut 0.3s ease-in forwards;
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Initialiser le système
NotificationSystem.init();

// Exporter le système
window.NotificationSystem = NotificationSystem;
