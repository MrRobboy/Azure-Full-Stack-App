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
		<h1>Gestion des Classes</h1>

		<div class="form-container">
			<h3>Ajouter une classe</h3>
			<form id="addClasseForm">
				<div class="form-row">
					<label for="nom_classe">Nom de la classe :</label>
					<input type="text" name="nom_classe" id="nom_classe" required>
				</div>
				<div class="form-row">
					<label for="niveau">Niveau :</label>
					<select name="niveau" id="niveau" required>
						<option value="">Sélectionnez un niveau</option>
						<option value="1ère Année">1ère Année</option>
						<option value="2ème Année">2ème Année</option>
						<option value="3ème Année">3ème Année</option>
						<option value="4ème Année">4ème Année</option>
						<option value="5ème Année">5ème Année</option>
					</select>
				</div>
				<div class="form-row">
					<label for="numero">Numéro :</label>
					<input type="text" name="numero" id="numero" required>
				</div>
				<div class="form-row">
					<label for="rythme">Rythme :</label>
					<select name="rythme" id="rythme" required>
						<option value="">Sélectionnez un rythme</option>
						<option value="Initial">Initial</option>
						<option value="Alternance">Alternance</option>
					</select>
				</div>
				<button type="submit" class="btn">Ajouter la classe</button>
			</form>
		</div>

		<div class="table-responsive">
			<h3>Liste des classes</h3>
			<div id="loading" class="text-center">
				<p>Chargement des classes...</p>
			</div>
			<div id="error-message" class="alert alert-danger" style="display: none;"></div>
			<table class="table" id="classes-table" style="display: none;">
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
	</div>
</div>

<!-- Modal pour éditer une classe -->
<div class="modal" id="editModal" style="display: none;">
	<div class="modal-content">
		<h3>Modifier la classe</h3>
		<form id="editClasseForm">
			<input type="hidden" id="edit_id_classe" name="id_classe">
			<div class="form-row">
				<label for="edit_nom_classe">Nom de la classe :</label>
				<input type="text" name="nom_classe" id="edit_nom_classe" required>
			</div>
			<div class="form-row">
				<label for="edit_niveau">Niveau :</label>
				<select name="niveau" id="edit_niveau" required>
					<option value="">Sélectionnez un niveau</option>
					<option value="1ère Année">1ère Année</option>
					<option value="2ème Année">2ème Année</option>
					<option value="3ème Année">3ème Année</option>
					<option value="4ème Année">4ème Année</option>
					<option value="5ème Année">5ème Année</option>
				</select>
			</div>
			<div class="form-row">
				<label for="edit_numero">Numéro :</label>
				<input type="text" name="numero" id="edit_numero" required>
			</div>
			<div class="form-row">
				<label for="edit_rythme">Rythme :</label>
				<select name="rythme" id="edit_rythme" required>
					<option value="">Sélectionnez un rythme</option>
					<option value="Initial">Initial</option>
					<option value="Alternance">Alternance</option>
				</select>
			</div>
			<div class="form-actions">
				<button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
				<button type="submit" class="btn">Enregistrer</button>
			</div>
		</form>
	</div>
</div>

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
	});

	// Fonction pour charger les classes
	async function loadClasses() {
		try {
			document.getElementById('loading').style.display = 'block';
			document.getElementById('classes-table').style.display = 'none';
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
							<button class="btn btn-edit" onclick="editClasse(${classe.id_classe}, '${classe.nom_classe}', '${classe.niveau}', '${classe.numero}', '${classe.rythme}')">Modifier</button>
							<button class="btn btn-danger" onclick="deleteClasse(${classe.id_classe})">Supprimer</button>
						</td>
					`;
					tbody.appendChild(tr);
				});
			}

			document.getElementById('loading').style.display = 'none';
			document.getElementById('classes-table').style.display = 'table';

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

	// Fonction pour mettre à jour une classe
	async function updateClasse(event) {
		event.preventDefault();

		try {
			const id = document.getElementById('edit_id_classe').value;
			const formData = new FormData(event.target);
			const data = {
				id: id,
				nom_classe: formData.get('nom_classe'),
				niveau: formData.get('niveau'),
				numero: formData.get('numero'),
				rythme: formData.get('rythme')
			};

			const result = await ApiService.request('classes', 'PUT', data);

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors de la mise à jour de la classe');
			}

			closeModal();
			NotificationSystem.success('Classe mise à jour avec succès');
			loadClasses();
		} catch (error) {
			console.error('Erreur:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour supprimer une classe
	async function deleteClasse(id) {
		if (!confirm('Êtes-vous sûr de vouloir supprimer cette classe ?')) {
			return;
		}

		try {
			const result = await ApiService.request('classes', 'DELETE', {
				id: id
			});

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors de la suppression de la classe');
			}

			NotificationSystem.success('Classe supprimée avec succès');
			loadClasses();
		} catch (error) {
			console.error('Erreur:', error);
			NotificationSystem.error(error.message);
		}
	}
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>