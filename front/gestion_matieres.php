<?php
session_start();

if (!isset($_SESSION['prof_id'])) {
	header('Location: login.php');
	exit();
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

<script src="js/notification-system.js"></script>
<script src="js/error-messages.js"></script>
<script src="js/config.js"></script>
<script>
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
		try {
			console.log('Chargement des matières...');
			const response = await fetch(getApiUrl('matieres'));
			const result = await response.json();
			console.log('Résultat matières:', result);

			if (!result.success) {
				throw new Error(result.error || ErrorMessages.GENERAL.SERVER_ERROR);
			}

			const tbody = document.querySelector('#matieresTable tbody');
			tbody.innerHTML = '';

			if (!result.data || result.data.length === 0) {
				tbody.innerHTML = '<tr><td colspan="2">Aucune matière trouvée</td></tr>';
				return;
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
			NotificationSystem.error(error.message);
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
			const response = await fetch(getApiUrl('matieres'), {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					nom
				})
			});

			const result = await response.json();
			console.log('Résultat de la création:', result);

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors de l\'ajout de la matière');
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
				const response = await fetch(`${getApiUrl('matieres')}/${id}`, {
					method: 'PUT',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						nom: newNom
					})
				});

				const result = await response.json();

				if (!result.success) {
					throw new Error(result.error || 'Erreur lors de la modification de la matière');
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
			const response = await fetch(`${getApiUrl('matieres')}/${id}`, {
				method: 'DELETE',
				headers: {
					'Content-Type': 'application/json'
				}
			});

			const result = await response.json();
			console.log('Résultat de la suppression:', result);

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors de la suppression de la matière');
			}

			NotificationSystem.success('La matière a été supprimée avec succès');
			loadMatieres();
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