<?php
session_start();

// Redirection vers la page de connexion si non connecté
if (!isset($_SESSION['user']) || !isset($_SESSION['token'])) {
	header('Location: login.php');
	exit();
}

$pageTitle = "Gestion des Matières";
require_once 'templates/base.php';
?>

<div class="container">
	<div class="main-content">
		<div class="page-header d-flex justify-content-between align-items-center">
			<div>
				<h1>Gestion des Matières</h1>
				<p class="subtitle">Ajouter, modifier ou supprimer des matières</p>
			</div>
			<div id="connection-status">
				<span id="offline-badge" class="badge bg-warning" style="display: none;">
					<i class="fas fa-exclamation-triangle"></i> Mode hors-ligne
				</span>
			</div>
		</div>

		<div class="card mb-4">
			<div class="card-header">
				<h3>Liste des matières</h3>
			</div>
			<div class="card-body">
				<div id="matieres-loading" class="text-center py-4">
					<div class="spinner-border text-primary" role="status">
						<span class="visually-hidden">Chargement...</span>
					</div>
					<p class="mt-2">Chargement des matières...</p>
				</div>

				<div id="matieres-error" class="alert alert-danger" style="display: none;"></div>

				<div id="matieres-list" style="display: none;">
					<table id="matieresTable" class="table table-striped">
						<thead>
							<tr>
								<th>Nom</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>

				<button id="refresh-btn" class="btn btn-outline-primary btn-sm mt-3">
					<i class="fas fa-sync-alt"></i> Rafraîchir la liste
				</button>
			</div>
		</div>

		<div class="card">
			<div class="card-header">
				<h3 id="form-title">Ajouter une matière</h3>
			</div>
			<div class="card-body">
				<form id="matiere-form">
					<input type="hidden" id="matiere-id" value="">

					<div class="form-group mb-3">
						<label for="nom">Nom de la matière :</label>
						<input type="text" class="form-control" id="nom" name="nom" required>
					</div>

					<div class="d-flex justify-content-between">
						<button type="submit" class="btn btn-primary">
							<i class="fas fa-save"></i> <span id="btn-action-text">Ajouter</span>
						</button>

						<button type="button" id="btn-cancel" class="btn btn-secondary" style="display: none;">
							<i class="fas fa-times"></i> Annuler
						</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<style>
	.subtitle {
		color: #6c757d;
		font-size: 1.1rem;
		margin-bottom: 20px;
	}

	.btn-action {
		margin-right: 5px;
	}

	#offline-badge {
		font-size: 0.9rem;
		padding: 0.5rem 0.75rem;
	}
</style>

<script src="js/notification-system.js?v=1.1"></script>
<script src="js/error-messages.js"></script>
<script src="js/config.js?v=5.0"></script>
<script src="js/api-service.js?v=2.0"></script>

