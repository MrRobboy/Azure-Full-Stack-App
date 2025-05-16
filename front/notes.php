<head>
	<!-- ... existing head content ... -->
	<!-- Load our API service -->
	<script src="js/api-service.js"></script>
	<!-- ... other scripts ... -->
</head>

<!-- ... existing content ... -->

<script>
	document.addEventListener('DOMContentLoaded', function() {
		// Load notes data
		loadNotes();

		// Setup form handlers
		setupNotesForm();
	});

	// Load all notes
	async function loadNotes() {
		try {
			// Show loading indicator
			const loadingEl = document.getElementById('notes-loading');
			if (loadingEl) loadingEl.style.display = 'block';

			// Get user profile to determine role
			const userResult = await ApiService.getCurrentUser();
			if (!userResult.success) {
				NotificationSystem.showError('Erreur de chargement du profil utilisateur');
				console.error('Failed to load user profile:', userResult);
				return;
			}

			const userData = userResult.data;

			// Get notes based on user role
			let notesResult;

			if (userData.user.role === 'ELEVE') {
				// Student sees only their own notes
				notesResult = await ApiService.notes.getByStudent(userData.user.id);
			} else {
				// Admin/teacher sees all notes
				notesResult = await ApiService.notes.getAll();
			}

			if (!notesResult.success) {
				NotificationSystem.showError('Erreur de chargement des notes');
				console.error('Failed to load notes:', notesResult);
				return;
			}

			// Display notes
			displayNotes(notesResult.data, userData.user.role);

			// Hide loading indicator
			if (loadingEl) loadingEl.style.display = 'none';

		} catch (error) {
			console.error('Error loading notes:', error);
			NotificationSystem.showError('Erreur technique lors du chargement des notes');
		}
	}

	// Display notes in the UI
	function displayNotes(notesData, userRole) {
		const tableBody = document.getElementById('notes-table-body');
		if (!tableBody) return;

		// Clear the table body
		tableBody.innerHTML = '';

		// Check if we have notes data
		if (!notesData || !notesData.notes || notesData.notes.length === 0) {
			tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center">Aucune note disponible</td>
                </tr>
            `;
			return;
		}

		// Add each note to the table
		notesData.notes.forEach(note => {
			const row = document.createElement('tr');

			// Format the row based on user role
			if (userRole === 'ELEVE') {
				// Simplified view for students
				row.innerHTML = `
                    <td>${note.matiere}</td>
                    <td>${note.valeur}/20</td>
                    <td>${note.examen}</td>
                    <td>${new Date(note.date).toLocaleDateString()}</td>
                `;
			} else {
				// Full view for teachers/admins with actions
				row.innerHTML = `
                    <td>${note.eleve}</td>
                    <td>${note.classe || '-'}</td>
                    <td>${note.matiere}</td>
                    <td>${note.valeur}/20</td>
                    <td>${note.examen}</td>
                    <td>
                        <button class="btn btn-sm btn-primary edit-note" data-id="${note.id}" data-value="${note.valeur}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-note" data-id="${note.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;

				// Add event listeners for edit and delete buttons
				row.querySelector('.edit-note').addEventListener('click', function() {
					openEditModal(note);
				});

				row.querySelector('.delete-note').addEventListener('click', function() {
					confirmDeleteNote(note.id);
				});
			}

			tableBody.appendChild(row);
		});
	}

	// Setup the notes form for teachers/admins
	function setupNotesForm() {
		const noteForm = document.getElementById('note-form');
		if (!noteForm) return;

		// Load students, subjects and exams for dropdowns
		loadFormData();

		// Handle form submission
		noteForm.addEventListener('submit', async function(e) {
			e.preventDefault();

			// Get form data
			const formData = {
				eleve_id: parseInt(document.getElementById('eleve-select').value),
				matiere_id: parseInt(document.getElementById('matiere-select').value),
				examen_id: parseInt(document.getElementById('examen-select').value),
				valeur: parseFloat(document.getElementById('note-value').value)
			};

			// Validate form data
			if (!formData.eleve_id || !formData.matiere_id || !formData.examen_id || isNaN(formData.valeur)) {
				NotificationSystem.showError('Veuillez remplir tous les champs');
				return;
			}

			// Create note
			try {
				const result = await ApiService.notes.create(formData);

				if (result.success) {
					NotificationSystem.showSuccess('Note ajoutée avec succès');
					noteForm.reset();

					// Reload notes
					loadNotes();
				} else {
					NotificationSystem.showError('Erreur lors de l\'ajout de la note');
					console.error('Failed to add note:', result);
				}
			} catch (error) {
				console.error('Error creating note:', error);
				NotificationSystem.showError('Erreur technique lors de l\'ajout de la note');
			}
		});
	}

	// Load data for form dropdowns
	async function loadFormData() {
		try {
			// Load students
			const studentsResult = await ApiService.students.getAll();
			if (studentsResult.success) {
				populateDropdown('eleve-select', studentsResult.data.eleves, 'id', 'nom', 'prenom');
			}

			// Load subjects
			const subjectsResult = await ApiService.subjects.getAll();
			if (subjectsResult.success) {
				populateDropdown('matiere-select', subjectsResult.data.matieres, 'id', 'nom');
			}

			// Load exams
			const examsResult = await ApiService.exams.getAll();
			if (examsResult.success) {
				populateDropdown('examen-select', examsResult.data.examens, 'id', 'nom');
			}
		} catch (error) {
			console.error('Error loading form data:', error);
			NotificationSystem.showError('Erreur lors du chargement des données du formulaire');
		}
	}

	// Helper function to populate dropdowns
	function populateDropdown(selectId, items, valueKey, labelKey, secondaryLabelKey = null) {
		const select = document.getElementById(selectId);
		if (!select) return;

		// Clear previous options
		select.innerHTML = '<option value="">Sélectionner...</option>';

		// Add options
		items.forEach(item => {
			const option = document.createElement('option');
			option.value = item[valueKey];

			if (secondaryLabelKey) {
				option.textContent = `${item[labelKey]} ${item[secondaryLabelKey]}`;
			} else {
				option.textContent = item[labelKey];
			}

			select.appendChild(option);
		});
	}

	// Open edit modal for a note
	function openEditModal(note) {
		const modal = document.getElementById('edit-note-modal');
		const noteIdInput = document.getElementById('edit-note-id');
		const noteValueInput = document.getElementById('edit-note-value');

		if (!modal || !noteIdInput || !noteValueInput) return;

		// Set values
		noteIdInput.value = note.id;
		noteValueInput.value = note.valeur;

		// Update modal title
		const modalTitle = modal.querySelector('.modal-title');
		if (modalTitle) {
			modalTitle.textContent = `Modifier la note de ${note.eleve} en ${note.matiere}`;
		}

		// Show modal
		const modalInstance = new bootstrap.Modal(modal);
		modalInstance.show();

		// Setup save button
		const saveButton = document.getElementById('save-note-button');
		if (saveButton) {
			// Remove previous event listeners
			const newSaveButton = saveButton.cloneNode(true);
			saveButton.parentNode.replaceChild(newSaveButton, saveButton);

			// Add new event listener
			newSaveButton.addEventListener('click', async function() {
				// Get updated value
				const newValue = parseFloat(noteValueInput.value);

				// Validate
				if (isNaN(newValue) || newValue < 0 || newValue > 20) {
					NotificationSystem.showError('Veuillez saisir une note valide entre 0 et 20');
					return;
				}

				// Update note
				try {
					const result = await ApiService.notes.update(note.id, newValue);

					if (result.success) {
						NotificationSystem.showSuccess('Note modifiée avec succès');
						modalInstance.hide();

						// Reload notes
						loadNotes();
					} else {
						NotificationSystem.showError('Erreur lors de la modification de la note');
						console.error('Failed to update note:', result);
					}
				} catch (error) {
					console.error('Error updating note:', error);
					NotificationSystem.showError('Erreur technique lors de la modification de la note');
				}
			});
		}
	}

	// Confirm and delete a note
	function confirmDeleteNote(noteId) {
		if (confirm('Êtes-vous sûr de vouloir supprimer cette note ?')) {
			deleteNote(noteId);
		}
	}

	// Delete a note
	async function deleteNote(noteId) {
		try {
			const result = await ApiService.notes.delete(noteId);

			if (result.success) {
				NotificationSystem.showSuccess('Note supprimée avec succès');

				// Reload notes
				loadNotes();
			} else {
				NotificationSystem.showError('Erreur lors de la suppression de la note');
				console.error('Failed to delete note:', result);
			}
		} catch (error) {
			console.error('Error deleting note:', error);
			NotificationSystem.showError('Erreur technique lors de la suppression de la note');
		}
	}
</script>

<!-- ... existing code ... -->