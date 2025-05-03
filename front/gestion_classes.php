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

<script src="js/errorHandler.js"></script>
<script>
	// Fonction pour charger les classes
	async function loadClasses() {
		try {
			const response = await fetch('../back/routes/api.php/classes');
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
						<button onclick="editClasse(${classe.id_classe})" class="btn btn-warning btn-sm">Modifier</button>
						<button onclick="deleteClasse(${classe.id_classe})" class="btn btn-danger btn-sm">Supprimer</button>
					</td>
				`;
				tbody.appendChild(tr);
			});
		} catch (error) {
			console.error('Erreur lors du chargement des classes:', error);
			ErrorHandler.handleApiError(error);
			const tbody = document.querySelector('#classesTable tbody');
			tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Erreur lors du chargement des classes</td></tr>';
		}
	}

	// Fonction pour ajouter une classe
	async function addClasse() {
		const nom_classe = document.getElementById('nom_classe').value;
		const niveau = document.getElementById('niveau').value;
		const numero = document.getElementById('numero').value;
		const rythme = document.getElementById('rythme').value;

		try {
			const response = await fetch('../back/routes/api.php/classes', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					nom_classe,
					niveau,
					numero,
					rythme
				})
			});

			if (!response.ok) {
				throw new Error(`Erreur HTTP: ${response.status}`);
			}

			const data = await response.json();
			if (!data.success) {
				throw new Error(data.message || 'Erreur lors de l\'ajout de la classe');
			}

			ErrorHandler.showSuccess('Classe ajoutée avec succès');
			document.getElementById('addClasseForm').reset();
			loadClasses();
		} catch (error) {
			console.error('Erreur lors de l\'ajout de la classe:', error);
			ErrorHandler.handleApiError(error);
		}
	}

	// Fonction pour modifier une classe
	async function editClasse(id) {
		const nom_classe = prompt('Nouveau nom de la classe:', document.querySelector(`#classesTable tbody tr[data-id="${id}"] td:nth-child(1)`).textContent);
		const niveau = prompt('Nouveau niveau:', document.querySelector(`#classesTable tbody tr[data-id="${id}"] td:nth-child(2)`).textContent);
		const numero = prompt('Nouveau numéro:', document.querySelector(`#classesTable tbody tr[data-id="${id}"] td:nth-child(3)`).textContent);
		const rythme = prompt('Nouveau rythme (Alternance ou Initial):', document.querySelector(`#classesTable tbody tr[data-id="${id}"] td:nth-child(4)`).textContent);

		if (nom_classe && niveau && numero && rythme &&
			(nom_classe !== document.querySelector(`#classesTable tbody tr[data-id="${id}"] td:nth-child(1)`).textContent ||
				niveau !== document.querySelector(`#classesTable tbody tr[data-id="${id}"] td:nth-child(2)`).textContent ||
				numero !== document.querySelector(`#classesTable tbody tr[data-id="${id}"] td:nth-child(3)`).textContent ||
				rythme !== document.querySelector(`#classesTable tbody tr[data-id="${id}"] td:nth-child(4)`).textContent)) {
			try {
				const response = await fetch(`../back/routes/api.php/classes/${id}`, {
					method: 'PUT',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify({
						nom_classe,
						niveau,
						numero,
						rythme
					})
				});

				if (!response.ok) {
					throw new Error(`Erreur HTTP: ${response.status}`);
				}

				const data = await response.json();
				if (!data.success) {
					throw new Error(data.message || 'Erreur lors de la modification de la classe');
				}

				ErrorHandler.showSuccess('Classe modifiée avec succès');
				loadClasses();
			} catch (error) {
				console.error('Erreur lors de la modification de la classe:', error);
				ErrorHandler.handleApiError(error);
			}
		}
	}

	// Fonction pour supprimer une classe
	async function deleteClasse(id) {
		if (!confirm('Êtes-vous sûr de vouloir supprimer cette classe ?')) {
			return;
		}

		try {
			const response = await fetch(`../back/routes/api.php/classes/${id}`, {
				method: 'DELETE'
			});

			if (!response.ok) {
				throw new Error(`Erreur HTTP: ${response.status}`);
			}

			const data = await response.json();
			if (!data.success) {
				throw new Error(data.message || 'Erreur lors de la suppression de la classe');
			}

			ErrorHandler.showSuccess('Classe supprimée avec succès');
			loadClasses();
		} catch (error) {
			console.error('Erreur lors de la suppression de la classe:', error);
			ErrorHandler.handleApiError(error);
		}
	}

	// Charger les classes au chargement de la page
	document.addEventListener('DOMContentLoaded', loadClasses);
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>