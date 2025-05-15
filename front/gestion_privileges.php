<?php
session_start();

// Redirection vers la page de connexion si non connecté
if (!isset($_SESSION['prof_id'])) {
	header('Location: login.php');
	exit();
}

$pageTitle = "Gestion des Privilèges";
require_once 'templates/base.php';
?>

<div class="container">
	<div class="main-content">
		<div class="page-header">
			<h1>Gestion des Privilèges</h1>
			<p class="subtitle">Définir les notes minimales pour certains étudiants</p>
		</div>

		<div class="card mb-4">
			<div class="card-header">
				<h3>Étudiants avec privilèges</h3>
			</div>
			<div class="card-body">
				<div id="privileges-list">
					<p class="loading">Chargement des privilèges...</p>
				</div>
			</div>
		</div>

		<div class="card">
			<div class="card-header">
				<h3>Ajouter ou modifier un privilège</h3>
			</div>
			<div class="card-body">
				<form id="privilege-form">
					<div class="form-group">
						<label for="classe">Classe :</label>
						<select name="classe" id="classe" class="form-control" required>
							<option value="">Sélectionnez une classe</option>
						</select>
					</div>

					<div class="form-group">
						<label for="etudiant">Étudiant :</label>
						<select name="etudiant" id="etudiant" class="form-control" required>
							<option value="">Sélectionnez un étudiant</option>
						</select>
					</div>

					<div class="form-group">
						<label for="min_note">Note minimale :</label>
						<input type="number" name="min_note" id="min_note" class="form-control" min="0" max="20" step="0.5" value="10" required>
						<small class="form-text text-muted">L'étudiant ne pourra pas recevoir de note inférieure à cette valeur.</small>
					</div>

					<button type="submit" class="btn btn-primary">Ajouter/Modifier le privilège</button>
				</form>
			</div>
		</div>
	</div>
</div>

<style>
	.subtitle {
		color: #6c757d;
		font-size: 1.1rem;
		margin-bottom: 20px;
	}

	.privilege-student {
		color: #007bff;
		font-weight: bold;
	}

	.privileges-table {
		width: 100%;
		border-collapse: collapse;
		margin-bottom: 20px;
	}

	.privileges-table th,
	.privileges-table td {
		padding: 10px;
		border-bottom: 1px solid #dee2e6;
		text-align: left;
	}

	.privileges-table th {
		background-color: #f8f9fa;
		font-weight: 600;
		color: #495057;
	}

	.privileges-table tr:hover {
		background-color: #f8f9fa;
	}

	.loading {
		color: #6c757d;
		font-style: italic;
	}

	.form-group {
		margin-bottom: 20px;
	}

	.btn-action {
		padding: 5px 10px;
		margin-right: 5px;
		font-size: 0.8rem;
	}

	.btn-danger {
		background-color: #dc3545;
		color: white;
		border: none;
	}

	.btn-danger:hover {
		background-color: #c82333;
	}

	.no-privileges {
		font-style: italic;
		color: #6c757d;
		padding: 20px 0;
	}
</style>

