<?php
session_start();

if (!isset($_SESSION['prof_id'])) {
	header('Location: login.php');
	exit();
}

$pageTitle = "Gestion des Classes";
ob_start();
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
					<input type="text" name="niveau" id="niveau" required>
				</div>
				<div class="form-row">
					<label for="numero">Numéro :</label>
					<input type="text" name="numero" id="numero" required>
				</div>
				<div class="form-row">
					<label for="rythme">Rythme :</label>
					<select name="rythme" id="rythme" required>
						<option value="">Sélectionnez un rythme</option>
						<option value="Alternance">Alternance</option>
						<option value="Initial">Initial</option>
					</select>
				</div>
				<button type="submit" class="btn">Ajouter la classe</button>
			</form>
		</div>

		<h3>Liste des classes</h3>
		<div class="table-responsive">
			<table class="table" id="classesTable">
				<thead>
					<tr>
						<th>Nom</th>
						<th>Niveau</th>
						<th>Numéro</th>
						<th>Rythme</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<!-- Les classes seront chargées dynamiquement -->
				</tbody>
			</table>
		</div>
	</div>
</div>

<!-- Modal de modification -->
<div id="editClasseModal" class="modal">
	<div class="modal-content">
		<span class="close">&times;</span>
		<h2>Modifier la classe</h2>
		<form id="editClasseForm">
			<input type="hidden" id="edit_id_classe">
			<div class="form-row">
				<label for="edit_nom_classe">Nom de la classe :</label>
				<input type="text" name="nom_classe" id="edit_nom_classe" required>
			</div>
			<div class="form-row">
				<label for="edit_niveau">Niveau :</label>
				<input type="text" name="niveau" id="edit_niveau" required>
			</div>
			<div class="form-row">
				<label for="edit_numero">Numéro :</label>
				<input type="text" name="numero" id="edit_numero" required>
			</div>
			<div class="form-row">
				<label for="edit_rythme">Rythme :</label>
				<select name="rythme" id="edit_rythme" required>
					<option value="">Sélectionnez un rythme</option>
					<option value="Alternance">Alternance</option>
					<option value="Initial">Initial</option>
				</select>
			</div>
			<button type="submit" class="btn">Enregistrer les modifications</button>
		</form>
	</div>
</div>

<style>
	.modal {
		display: none;
		position: fixed;
		z-index: 1000;
		left: 0;
		top: 0;
		width: 100%;
		height: 100%;
		background-color: rgba(0, 0, 0, 0.4);
	}

	.modal-content {
		background-color: #fefefe;
		margin: 15% auto;
		padding: 20px;
		border: 1px solid #888;
		width: 80%;
		max-width: 500px;
		border-radius: 8px;
	}

	.close {
		color: #aaa;
		float: right;
		font-size: 28px;
		font-weight: bold;
		cursor: pointer;
	}

	.close:hover {
		color: black;
	}

	.form-row {
		margin-bottom: 15px;
	}

	.form-row label {
		display: block;
		margin-bottom: 5px;
	}

	.form-row input,
	.form-row select {
		width: 100%;
		padding: 8px;
		border: 1px solid #ddd;
		border-radius: 4px;
	}
</style>

