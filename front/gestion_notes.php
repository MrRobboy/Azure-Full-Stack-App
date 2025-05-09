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

	// Fonction utilitaire pour logger les requêtes et réponses
	async function fetchWithLogging(url, options = {}) {
		console.group(`🌐 Requête API: ${url}`);
		console.log('Options:', options);

		try {
			const response = await fetch(url, options);
			const data = await response.json();

			console.log('Status:', response.status);
			console.log('Headers:', Object.fromEntries(response.headers.entries()));
			console.log('Réponse:', data);

			console.groupEnd();
			return {
				response,
				data
			};
		} catch (error) {
			console.error('Erreur:', error);
			console.groupEnd();
			throw error;
		}
	}

	// Fonction pour charger les informations de l'examen
	async function loadExamInfo() {
		try {
			const {
				data: result
			} = await fetchWithLogging(`/api/exams/${examId}`);

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
			const {
				data: result
			} = await fetchWithLogging(`/api/classes/eleves/${classeId}`);

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
			const {
				data: result
			} = await fetchWithLogging(`/api/notes/exam/${examId}`);

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

			const {
				data: result
			} = await fetchWithLogging('/api/notes', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify(data)
			});

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
			const {
				data: result
			} = await fetchWithLogging(`/api/notes/${noteId}`);

			if (!result.success) {
				throw new Error(result.message || 'Erreur lors de la récupération de la note');
			}

			const note = result.data;
			const form = document.getElementById('noteForm');
			form.querySelector('#etudiant').value = note.id_eleve;
			form.querySelector('#note').value = note.valeur;
		} catch (error) {
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour supprimer une note
	async function deleteNote(noteId) {
		if (!confirm('Êtes-vous sûr de vouloir supprimer cette note ?')) {
			return;
		}

		try {
			const {
				data: result
			} = await fetchWithLogging(`/api/notes/${noteId}`, {
				method: 'DELETE'
			});

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
		console.log('🚀 Initialisation de la page de gestion des notes');
		console.log('ID de l\'examen:', examId);

		// Chargement des informations de l'examen
		loadExamInfo();

		// Gestion du formulaire d'ajout de note
		document.getElementById('noteForm').addEventListener('submit', addNote);
	});
</script>