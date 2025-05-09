<?php
session_start();

if (!isset($_SESSION['prof_id'])) {
	header('Location: login.php');
	exit();
}

$pageTitle = "Gestion des Utilisateurs";
ob_start();
?>

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
		<h1>Gestion des Utilisateurs</h1>

		<div class="form-container">
			<h3>Ajouter un utilisateur</h3>
			<form id="addUserForm">
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
				<div class="form-row">
					<label for="classe">Classe :</label>
					<select name="classe" id="classe" required>
						<option value="">Sélectionnez une classe</option>
					</select>
				</div>
				<button type="submit" class="btn">Ajouter l'utilisateur</button>
			</form>
		</div>

		<div class="table-responsive">
			<table class="table">
				<thead>
					<tr>
						<th>ID</th>
						<th>Nom</th>
						<th>Prénom</th>
						<th>Email</th>
						<th>Classe</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody id="usersTableBody">
				</tbody>
			</table>
		</div>
	</div>
</div>

<!-- Modal pour l'édition -->
<div id="editModal" class="modal" style="display: none;">
	<div class="modal-content">
		<h3>Modifier l'utilisateur</h3>
		<form id="editUserForm">
			<input type="hidden" id="editUserId">
			<div class="form-row">
				<label for="editNom">Nom :</label>
				<input type="text" name="nom" id="editNom" required>
			</div>
			<div class="form-row">
				<label for="editPrenom">Prénom :</label>
				<input type="text" name="prenom" id="editPrenom" required>
			</div>
			<div class="form-row">
				<label for="editEmail">Email :</label>
				<input type="email" name="email" id="editEmail" required>
			</div>
			<div class="form-row">
				<label for="editPassword">Nouveau mot de passe :</label>
				<input type="password" name="password" id="editPassword">
				<small>Laissez vide pour ne pas modifier le mot de passe</small>
			</div>
			<div class="form-row">
				<label for="editClasse">Classe :</label>
				<select name="classe" id="editClasse" required>
					<option value="">Sélectionnez une classe</option>
				</select>
			</div>
			<div class="form-actions">
				<button type="button" class="btn btn-secondary" onclick="closeEditModal()">Annuler</button>
				<button type="submit" class="btn btn-edit">Enregistrer</button>
			</div>
		</form>
	</div>
</div>

