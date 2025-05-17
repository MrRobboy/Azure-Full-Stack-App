<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['token'])) {
	header('Location: login.php');
	exit;
}

$pageTitle = "Gestion des Classes";
ob_start(); // Début de la mise en tampon
?>

<div class="container">
	<div class="main-content">
		<div class="page-header d-flex justify-content-between align-items-center">
			<div>
				<h1>Gestion des Classes</h1>
				<p class="subtitle">Ajouter, modifier ou supprimer des classes</p>
			</div>
			<div id="connection-status">
				<span id="offline-badge" class="badge bg-warning" style="display: none;">
					<i class="fas fa-exclamation-triangle"></i> Mode hors-ligne
				</span>
			</div>
		</div>

		<div class="card mb-4">
			<div class="card-header">
				<h3>Liste des classes</h3>
			</div>
			<div class="card-body">
				<div id="loading" class="text-center py-4">
					<div class="spinner-border text-primary" role="status">
						<span class="visually-hidden">Chargement...</span>
					</div>
					<p class="mt-2">Chargement des classes...</p>
				</div>

				<div id="error-message" class="alert alert-danger" style="display: none;"></div>

				<div id="classes-container" style="display: none;">
					<table class="table table-striped" id="classes-table">
						<thead>
							<tr>
								<th>Nom</th>
								<th>Niveau</th>
								<th>Numéro</th>
								<th>Rythme</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>

				<button id="refresh-btn" class="btn btn-outline-primary btn-sm mt-3" onclick="loadClasses()">
					<i class="fas fa-sync-alt"></i> Rafraîchir la liste
				</button>
			</div>
		</div>

		<div class="card">
			<div class="card-header">
				<h3 id="form-title">Ajouter une classe</h3>
			</div>
			<div class="card-body">
				<form id="addClasseForm">
					<div class="form-group mb-3">
						<label for="nom_classe">Nom de la classe :</label>
						<input type="text" class="form-control" name="nom_classe" id="nom_classe" required>
					</div>
					<div class="form-group mb-3">
						<label for="niveau">Niveau :</label>
						<select name="niveau" id="niveau" class="form-select" required>
							<option value="">Sélectionnez un niveau</option>
							<option value="1ère Année">1ère Année</option>
							<option value="2ème Année">2ème Année</option>
							<option value="3ème Année">3ème Année</option>
							<option value="4ème Année">4ème Année</option>
							<option value="5ème Année">5ème Année</option>
						</select>
					</div>
					<div class="form-group mb-3">
						<label for="numero">Numéro :</label>
						<input type="text" class="form-control" name="numero" id="numero" required>
					</div>
					<div class="form-group mb-3">
						<label for="rythme">Rythme :</label>
						<select name="rythme" id="rythme" class="form-select" required>
							<option value="">Sélectionnez un rythme</option>
							<option value="Initial">Initial</option>
							<option value="Alternance">Alternance</option>
						</select>
					</div>
					<div class="d-flex justify-content-between">
						<button type="submit" class="btn btn-primary">
							<i class="fas fa-save"></i> Ajouter
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<!-- Modal pour éditer une classe -->
<div class="modal" id="editModal" style="display: none;">
	<div class="modal-content">
		<div class="modal-header">
			<h3>Modifier la classe</h3>
			<button type="button" class="btn-close" onclick="closeModal()" aria-label="Fermer"></button>
		</div>
		<div class="modal-body">
			<form id="editClasseForm">
				<input type="hidden" id="edit_id_classe" name="id_classe">
				<div class="form-group mb-3">
					<label for="edit_nom_classe">Nom de la classe :</label>
					<input type="text" class="form-control" name="nom_classe" id="edit_nom_classe" required>
				</div>
				<div class="form-group mb-3">
					<label for="edit_niveau">Niveau :</label>
					<select name="niveau" id="edit_niveau" class="form-select" required>
						<option value="">Sélectionnez un niveau</option>
						<option value="1ère Année">1ère Année</option>
						<option value="2ème Année">2ème Année</option>
						<option value="3ème Année">3ème Année</option>
						<option value="4ème Année">4ème Année</option>
						<option value="5ème Année">5ème Année</option>
					</select>
				</div>
				<div class="form-group mb-3">
					<label for="edit_numero">Numéro :</label>
					<input type="text" class="form-control" name="numero" id="edit_numero" required>
				</div>
				<div class="form-group mb-3">
					<label for="edit_rythme">Rythme :</label>
					<select name="rythme" id="edit_rythme" class="form-select" required>
						<option value="">Sélectionnez un rythme</option>
						<option value="Initial">Initial</option>
						<option value="Alternance">Alternance</option>
					</select>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
					<button type="submit" class="btn btn-primary">Enregistrer</button>
				</div>
			</form>
		</div>
	</div>
</div>

<style>
	.subtitle {
		color: #6c757d;
		font-size: 1.1rem;
		margin-bottom: 20px;
	}

	.modal {
		display: none;
		position: fixed;
		z-index: 1000;
		left: 0;
		top: 0;
		width: 100%;
		height: 100%;
		background-color: rgba(0, 0, 0, 0.5);
	}

	.modal-content {
		background-color: #fff;
		margin: 10% auto;
		padding: 0;
		border-radius: 5px;
		width: 80%;
		max-width: 600px;
		box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
	}

	.modal-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 15px 20px;
		border-bottom: 1px solid #e9ecef;
	}

	.modal-body {
		padding: 20px;
	}

	.modal-footer {
		padding: 15px 20px;
		border-top: 1px solid #e9ecef;
		display: flex;
		justify-content: flex-end;
		gap: 10px;
	}

	.btn-action {
		margin-right: 5px;
	}

	#offline-badge {
		font-size: 0.9rem;
		padding: 0.5rem 0.75rem;
	}
