<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['token'])) {
	header('Location: login.php');
	exit;
}

// Get user data
$user = $_SESSION['user'];

// Check if user has admin role
if ($user['role'] !== 'admin') {
	// Redirect non-admin users
	header('Location: dashboard.php');
	exit;
}

$pageTitle = "Gestion des Matières";
ob_start();
?>

<head>
	<title><?php echo $pageTitle; ?></title>
	<link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
	<link rel="stylesheet" href="css/styles.css">
</head>

<style>
	.container {
		max-width: 1200px;
		margin: 0 auto;
		padding: 20px;
	}

	.main-content {
		background: white;
		padding: 20px;
		border-radius: 8px;
		box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
	}

	h1 {
		color: #333;
		margin-bottom: 20px;
	}

	.form-container {
		margin-bottom: 30px;
		padding: 20px;
		background: #f8f9fa;
		border-radius: 8px;
	}

	.form-row {
		margin-bottom: 15px;
	}

	label {
		display: block;
		margin-bottom: 5px;
		color: #555;
	}

	input[type="text"] {
		width: 100%;
		padding: 8px;
		border: 1px solid #ddd;
		border-radius: 4px;
		font-size: 14px;
	}

	.btn {
		background: #007bff;
		color: white;
		padding: 10px 20px;
		border: none;
		border-radius: 4px;
		cursor: pointer;
		font-size: 14px;
		transition: background 0.3s;
	}

	.btn:hover {
		background: #0056b3;
	}

	.table-responsive {
		overflow-x: auto;
	}

	.table {
		width: 100%;
		border-collapse: collapse;
		margin-top: 20px;
	}

	.table th,
	.table td {
		padding: 12px;
		text-align: left;
		border-bottom: 1px solid #ddd;
	}

	.table th {
		background: #f8f9fa;
		font-weight: 600;
	}

	.btn-edit {
		background: #28a745;
		margin-right: 5px;
	}

	.btn-edit:hover {
		background: #218838;
	}

	.btn-danger {
		background: #dc3545;
	}

	.btn-danger:hover {
		background: #c82333;
	}

	.modal {
		position: fixed;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background-color: rgba(0, 0, 0, 0.5);
		z-index: 1000;
	}

	.modal-content {
		background: white;
		padding: 20px;
		border-radius: 8px;
		width: 90%;
		max-width: 500px;
		box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
	}

	.modal-content h3 {
		margin-top: 0;
		color: #333;
	}

	.form-actions {
		display: flex;
		justify-content: flex-end;
		gap: 10px;
		margin-top: 20px;
	}

	.btn-secondary {
		background: #6c757d;
		color: white;
	}

	.btn-secondary:hover {
		background: #5a6268;
	}
</style>

<div class="container">
	<div class="main-content">
		<h1>Gestion des Matières</h1>

		<div class="form-container">
			<h3>Ajouter une matière</h3>
			<form id="addMatiereForm">
				<div class="form-row">
					<label for="nom">Nom de la matière :</label>
					<input type="text" name="nom" id="nom" required>
				</div>
				<button type="submit" class="btn">Ajouter la matière</button>
			</form>
		</div>

		<h3>Liste des matières</h3>
		<div class="table-responsive">
			<table class="table" id="matieresTable">
				<thead>
					<tr>
						<th>Nom</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<!-- Les matières seront chargées dynamiquement -->
				</tbody>
			</table>
		</div>
	</div>
</div>

