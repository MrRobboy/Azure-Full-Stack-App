<?php
session_start();

if (!isset($_SESSION['prof_id'])) {
	header('Location: login.php');
	exit();
}

$pageTitle = "Gestion des Matières";
ob_start();
?>

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

<script>
	// Fonction pour charger les matières
	async function loadMatieres() {
		try {
			const response = await fetch('../api/matieres');
			const matieres = await response.json();

			const tbody = document.querySelector('#matieresTable tbody');
			tbody.innerHTML = '';

			matieres.forEach(matiere => {
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
			handleApiError(error);
		}
	}

	// Fonction pour ajouter une matière
	document.getElementById('addMatiereForm').addEventListener('submit', async function(e) {
		e.preventDefault();
		const nom = document.getElementById('nom').value;

		try {
			const response = await fetch('../api/matieres', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					nom
				})
			});

			if (response.ok) {
				document.getElementById('nom').value = '';
				showSuccess('Matière ajoutée avec succès');
				loadMatieres();
			} else {
				const error = await response.json();
				showError(error.message || 'Erreur lors de l\'ajout de la matière');
			}
		} catch (error) {
			console.error('Erreur:', error);
			handleApiError(error);
		}
	});

	// Fonction pour modifier une matière
	async function editMatiere(id, currentNom) {
		const newNom = prompt('Nouveau nom de la matière:', currentNom);
		if (newNom && newNom !== currentNom) {
			try {
				const response = await fetch(`../api/matieres/${id}`, {
					method: 'PUT',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						nom: newNom
					})
				});

				if (response.ok) {
					showSuccess('Matière modifiée avec succès');
					loadMatieres();
				} else {
					const error = await response.json();
					showError(error.message || 'Erreur lors de la modification de la matière');
				}
			} catch (error) {
				console.error('Erreur:', error);
				handleApiError(error);
			}
		}
	}

	// Fonction pour supprimer une matière
	async function deleteMatiere(id) {
		if (confirm('Êtes-vous sûr de vouloir supprimer cette matière ?')) {
			try {
				const response = await fetch(`../api/matieres/${id}`, {
					method: 'DELETE'
				});

				if (response.ok) {
					showSuccess('Matière supprimée avec succès');
					loadMatieres();
				} else {
					const error = await response.json();
					showError(error.message || 'Erreur lors de la suppression de la matière');
				}
			} catch (error) {
				console.error('Erreur:', error);
				handleApiError(error);
			}
		}
	}

	// Charger les matières au chargement de la page
	document.addEventListener('DOMContentLoaded', loadMatieres);
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>