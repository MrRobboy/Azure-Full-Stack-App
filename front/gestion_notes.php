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
	// Vérifier que le système de notification est bien chargé
	console.log('Système de notification disponible:', typeof NotificationSystem !== 'undefined');
	if (typeof NotificationSystem === 'undefined') {
		console.error('Le système de notification n\'est pas chargé !');
	}

	const examId = <?php echo $examId; ?>;
	let examInfo = null; // Variable globale pour stocker les informations de l'examen
	console.log('🚀 Initialisation de la page de gestion des notes');
	console.log('ID de l\'examen:', examId);

	// Fonction utilitaire pour logger les requêtes et réponses
	async function fetchWithLogging(url, options = {}) {
		console.group(`🌐 Requête API: ${url}`);
		console.log('Options:', options);

		// Ajouter les cookies de session aux options
		options.credentials = 'include';
		options.headers = {
			...options.headers,
			'Accept': 'application/json',
			'Content-Type': 'application/json'
		};

		try {
			console.log('Envoi de la requête...');
			const response = await fetch(url, options);
			console.log('Réponse reçue, status:', response.status);

			const contentType = response.headers.get('content-type');
			console.log('Content-Type:', contentType);

			if (!contentType || !contentType.includes('application/json')) {
				throw new Error(`Réponse non-JSON reçue: ${contentType}`);
			}

			const data = await response.json();
			console.log('Données reçues:', data);

			console.groupEnd();
			return {
				response,
				data
			};
		} catch (error) {
			console.error('Erreur lors de la requête:', error);
			console.groupEnd();
			throw error;
		}
	}

	// Fonction pour charger les informations de l'examen
	async function loadExamInfo() {
		try {
			console.log('Chargement des informations de l\'examen:', examId);
			const {
				data: result
			} = await fetchWithLogging(`api/examens/${examId}`);

			if (!result.success) {
				throw new Error(result.message || 'Erreur lors du chargement des informations de l\'examen');
			}

			console.log('Informations de l\'examen reçues:', result.data);

			// Stocker les informations de l'examen dans la variable globale
			examInfo = result.data;

			const examInfoDiv = document.getElementById('examInfo');
			examInfoDiv.innerHTML = `
				<div class="card-body">
					<h3>${result.data.titre}</h3>
					<p>Matière : ${result.data.nom_matiere}</p>
					<p>Classe : ${result.data.nom_classe}</p>
					<p>Date : ${result.data.date ? new Date(result.data.date).toLocaleDateString('fr-FR') : 'Non définie'}</p>
				</div>
			`;

			// Vérifier que nous avons bien l'ID de la classe
			if (!result.data.id_classe) {
				throw new Error('ID de la classe manquant dans les informations de l\'examen');
			}

			console.log('Chargement des étudiants pour la classe:', result.data.id_classe);
			await loadStudents(result.data.id_classe);
			await loadNotes(examId);
		} catch (error) {
			console.error('Erreur lors du chargement des informations de l\'examen:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour charger les étudiants d'une classe
	async function loadStudents(classeId) {
		try {
			console.log('Chargement des étudiants pour la classe:', classeId);
			const {
				data: classeResult
			} = await fetchWithLogging(`api/users/classe/${classeId}`);
			console.log('Informations des étudiants reçues:', classeResult);

			if (!classeResult.success) {
				throw new Error(classeResult.message || 'Erreur lors du chargement des étudiants');
			}

			const select = document.getElementById('etudiant');
			select.innerHTML = '<option value="">Sélectionnez un étudiant</option>';

			if (classeResult.data && Array.isArray(classeResult.data) && classeResult.data.length > 0) {
				classeResult.data.forEach(student => {
					const option = document.createElement('option');
					option.value = student.id_user;
					option.textContent = `${student.nom} ${student.prenom}`;
					select.appendChild(option);
				});
			} else {
				console.warn('Aucun étudiant trouvé pour cette classe');
				select.innerHTML = '<option value="">Aucun étudiant disponible</option>';
			}
		} catch (error) {
			console.error('Erreur lors du chargement des étudiants:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour charger les notes d'un examen
	async function loadNotes(examId) {
		try {
			console.log('Chargement des notes pour l\'examen:', examId);
			const {
				data: result
			} = await fetchWithLogging(`api/notes/exam/${examId}`);

			if (!result.success) {
				throw new Error(result.message || 'Erreur lors du chargement des notes');
			}

			console.log('Notes reçues:', result.data);

			const notesList = document.getElementById('notesList');
			notesList.innerHTML = `
				<div class="card-header">
					<h3>Notes de l'examen</h3>
				</div>
				<div class="card-body">
			`;

			if (!result.data || !Array.isArray(result.data) || result.data.length === 0) {
				notesList.querySelector('.card-body').innerHTML = '<p>Aucune note disponible pour cet examen</p>';
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
			console.error('Erreur lors du chargement des notes:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour ajouter une note
	async function addNote(event) {
		event.preventDefault();
		const form = event.target;
		const formData = new FormData(form);

		if (!examInfo) {
			NotificationSystem.error('Les informations de l\'examen ne sont pas disponibles');
			return;
		}

		try {
			const {
				data: result
			} = await fetchWithLogging(`api/notes`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					id_examen: examId,
					id_eleve: formData.get('etudiant'),
					id_matiere: examInfo.id_matiere,
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
			console.error('Erreur lors de l\'ajout de la note:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour modifier une note
	async function editNote(noteId) {
		try {
			console.log('Récupération de la note:', noteId);
			const {
				data: result
			} = await fetchWithLogging(`api/notes/${noteId}`);

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors de la récupération de la note');
			}

			console.log('Note récupérée:', result.data);
			const currentNote = result.data.valeur;

			// Créer le modal
			const modal = document.createElement('div');
			modal.className = 'modal';
			modal.innerHTML = `
				<div class="modal-content">
					<h3>Modifier la note</h3>
					<form id="editNoteForm">
						<div class="form-group">
							<label for="edit_note">Nouvelle note :</label>
							<input type="number" id="edit_note" class="form-control" min="0" max="20" step="0.5" value="${currentNote}" required>
						</div>
						<div class="form-actions">
							<button type="button" class="btn btn-secondary" onclick="closeNoteModal()">Annuler</button>
							<button type="submit" class="btn btn-primary">Enregistrer</button>
						</div>
					</form>
				</div>
			`;

			document.body.appendChild(modal);

			// Gérer la soumission du formulaire
			document.getElementById('editNoteForm').addEventListener('submit', async function(e) {
				e.preventDefault();
				const newNote = parseFloat(document.getElementById('edit_note').value);

				if (isNaN(newNote) || newNote < 0 || newNote > 20) {
					NotificationSystem.warning('La note doit être un nombre compris entre 0 et 20');
					return;
				}

				try {
					console.log('Envoi de la modification pour la note:', noteId, 'Nouvelle valeur:', newNote);
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

					console.log('Réponse de la modification:', result);

					if (!result.success) {
						throw new Error(result.error || 'Erreur lors de la modification de la note');
					}

					closeNoteModal();
					NotificationSystem.success('Note modifiée avec succès');
					await loadNotes(examId);
				} catch (error) {
					console.error('Erreur lors de la modification de la note:', error);
					NotificationSystem.error(error.message);
				}
			});
		} catch (error) {
			console.error('Erreur lors de la récupération de la note:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour fermer le modal de modification de note
	function closeNoteModal() {
		const modal = document.querySelector('.modal');
		if (modal) {
			modal.remove();
		}
	}

	// Ajouter les styles pour le modal
	const modalStyle = document.createElement('style');
	modalStyle.textContent = `
		.modal {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background-color: rgba(0, 0, 0, 0.5);
			display: flex;
			justify-content: center;
			align-items: center;
			z-index: 1000;
		}

		.modal-content {
			background: white;
			padding: 20px;
			border-radius: 8px;
			width: 90%;
			max-width: 400px;
			box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
		}

		.modal-content h3 {
			margin-top: 0;
			margin-bottom: 20px;
			color: #333;
		}

		.form-group {
			margin-bottom: 15px;
		}

		.form-group label {
			display: block;
			margin-bottom: 5px;
			color: #555;
		}

		.form-control {
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

		.btn-primary {
			background: #007bff;
			color: white;
		}

		.btn-primary:hover {
			background: #0056b3;
		}

		.btn-secondary {
			background: #6c757d;
			color: white;
		}

		.btn-secondary:hover {
			background: #5a6268;
		}
	`;
	document.head.appendChild(modalStyle);

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