<?php
session_start();

if (!isset($_SESSION['prof_id'])) {
	header('Location: login.php');
	exit();
}

// Ajouter des logs pour déboguer
error_log('GET params: ' . print_r($_GET, true));
error_log('exam_id: ' . (isset($_GET['exam_id']) ? $_GET['exam_id'] : 'non défini'));

if (!isset($_GET['exam_id']) || !is_numeric($_GET['exam_id'])) {
	error_log('Redirection vers gestion_exams.php car exam_id invalide');
	header('Location: gestion_exams.php');
	exit();
}

$examId = intval($_GET['exam_id']);
error_log('examId après conversion: ' . $examId);

$pageTitle = "Gestion des Notes";
require_once 'templates/base.php';
?>

<div class="container">
	<div class="main-content">
		<div class="page-header">
			<h1>Gestion des Notes</h1>
		</div>

		<div id="examInfo" class="card mb-4">
			<!-- Les informations de l'examen seront chargées ici -->
		</div>

		<div id="notesList" class="card mb-4">
			<!-- Les notes seront chargées ici -->
		</div>

		<div id="addNoteForm" class="card">
			<div class="card-header">
				<h3>Ajouter une note</h3>
			</div>
			<div class="card-body">
				<form id="noteForm">
					<div class="form-group">
						<label for="etudiant">Étudiant :</label>
						<select name="etudiant" id="etudiant" class="form-control" required>
							<option value="">Sélectionnez un étudiant</option>
						</select>
					</div>

					<div class="form-group">
						<label for="note">Note :</label>
						<input type="number" name="note" id="note" class="form-control" min="0" max="20" step="0.5" required>
					</div>

					<button type="submit" class="btn btn-primary">Ajouter la note</button>
				</form>
			</div>
		</div>
	</div>
</div>

<script src="js/notification-system.js"></script>
<script>
	const examId = <?php echo $examId; ?>;
	console.log('🚀 Initialisation de la page de gestion des notes');
	console.log('ID de l\'examen:', examId);

	// Fonction utilitaire pour logger les requêtes et réponses
	async function fetchWithLogging(url, options = {}) {
		console.group(`🌐 Requête API: ${url}`);
		console.log('Options:', options);

		// Ajouter les cookies de session aux options
		options.credentials = 'include';

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
			} = await fetchWithLogging(`api/exams/${examId}`);

			if (!result.success) {
				throw new Error(result.message || 'Erreur lors du chargement des informations de l\'examen');
			}

			// Stocker les informations de l'examen pour une utilisation ultérieure
			window.examInfo = result.data;

			const examInfo = document.getElementById('examInfo');
			examInfo.innerHTML = `
				<div class="card-body">
					<h3>${result.data.titre}</h3>
					<p>Matière : ${result.data.nom_matiere}</p>
					<p>Classe : ${result.data.nom_classe}</p>
					<p>Date : ${result.data.date ? new Date(result.data.date).toLocaleDateString('fr-FR') : 'Non définie'}</p>
				</div>
			`;

			await loadStudents(result.data.id_classe);
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
			} = await fetchWithLogging(`api/classes/eleves/${classeId}`);

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
			} = await fetchWithLogging(`api/notes/exam/${examId}`);

			if (!result.success) {
				throw new Error(result.message || 'Erreur lors du chargement des notes');
			}

			const notesList = document.getElementById('notesList');
			notesList.innerHTML = `
				<div class="card-header">
					<h3>Notes de l'examen</h3>
				</div>
				<div class="card-body">
			`;

			if (result.data.length === 0) {
				notesList.querySelector('.card-body').innerHTML = '<p>Aucune note disponible</p>';
				return;
			}

			const table = document.createElement('table');
			table.className = 'table table-striped';
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
						<button class="btn btn-warning btn-sm" onclick="editNote(${note.id_note})">Modifier</button>
						<button class="btn btn-danger btn-sm" onclick="deleteNote(${note.id_note})">Supprimer</button>
					</td>
				`;
				table.querySelector('tbody').appendChild(tr);
			});

			notesList.querySelector('.card-body').appendChild(table);
		} catch (error) {
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour ajouter une note
	async function addNote(event) {
		event.preventDefault();
		const form = event.target;
		const formData = new FormData(form);

		try {
			const {
				data: result
			} = await fetchWithLogging(`api/notes`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					exam_id: examId,
					user_id: formData.get('etudiant'),
					valeur: formData.get('note')
				})
			});

			if (!result.success) {
				throw new Error(result.message || 'Erreur lors de l\'ajout de la note');
			}

			NotificationSystem.success('Note ajoutée avec succès');
			form.reset();
			await loadNotes(examId);
		} catch (error) {
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour modifier une note
	async function editNote(noteId) {
		const newNote = prompt('Entrez la nouvelle note :');
		if (newNote === null) return;

		try {
			const {
				data: result
			} = await fetchWithLogging(`api/notes/${noteId}`, {
				method: 'PUT',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					valeur: newNote
				})
			});

			if (!result.success) {
				throw new Error(result.message || 'Erreur lors de la modification de la note');
			}

			NotificationSystem.success('Note modifiée avec succès');
			await loadNotes(examId);
		} catch (error) {
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour supprimer une note
	async function deleteNote(noteId) {
		if (!confirm('Êtes-vous sûr de vouloir supprimer cette note ?')) return;

		try {
			const {
				data: result
			} = await fetchWithLogging(`api/notes/${noteId}`, {
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
	document.getElementById('noteForm').addEventListener('submit', addNote);
	loadExamInfo();
</script>