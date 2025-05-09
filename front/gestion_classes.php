<?php
session_start();

if (!isset($_SESSION['prof_id'])) {
	header('Location: login.php');
	exit();
}

$pageTitle = "Gestion des Classes";
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

	.form-actions {
		display: flex;
		justify-content: flex-end;
		gap: 10px;
		margin-top: 20px;
	}

	.btn-secondary {
		background: #6c757d;
		color: white;
	}

	.btn-secondary:hover {
		background: #5a6268;
	}
</style>

<div class="container">
	<div class="main-content">
		<h1>Gestion des Classes</h1>

		<div class="form-container">
			<h3>Ajouter une classe</h3>
			<form id="addClasseForm">
				<div class="form-row">
					<label for="nom_classe">Nom de la classe :</label>
					<input type="text" name="nom_classe" id="nom_classe" required>
				</div>
				<div class="form-row">
					<label for="niveau">Niveau :</label>
					<select name="niveau" id="niveau" required>
						<option value="">Sélectionnez un niveau</option>
						<option value="1ère Année">1ère Année</option>
						<option value="2ème Année">2ème Année</option>
						<option value="3ème Année">3ème Année</option>
						<option value="4ème Année">4ème Année</option>
						<option value="5ème Année">5ème Année</option>
					</select>
				</div>
				<div class="form-row">
					<label for="numero">Numéro :</label>
					<input type="text" name="numero" id="numero" required>
				</div>
				<div class="form-row">
					<label for="rythme">Rythme :</label>
					<select name="rythme" id="rythme" required>
						<option value="">Sélectionnez un rythme</option>
						<option value="Alternance">Alternance</option>
						<option value="Initial">Initial</option>
					</select>
				</div>
				<button type="submit" class="btn">Ajouter la classe</button>
			</form>
		</div>

		<h3>Liste des classes</h3>
		<div class="table-responsive">
			<table class="table" id="classesTable">
				<thead>
					<tr>
						<th>Nom</th>
						<th>Niveau</th>
						<th>Numéro</th>
						<th>Rythme</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<!-- Les classes seront chargées dynamiquement -->
				</tbody>
			</table>
		</div>
	</div>
</div>