</style>

<script src="js/notification-system.js?v=1.1"></script>
<script src="js/config.js?v=5.0"></script>
<script src="js/api-service.js?v=2.0"></script>
<script>
	document.addEventListener('DOMContentLoaded', function() {
		// Charger les classes au chargement de la page
		loadClasses();

		// Ajouter les écouteurs d'événements
		document.getElementById('addClasseForm').addEventListener('submit', addClasse);
		document.getElementById('editClasseForm').addEventListener('submit', updateClasse);
		document.getElementById('refresh-btn').addEventListener('click', loadClasses);
	});

	// Fonction pour charger les classes
	async function loadClasses() {
		try {
			document.getElementById('loading').style.display = 'block';
			document.getElementById('classes-container').style.display = 'none';
			document.getElementById('error-message').style.display = 'none';

			const result = await ApiService.request('classes');

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors du chargement des classes');
			}

			// Extraire les données
			let classes = [];
			if (result.data && result.data.data) {
				classes = result.data.data;
			} else if (result.data) {
				classes = Array.isArray(result.data) ? result.data : [];
			}

			// Afficher les classes
			const tbody = document.querySelector('#classes-table tbody');
			tbody.innerHTML = '';

			if (classes.length === 0) {
				tbody.innerHTML = '<tr><td colspan="5" class="text-center">Aucune classe trouvée</td></tr>';
			} else {
				classes.forEach(classe => {
					const tr = document.createElement('tr');
					tr.innerHTML = `
						<td>${classe.nom_classe}</td>
						<td>${classe.niveau}</td>
						<td>${classe.numero}</td>
						<td>${classe.rythme}</td>
						<td>
							<button class="btn btn-sm btn-outline-primary btn-action" onclick="editClasse(${classe.id_classe}, '${classe.nom_classe}', '${classe.niveau}', '${classe.numero}', '${classe.rythme}')">
								<i class="fas fa-edit"></i> Modifier
							</button>
							<button class="btn btn-sm btn-outline-danger btn-action" onclick="deleteClasse(${classe.id_classe})">
								<i class="fas fa-trash"></i> Supprimer
							</button>
						</td>
					`;
					tbody.appendChild(tr);
				});
			}

			document.getElementById('loading').style.display = 'none';
			document.getElementById('classes-container').style.display = 'block';

			NotificationSystem.success('Classes chargées avec succès');
		} catch (error) {
			console.error('Erreur:', error);
			document.getElementById('error-message').textContent = error.message;
			document.getElementById('error-message').style.display = 'block';
			document.getElementById('loading').style.display = 'none';

			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour ajouter une classe
	async function addClasse(event) {
		event.preventDefault();

		try {
			const formData = new FormData(event.target);
			const data = {
				nom_classe: formData.get('nom_classe'),
				niveau: formData.get('niveau'),
				numero: formData.get('numero'),
				rythme: formData.get('rythme')
			};

			NotificationSystem.info('Ajout en cours...');
			const result = await ApiService.request('classes', 'POST', data);

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors de l\'ajout de la classe');
			}

			NotificationSystem.success('Classe ajoutée avec succès');
			event.target.reset();
			loadClasses();
		} catch (error) {
			console.error('Erreur:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour afficher le modal d'édition
	function editClasse(id, nom, niveau, numero, rythme) {
		document.getElementById('edit_id_classe').value = id;
		document.getElementById('edit_nom_classe').value = nom;
		document.getElementById('edit_niveau').value = niveau;
		document.getElementById('edit_numero').value = numero;
		document.getElementById('edit_rythme').value = rythme;

		document.getElementById('editModal').style.display = 'block';
	}

	// Fermer le modal
	function closeModal() {
		document.getElementById('editModal').style.display = 'none';
	}

	// Fermer le modal si on clique en dehors
	window.onclick = function(event) {
		const modal = document.getElementById('editModal');
		if (event.target === modal) {
			closeModal();
		}
	}

	// Fonction pour mettre à jour une classe	async function updateClasse(event) {		event.preventDefault();				try {			const id = document.getElementById('edit_id_classe').value;			const formData = new FormData(event.target);			const data = {				id: id, // Le backend attend l'ID dans le corps de la requête				nom_classe: formData.get('nom_classe'),				niveau: formData.get('niveau'),				numero: formData.get('numero'),				rythme: formData.get('rythme')			};						NotificationSystem.info('Mise à jour en cours...');			const result = await ApiService.request('classes', 'PUT', data);						if (!result.success) {				throw new Error(result.error || 'Erreur lors de la mise à jour de la classe');			}						closeModal();			NotificationSystem.success('Classe mise à jour avec succès');			loadClasses();		} catch (error) {			console.error('Erreur:', error);			NotificationSystem.error(error.message);		}	}

	// Fonction pour supprimer une classe	async function deleteClasse(id) {		if (!confirm('Êtes-vous sûr de vouloir supprimer cette classe ?')) {			return;		}				try {			NotificationSystem.info('Suppression en cours...');			// Le backend attend l'ID dans le corps de la requête, pas dans l'URL			const result = await ApiService.request('classes', 'DELETE', {				id: id			});						if (!result.success) {				// Vérifier si l'erreur est liée à des élèves associés à la classe				if (result.message && result.message.includes('contient des élèves')) {					NotificationSystem.error(result.message);					throw new Error(result.message);				} else {					throw new Error(result.error || result.message || 'Erreur lors de la suppression de la classe');				}			}						NotificationSystem.success('Classe supprimée avec succès');			loadClasses();		} catch (error) {			console.error('Erreur:', error);			NotificationSystem.error(error.message);		}	}
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>