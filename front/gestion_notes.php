<?php
session_start();

if (!isset($_SESSION['prof_id'])) {
	header('Location: login.php');
	exit();
}

$pageTitle = "Gestion des Notes";
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
	input[type="email"],
	input[type="password"],
	input[type="number"],
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
</style>

<div class="container">
	<div class="main-content">
		<h1>Gestion des Notes</h1>

		<div id="examsList">
			<!-- La liste des examens sera chargée ici -->
		</div>

		<div id="notesList">
			<!-- Les notes seront chargées ici -->
		</div>

		<div id="addNoteForm" style="display: none;">
			<h3>Ajouter une note</h3>
			<form id="noteForm">
				<div class="form-row">
					<label for="etudiant">Étudiant :</label>
					<select name="etudiant" id="etudiant" required>
						<option value="">Sélectionnez un étudiant</option>
					</select>
				</div>

				<div class="form-row">
					<label for="note">Note :</label>
					<input type="number" name="note" id="note" min="0" max="20" step="0.5" required>
				</div>

				<button type="submit" class="btn">Ajouter la note</button>
			</form>
		</div>
	</div>
</div>

<script src="js/notification-system.js"></script>
<script src="js/error-messages.js"></script>
<script>
	let currentExamId = null;

	// Fonction pour charger la liste des examens
	async function loadExams() {
		try {
			const response = await fetch('api/examens');
			const result = await response.json();

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors du chargement des examens');
			}

			const examsList = document.getElementById('examsList');
			examsList.innerHTML = '<h3>Sélectionnez un examen</h3>';

			if (result.data.length === 0) {
				examsList.innerHTML += '<p>Aucun examen disponible</p>';
				return;
			}

			const table = document.createElement('table');
			table.innerHTML = `
			<thead>
				<tr>
					<th>Titre</th>
					<th>Matière</th>
					<th>Classe</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody></tbody>
		`;

			result.data.forEach(exam => {
				const tr = document.createElement('tr');
				tr.innerHTML = `
				<td>${exam.titre}</td>
				<td>${exam.nom_matiere}</td>
				<td>${exam.nom_classe}</td>
				<td>
					<button class="btn" onclick="selectExam(${exam.id_examen})">Gérer les notes</button>
				</td>
			`;
				table.querySelector('tbody').appendChild(tr);
			});

			examsList.appendChild(table);
		} catch (error) {
			console.error('Erreur:', error);
			NotificationSystem.error(error.message || ErrorMessages.GENERAL.SERVER_ERROR);
		}
	}

	// Fonction pour sélectionner un examen
	async function selectExam(examId) {
		currentExamId = examId;
		document.getElementById('addNoteForm').style.display = 'block';
		await Promise.all([loadEtudiants(), loadNotes()]);
	}

	// Fonction pour charger les étudiants
	async function loadEtudiants() {
		try {
			const response = await fetch(`api/examens/${currentExamId}/etudiants`);
			const result = await response.json();

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors du chargement des étudiants');
			}

			const select = document.getElementById('etudiant');
			select.innerHTML = '<option value="">Sélectionnez un étudiant</option>';

			result.data.forEach(etudiant => {
				const option = document.createElement('option');
				option.value = etudiant.id_user;
				option.textContent = `${etudiant.prenom} ${etudiant.nom}`;
				select.appendChild(option);
			});
		} catch (error) {
			console.error('Erreur:', error);
			NotificationSystem.error(error.message || ErrorMessages.GENERAL.SERVER_ERROR);
		}
	}

	// Fonction pour charger les notes
	async function loadNotes() {
		try {
			const response = await fetch(`api/examens/${currentExamId}/notes`);
			const result = await response.json();

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors du chargement des notes');
			}

			const notesList = document.getElementById('notesList');
			notesList.innerHTML = '<h3>Notes</h3>';

			if (result.data.length === 0) {
				notesList.innerHTML += '<p>Aucune note enregistrée</p>';
				return;
			}

			const table = document.createElement('table');
			table.innerHTML = `
			<thead>
				<tr>
					<th>Étudiant</th>
					<th>Note</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody></tbody>
		`;

			result.data.forEach(note => {
				const tr = document.createElement('tr');
				tr.innerHTML = `
				<td>${note.prenom} ${note.nom}</td>
				<td>${note.note}</td>
				<td>
					<button class="btn btn-edit" onclick="editNote(${note.id_note}, ${note.note})">Modifier</button>
					<button class="btn btn-danger" onclick="deleteNote(${note.id_note})">Supprimer</button>
				</td>
			`;
				table.querySelector('tbody').appendChild(tr);
			});

			notesList.appendChild(table);
		} catch (error) {
			console.error('Erreur:', error);
			NotificationSystem.error(error.message || ErrorMessages.GENERAL.SERVER_ERROR);
		}
	}

	// Fonction pour ajouter une note
	document.getElementById('noteForm').addEventListener('submit', async function(e) {
		e.preventDefault();

		const etudiant = document.getElementById('etudiant').value;
		const note = document.getElementById('note').value;

		try {
			const response = await fetch(`api/examens/${currentExamId}/notes`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					etudiant,
					note
				})
			});

			const result = await response.json();

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors de l\'ajout de la note');
			}

			NotificationSystem.success(result.message || 'Note ajoutée avec succès');
			document.getElementById('etudiant').value = '';
			document.getElementById('note').value = '';
			await loadNotes();
		} catch (error) {
			console.error('Erreur:', error);
			NotificationSystem.error(error.message || ErrorMessages.GENERAL.SERVER_ERROR);
		}
	});

	// Fonction pour modifier une note
	async function editNote(noteId, currentNote) {
		const newNote = prompt('Nouvelle note:', currentNote);

		if (newNote !== null && newNote !== currentNote) {
			try {
				const response = await fetch(`api/notes/${noteId}`, {
					method: 'PUT',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						note: newNote
					})
				});

				const result = await response.json();

				if (!result.success) {
					throw new Error(result.error || 'Erreur lors de la modification de la note');
				}

				NotificationSystem.success(result.message || 'Note modifiée avec succès');
				await loadNotes();
			} catch (error) {
				console.error('Erreur:', error);
				NotificationSystem.error(error.message || ErrorMessages.GENERAL.SERVER_ERROR);
			}
		}
	}

	// Fonction pour supprimer une note
	async function deleteNote(noteId) {
		if (confirm('Êtes-vous sûr de vouloir supprimer cette note ?')) {
			try {
				const response = await fetch(`api/notes/${noteId}`, {
					method: 'DELETE'
				});

				const result = await response.json();

				if (!result.success) {
					throw new Error(result.error || 'Erreur lors de la suppression de la note');
				}

				NotificationSystem.success(result.message || 'Note supprimée avec succès');
				await loadNotes();
			} catch (error) {
				console.error('Erreur:', error);
				NotificationSystem.error(error.message || ErrorMessages.GENERAL.SERVER_ERROR);
			}
		}
	}

	// Charger les examens au chargement de la page
	document.addEventListener('DOMContentLoaded', loadExams);
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>