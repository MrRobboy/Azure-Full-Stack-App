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

<style>
	/* Styles pour les étudiants privilégiés */
	.privileged-student {
		color: #ffc107;
		font-weight: bold;
	}

	.form-text.text-warning {
		color: #ff9800;
		margin-top: 5px;
		padding: 5px 10px;
		background-color: rgba(255, 152, 0, 0.1);
		border-left: 3px solid #ff9800;
		border-radius: 3px;
	}

	.form-text.text-muted {
		margin-top: 5px;
		font-size: 12px;
		color: #6c757d;
	}

	/* Styles pour améliorer la lisibilité du formulaire */
	.form-group {
		margin-bottom: 20px;
	}

	.form-control:focus {
		border-color: #007bff;
		box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
	}

	/* Style pour l'option sélectionnée avec privilège */
	#etudiant option:checked.privileged-student {
		background-color: rgba(255, 193, 7, 0.2);
	}
</style>

<script src="js/notification-system.js?v=1.1"></script>
<script src="js/error-messages.js"></script>
<script src="js/config.js?v=1.1"></script>
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

	// Cache pour les privilèges d'étudiants appris lors des erreurs
	let learnedPrivileges = {};

	// Fonction pour apprendre et stocker les privilèges des étudiants à partir des erreurs
	function learnPrivilegeFromError(studentId, error) {
		if (!error) return null;

		// Essayer d'extraire la note minimale de l'erreur
		const minNoteRegex = /ne peut pas avoir une note inférieure à (\d+\.?\d*)/i;
		const match = error.match(minNoteRegex);

		if (match && match[1]) {
			const minNote = parseFloat(match[1]);

			// Stocker cette information pour usage futur
			learnedPrivileges[studentId] = minNote;
			console.log(`Privilège appris pour l'étudiant ${studentId}: note minimale = ${minNote}`);

			// Mettre à jour l'option dans le sélecteur si nécessaire
			const select = document.getElementById('etudiant');
			for (let i = 0; i < select.options.length; i++) {
				const option = select.options[i];
				if (option.value == studentId && !option.dataset.minNote) {
					option.textContent = `★ ${option.textContent}`;
					option.dataset.minNote = minNote;
					option.title = `Note minimum requise: ${minNote}`;
					option.className = 'privileged-student';

					// Si c'est l'option actuellement sélectionnée, mettre à jour le champ de note
					if (select.selectedIndex === i) {
						const noteInput = document.getElementById('note');
						noteInput.min = minNote;
						noteInput.title = `Note minimum: ${minNote}`;

						let minNoteInfo = document.getElementById('min-note-info');
						if (!minNoteInfo) {
							minNoteInfo = document.createElement('small');
							minNoteInfo.id = 'min-note-info';
							minNoteInfo.className = 'form-text text-warning';
							noteInput.parentNode.appendChild(minNoteInfo);
						}
						minNoteInfo.innerHTML = `<strong>Note:</strong> Cet étudiant ne peut pas avoir une note inférieure à ${minNote}`;
					}
					break;
				}
			}

			// Si aucune info-bulle d'explication n'est présente, ajouter
			const infoText = document.querySelector('.form-text.text-muted');
			if (!infoText) {
				const select = document.getElementById('etudiant');
				const newInfoText = document.createElement('small');
				newInfoText.className = 'form-text text-muted';
				newInfoText.innerHTML = '★ indique un étudiant avec une note minimale requise';
				select.parentNode.appendChild(newInfoText);
			}

			return minNote;
		}

		return null;
	}

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

			// Initialiser avec les privilèges déjà connus
			let studentPrivileges = {
				...learnedPrivileges
			};
			console.log('Privilèges déjà connus:', studentPrivileges);

			const {
				data: classeResult
			} = await fetchWithLogging(`api/users/classe/${classeId}`);
			console.log('Informations des étudiants reçues:', classeResult);

			if (!classeResult.success) {
				throw new Error(classeResult.message || 'Erreur lors du chargement des étudiants');
			}

			// Obtenir les informations sur les privilèges de chaque étudiant
			try {
				// Tentative d'appel à l'API des privilèges
				console.log('Tentative de récupération des privilèges des étudiants...');
				const response = await fetch(getApiUrl('privileges') + '/students', {
					headers: {
						'Accept': 'application/json',
						'Content-Type': 'application/json'
					},
					credentials: 'include'
				});

				// Si l'API renvoie une 404, c'est probablement parce que l'endpoint n'existe pas encore
				if (response.status === 404) {
					console.warn('API de privilèges non disponible (404): cela pourrait être normal si cette fonctionnalité n\'est pas encore implémentée côté serveur');
					// Continuer sans afficher d'erreur à l'utilisateur
				} else if (response.ok) {
					const privilegesResult = await response.json();
					if (privilegesResult.success && privilegesResult.data) {
						// Créer un mapping des privilèges par ID d'étudiant
						privilegesResult.data.forEach(privilege => {
							studentPrivileges[privilege.id_user] = privilege.min_note;
						});
					}
					console.log('Privilèges des étudiants:', studentPrivileges);
				} else {
					console.warn(`Erreur lors de la récupération des privilèges: ${response.status} ${response.statusText}`);
				}
			} catch (error) {
				console.warn('Impossible de charger les privilèges des étudiants:', error);
				// Ne pas afficher d'erreur à l'utilisateur, simplement logger
			}

			// Incorporer les privilèges déjà appris via les messages d'erreur
			if (Object.keys(learnedPrivileges).length > 0) {
				console.log('Incorporation des privilèges appris précédemment:', learnedPrivileges);
				studentPrivileges = {
					...studentPrivileges,
					...learnedPrivileges
				};
			}

			const select = document.getElementById('etudiant');
			select.innerHTML = '<option value="">Sélectionnez un étudiant</option>';

			if (classeResult.data && Array.isArray(classeResult.data) && classeResult.data.length > 0) {
				classeResult.data.forEach(student => {
					const option = document.createElement('option');
					option.value = student.id_user;

					// Vérifier si l'étudiant a un privilège de note minimum
					const hasPrivilege = studentPrivileges[student.id_user] !== undefined;
					const minNote = hasPrivilege ? studentPrivileges[student.id_user] : null;

					if (hasPrivilege) {
						option.textContent = `★ ${student.nom} ${student.prenom}`;
						option.dataset.minNote = minNote;
						option.title = `Note minimum requise: ${minNote}`;
						option.className = 'privileged-student';
					} else {
						option.textContent = `${student.nom} ${student.prenom}`;
					}

					select.appendChild(option);
				});

				// Ajouter une info-bulle pour expliquer l'étoile
				const infoText = document.createElement('small');
				infoText.className = 'form-text text-muted';
				infoText.innerHTML = '★ indique un étudiant avec une note minimale requise';
				select.parentNode.appendChild(infoText);

				// Ajouter un écouteur d'événements pour afficher la note minimale si disponible
				select.addEventListener('change', function() {
					const selectedOption = this.options[this.selectedIndex];
					const noteInput = document.getElementById('note');

					if (selectedOption.dataset.minNote) {
						const minNote = parseFloat(selectedOption.dataset.minNote);
						noteInput.min = minNote;
						noteInput.title = `Note minimum: ${minNote}`;

						// Ajouter ou mettre à jour l'info sur la note minimale
						let minNoteInfo = document.getElementById('min-note-info');
						if (!minNoteInfo) {
							minNoteInfo = document.createElement('small');
							minNoteInfo.id = 'min-note-info';
							minNoteInfo.className = 'form-text text-warning';
							noteInput.parentNode.appendChild(minNoteInfo);
						}
						minNoteInfo.innerHTML = `<strong>Note:</strong> Cet étudiant ne peut pas avoir une note inférieure à ${minNote}`;
					} else {
						noteInput.min = 0;
						noteInput.title = '';

						// Supprimer l'info sur la note minimale si elle existe
						const minNoteInfo = document.getElementById('min-note-info');
						if (minNoteInfo) {
							minNoteInfo.remove();
						}
					}
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

		const studentId = formData.get('etudiant');
		const grade = formData.get('note');

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
					id_eleve: studentId,
					id_matiere: examInfo.id_matiere,
					valeur: grade
				})
			});

			if (!result.success) {
				// Vérifier si l'erreur concerne un privilège de note minimum
				const minNoteRegex = /ne peut pas avoir une note inférieure à (\d+\.?\d*)/i;
				const match = result.error ? result.error.match(minNoteRegex) : null;

				if (match) {
					// Apprendre et enregistrer ce privilège pour le futur
					const minNote = learnPrivilegeFromError(studentId, result.error);

					const etudiant = document.getElementById('etudiant');
					const etudiantNom = etudiant.options[etudiant.selectedIndex].text;

					// Créer un message d'erreur personnalisé
					const errorMessage = `
						<div class="privilege-error">
							<strong>Privilège détecté</strong><br>
							L'étudiant ${etudiantNom} ne peut pas recevoir une note inférieure à <strong>${minNote}</strong>.<br>
							Veuillez entrer une note plus élevée.
						</div>`;

					NotificationSystem.error(errorMessage);
				} else {
					throw new Error(result.error || result.message || 'Erreur lors de l\'ajout de la note');
				}
				return;
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
						// Récupérer l'ID de l'étudiant à partir de la note
						let studentId = null;

						// Essayer de récupérer les détails de la note pour avoir l'ID de l'étudiant
						try {
							const noteDetailsResponse = await fetchWithLogging(`api/notes/${noteId}`);
							const noteDetails = noteDetailsResponse.data;
							if (noteDetails.success && noteDetails.data && noteDetails.data.id_eleve) {
								studentId = noteDetails.data.id_eleve;
							}
						} catch (error) {
							console.warn('Impossible de récupérer les détails de la note:', error);
						}

						// Vérifier si l'erreur concerne un privilège de note minimum
						const minNoteRegex = /ne peut pas avoir une note inférieure à (\d+\.?\d*)/i;
						const match = result.error ? result.error.match(minNoteRegex) : null;

						if (match) {
							// Si on a l'ID de l'étudiant, apprendre ce privilège
							if (studentId) {
								learnPrivilegeFromError(studentId, result.error);
							}

							const minNote = match[1]; // Extraire la valeur minimale

							// Créer un message d'erreur personnalisé
							const errorMessage = `
								<div class="privilege-error">
									<strong>Privilège détecté</strong><br>
									Cet étudiant ne peut pas recevoir une note inférieure à <strong>${minNote}</strong>.<br>
									Veuillez entrer une note plus élevée.
								</div>`;

							NotificationSystem.error(errorMessage);
						} else {
							throw new Error(result.error || 'Erreur lors de la modification de la note');
						}
						return;
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