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

	.notification.debug {
		background: #6c757d;
		position: fixed;
		top: 20px;
		left: 20px;
		z-index: 1000;
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

<script src="js/error-messages.js"></script>
<script>
	// Fonction pour afficher un message de débogage
	function showDebug(message) {
		const debugDiv = document.createElement('div');
		debugDiv.className = 'notification debug';
		debugDiv.innerHTML = `
			<span class="close">&times;</span>
			<p>${message}</p>
		`;
		document.body.appendChild(debugDiv);

		// Fermer la notification après 10 secondes
		setTimeout(() => {
			debugDiv.classList.add('slideOut');
			setTimeout(() => debugDiv.remove(), 500);
		}, 10000);

		// Fermer la notification au clic sur le bouton
		debugDiv.querySelector('.close').addEventListener('click', () => {
			debugDiv.classList.add('slideOut');
			setTimeout(() => debugDiv.remove(), 500);
		});
	}

	// Fonction pour charger les matières
	async function loadMatieres() {
		try {
			showDebug('Chargement des matières...');
			const response = await fetch('api/matieres');
			showDebug('Réponse reçue: ' + response.status);
			const result = await response.json();
			showDebug('Données reçues: ' + JSON.stringify(result));

			if (!result.success) {
				throw new Error(result.error || ErrorMessages.GENERAL.SERVER_ERROR);
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
			showError(error.message);
		}
	}

	// Fonction pour charger les classes
	async function loadClasses() {
		try {
			showDebug('Chargement des classes...');
			const response = await fetch('api/classes');
			showDebug('Réponse reçue: ' + response.status);
			const result = await response.json();
			showDebug('Données reçues: ' + JSON.stringify(result));

			if (!result.success) {
				throw new Error(result.error || ErrorMessages.GENERAL.SERVER_ERROR);
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
			showError(error.message);
		}
	}

	// Fonction pour charger les examens
	async function loadExams() {
		try {
			showDebug('Chargement des examens...');
			const response = await fetch('api/examens');
			showDebug('Réponse reçue: ' + response.status);
			const result = await response.json();
			showDebug('Données reçues: ' + JSON.stringify(result));

			if (!result.success) {
				throw new Error(result.error || ErrorMessages.GENERAL.SERVER_ERROR);
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
			showError(error.message);
		}
	}

	// Fonction pour ajouter un examen
	document.getElementById('addExamForm').addEventListener('submit', async function(e) {
		e.preventDefault();
		showDebug('Tentative d\'ajout d\'un examen...');

		const titre = document.getElementById('titre').value;
		const matiere = document.getElementById('matiere').value;
		const classe = document.getElementById('classe').value;

		showDebug('Données du formulaire: ' + JSON.stringify({
			titre,
			matiere,
			classe
		}));

		if (!titre || !matiere || !classe) {
			showDebug('Champs manquants');
			showError(ErrorMessages.GENERAL.REQUIRED_FIELDS);
			return;
		}

		try {
			showDebug('Envoi de la requête...');
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

			showDebug('Réponse reçue: ' + response.status);
			const result = await response.json();
			showDebug('Résultat: ' + JSON.stringify(result));

			if (!response.ok) {
				throw new Error(result.error || ErrorMessages.EXAMS.CREATE.ERROR);
			}

			if (!result.success) {
				throw new Error(result.error || ErrorMessages.EXAMS.CREATE.ERROR);
			}

			document.getElementById('titre').value = '';
			document.getElementById('matiere').value = '';
			document.getElementById('classe').value = '';
			showSuccess(ErrorMessages.EXAMS.CREATE.SUCCESS);
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
				console.log('Tentative de modification de l\'examen...');
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

				console.log('Réponse reçue:', response);
				const result = await response.json();
				console.log('Résultat:', result);

				if (!response.ok) {
					throw new Error(result.error || ErrorMessages.EXAMS.UPDATE.ERROR);
				}

				if (!result.success) {
					throw new Error(result.error || ErrorMessages.EXAMS.UPDATE.ERROR);
				}

				showSuccess(ErrorMessages.EXAMS.UPDATE.SUCCESS);
				loadExams();
			} catch (error) {
				console.error('Erreur lors de la modification de l\'examen:', error);
				showError(error.message);
			}
		}
	}

	// Fonction pour supprimer un examen
	async function deleteExam(id) {
		if (confirm('Êtes-vous sûr de vouloir supprimer cet examen ?')) {
			try {
				console.log('Tentative de suppression de l\'examen...');
				const response = await fetch(`api/examens/${id}`, {
					method: 'DELETE'
				});

				console.log('Réponse reçue:', response);
				const result = await response.json();
				console.log('Résultat:', result);

				if (!response.ok) {
					throw new Error(result.error || ErrorMessages.EXAMS.DELETE.ERROR);
				}

				if (!result.success) {
					throw new Error(result.error || ErrorMessages.EXAMS.DELETE.ERROR);
				}

				showSuccess(ErrorMessages.EXAMS.DELETE.SUCCESS);
				loadExams();
			} catch (error) {
				console.error('Erreur lors de la suppression de l\'examen:', error);
				showError(error.message);
			}
		}
	}

	// Vérifier que le script d'erreurs est chargé
	if (typeof ErrorMessages === 'undefined') {
		showError('Le script error-messages.js n\'est pas chargé correctement');
	} else {
		showDebug('Script d\'erreurs chargé avec succès');
	}

	// Charger les données au chargement de la page
	document.addEventListener('DOMContentLoaded', () => {
		showDebug('Chargement de la page...');
		loadMatieres();
		loadClasses();
		loadExams();
	});
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>