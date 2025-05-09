<?php
session_start();

if (!isset($_SESSION['prof_id'])) {
	header('Location: login.php');
	exit();
}

$pageTitle = "Gestion des Examens";
ob_start();
?>

<head>
	<title><?php echo $pageTitle; ?></title>
	<link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
	<link rel="stylesheet" href="css/styles.css">
</head>

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

	.notification-container {
		position: fixed;
		top: 20px;
		right: 20px;
		z-index: 9999;
		display: flex;
		flex-direction: column;
		align-items: flex-end;
	}

	.notification {
		position: relative;
		padding: 15px;
		margin-bottom: 10px;
		border-radius: 4px;
		color: white;
		width: 300px;
		box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
		animation: slideIn 0.3s ease-out;
		z-index: 10000;
	}

	.notification.error {
		background-color: #dc3545;
	}

	.notification.success {
		background-color: #28a745;
	}

	.notification .close {
		position: absolute;
		right: 10px;
		top: 10px;
		cursor: pointer;
		font-size: 20px;
		background: none;
		border: none;
		color: white;
		padding: 0;
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

	.calendar {
		display: grid;
		grid-template-columns: repeat(7, 1fr);
		gap: 5px;
		margin-top: 20px;
	}

	.calendar-day {
		aspect-ratio: 1;
		display: flex;
		align-items: center;
		justify-content: center;
		border: 1px solid #ddd;
		padding: 5px;
		position: relative;
	}

	.calendar-day.has-exam {
		background-color: #e3f2fd;
	}

	.calendar-day.has-exam::after {
		content: '';
		position: absolute;
		bottom: 2px;
		left: 50%;
		transform: translateX(-50%);
		width: 6px;
		height: 6px;
		background-color: #2196f3;
		border-radius: 50%;
	}

	.calendar-day.today {
		background-color: #fff3e0;
	}

	.calendar-day.today.has-exam {
		background-color: #e3f2fd;
	}

	.calendar-day.today.has-exam::after {
		background-color: #f44336;
	}

	.calendar-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 10px;
	}

	.calendar-header button {
		padding: 5px 10px;
		background: #007bff;
		color: white;
		border: none;
		border-radius: 4px;
		cursor: pointer;
	}

	.calendar-header button:hover {
		background: #0056b3;
	}

	.exam-tooltip {
		position: absolute;
		background: white;
		border: 1px solid #ddd;
		padding: 5px;
		border-radius: 4px;
		box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
		z-index: 1000;
		display: none;
		white-space: nowrap;
	}

	.calendar-day:hover .exam-tooltip {
		display: block;
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
				<div class="form-row">
					<label for="date">Date de l'examen :</label>
					<input type="date" name="date" id="date" required>
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
						<th>Date</th>
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
<script src="js/notification-system.js"></script>
<script>
	// Vérifier que les scripts sont chargés
	console.log('Vérification du chargement des scripts...');
	console.log('ErrorMessages:', typeof ErrorMessages);
	console.log('NotificationSystem:', typeof NotificationSystem);

	if (typeof ErrorMessages === 'undefined') {
		console.error('Le script error-messages.js n\'est pas chargé correctement');
	}

	if (typeof NotificationSystem === 'undefined') {
		console.error('Le script notification-system.js n\'est pas chargé correctement');
	}

	// Fonction pour charger les matières
	async function loadMatieres() {
		try {
			console.log('Chargement des matières...');
			const response = await fetch('api/matieres');
			const result = await response.json();
			console.log('Résultat matières:', result);

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
			console.error('Erreur lors du chargement des matières:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour charger les classes
	async function loadClasses() {
		try {
			console.log('Chargement des classes...');
			const response = await fetch('api/classes');
			const result = await response.json();
			console.log('Résultat classes:', result);

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
			console.error('Erreur lors du chargement des classes:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour charger les examens
	async function loadExams() {
		try {
			console.log('Chargement des examens...');
			const response = await fetch('api/exams');
			const result = await response.json();
			console.log('Résultat examens:', result);

			if (!result.success) {
				throw new Error(result.error || ErrorMessages.GENERAL.SERVER_ERROR);
			}

			const tbody = document.querySelector('#examsTable tbody');
			tbody.innerHTML = '';

			if (!result.data || result.data.length === 0) {
				tbody.innerHTML = '<tr><td colspan="5">Aucun examen trouvé</td></tr>';
				return;
			}

			result.data.forEach(exam => {
				const tr = document.createElement('tr');
				tr.innerHTML = `
					<td>${exam.titre}</td>
					<td>${exam.nom_matiere}</td>
					<td>${exam.nom_classe}</td>
					<td>${exam.date ? new Date(exam.date).toLocaleDateString('fr-FR') : 'Non défini'}</td>
					<td>
						<button class="btn btn-primary" onclick="editExam(${exam.id_exam})">Modifier</button>
						<button class="btn btn-danger" onclick="deleteExam(${exam.id_exam})">Supprimer</button>
						<button class="btn btn-info" onclick="manageNotes(${exam.id_exam})">Gérer les notes</button>
					</td>
				`;
				tbody.appendChild(tr);
			});
		} catch (error) {
			console.error('Erreur lors du chargement des examens:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Charger les données au chargement de la page
	document.addEventListener('DOMContentLoaded', function() {
		console.log('Chargement de la page...');

		// Tester le système de notification
		console.log('Test du système de notification...');
		NotificationSystem.info('Bienvenue sur la page de gestion des examens');

		loadMatieres();
		loadClasses();
		loadExams();

		// Ajouter l'écouteur d'événements pour le formulaire de création
		const examForm = document.getElementById('addExamForm');
		if (examForm) {
			console.log('Formulaire trouvé, ajout de l\'écouteur d\'événements');
			examForm.addEventListener('submit', createExam);
		} else {
			console.error('Formulaire non trouvé');
			NotificationSystem.error('Erreur : Le formulaire d\'ajout d\'examen n\'a pas été trouvé');
		}
	});

	// Fonction pour créer un nouvel examen
	async function createExam(event) {
		event.preventDefault();
		console.log('Tentative d\'ajout d\'un examen...');

		const formData = {
			titre: document.getElementById('titre').value,
			matiere: document.getElementById('matiere').value,
			classe: document.getElementById('classe').value,
			date: document.getElementById('date').value
		};

		console.log('Données du formulaire:', formData);

		if (!formData.titre || !formData.matiere || !formData.classe || !formData.date) {
			NotificationSystem.warning('Veuillez remplir tous les champs du formulaire');
			return;
		}

		try {
			console.log('Envoi de la requête...');
			const response = await fetch('api/exams', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify(formData)
			});

			const result = await response.json();
			console.log('Résultat de la création:', result);

			if (!response.ok) {
				throw new Error(result.error || 'Erreur lors de la création de l\'examen');
			}

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors de la création de l\'examen');
			}

			console.log('Succès, affichage du message...');
			NotificationSystem.success('L\'examen a été créé avec succès');
			document.getElementById('addExamForm').reset();
			console.log('Chargement des examens...');
			loadExams();
		} catch (error) {
			console.error('Erreur lors de la création:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour modifier un examen
	async function editExam(id, currentTitre, currentMatiere, currentClasse, currentDate) {
		const modal = document.createElement('div');
		modal.className = 'modal';
		modal.innerHTML = `
			<div class="modal-content">
				<h3>Modifier l'examen</h3>
				<form id="editExamForm">
					<div class="form-row">
						<label for="edit_titre">Titre :</label>
						<input type="text" id="edit_titre" value="${currentTitre}" required>
					</div>
					<div class="form-row">
						<label for="edit_matiere">Matière :</label>
						<select id="edit_matiere" required>
							<option value="">Sélectionnez une matière</option>
						</select>
					</div>
					<div class="form-row">
						<label for="edit_classe">Classe :</label>
						<select id="edit_classe" required>
							<option value="">Sélectionnez une classe</option>
						</select>
					</div>
					<div class="form-row">
						<label for="edit_date">Date :</label>
						<input type="date" id="edit_date" value="${currentDate || ''}" required>
					</div>
					<div class="form-actions">
						<button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
						<button type="submit" class="btn">Enregistrer</button>
					</div>
				</form>
			</div>
		`;

		document.body.appendChild(modal);

		// Charger les matières et classes dans les select
		await Promise.all([
			loadMatieresForEdit(currentMatiere),
			loadClassesForEdit(currentClasse)
		]);

		// Gérer la soumission du formulaire
		document.getElementById('editExamForm').addEventListener('submit', async function(e) {
			e.preventDefault();
			const formData = {
				titre: document.getElementById('edit_titre').value,
				matiere: document.getElementById('edit_matiere').value,
				classe: document.getElementById('edit_classe').value,
				date: document.getElementById('edit_date').value
			};

			if (!formData.titre || !formData.matiere || !formData.classe || !formData.date) {
				NotificationSystem.warning('Veuillez remplir tous les champs');
				return;
			}

			try {
				const response = await fetch(`api/exams/${id}`, {
					method: 'PUT',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify(formData)
				});

				const result = await response.json();

				if (!result.success) {
					throw new Error(result.error || 'Erreur lors de la modification de l\'examen');
				}

				closeModal();
				NotificationSystem.success('L\'examen a été modifié avec succès');
				loadExams();
			} catch (error) {
				console.error('Erreur lors de la modification:', error);
				NotificationSystem.error(error.message);
			}
		});
	}

	// Fonction pour charger les matières dans le modal d'édition
	async function loadMatieresForEdit(selectedMatiere) {
		try {
			const response = await fetch('api/matieres');
			const result = await response.json();

			if (!result.success) {
				throw new Error(result.error || ErrorMessages.GENERAL.SERVER_ERROR);
			}

			const select = document.getElementById('edit_matiere');
			select.innerHTML = '<option value="">Sélectionnez une matière</option>';

			result.data.forEach(matiere => {
				const option = document.createElement('option');
				option.value = matiere.id_matiere;
				option.textContent = matiere.nom;
				option.selected = matiere.id_matiere == selectedMatiere;
				select.appendChild(option);
			});
		} catch (error) {
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour charger les classes dans le modal d'édition
	async function loadClassesForEdit(selectedClasse) {
		try {
			const response = await fetch('api/classes');
			const result = await response.json();

			if (!result.success) {
				throw new Error(result.error || ErrorMessages.GENERAL.SERVER_ERROR);
			}

			const select = document.getElementById('edit_classe');
			select.innerHTML = '<option value="">Sélectionnez une classe</option>';

			result.data.forEach(classe => {
				const option = document.createElement('option');
				option.value = classe.id_classe;
				option.textContent = `${classe.nom_classe} (${classe.niveau}${classe.numero})`;
				option.selected = classe.id_classe == selectedClasse;
				select.appendChild(option);
			});
		} catch (error) {
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour fermer le modal
	function closeModal() {
		const modal = document.querySelector('.modal');
		if (modal) {
			modal.remove();
		}
	}

	// Fonction pour supprimer un examen
	async function deleteExam(id) {
		if (!confirm('Êtes-vous sûr de vouloir supprimer cet examen ?')) {
			return;
		}

		try {
			console.log('Tentative de suppression de l\'examen:', id);
			const response = await fetch(`api/exams/${id}`, {
				method: 'DELETE',
				headers: {
					'Content-Type': 'application/json'
				}
			});

			const result = await response.json();
			console.log('Résultat de la suppression:', result);

			if (!response.ok) {
				throw new Error(result.error || 'Erreur lors de la suppression de l\'examen');
			}

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors de la suppression de l\'examen');
			}

			NotificationSystem.success('L\'examen a été supprimé avec succès');
			loadExams();
		} catch (error) {
			console.error('Erreur lors de la suppression:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Ajouter les styles pour le modal et les notifications
	const modalStyle = document.createElement('style');
	modalStyle.textContent = `
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

		.form-row {
			margin-bottom: 15px;
		}

		.form-row label {
			display: block;
			margin-bottom: 5px;
			color: #555;
		}

		.form-row input,
		.form-row select {
			width: 100%;
			padding: 8px;
			border: 1px solid #ddd;
			border-radius: 4px;
			font-size: 14px;
		}

		.form-actions {
			display: flex;
			justify-content: flex-end;
			gap: 10px;
			margin-top: 20px;
		}

		.btn {
			padding: 8px 16px;
			border: none;
			border-radius: 4px;
			cursor: pointer;
			font-size: 14px;
			transition: background-color 0.3s;
		}

		.btn-secondary {
			background: #6c757d;
			color: white;
		}

		.btn-secondary:hover {
			background: #5a6268;
		}

		.btn-primary {
			background: #007bff;
			color: white;
		}

		.btn-primary:hover {
			background: #0056b3;
		}
	`;
	document.head.appendChild(modalStyle);

	// Ajouter la fonction manageNotes
	function manageNotes(examId) {
		window.location.href = `/gestion_notes.php?exam_id=${examId}`;
	}
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>