<?php
session_start();

if (!isset($_SESSION['prof_id'])) {
	header('Location: login.php');
	exit();
}

$pageTitle = "Gestion des Examens";
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

	.notification {
		position: fixed;
		top: 20px;
		right: 20px;
		padding: 15px;
		border-radius: 4px;
		color: white;
		z-index: 1000;
		animation: slideIn 0.3s ease-out;
	}

	.notification.error {
		background: #dc3545;
	}

	.notification.success {
		background: #28a745;
	}

	.notification .close {
		float: right;
		cursor: pointer;
		margin-left: 10px;
	}

	@keyframes slideIn {
		from {
			transform: translateX(100%);
			opacity: 0;
		}

		to {
			transform: translateX(0);
			opacity: 1;
		}
	}

	.slideOut {
		animation: slideOut 0.3s ease-in forwards;
	}

	@keyframes slideOut {
		from {
			transform: translateX(0);
			opacity: 1;
		}

		to {
			transform: translateX(100%);
			opacity: 0;
		}
	}
</style>

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

		if (!titre || !matiere || !classe) {
			showError('Veuillez remplir tous les champs');
			return;
		}

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

			if (!response.ok) {
				throw new Error(result.error || 'Erreur lors de l\'ajout de l\'examen');
			}

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors de l\'ajout de l\'examen');
			}

			document.getElementById('titre').value = '';
			document.getElementById('matiere').value = '';
			document.getElementById('classe').value = '';
			showSuccess(result.message || 'Examen ajouté avec succès');
			loadExams();
		} catch (error) {
			showError(error.message);
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

				if (!response.ok) {
					const errorData = await response.json();
					throw new Error(errorData.error || 'Erreur lors de la modification de l\'examen');
				}

				const result = await response.json();

				if (!result.success) {
					throw new Error(result.error || 'Erreur lors de la modification de l\'examen');
				}

				showSuccess(result.message || 'Examen modifié avec succès');
				loadExams();
			} catch (error) {
				showError(error.message);
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

				if (!response.ok) {
					const errorData = await response.json();
					throw new Error(errorData.error || 'Erreur lors de la suppression de l\'examen');
				}

				const result = await response.json();

				if (!result.success) {
					throw new Error(result.error || 'Erreur lors de la suppression de l\'examen');
				}

				showSuccess(result.message || 'Examen supprimé avec succès');
				loadExams();
			} catch (error) {
				showError(error.message);
			}
		}
	}

	// Fonction pour afficher une erreur
	function showError(message) {
		const notification = document.createElement('div');
		notification.className = 'notification error';
		notification.innerHTML = `
			<span class="close">&times;</span>
			<p>${message}</p>
		`;
		document.body.appendChild(notification);

		// Supprimer les anciennes notifications
		const oldNotifications = document.querySelectorAll('.notification.error');
		oldNotifications.forEach((old, index) => {
			if (index < oldNotifications.length - 1) {
				old.remove();
			}
		});

		// Fermer la notification après 5 secondes
		setTimeout(() => {
			notification.classList.add('slideOut');
			setTimeout(() => notification.remove(), 500);
		}, 5000);

		// Fermer la notification au clic sur le bouton
		notification.querySelector('.close').addEventListener('click', () => {
			notification.classList.add('slideOut');
			setTimeout(() => notification.remove(), 500);
		});
	}

	// Fonction pour afficher un succès
	function showSuccess(message) {
		const notification = document.createElement('div');
		notification.className = 'notification success';
		notification.innerHTML = `
			<span class="close">&times;</span>
			<p>${message}</p>
		`;
		document.body.appendChild(notification);

		// Supprimer les anciennes notifications
		const oldNotifications = document.querySelectorAll('.notification.success');
		oldNotifications.forEach((old, index) => {
			if (index < oldNotifications.length - 1) {
				old.remove();
			}
		});

		// Fermer la notification après 5 secondes
		setTimeout(() => {
			notification.classList.add('slideOut');
			setTimeout(() => notification.remove(), 500);
		}, 5000);

		// Fermer la notification au clic sur le bouton
		notification.querySelector('.close').addEventListener('click', () => {
			notification.classList.add('slideOut');
			setTimeout(() => notification.remove(), 500);
		});
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