<script src="js/notification-system.js?v=1.1"></script>
<script src="js/error-messages.js"></script>
<script src="js/config.js?v=1.1"></script>
<script>
	// Vérifier que le système de notification est bien chargé
	console.log('Système de notification disponible:', typeof NotificationSystem !== 'undefined');
	if (typeof NotificationSystem === 'undefined') {
		console.error('Le système de notification n\'est pas chargé !');
	}

	// Initialisation
	console.log('🚀 Initialisation de la page de gestion des privilèges');

	// Fonction pour charger les classes
	async function loadClasses() {
		try {
			console.log('Chargement des classes...');
			const response = await fetch(getApiUrl('classes'), {
				headers: {
					'Accept': 'application/json',
					'Content-Type': 'application/json'
				},
				credentials: 'include'
			});

			if (!response.ok) {
				throw new Error(`Erreur HTTP: ${response.status}`);
			}

			const result = await response.json();
			if (!result.success) {
				throw new Error(result.message || 'Erreur lors du chargement des classes');
			}

			console.log('Classes reçues:', result.data);
			const select = document.getElementById('classe');
			select.innerHTML = '<option value="">Sélectionnez une classe</option>';

			if (result.data && Array.isArray(result.data)) {
				result.data.forEach(classe => {
					const option = document.createElement('option');
					option.value = classe.id_classe;
					option.textContent = classe.nom_classe;
					select.appendChild(option);
				});
			}

			// Ajouter un écouteur d'événements pour charger les étudiants lors du changement de classe
			select.addEventListener('change', function() {
				if (this.value) {
					loadStudents(this.value);
				} else {
					const etudiantSelect = document.getElementById('etudiant');
					etudiantSelect.innerHTML = '<option value="">Sélectionnez un étudiant</option>';
				}
			});
		} catch (error) {
			console.error('Erreur lors du chargement des classes:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour charger les étudiants d'une classe
	async function loadStudents(classeId) {
		try {
			console.log('Chargement des étudiants pour la classe:', classeId);
			const response = await fetch(getApiUrl(`users/classe/${classeId}`), {
				headers: {
					'Accept': 'application/json',
					'Content-Type': 'application/json'
				},
				credentials: 'include'
			});

			if (!response.ok) {
				throw new Error(`Erreur HTTP: ${response.status}`);
			}

			const result = await response.json();
			if (!result.success) {
				throw new Error(result.message || 'Erreur lors du chargement des étudiants');
			}

			console.log('Étudiants reçus:', result.data);
			const select = document.getElementById('etudiant');
			select.innerHTML = '<option value="">Sélectionnez un étudiant</option>';

			if (result.data && Array.isArray(result.data)) {
				result.data.forEach(etudiant => {
					const option = document.createElement('option');
					option.value = etudiant.id_user;
					option.textContent = `${etudiant.nom} ${etudiant.prenom}`;

					// Vérifier si cet étudiant a déjà un privilège
					if (window.privilegesMap && window.privilegesMap[etudiant.id_user]) {
						option.classList.add('privilege-student');
						option.textContent = `★ ${etudiant.nom} ${etudiant.prenom}`;
					}

					select.appendChild(option);
				});
			}
		} catch (error) {
			console.error('Erreur lors du chargement des étudiants:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour charger tous les privilèges existants
	async function loadPrivileges() {
		try {
			console.log('Chargement des privilèges...');
			const response = await fetch(getApiUrl('privileges') + '/students', {
				headers: {
					'Accept': 'application/json',
					'Content-Type': 'application/json'
				},
				credentials: 'include'
			});

			if (!response.ok) {
				if (response.status === 404) {
					console.warn('API de privilèges non disponible (404): cela pourrait être normal si cette fonctionnalité n\'est pas encore implémentée côté serveur');
					document.getElementById('privileges-list').innerHTML = '<div class="no-privileges">Aucun privilège configuré pour le moment.</div>';
					return;
				}
				throw new Error(`Erreur HTTP: ${response.status}`);
			}

			const result = await response.json();
			if (!result.success) {
				throw new Error(result.message || 'Erreur lors du chargement des privilèges');
			}

			console.log('Privilèges reçus:', result.data);

			// Créer une map des privilèges pour un accès facile par ID d'étudiant
			window.privilegesMap = {};
			if (result.data && Array.isArray(result.data)) {
				result.data.forEach(privilege => {
					window.privilegesMap[privilege.id_user] = privilege.min_note;
				});
			}

			// Afficher les privilèges dans le tableau
			const privilegesList = document.getElementById('privileges-list');

			if (!result.data || !Array.isArray(result.data) || result.data.length === 0) {
				privilegesList.innerHTML = '<div class="no-privileges">Aucun privilège configuré pour le moment.</div>';
				return;
			}

			const table = document.createElement('table');
			table.className = 'privileges-table';
			table.innerHTML = `
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Note minimale</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            `;

			result.data.forEach(privilege => {
				const tr = document.createElement('tr');
				tr.innerHTML = `
                    <td>${privilege.prenom} ${privilege.nom}</td>
                    <td>${privilege.min_note}</td>
                    <td>
                        <button class="btn btn-action btn-danger" onclick="removePrivilege(${privilege.id_user})">Supprimer</button>
                    </td>
                `;
				table.querySelector('tbody').appendChild(tr);
			});

			privilegesList.innerHTML = '';
			privilegesList.appendChild(table);
		} catch (error) {
			console.error('Erreur lors du chargement des privilèges:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour ajouter ou modifier un privilège
	async function addPrivilege(event) {
		event.preventDefault();
		const form = event.target;
		const formData = new FormData(form);

		const studentId = formData.get('etudiant');
		const minNote = formData.get('min_note');

		if (!studentId || !minNote) {
			NotificationSystem.warning('Veuillez sélectionner un étudiant et spécifier une note minimale');
			return;
		}

		try {
			console.log(`Ajout/modification du privilège pour l'étudiant ${studentId} avec une note minimale de ${minNote}`);

			const response = await fetch(getApiUrl('privileges'), {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'Accept': 'application/json'
				},
				credentials: 'include',
				body: JSON.stringify({
					id_user: studentId,
					min_note: minNote
				})
			});

			if (!response.ok) {
				throw new Error(`Erreur HTTP: ${response.status}`);
			}

			const result = await response.json();
			if (!result.success) {
				throw new Error(result.message || 'Erreur lors de l\'ajout du privilège');
			}

			NotificationSystem.success('Privilège ajouté avec succès');

			// Réinitialiser le formulaire
			form.reset();
			document.getElementById('classe').selectedIndex = 0;
			document.getElementById('etudiant').innerHTML = '<option value="">Sélectionnez un étudiant</option>';

			// Recharger la liste des privilèges
			await loadPrivileges();
		} catch (error) {
			console.error('Erreur lors de l\'ajout du privilège:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour supprimer un privilège
	async function removePrivilege(studentId) {
		if (!confirm('Êtes-vous sûr de vouloir supprimer ce privilège ?')) {
			return;
		}

		try {
			console.log(`Suppression du privilège pour l'étudiant ${studentId}`);

			const response = await fetch(`${getApiUrl('privileges')}/${studentId}`, {
				method: 'DELETE',
				headers: {
					'Accept': 'application/json'
				},
				credentials: 'include'
			});

			if (!response.ok) {
				throw new Error(`Erreur HTTP: ${response.status}`);
			}

			const result = await response.json();
			if (!result.success) {
				throw new Error(result.message || 'Erreur lors de la suppression du privilège');
			}

			NotificationSystem.success('Privilège supprimé avec succès');

			// Recharger la liste des privilèges
			await loadPrivileges();
		} catch (error) {
			console.error('Erreur lors de la suppression du privilège:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Initialisation de la page
	document.addEventListener('DOMContentLoaded', async function() {
		try {
			await loadPrivileges();
			await loadClasses();

			// Ajouter l'écouteur d'événements pour le formulaire d'ajout de privilège
			document.getElementById('privilege-form').addEventListener('submit', addPrivilege);
		} catch (error) {
			console.error('Erreur lors de l\'initialisation de la page:', error);
			NotificationSystem.error(error.message);
		}
	});
</script>