<script src="/js/notification-system.js"></script>
<script>
	// Fonction pour charger les classes
	async function loadClasses() {
		try {
			const response = await fetch('/api/classes');
			const data = await response.json();
			if (data.success) {
				const classes = data.data;
				const classeSelect = document.getElementById('classe');
				const editClasseSelect = document.getElementById('editClasse');

				// Vider les selects
				classeSelect.innerHTML = '<option value="">Sélectionnez une classe</option>';
				editClasseSelect.innerHTML = '<option value="">Sélectionnez une classe</option>';

				// Ajouter les options
				classes.forEach(classe => {
					classeSelect.innerHTML += `<option value="${classe.id_classe}">${classe.nom_classe}</option>`;
					editClasseSelect.innerHTML += `<option value="${classe.id_classe}">${classe.nom_classe}</option>`;
				});
			}
		} catch (error) {
			console.error('Erreur lors du chargement des classes:', error);
			NotificationSystem.error('Erreur lors du chargement des classes');
		}
	}

	// Fonction pour charger les utilisateurs
	async function loadUsers() {
		try {
			const response = await fetch('/api/users');
			const data = await response.json();
			if (data.success) {
				const tbody = document.getElementById('usersTableBody');
				tbody.innerHTML = '';

				data.data.forEach(user => {
					tbody.innerHTML += `
						<tr>
							<td>${user.id_user}</td>
							<td>${user.nom}</td>
							<td>${user.prenom}</td>
							<td>${user.email}</td>
							<td>${user.nom_classe || 'Non assigné'}</td>
							<td>
								<button class="btn btn-edit" onclick="openEditModal(${JSON.stringify(user).replace(/"/g, '&quot;')})">Modifier</button>
								<button class="btn btn-danger" onclick="deleteUser(${user.id_user})">Supprimer</button>
							</td>
						</tr>
					`;
				});
			}
		} catch (error) {
			console.error('Erreur lors du chargement des utilisateurs:', error);
			NotificationSystem.error('Erreur lors du chargement des utilisateurs');
		}
	}

	// Fonction pour ajouter un utilisateur
	document.getElementById('addUserForm').addEventListener('submit', async function(e) {
		e.preventDefault();

		const formData = {
			nom: document.getElementById('nom').value,
			prenom: document.getElementById('prenom').value,
			email: document.getElementById('email').value,
			password: document.getElementById('password').value,
			classe: document.getElementById('classe').value
		};

		try {
			const response = await fetch('/api/users', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify(formData)
			});

			const data = await response.json();
			if (data.success) {
				NotificationSystem.success('Utilisateur ajouté avec succès');
				this.reset();
				loadUsers();
			} else {
				NotificationSystem.error(data.message || 'Erreur lors de l\'ajout de l\'utilisateur');
			}
		} catch (error) {
			console.error('Erreur lors de l\'ajout de l\'utilisateur:', error);
			NotificationSystem.error('Erreur lors de l\'ajout de l\'utilisateur');
		}
	});

	// Fonction pour ouvrir le modal d'édition
	function openEditModal(user) {
		document.getElementById('editUserId').value = user.id_user;
		document.getElementById('editNom').value = user.nom;
		document.getElementById('editPrenom').value = user.prenom;
		document.getElementById('editEmail').value = user.email;
		document.getElementById('editClasse').value = user.classe || '';
		document.getElementById('editModal').style.display = 'block';
	}

	// Fonction pour fermer le modal d'édition
	function closeEditModal() {
		document.getElementById('editModal').style.display = 'none';
	}

	// Fonction pour modifier un utilisateur
	document.getElementById('editUserForm').addEventListener('submit', async function(e) {
		e.preventDefault();

		const userId = document.getElementById('editUserId').value;
		const formData = {
			nom: document.getElementById('editNom').value,
			prenom: document.getElementById('editPrenom').value,
			email: document.getElementById('editEmail').value,
			classe: document.getElementById('editClasse').value
		};

		// Ajouter le mot de passe seulement s'il a été modifié
		const password = document.getElementById('editPassword').value;
		if (password) {
			formData.password = password;
		}

		try {
			const response = await fetch(`/api/users/${userId}`, {
				method: 'PUT',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify(formData)
			});

			const data = await response.json();
			if (data.success) {
				NotificationSystem.success('Utilisateur modifié avec succès');
				closeEditModal();
				loadUsers();
			} else {
				NotificationSystem.error(data.message || 'Erreur lors de la modification de l\'utilisateur');
			}
		} catch (error) {
			console.error('Erreur lors de la modification de l\'utilisateur:', error);
			NotificationSystem.error('Erreur lors de la modification de l\'utilisateur');
		}
	});

	// Fonction pour supprimer un utilisateur
	async function deleteUser(userId) {
		if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
			return;
		}

		try {
			const response = await fetch(`/api/users/${userId}`, {
				method: 'DELETE'
			});

			const data = await response.json();
			if (data.success) {
				NotificationSystem.success('Utilisateur supprimé avec succès');
				loadUsers();
			} else {
				NotificationSystem.error(data.message || 'Erreur lors de la suppression de l\'utilisateur');
			}
		} catch (error) {
			console.error('Erreur lors de la suppression de l\'utilisateur:', error);
			NotificationSystem.error('Erreur lors de la suppression de l\'utilisateur');
		}
	}

	// Charger les données au chargement de la page
	document.addEventListener('DOMContentLoaded', function() {
		console.log('Chargement de la page...');
		NotificationSystem.info('Bienvenue sur la page de gestion des utilisateurs');
		loadClasses();
		loadUsers();
	});
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>