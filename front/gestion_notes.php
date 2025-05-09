<?php
session_start();

if (!isset($_SESSION['prof_id'])) {
	header('Location: login.php');
	exit();
}

if (!isset($_GET['exam_id'])) {
	header('Location: gestion_exams.php');
	exit();
}

$examId = $_GET['exam_id'];
$pageTitle = "Gestion des Notes";
require_once 'templates/base.php';
?>

<div class="container">
	<div class="main-content">
		<h1>Gestion des Notes</h1>

		<div id="examInfo" class="mb-4">
			<!-- Les informations de l'examen seront chargées ici -->
		</div>

		<div id="notesList" class="mb-4">
			<!-- Les notes seront chargées ici -->
		</div>

		<div id="addNoteForm" class="form-container">
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

				<button type="submit" class="btn btn-primary">Ajouter la note</button>
			</form>
		</div>
	</div>
</div>

<script src="/js/notification-system.js"></script>
<script>
	const examId = <?php echo $examId; ?>;

	// Fonction pour charger les informations de l'examen
	async function loadExamInfo() {
		try {
			const response = await fetch(`/api/exams/${examId}`);
			const result = await response.json();

			if (!result.success) {
				throw new Error(result.message || 'Erreur lors du chargement des informations de l\'examen');
			}

			const examInfo = document.getElementById('examInfo');
			examInfo.innerHTML = `
				<div class="card">
					<div class="card-body">
						<h3>${result.data.titre}</h3>
						<p>Matière : ${result.data.nom_matiere}</p>
						<p>Classe : ${result.data.nom_classe}</p>
						<p>Date : ${result.data.date ? new Date(result.data.date).toLocaleDateString('fr-FR') : 'Non définie'}</p>
					</div>
				</div>
			`;

			await loadStudents(result.data.classe);
			await loadNotes(examId);
		} catch (error) {
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour charger les étudiants d'une classe
	async function loadStudents(classeId) {
		try {
			const response = await fetch(`/api/classes/eleves/${classeId}`);
			const result = await response.json();

			if (!result.success) {
				throw new Error(result.message || 'Erreur lors du chargement des étudiants');
			}

			const select = document.getElementById('etudiant');
			select.innerHTML = '<option value="">Sélectionnez un étudiant</option>';

			result.data.forEach(student => {
				const option = document.createElement('option');
				option.value = student.id_user;
				option.textContent = `${student.nom} ${student.prenom}`;
				select.appendChild(option);
			});
		} catch (error) {
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour charger les notes d'un examen
	async function loadNotes(examId) {
		try {
			const response = await fetch(`/api/notes/exam/${examId}`);
			const result = await response.json();

			if (!result.success) {
				throw new Error(result.message || 'Erreur lors du chargement des notes');
			}

			const notesList = document.getElementById('notesList');
			notesList.innerHTML = '<h3>Notes de l\'examen</h3>';

			if (result.data.length === 0) {
				notesList.innerHTML += '<p>Aucune note disponible</p>';
				return;
			}

			const table = document.createElement('table');
			table.className = 'table';
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
					<td>${note.nom} ${note.prenom}</td>
					<td>${note.valeur}</td>
					<td>
						<button class="btn btn-warning" onclick="editNote(${note.id_note})">Modifier</button>
						<button class="btn btn-danger" onclick="deleteNote(${note.id_note})">Supprimer</button>
					</td>
				`;
				table.querySelector('tbody').appendChild(tr);
			});

			notesList.appendChild(table);
		} catch (error) {
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour ajouter une note
	async function addNote(event) {
		event.preventDefault();
		try {
			const formData = new FormData(event.target);
			const data = {
				id_eleve: formData.get('etudiant'),
				id_examen: examId,
				valeur: formData.get('note')
			};

			const response = await fetch('/api/notes', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify(data)
			});

			const result = await response.json();

			if (!result.success) {
				throw new Error(result.message || 'Erreur lors de l\'ajout de la note');
			}

			NotificationSystem.success('Note ajoutée avec succès');
			event.target.reset();
			await loadNotes(examId);
		} catch (error) {
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour modifier une note
	async function editNote(noteId) {
		try {
			const response = await fetch(`/api/notes/${noteId}`);
			const result = await response.json();

			if (!result.success) {
				throw new Error(result.message || 'Erreur lors de la récupération de la note');
			}

			const note = prompt('Entrez la nouvelle note :', result.data.valeur);
			if (note === null) return;

			const updateResponse = await fetch(`/api/notes/${noteId}`, {
				method: 'PUT',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					valeur: note
				})
			});

			const updateResult = await updateResponse.json();

			if (!updateResult.success) {
				throw new Error(updateResult.message || 'Erreur lors de la modification de la note');
			}

			NotificationSystem.success('Note modifiée avec succès');
			await loadNotes(examId);
		} catch (error) {
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour supprimer une note
	async function deleteNote(noteId) {
		try {
			if (!confirm('Êtes-vous sûr de vouloir supprimer cette note ?')) {
				return;
			}

			const response = await fetch(`/api/notes/${noteId}`, {
				method: 'DELETE'
			});

			const result = await response.json();

			if (!result.success) {
				throw new Error(result.message || 'Erreur lors de la suppression de la note');
			}

			NotificationSystem.success('Note supprimée avec succès');
			await loadNotes(examId);
		} catch (error) {
			NotificationSystem.error(error.message);
		}
	}

	// Initialisation
	document.addEventListener('DOMContentLoaded', () => {
		loadExamInfo();
		document.getElementById('noteForm').addEventListener('submit', addNote);
	});
</script>