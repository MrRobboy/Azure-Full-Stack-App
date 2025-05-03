// Système de notification centralisé
const NotificationSystem = {
	// Initialiser le système
	init() {
		console.log("Initialisation du système de notification...");
		this.createContainer();
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
		notification.innerHTML = `
            <span class="close">&times;</span>
            <p>${message}</p>
        `;

		// Ajouter la notification au conteneur
		container.appendChild(notification);

		// Fermer la notification après 5 secondes
		setTimeout(() => {
			notification.classList.add("slideOut");
			setTimeout(() => notification.remove(), 300);
		}, 5000);

		// Fermer la notification au clic sur le bouton
		notification
			.querySelector(".close")
			.addEventListener("click", () => {
				notification.classList.add("slideOut");
				setTimeout(() => notification.remove(), 300);
			});
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
        padding: 15px;
        margin-bottom: 10px;
        border-radius: 4px;
        color: white;
        width: 300px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        animation: slideIn 0.3s ease-out;
    }

    .notification.error {
        background-color: #dc3545;
    }

    .notification.success {
        background-color: #28a745;
    }

    .notification.info {
        background-color: #17a2b8;
    }

    .notification.warning {
        background-color: #ffc107;
        color: #212529;
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