<script src="js/notification-system.js?v=1.1"></script>
<script src="js/error-messages.js"></script>
<script src="js/config.js?v=1.9"></script>
<script>
	// Function to get API endpoint with proxy support
	function getApiEndpoint(endpoint) {
		return `${appConfig.proxyUrl}?endpoint=${encodeURIComponent(endpoint)}`;
	}

	// Vérifier que les scripts sont chargés
	console.log('Vérification du chargement des scripts...');
	console.log('NotificationSystem:', typeof NotificationSystem);
	console.log('ErrorMessages:', typeof ErrorMessages);

	if (typeof NotificationSystem === 'undefined') {
		console.error('Le script notification-system.js n\'est pas chargé correctement');
	}

	if (typeof ErrorMessages === 'undefined') {
		console.error('Le script error-messages.js n\'est pas chargé correctement');
	}

	// Fonction pour charger les matières
	async function loadMatieres() {
		console.log('Chargement des matières...');
		const fallbackData = [{
				id_matiere: 1,
				nom: "Mathématiques"
			},
			{
				id_matiere: 2,
				nom: "Français"
			},
			{
				id_matiere: 3,
				nom: "Anglais"
			},
			{
				id_matiere: 4,
				nom: "Histoire-Géographie"
			},
			{
				id_matiere: 16,
				nom: "Docker"
			},
			{
				id_matiere: 17,
				nom: "Azure"
			}
		];

		try {
			// Try each method in sequence until one works
			let response, result;
			let methodUsed = "";

			// Method 1: Try using the special matieres-proxy
			try {
				console.log('Tentative avec matieres-proxy.php...');
				response = await fetch('matieres-proxy.php');
				result = await response.json();
				console.log('Résultat avec matieres-proxy:', result);
				methodUsed = "matieres-proxy.php";
			} catch (proxyError) {
				console.log('Échec avec matieres-proxy, tentative avec proxy unifié');

				// Method 2: Try using the unified proxy
				try {
					response = await fetch(getApiEndpoint('matieres'));
					result = await response.json();
					console.log('Résultat matières avec unified-proxy:', result);
					methodUsed = "unified-proxy";
				} catch (unifiedError) {
					console.log('Échec avec unified-proxy, tentative avec direct-matieres.php');

					// Method 3: Try using direct endpoint as last resort
					try {
						response = await fetch('direct-matieres.php');
						result = await response.json();
						console.log('Résultat avec direct-matieres:', result);
						methodUsed = "direct-matieres.php";
					} catch (directError) {
						console.error('Tous les endpoints ont échoué, utilisation des données en dur');
						// Use the in-code fallback data if all methods fail
						result = {
							success: true,
							data: fallbackData,
							message: 'Utilisation des données en dur (tous les endpoints ont échoué)',
							is_fallback: true
						};
						methodUsed = "hardcoded fallback";
					}
				}
			}

			// Check for valid response format
			if (!result || typeof result !== 'object') {
				console.error('Format de réponse invalide:', result);
				throw new Error(ErrorMessages.GENERAL.INVALID_RESPONSE);
			}

			// Check for empty data array
			if (!result.data || !Array.isArray(result.data)) {
				console.warn('Données manquantes ou format invalide, utilisation des données de secours');

				// Use fallback data if the response doesn't contain a data array
				result = {
					success: true,
					data: fallbackData,
					message: 'Utilisation des données de secours (format de réponse invalide)',
					is_fallback: true
				};

				// Show a notification
				NotificationSystem.warning('Utilisation des données de secours (format de réponse invalide)');
			}

			// Populate the table
			const tbody = document.querySelector('#matieresTable tbody');
			tbody.innerHTML = '';

			if (result.data.length === 0) {
				tbody.innerHTML = '<tr><td colspan="2">Aucune matière trouvée</td></tr>';
				return;
			}

			// If fallback was used, show a notification
			if (result.is_fallback || result.is_direct) {
				NotificationSystem.info('Source des données: ' + methodUsed + ' - ' + (result.message || 'API non disponible'));
			}

			result.data.forEach(matiere => {
				const tr = document.createElement('tr');
				tr.innerHTML = `
					<td>${matiere.nom}</td>
					<td>
						<button class="btn btn-edit" onclick="editMatiere(${matiere.id_matiere}, '${matiere.nom}')">Modifier</button>
						<button class="btn btn-danger" onclick="deleteMatiere(${matiere.id_matiere})">Supprimer</button>
					</td>
				`;
				tbody.appendChild(tr);
			});
		} catch (error) {
			console.error('Erreur lors du chargement des matières:', error);
			NotificationSystem.error('Erreur: ' + error.message);

			// Show fallback data in case of error
			const tbody = document.querySelector('#matieresTable tbody');
			tbody.innerHTML = '';

			fallbackData.forEach(matiere => {
				const tr = document.createElement('tr');
				tr.innerHTML = `
					<td>${matiere.nom}</td>
					<td>
						<button class="btn btn-edit" onclick="editMatiere(${matiere.id_matiere}, '${matiere.nom}')">Modifier</button>
						<button class="btn btn-danger" onclick="deleteMatiere(${matiere.id_matiere})">Supprimer</button>
					</td>
				`;
				tbody.appendChild(tr);
			});

			NotificationSystem.warning('Affichage des données de secours suite à une erreur. Fonctionnalités limitées.');
		}
	}

	// Fonction pour ajouter une matière
	document.getElementById('addMatiereForm').addEventListener('submit', async function(e) {
		e.preventDefault();
		const nom = document.getElementById('nom').value;

		if (!nom) {
			NotificationSystem.warning('Veuillez entrer un nom pour la matière');
			return;
		}

		try {
			console.log('Ajout d\'une nouvelle matière:', nom);

			const response = await fetch(getApiEndpoint('matieres'), {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					nom
				})
			});

			// Check if response is OK
			if (!response.ok) {
				throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
			}

			const responseText = await response.text();
			let result;

			try {
				result = JSON.parse(responseText);
			} catch (e) {
				console.error('Réponse invalide (non-JSON):', responseText);
				throw new Error('Réponse invalide du serveur: ' + responseText.substring(0, 100));
			}

			console.log('Résultat de la création:', result);

			if (!result.success) {
				throw new Error(result.message || result.error || 'Erreur lors de l\'ajout de la matière');
			}

			document.getElementById('nom').value = '';
			NotificationSystem.success('La matière a été ajoutée avec succès');
			loadMatieres();
		} catch (error) {
			console.error('Erreur lors de l\'ajout de la matière:', error);
			NotificationSystem.error(error.message);
		}
	});

	// Fonction pour modifier une matière
	async function editMatiere(id, currentNom) {
		const modal = document.createElement('div');
		modal.className = 'modal';
		modal.innerHTML = `
			<div class="modal-content">
				<h3>Modifier la matière</h3>
				<form id="editMatiereForm">
					<div class="form-row">
						<label for="editNom">Nom :</label>
						<input type="text" id="editNom" value="${currentNom}" required>
					</div>
					<div class="form-actions">
						<button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
						<button type="submit" class="btn">Enregistrer</button>
					</div>
				</form>
			</div>
		`;

		document.body.appendChild(modal);

		// Gérer la soumission du formulaire
		document.getElementById('editMatiereForm').addEventListener('submit', async function(e) {
			e.preventDefault();
			const newNom = document.getElementById('editNom').value;

			if (!newNom) {
				NotificationSystem.warning('Veuillez entrer un nom pour la matière');
				return;
			}

			try {
				console.log(`Modification de la matière ID ${id}: ${newNom}`);

				const response = await fetch(getApiEndpoint(`matieres/${id}`), {
					method: 'PUT',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						nom: newNom
					})
				});

				// Check if response is OK
				if (!response.ok) {
					throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
				}

				const responseText = await response.text();
				let result;

				try {
					result = JSON.parse(responseText);
				} catch (e) {
					console.error('Réponse invalide (non-JSON):', responseText);
					throw new Error('Réponse invalide du serveur: ' + responseText.substring(0, 100));
				}

				if (!result.success) {
					throw new Error(result.error || result.message || 'Erreur lors de la modification de la matière');
				}

				closeModal();
				NotificationSystem.success('La matière a été modifiée avec succès');
				loadMatieres();
			} catch (error) {
				console.error('Erreur lors de la modification:', error);
				NotificationSystem.error(error.message);
			}
		});
	}

	// Fonction pour fermer le modal
	function closeModal() {
		const modal = document.querySelector('.modal');
		if (modal) {
			modal.remove();
		}
	}

	// Fonction pour supprimer une matière
	async function deleteMatiere(id) {
		if (!confirm('Êtes-vous sûr de vouloir supprimer cette matière ?')) {
			return;
		}

		try {
			console.log('Tentative de suppression de la matière:', id);

			const response = await fetch(getApiEndpoint(`matieres/${id}`), {
				method: 'DELETE',
				headers: {
					'Content-Type': 'application/json'
				}
			});

			// Check if response is OK
			if (!response.ok) {
				throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
			}

			const responseText = await response.text();
			let result;

			try {
				result = JSON.parse(responseText);
			} catch (e) {
				console.error('Réponse invalide (non-JSON):', responseText);
				throw new Error('Réponse invalide du serveur: ' + responseText.substring(0, 100));
			}

			if (result.success) {
				NotificationSystem.success(result.message || 'La matière a été supprimée avec succès');
				// Attendre un court instant avant de recharger les matières
				await new Promise(resolve => setTimeout(resolve, 500));
				await loadMatieres();
			} else {
				throw new Error(result.message || result.error || 'Erreur lors de la suppression de la matière');
			}
		} catch (error) {
			console.error('Erreur lors de la suppression:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Charger les données au chargement de la page
	document.addEventListener('DOMContentLoaded', function() {
		console.log('Chargement de la page...');

		// Tester le système de notification
		console.log('Test du système de notification...');
		NotificationSystem.info('Bienvenue sur la page de gestion des matières');

		loadMatieres();
	});
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>