<script src="js/config.js"></script>
<script src="js/errorHandler.js"></script>
<script>
	// Fonction pour charger les classes
	async function loadClasses() {
		try {
			const response = await fetch(getApiUrl('classes'));
			if (!response.ok) {
				throw new Error(`Erreur HTTP: ${response.status}`);
			}
			const data = await response.json();

			if (!data.success) {
				throw new Error(data.message || 'Erreur lors du chargement des classes');
			}

			const classes = data.data;
			const tbody = document.querySelector('#classesTable tbody');
			tbody.innerHTML = '';

			if (!Array.isArray(classes) || classes.length === 0) {
				tbody.innerHTML = '<tr><td colspan="5" class="text-center">Aucune classe trouvée</td></tr>';
				return;
			}

			classes.forEach(classe => {
				const tr = document.createElement('tr');
				tr.setAttribute('data-id', classe.id_classe);
				tr.innerHTML = `
					<td>${classe.nom_classe || '-'}</td>
					<td>${classe.niveau || '-'}</td>
					<td>${classe.numero || '-'}</td>
					<td>${classe.rythme || '-'}</td>
					<td>
						<button onclick="openEditModal(${classe.id_classe})" class="btn btn-warning btn-sm">Modifier</button>
						<button onclick="deleteClasse(${classe.id_classe})" class="btn btn-danger btn-sm">Supprimer</button>
					</td>
				`;
				tbody.appendChild(tr);
			});
		} catch (error) {
			console.error('Erreur lors du chargement des classes:', error);
			const tbody = document.querySelector('#classesTable tbody');
			tbody.innerHTML = `<tr><td colspan="5" class="text-center error">Erreur lors du chargement des classes: ${error.message}</td></tr>`;
		}
	}

	// Fonction pour ajouter une classe
	async function addClasse(event) {
		event.preventDefault();
		const form = event.target;
		const formData = new FormData(form);

		try {
			const response = await fetch(getApiUrl('classes'), {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					nom_classe: formData.get('nom_classe'),
					niveau: formData.get('niveau'),
					numero: formData.get('numero'),
					rythme: formData.get('rythme')
				})
			});

			if (!response.ok) {
				throw new Error(`Erreur HTTP: ${response.status}`);
			}

			const data = await response.json();
			if (!data.success) {
				throw new Error(data.message || 'Erreur lors de l\'ajout de la classe');
			}

			form.reset();
			loadClasses();
			showSuccess('Classe ajoutée avec succès');
		} catch (error) {
			console.error('Erreur lors de l\'ajout de la classe:', error);
			showError(`Erreur lors de l'ajout de la classe: ${error.message}`);
		}
	}

	// Fonction pour ouvrir le modal de modification
	async function openEditModal(id) {
		try {
			const response = await fetch(getApiUrl(`classes/${id}`));
			const responseText = await response.text();

			// Vérifier si la réponse est vide
			if (!responseText) {
				showError('Le serveur n\'a pas renvoyé de réponse');
				return;
			}

			// Tenter de parser la réponse
			let data;
			try {
				data = JSON.parse(responseText);
			} catch (e) {
				showError(`Erreur de format de réponse: ${responseText}`);
				return;
			}

			if (!data.success) {
				showError(data.message || 'Erreur lors de la récupération de la classe');
				return;
			}

			const classe = data.data;
			if (!classe) {
				showError('Aucune donnée de classe trouvée');
				return;
			}

			document.getElementById('edit_id_classe').value = classe.id_classe;
			document.getElementById('edit_nom_classe').value = classe.nom_classe;
			document.getElementById('edit_niveau').value = classe.niveau;
			document.getElementById('edit_numero').value = classe.numero;
			document.getElementById('edit_rythme').value = classe.rythme;

			// Afficher le modal
			document.getElementById('editClasseModal').style.display = 'block';
		} catch (error) {
			showError(`Erreur: ${error.message}`);
		}
	}

	// Fonction pour modifier une classe
	async function editClasse(event) {
		event.preventDefault();
		const id = document.getElementById('edit_id_classe').value;

		try {
			const response = await fetch(getApiUrl(`classes/${id}`), {
				method: 'PUT',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					nom_classe: document.getElementById('edit_nom_classe').value,
					niveau: document.getElementById('edit_niveau').value,
					numero: document.getElementById('edit_numero').value,
					rythme: document.getElementById('edit_rythme').value
				})
			});

			if (!response.ok) {
				throw new Error(`Erreur HTTP: ${response.status}`);
			}

			const data = await response.json();
			if (!data.success) {
				throw new Error(data.message || 'Erreur lors de la modification de la classe');
			}

			document.getElementById('editClasseModal').style.display = 'none';
			loadClasses();
			showSuccess('Classe modifiée avec succès');
		} catch (error) {
			console.error('Erreur lors de la modification de la classe:', error);
			showError(`Erreur lors de la modification de la classe: ${error.message}`);
		}
	}

	// Fonction pour supprimer une classe
	async function deleteClasse(id) {
		if (!confirm('Êtes-vous sûr de vouloir supprimer cette classe ?')) {
			return;
		}

		try {
			const response = await fetch(getApiUrl(`classes/${id}`), {
				method: 'DELETE'
			});

			if (!response.ok) {
				throw new Error(`Erreur HTTP: ${response.status}`);
			}

			const data = await response.json();
			if (!data.success) {
				throw new Error(data.message || 'Erreur lors de la suppression de la classe');
			}

			loadClasses();
			showSuccess('Classe supprimée avec succès');
		} catch (error) {
			console.error('Erreur lors de la suppression de la classe:', error);
			showError(`Erreur lors de la suppression de la classe: ${error.message}`);
		}
	}

	// Fonction pour afficher les erreurs
	function showError(message) {
		// Supprimer les messages d'erreur existants
		const existingErrors = document.querySelectorAll('.error-message');
		existingErrors.forEach(error => error.remove());

		// Créer le message d'erreur
		const errorMessage = document.createElement('div');
		errorMessage.className = 'error-message';
		errorMessage.style.cssText = `
			position: fixed;
			top: 20px;
			right: 20px;
			padding: 15px;
			background-color: #ff4444;
			color: white;
			border-radius: 5px;
			z-index: 1001;
			max-width: 400px;
			box-shadow: 0 2px 5px rgba(0,0,0,0.2);
			animation: slideIn 0.3s ease-out;
		`;

		errorMessage.innerHTML = `
			<div style="display: flex; align-items: center; justify-content: space-between;">
				<div style="flex: 1;">
					<strong style="display: block; margin-bottom: 5px;">Erreur</strong>
					${message}
				</div>
				<button onclick="this.parentElement.parentElement.remove()" 
					style="background: none; border: none; color: white; cursor: pointer; margin-left: 10px;">
					×
				</button>
			</div>
		`;

		// Ajouter le style d'animation
		const style = document.createElement('style');
		style.textContent = `
			@keyframes slideIn {
				from { transform: translateX(100%); opacity: 0; }
				to { transform: translateX(0); opacity: 1; }
			}
		`;
		document.head.appendChild(style);

		document.body.appendChild(errorMessage);

		// Supprimer le message après 5 secondes
		setTimeout(() => {
			if (errorMessage.parentNode) {
				errorMessage.style.animation = 'slideOut 0.3s ease-in';
				setTimeout(() => errorMessage.remove(), 300);
			}
		}, 5000);
	}

	// Fonction pour afficher les succès
	function showSuccess(message) {
		// Supprimer les messages de succès existants
		const existingSuccess = document.querySelectorAll('.success-message');
		existingSuccess.forEach(success => success.remove());

		// Créer le message de succès
		const successMessage = document.createElement('div');
		successMessage.className = 'success-message';
		successMessage.style.cssText = `
			position: fixed;
			top: 20px;
			right: 20px;
			padding: 15px;
			background-color: #4CAF50;
			color: white;
			border-radius: 5px;
			z-index: 1001;
			max-width: 400px;
			box-shadow: 0 2px 5px rgba(0,0,0,0.2);
			animation: slideIn 0.3s ease-out;
		`;

		successMessage.innerHTML = `
			<div style="display: flex; align-items: center; justify-content: space-between;">
				<div style="flex: 1;">
					<strong style="display: block; margin-bottom: 5px;">Succès</strong>
					${message}
				</div>
				<button onclick="this.parentElement.parentElement.remove()" 
					style="background: none; border: none; color: white; cursor: pointer; margin-left: 10px;">
					×
				</button>
			</div>
		`;

		document.body.appendChild(successMessage);

		// Supprimer le message après 3 secondes
		setTimeout(() => {
			if (successMessage.parentNode) {
				successMessage.style.animation = 'slideOut 0.3s ease-in';
				setTimeout(() => successMessage.remove(), 300);
			}
		}, 3000);
	}

	// Gestionnaires d'événements
	document.getElementById('addClasseForm').addEventListener('submit', addClasse);
	document.getElementById('editClasseForm').addEventListener('submit', editClasse);
	document.querySelector('.close').addEventListener('click', () => {
		document.getElementById('editClasseModal').style.display = 'none';
	});

	// Charger les classes au chargement de la page
	document.addEventListener('DOMContentLoaded', loadClasses);
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>