<?php
session_start();

if (!isset($_SESSION['prof_id'])) {
	header('Location: login.php');
	exit();
}

$pageTitle = "Gestion des Examens";
ob_start();
?>

<div class="container">
	<div class="main-content">
		<h1>Gestion des Examens</h1>

		<div class="form-container">
			<h3>Ajouter un examen</h3>
			<form id="addExamForm">
				<div class="form-row">
					<label for="titre">Titre de l'examen :</label>
					<input type="text" name="titre" id="titre" required>
				</div>
				<div class="form-row">
					<label for="matiere">Matière :</label>
					<select name="matiere" id="matiere" required>
						<option value="">Sélectionnez une matière</option>
					</select>
				</div>
				<div class="form-row">
					<label for="classe">Classe :</label>
					<select name="classe" id="classe" required>
						<option value="">Sélectionnez une classe</option>
					</select>
				</div>
				<button type="submit" class="btn">Ajouter l'examen</button>
			</form>
		</div>

		<h3>Liste des examens</h3>
		<div class="table-responsive">
			<table class="table" id="examsTable">
				<thead>
					<tr>
						<th>Titre</th>
						<th>Matière</th>
						<th>Classe</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<!-- Les examens seront chargés dynamiquement -->
				</tbody>
			</table>
		</div>
	</div>
</div>

<script>
	// Fonction pour charger les matières
	async function loadMatieres() {
		try {
			const response = await fetch('api/matieres');
			const result = await response.json();

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors du chargement des matières');
			}

			const select = document.getElementById('matiere');
			select.innerHTML = '<option value="">Sélectionnez une matière</option>';

			result.data.forEach(matiere => {
				const option = document.createElement('option');
				option.value = matiere.id_matiere;
				option.textContent = matiere.nom;
				select.appendChild(option);
			});
		} catch (error) {
			console.error('Erreur lors du chargement des matières:', error);
			handleApiError(error);
		}
	}

	// Fonction pour charger les classes
	async function loadClasses() {
		try {
			const response = await fetch('api/classes');
			const result = await response.json();

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors du chargement des classes');
			}

			const select = document.getElementById('classe');
			select.innerHTML = '<option value="">Sélectionnez une classe</option>';

			result.data.forEach(classe => {
				const option = document.createElement('option');
				option.value = classe.id_classe;
				option.textContent = `${classe.nom_classe} (${classe.niveau}${classe.numero})`;
				select.appendChild(option);
			});
		} catch (error) {
			console.error('Erreur lors du chargement des classes:', error);
			handleApiError(error);
		}
	}

	// Fonction pour charger les examens
	async function loadExams() {
		try {
			const response = await fetch('api/examens');
			const result = await response.json();

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors du chargement des examens');
			}

			const tbody = document.querySelector('#examsTable tbody');
			tbody.innerHTML = '';

			if (result.data.length === 0) {
				tbody.innerHTML = '<tr><td colspan="4">Aucun examen trouvé</td></tr>';
				return;
			}

			result.data.forEach(exam => {
				const tr = document.createElement('tr');
				tr.innerHTML = `
					<td>${exam.titre}</td>
					<td>${exam.nom_matiere}</td>
					<td>${exam.nom_classe}</td>
					<td>
						<button class="btn btn-edit" onclick="editExam(${exam.id_exam}, '${exam.titre}', ${exam.matiere}, ${exam.classe})">Modifier</button>
						<button class="btn btn-danger" onclick="deleteExam(${exam.id_exam})">Supprimer</button>
					</td>
				`;
				tbody.appendChild(tr);
			});
		} catch (error) {
			console.error('Erreur lors du chargement des examens:', error);
			handleApiError(error);
		}
	}

	// Fonction pour ajouter un examen
	document.getElementById('addExamForm').addEventListener('submit', async function(e) {
		e.preventDefault();
		const titre = document.getElementById('titre').value;
		const matiere = document.getElementById('matiere').value;
		const classe = document.getElementById('classe').value;

		try {
			const response = await fetch('api/examens', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					titre,
					matiere,
					classe
				})
			});

			const result = await response.json();

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors de l\'ajout de l\'examen');
			}

			document.getElementById('titre').value = '';
			document.getElementById('matiere').value = '';
			document.getElementById('classe').value = '';
			showSuccess(result.message || 'Examen ajouté avec succès');
			loadExams();
		} catch (error) {
			console.error('Erreur:', error);
			handleApiError(error);
		}
	});

	// Fonction pour modifier un examen
	async function editExam(id, currentTitre, currentMatiere, currentClasse) {
		const newTitre = prompt('Nouveau titre de l\'examen:', currentTitre);

		if (newTitre && newTitre !== currentTitre) {
			try {
				const response = await fetch(`api/examens/${id}`, {
					method: 'PUT',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						titre: newTitre,
						matiere: currentMatiere,
						classe: currentClasse
					})
				});

				const result = await response.json();

				if (!result.success) {
					throw new Error(result.error || 'Erreur lors de la modification de l\'examen');
				}

				showSuccess(result.message || 'Examen modifié avec succès');
				loadExams();
			} catch (error) {
				console.error('Erreur:', error);
				handleApiError(error);
			}
		}
	}

	// Fonction pour supprimer un examen
	async function deleteExam(id) {
		if (confirm('Êtes-vous sûr de vouloir supprimer cet examen ?')) {
			try {
				const response = await fetch(`api/examens/${id}`, {
					method: 'DELETE'
				});

				const result = await response.json();

				if (!result.success) {
					throw new Error(result.error || 'Erreur lors de la suppression de l\'examen');
				}

				showSuccess(result.message || 'Examen supprimé avec succès');
				loadExams();
			} catch (error) {
				console.error('Erreur:', error);
				handleApiError(error);
			}
		}
	}

	// Charger les données au chargement de la page
	document.addEventListener('DOMContentLoaded', () => {
		loadMatieres();
		loadClasses();
		loadExams();
	});
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>