<script>
	// Mode édition
	let editMode = false;
	let currentMatiere = null;

	// Fonction pour charger les matières
	async function loadMatieres() {
		try {
			console.log('Chargement des matières...');

			// Afficher l'indicateur de chargement
			document.getElementById('matieres-loading').style.display = 'block';
			document.getElementById('matieres-list').style.display = 'none';
			document.getElementById('matieres-error').style.display = 'none';

			// Appel API via notre service
			const result = await ApiService.request('matieres');
			console.log('Résultat de la requête:', result);

			// Gérer les erreurs
			if (!result.success) {
				throw new Error(result.error || (result.data && result.data.message) || 'Erreur lors du chargement des matières');
			}

			// Vérifier si nous avons des données
			let matieres = [];
			if (result.data && result.data.data) {
				matieres = result.data.data;
			} else if (result.data) {
				matieres = Array.isArray(result.data) ? result.data : [];
			}

			// Vérifier les données simulées
			if (result.data && result.data.simulated) {
				console.warn('Utilisation de données simulées pour les matières');
				NotificationSystem.warning('Mode hors-ligne - Données simulées');
				document.getElementById('offline-badge').style.display = 'inline-block';
			} else {
				document.getElementById('offline-badge').style.display = 'none';
			}

			// Afficher les matières dans le tableau
			displayMatieres(matieres);

			// Notification de succès
			NotificationSystem.success('Matières chargées avec succès');

		} catch (error) {
			console.error('Erreur lors du chargement des matières:', error);
			NotificationSystem.error(error.message);
			document.getElementById('matieres-error').textContent = error.message;
			document.getElementById('matieres-error').style.display = 'block';
		} finally {
			// Masquer l'indicateur de chargement
			document.getElementById('matieres-loading').style.display = 'none';
			document.getElementById('matieres-list').style.display = 'block';
		}
	}

	// Fonction pour afficher les matières dans le tableau
	function displayMatieres(matieres) {
		const tbody = document.querySelector('#matieresTable tbody');
		tbody.innerHTML = '';

		if (matieres.length === 0) {
			tbody.innerHTML = '<tr><td colspan="2" class="text-center">Aucune matière trouvée</td></tr>';
			return;
		}

		matieres.forEach(matiere => {
			const tr = document.createElement('tr');
			tr.innerHTML = `
				<td>${matiere.nom}</td>
				<td>
					<button class="btn btn-sm btn-outline-primary btn-action" onclick="editMatiere(${matiere.id_matiere || matiere.id}, '${matiere.nom}')">
						<i class="fas fa-edit"></i> Modifier
					</button>
					<button class="btn btn-sm btn-outline-danger btn-action" onclick="deleteMatiere(${matiere.id_matiere || matiere.id})">
						<i class="fas fa-trash"></i> Supprimer
					</button>
				</td>
			`;
			tbody.appendChild(tr);
		});
	}

	// Fonction pour éditer une matière
	function editMatiere(id, nom) {
		editMode = true;
		currentMatiere = {
			id_matiere: id,
			nom
		};

		document.getElementById('matiere-id').value = id;
		document.getElementById('nom').value = nom;
		document.getElementById('form-title').textContent = 'Modifier une matière';
		document.getElementById('btn-action-text').textContent = 'Modifier';
		document.getElementById('btn-cancel').style.display = 'block';

		// Scroll vers le formulaire
		document.getElementById('matiere-form').scrollIntoView({
			behavior: 'smooth'
		});
	}

	// Fonction pour annuler l'édition
	function cancelEdit() {
		editMode = false;
		currentMatiere = null;

		document.getElementById('matiere-id').value = '';
		document.getElementById('nom').value = '';
		document.getElementById('form-title').textContent = 'Ajouter une matière';
		document.getElementById('btn-action-text').textContent = 'Ajouter';
		document.getElementById('btn-cancel').style.display = 'none';
	}

	// Fonction pour supprimer une matière
	async function deleteMatiere(id) {
		if (!confirm('Êtes-vous sûr de vouloir supprimer cette matière ?')) {
			return;
		}

		try {
			console.log(`Suppression de la matière #${id}`);
			NotificationSystem.info('Suppression en cours...');

			// Appel API via notre service
			const result = await ApiService.request(`matieres/${id}`, 'DELETE');

			if (!result.success) {
				throw new Error(result.error || (result.data && result.data.message) || 'Erreur lors de la suppression de la matière');
			}

			// Vérifier si les données sont simulées
			if (result.data && result.data.simulated) {
				console.warn('Réponse simulée pour la suppression de matière');
				NotificationSystem.warning('Suppression simulée (backend indisponible)');
			}

			// Recharger les matières
			await loadMatieres();

			// Notification de succès
			NotificationSystem.success('Matière supprimée avec succès');

		} catch (error) {
			console.error('Erreur lors de la suppression de la matière:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour gérer la soumission du formulaire
	async function handleMatiereSubmit(event) {
		event.preventDefault();

		const nomInput = document.getElementById('nom');
		const nom = nomInput.value.trim();

		if (!nom) {
			NotificationSystem.warning('Veuillez entrer un nom de matière');
			nomInput.focus();
			return;
		}

		try {
			NotificationSystem.info('Traitement en cours...');

			if (editMode) {
				// Mode édition - Mettre à jour une matière existante
				const id = document.getElementById('matiere-id').value;
				console.log(`Mise à jour de la matière #${id}:`, {
					nom
				});

				const result = await ApiService.request(`matieres/${id}`, 'PUT', {
					nom
				});

				if (!result.success) {
					throw new Error(result.error || (result.data && result.data.message) || 'Erreur lors de la mise à jour de la matière');
				}

				// Vérifier si les données sont simulées
				if (result.data && result.data.simulated) {
					console.warn('Réponse simulée pour la mise à jour de matière');
					NotificationSystem.warning('Mise à jour simulée (backend indisponible)');
				}

				NotificationSystem.success('Matière mise à jour avec succès');
			} else {
				// Mode ajout - Créer une nouvelle matière
				console.log('Création d\'une matière:', {
					nom
				});

				const result = await ApiService.request('matieres', 'POST', {
					nom
				});

				if (!result.success) {
					throw new Error(result.error || (result.data && result.data.message) || 'Erreur lors de la création de la matière');
				}

				// Vérifier si les données sont simulées
				if (result.data && result.data.simulated) {
					console.warn('Réponse simulée pour la création de matière');
					NotificationSystem.warning('Création simulée (backend indisponible)');
				}

				NotificationSystem.success('Matière ajoutée avec succès');
			}

			// Réinitialiser le formulaire
			cancelEdit();
			nomInput.value = '';

			// Recharger les matières
			await loadMatieres();

		} catch (error) {
			console.error('Erreur lors de la soumission du formulaire:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Initialisation de la page
	document.addEventListener('DOMContentLoaded', async function() {
		try {
			// Vérifier que le système de notification est chargé
			if (typeof NotificationSystem === 'undefined') {
				console.error('Le système de notification n\'est pas chargé !');
				alert('Erreur de chargement du système de notification');
			}

			// Charger les matières
			await loadMatieres();

			// Gestionnaires d'événements
			document.getElementById('matiere-form').addEventListener('submit', handleMatiereSubmit);
			document.getElementById('btn-cancel').addEventListener('click', cancelEdit);
			document.getElementById('refresh-btn').addEventListener('click', loadMatieres);

		} catch (error) {
			console.error('Erreur lors de l\'initialisation de la page:', error);
			if (typeof NotificationSystem !== 'undefined') {
				NotificationSystem.error(error.message);
			} else {
				alert(`Erreur: ${error.message}`);
			}
		}
	});
</script>