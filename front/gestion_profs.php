<?php
session_start();

if (!isset($_SESSION['prof_id'])) {
	header('Location: login.php');
	exit();
}

$pageTitle = "Gestion des Professeurs";
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

	input[type="text"],
	input[type="email"],
	input[type="password"],
	select {
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
		<h1>Gestion des Professeurs</h1>

		<div class="form-container">
			<h3>Ajouter un professeur</h3>
			<form id="addProfForm">
				<div class="form-row">
					<label for="nom">Nom :</label>
					<input type="text" name="nom" id="nom" required>
				</div>
				<div class="form-row">
					<label for="prenom">Prénom :</label>
					<input type="text" name="prenom" id="prenom" required>
				</div>
				<div class="form-row">
					<label for="email">Email :</label>
					<input type="email" name="email" id="email" required>
				</div>
				<div class="form-row">
					<label for="password">Mot de passe :</label>
					<input type="password" name="password" id="password" required>
				</div>
				<button type="submit" class="btn">Ajouter le professeur</button>
			</form>
		</div>

		<h3>Liste des professeurs</h3>
		<div class="table-responsive">
			<table class="table" id="profsTable">
				<thead>
					<tr>
						<th>Nom</th>
						<th>Prénom</th>
						<th>Email</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<!-- Les professeurs seront chargés dynamiquement -->
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

	// Fonction pour charger les professeurs
	async function loadProfs() {
		try {
			console.log('Chargement des professeurs...');
			const response = await fetch(getApiUrl('profs'));
			const result = await response.json();
			console.log('Résultat professeurs:', result);

			if (!result.success) {
				throw new Error(result.error || ErrorMessages.GENERAL.SERVER_ERROR);
			}

			const tbody = document.querySelector('#profsTable tbody');
			tbody.innerHTML = '';

			if (!result.data || result.data.length === 0) {
				tbody.innerHTML = '<tr><td colspan="4">Aucun professeur trouvé</td></tr>';
				return;
			}

			result.data.forEach(prof => {
				const tr = document.createElement('tr');
				tr.innerHTML = `
					<td>${prof.nom}</td>
					<td>${prof.prenom}</td>
					<td>${prof.email}</td>
					<td>
						<button class="btn btn-edit" onclick="editProf(${prof.id_prof}, '${prof.nom}', '${prof.prenom}', '${prof.email}')">Modifier</button>
						<button class="btn btn-danger" onclick="deleteProf(${prof.id_prof})">Supprimer</button>
					</td>
				`;
				tbody.appendChild(tr);
			});
		} catch (error) {
			console.error('Erreur lors du chargement des professeurs:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour ajouter un professeur
	document.getElementById('addProfForm').addEventListener('submit', async function(e) {
		e.preventDefault();
		const formData = {
			nom: document.getElementById('nom').value,
			prenom: document.getElementById('prenom').value,
			email: document.getElementById('email').value,
			password: document.getElementById('password').value
		};

		if (!formData.nom || !formData.prenom || !formData.email || !formData.password) {
			NotificationSystem.warning('Veuillez remplir tous les champs du formulaire');
			return;
		}

		try {
			const response = await fetch(getApiUrl('profs'), {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify(formData)
			});

			const result = await response.json();
			console.log('Résultat de la création:', result);

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors de l\'ajout du professeur');
			}

			document.getElementById('addProfForm').reset();
			NotificationSystem.success('Le professeur a été ajouté avec succès');
			loadProfs();
		} catch (error) {
			console.error('Erreur lors de l\'ajout du professeur:', error);
			NotificationSystem.error(error.message);
		}
	});

	// Fonction pour modifier un professeur
	async function editProf(id, currentNom, currentPrenom, currentEmail) {
		const modal = document.createElement('div');
		modal.className = 'modal';
		modal.innerHTML = `
			<div class="modal-content">
				<h3>Modifier le professeur</h3>
				<form id="editProfForm">
					<div class="form-row">
						<label for="edit_nom">Nom :</label>
						<input type="text" id="edit_nom" value="${currentNom}" required>
					</div>
					<div class="form-row">
						<label for="edit_prenom">Prénom :</label>
						<input type="text" id="edit_prenom" value="${currentPrenom}" required>
					</div>
					<div class="form-row">
						<label for="edit_email">Email :</label>
						<input type="email" id="edit_email" value="${currentEmail}" required>
					</div>
					<div class="form-row">
						<label for="edit_password">Nouveau mot de passe (optionnel) :</label>
						<input type="password" id="edit_password">
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
		document.getElementById('editProfForm').addEventListener('submit', async function(e) {
			e.preventDefault();
			const formData = {
				nom: document.getElementById('edit_nom').value,
				prenom: document.getElementById('edit_prenom').value,
				email: document.getElementById('edit_email').value
			};

			const newPassword = document.getElementById('edit_password').value;
			if (newPassword) {
				formData.password = newPassword;
			}

			if (!formData.nom || !formData.prenom || !formData.email) {
				NotificationSystem.warning('Veuillez remplir tous les champs obligatoires');
				return;
			}

			try {
				const response = await fetch(`${getApiUrl('profs')}/${id}`, {
					method: 'PUT',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify(formData)
				});

				const result = await response.json();

				if (!result.success) {
					throw new Error(result.error || 'Erreur lors de la modification du professeur');
				}

				closeModal();
				NotificationSystem.success('Le professeur a été modifié avec succès');
				loadProfs();
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

	// Fonction pour supprimer un professeur
	async function deleteProf(id) {
		if (!confirm('Êtes-vous sûr de vouloir supprimer ce professeur ?')) {
			return;
		}

		try {
			console.log('Tentative de suppression du professeur:', id);
			const response = await fetch(`${getApiUrl('profs')}/${id}`, {
				method: 'DELETE',
				headers: {
					'Content-Type': 'application/json'
				}
			});

			const result = await response.json();
			console.log('Résultat de la suppression:', result);

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors de la suppression du professeur');
			}

			NotificationSystem.success('Le professeur a été supprimé avec succès');
			loadProfs();
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
		NotificationSystem.info('Bienvenue sur la page de gestion des professeurs');

		loadProfs();
	});
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>