<script src="js/notification-system.js"></script>
<script src="js/error-messages.js"></script>
<script src="js/config.js"></script>
<script>
	// Vérifier que les scripts sont chargés
	console.log('Vérification du chargement des scripts...');
	console.log('NotificationSystem:', typeof NotificationSystem);
	console.log('ErrorMessages:', typeof ErrorMessages);

	if (typeof NotificationSystem === 'undefined') {
		console.error('Le script notification-system.js n\'est pas chargé correctement');
	}

	if (typeof ErrorMessages === 'undefined') {
		console.error('Le script error-messages.js n\'est pas chargé correctement');
	}

	// Fonction pour charger les classes
	async function loadClasses() {
		try {
			console.log('Chargement des classes...');
			const response = await fetch(getApiUrl('classes'));
			const result = await response.json();
			console.log('Résultat classes:', result);

			if (!result.success) {
				throw new Error(result.error || ErrorMessages.GENERAL.SERVER_ERROR);
			}

			const tbody = document.querySelector('#classesTable tbody');
			tbody.innerHTML = '';

			if (!result.data || result.data.length === 0) {
				tbody.innerHTML = '<tr><td colspan="5">Aucune classe trouvée</td></tr>';
				return;
			}

			result.data.forEach(classe => {
				const tr = document.createElement('tr');
				tr.innerHTML = `
					<td>${classe.nom_classe}</td>
					<td>${classe.niveau}</td>
					<td>${classe.numero}</td>
					<td>${classe.rythme}</td>
					<td>
						<button class="btn btn-edit" onclick="editClasse(${classe.id_classe}, '${classe.nom_classe}', '${classe.niveau}', '${classe.numero}', '${classe.rythme}')">Modifier</button>
						<button class="btn btn-danger" onclick="deleteClasse(${classe.id_classe})">Supprimer</button>
					</td>
				`;
				tbody.appendChild(tr);
			});
		} catch (error) {
			console.error('Erreur lors du chargement des classes:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Fonction pour ajouter une classe
	document.getElementById('addClasseForm').addEventListener('submit', async function(e) {
		e.preventDefault();
		const formData = {
			nom_classe: document.getElementById('nom_classe').value,
			niveau: document.getElementById('niveau').value,
			numero: document.getElementById('numero').value,
			rythme: document.getElementById('rythme').value
		};

		if (!formData.nom_classe || !formData.niveau || !formData.numero || !formData.rythme) {
			NotificationSystem.warning('Veuillez remplir tous les champs du formulaire');
			return;
		}

		try {
			const response = await fetch(getApiUrl('classes'), {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify(formData)
			});

			const result = await response.json();
			console.log('Résultat de la création:', result);

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors de l\'ajout de la classe');
			}

			document.getElementById('addClasseForm').reset();
			NotificationSystem.success('La classe a été ajoutée avec succès');
			loadClasses();
		} catch (error) {
			console.error('Erreur lors de l\'ajout de la classe:', error);
			NotificationSystem.error(error.message);
		}
	});

	// Fonction pour modifier une classe
	async function editClasse(id, currentNom, currentNiveau, currentNumero, currentRythme) {
		const modal = document.createElement('div');
		modal.className = 'modal';
		modal.innerHTML = `
			<div class="modal-content">
				<h3>Modifier la classe</h3>
				<form id="editClasseForm">
					<div class="form-row">
						<label for="edit_nom_classe">Nom de la classe :</label>
						<input type="text" id="edit_nom_classe" value="${currentNom}" required>
					</div>
					<div class="form-row">
						<label for="edit_niveau">Niveau :</label>
						<select id="edit_niveau" required>
							<option value="">Sélectionnez un niveau</option>
							<option value="1ère Année" ${currentNiveau === '1ère Année' ? 'selected' : ''}>1ère Année</option>
							<option value="2ème Année" ${currentNiveau === '2ème Année' ? 'selected' : ''}>2ème Année</option>
							<option value="3ème Année" ${currentNiveau === '3ème Année' ? 'selected' : ''}>3ème Année</option>
							<option value="4ème Année" ${currentNiveau === '4ème Année' ? 'selected' : ''}>4ème Année</option>
							<option value="5ème Année" ${currentNiveau === '5ème Année' ? 'selected' : ''}>5ème Année</option>
						</select>
					</div>
					<div class="form-row">
						<label for="edit_numero">Numéro :</label>
						<input type="text" id="edit_numero" value="${currentNumero}" required>
					</div>
					<div class="form-row">
						<label for="edit_rythme">Rythme :</label>
						<select id="edit_rythme" required>
							<option value="">Sélectionnez un rythme</option>
							<option value="Alternance" ${currentRythme === 'Alternance' ? 'selected' : ''}>Alternance</option>
							<option value="Initial" ${currentRythme === 'Initial' ? 'selected' : ''}>Initial</option>
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

		// Gérer la soumission du formulaire
		document.getElementById('editClasseForm').addEventListener('submit', async function(e) {
			e.preventDefault();
			const formData = {
				nom_classe: document.getElementById('edit_nom_classe').value,
				niveau: document.getElementById('edit_niveau').value,
				numero: document.getElementById('edit_numero').value,
				rythme: document.getElementById('edit_rythme').value
			};

			if (!formData.nom_classe || !formData.niveau || !formData.numero || !formData.rythme) {
				NotificationSystem.warning('Veuillez remplir tous les champs du formulaire');
				return;
			}

			try {
				const response = await fetch(`${getApiUrl('classes')}/${id}`, {
					method: 'PUT',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify(formData)
				});

				const result = await response.json();

				if (!result.success) {
					throw new Error(result.error || 'Erreur lors de la modification de la classe');
				}

				closeModal();
				NotificationSystem.success('La classe a été modifiée avec succès');
				loadClasses();
			} catch (error) {
				console.error('Erreur lors de la modification:', error);
				NotificationSystem.error(error.message);
			}
		});
	}

	// Fonction pour fermer le modal
	function closeModal() {
		const modal = document.querySelector('.modal');
		if (modal) {
			modal.remove();
		}
	}

	// Fonction pour supprimer une classe
	async function deleteClasse(id) {
		if (!confirm('Êtes-vous sûr de vouloir supprimer cette classe ?')) {
			return;
		}

		try {
			console.log('Tentative de suppression de la classe:', id);
			const response = await fetch(`${getApiUrl('classes')}/${id}`, {
				method: 'DELETE',
				headers: {
					'Content-Type': 'application/json'
				}
			});

			const result = await response.json();
			console.log('Résultat de la suppression:', result);

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors de la suppression de la classe');
			}

			NotificationSystem.success('La classe a été supprimée avec succès');
			loadClasses();
		} catch (error) {
			console.error('Erreur lors de la suppression:', error);
			NotificationSystem.error(error.message);
		}
	}

	// Charger les données au chargement de la page
	document.addEventListener('DOMContentLoaded', function() {
		console.log('Chargement de la page...');

		// Tester le système de notification
		console.log('Test du système de notification...');
		NotificationSystem.info('Bienvenue sur la page de gestion des classes');

		loadClasses();
	});
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>