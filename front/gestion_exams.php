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
		z-index: 1000;
		display: flex;
		flex-direction: column;
		align-items: flex-end;
	}

	.notification {
		position: relative;
		padding: 15px;
		border-radius: 4px;
		color: white;
		margin-bottom: 10px;
		width: 300px;
		box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
		animation: slideIn 0.3s ease-out;
	}

	.notification.error {
		background: #dc3545;
	}

	.notification.success {
		background: #28a745;
	}

	.notification .close {
		position: absolute;
		top: 5px;
		right: 10px;
		cursor: pointer;
		font-weight: bold;
		font-size: 20px;
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

<script src="js/error-messages.js"></script>
<script>
	// Vérifier que le script d'erreurs est chargé
	console.log('Vérification du chargement du script...');
	console.log('ErrorMessages:', typeof ErrorMessages);
	console.log('showError:', typeof showError);
	console.log('showSuccess:', typeof showSuccess);
	console.log('showNotification:', typeof showNotification);

	if (typeof ErrorMessages === 'undefined') {
		console.error('Le script error-messages.js n\'est pas chargé correctement');
	}

	// Fonction pour afficher une notification
	function showNotification(message, type = 'error') {
		console.log('Tentative d\'affichage d\'une notification:', {
			message,
			type
		});
		const container = document.querySelector('.notification-container') || createNotificationContainer();
		console.log('Conteneur de notification:', container);

		const notification = document.createElement('div');
		notification.className = `notification ${type}`;
		notification.innerHTML = `
			<span class="close">&times;</span>
			${message}
		`;
		console.log('Notification créée:', notification);

		// Supprimer les anciennes notifications du même type
		const oldNotifications = container.querySelectorAll(`.notification.${type}`);
		oldNotifications.forEach(notif => notif.remove());

		container.appendChild(notification);
		console.log('Notification ajoutée au conteneur');

		// Fermer la notification après 5 secondes
		setTimeout(() => {
			notification.classList.add('slideOut');
			setTimeout(() => notification.remove(), 300);
		}, 5000);

		// Fermer la notification au clic sur le bouton
		notification.querySelector('.close').addEventListener('click', () => {
			notification.classList.add('slideOut');
			setTimeout(() => notification.remove(), 300);
		});
	}

	// Fonction pour créer le conteneur de notifications s'il n'existe pas
	function createNotificationContainer() {
		console.log('Création d\'un nouveau conteneur de notifications');
		const container = document.createElement('div');
		container.className = 'notification-container';
		document.body.appendChild(container);
		console.log('Conteneur créé et ajouté au body:', container);
		return container;
	}

	// Fonction pour afficher une erreur
	function showError(message) {
		console.error('Affichage d\'une erreur:', message);
		showNotification(message, 'error');
	}

	// Fonction pour afficher un succès
	function showSuccess(message) {
		console.log('Affichage d\'un succès:', message);
		showNotification(message, 'success');
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
			showError(error.message);
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
			showError(error.message);
		}
	}

	// Fonction pour charger les examens
	async function loadExams() {
		try {
			console.log('Chargement des examens...');
			const response = await fetch('api/examens');
			const result = await response.json();
			console.log('Résultat examens:', result);

			if (!result.success) {
				throw new Error(result.error || ErrorMessages.GENERAL.SERVER_ERROR);
			}

			const tbody = document.querySelector('#examsTable tbody');
			tbody.innerHTML = '';

			if (!result.data || result.data.length === 0) {
				tbody.innerHTML = '<tr><td colspan="4">Aucun examen trouvé</td></tr>';
				return;
			}

			result.data.forEach(exam => {
				const tr = document.createElement('tr');
				tr.innerHTML = `
					<td>${exam.titre || ''}</td>
					<td>${exam.nom_matiere || ''}</td>
					<td>${exam.nom_classe || ''}</td>
					<td>
						<button class="btn btn-edit" onclick="editExam(${exam.id_exam}, '${exam.titre || ''}', ${exam.matiere || ''}, ${exam.classe || ''})">Modifier</button>
						<button class="btn btn-danger" onclick="deleteExam(${exam.id_exam})">Supprimer</button>
					</td>
				`;
				tbody.appendChild(tr);
			});
		} catch (error) {
			console.error('Erreur lors du chargement des examens:', error);
			showError(error.message);
		}
	}

	// Fonction pour modifier un examen
	async function editExam(id, currentTitre, currentMatiere, currentClasse) {
		const modal = document.createElement('div');
		modal.className = 'modal';
		modal.innerHTML = `
			<div class="modal-content">
				<h3>Modifier l'examen</h3>
				<form id="editExamForm">
					<div class="form-row">
						<label for="editTitre">Titre :</label>
						<input type="text" id="editTitre" value="${currentTitre}" required>
					</div>
					<div class="form-row">
						<label for="editMatiere">Matière :</label>
						<select id="editMatiere" required>
							<option value="">Sélectionnez une matière</option>
						</select>
					</div>
					<div class="form-row">
						<label for="editClasse">Classe :</label>
						<select id="editClasse" required>
							<option value="">Sélectionnez une classe</option>
						</select>
					</div>
					<div class="form-actions">
						<button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
						<button type="submit" class="btn">Enregistrer</button>
					</div>
				</form>
			</div>
		`;

		document.body.appendChild(modal);

		// Charger les matières et les classes
		await Promise.all([
			loadMatieresForEdit(currentMatiere),
			loadClassesForEdit(currentClasse)
		]);

		// Gérer la soumission du formulaire
		document.getElementById('editExamForm').addEventListener('submit', async function(e) {
			e.preventDefault();
			const newTitre = document.getElementById('editTitre').value;
			const newMatiere = document.getElementById('editMatiere').value;
			const newClasse = document.getElementById('editClasse').value;

			try {
				const response = await fetch(`api/examens/${id}`, {
					method: 'PUT',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						titre: newTitre,
						matiere: newMatiere,
						classe: newClasse
					})
				});

				const result = await response.json();

				if (!result.success) {
					throw new Error(result.error || ErrorMessages.EXAMS.UPDATE.ERROR);
				}

				closeModal();
				showSuccess(ErrorMessages.EXAMS.UPDATE.SUCCESS);
				loadExams();
			} catch (error) {
				showError(error.message);
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

			const select = document.getElementById('editMatiere');
			select.innerHTML = '<option value="">Sélectionnez une matière</option>';

			result.data.forEach(matiere => {
				const option = document.createElement('option');
				option.value = matiere.id_matiere;
				option.textContent = matiere.nom;
				option.selected = matiere.id_matiere == selectedMatiere;
				select.appendChild(option);
			});
		} catch (error) {
			showError(error.message);
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

			const select = document.getElementById('editClasse');
			select.innerHTML = '<option value="">Sélectionnez une classe</option>';

			result.data.forEach(classe => {
				const option = document.createElement('option');
				option.value = classe.id_classe;
				option.textContent = `${classe.nom_classe} (${classe.niveau}${classe.numero})`;
				option.selected = classe.id_classe == selectedClasse;
				select.appendChild(option);
			});
		} catch (error) {
			showError(error.message);
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
		if (confirm('Êtes-vous sûr de vouloir supprimer cet examen ?')) {
			try {
				const response = await fetch(`api/examens/${id}`, {
					method: 'DELETE'
				});

				const result = await response.json();

				if (!response.ok) {
					throw new Error(result.error || ErrorMessages.EXAMS.DELETE.ERROR);
				}

				if (!result.success) {
					throw new Error(result.error || ErrorMessages.EXAMS.DELETE.ERROR);
				}

				showSuccess(ErrorMessages.EXAMS.DELETE.SUCCESS);
				loadExams();
			} catch (error) {
				showError(error.message);
			}
		}
	}

	// Fonction pour ajouter un examen
	document.getElementById('addExamForm').addEventListener('submit', async function(e) {
		e.preventDefault();
		console.log('Tentative d\'ajout d\'un examen...');

		const titre = document.getElementById('titre').value;
		const matiere = document.getElementById('matiere').value;
		const classe = document.getElementById('classe').value;

		console.log('Données du formulaire:', {
			titre,
			matiere,
			classe
		});

		if (!titre || !matiere || !classe) {
			console.log('Champs manquants, affichage de l\'erreur...');
			showError(ErrorMessages.GENERAL.REQUIRED_FIELDS);
			return;
		}

		try {
			console.log('Envoi de la requête...');
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
			console.log('Résultat de la création:', result);

			if (!response.ok) {
				throw new Error(result.error || ErrorMessages.EXAMS.CREATE.ERROR);
			}

			if (!result.success) {
				throw new Error(result.error || ErrorMessages.EXAMS.CREATE.ERROR);
			}

			document.getElementById('titre').value = '';
			document.getElementById('matiere').value = '';
			document.getElementById('classe').value = '';
			console.log('Succès, affichage du message...');
			showSuccess(ErrorMessages.EXAMS.CREATE.SUCCESS);
			loadExams();
		} catch (error) {
			console.error('Erreur lors de l\'ajout de l\'examen:', error);
			showError(error.message);
		}
	});

	// Charger les données au chargement de la page
	document.addEventListener('DOMContentLoaded', () => {
		console.log('Chargement de la page...');
		loadMatieres();
		loadClasses();
		loadExams();
	});

	// Ajouter les styles pour le modal et les notifications
	const style = document.createElement('style');
	style.textContent = `
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

		.notification-container {
			position: fixed;
			top: 20px;
			right: 20px;
			z-index: 1001;
		}

		.notification {
			padding: 15px;
			margin-bottom: 10px;
			border-radius: 4px;
			color: white;
			animation: slideIn 0.3s ease-out;
			position: relative;
			min-width: 300px;
			box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
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
	`;
	document.head.appendChild(style);
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>