<?php
session_start();

if (!isset($_SESSION['prof_id'])) {
	header('Location: login.php');
	exit();
}

$pageTitle = "Gestion des Matières";
ob_start();
?>

<div class="container">
	<div class="main-content">
		<h1>Gestion des Matières</h1>

		<div class="form-container">
			<h3>Ajouter une matière</h3>
			<form id="addMatiereForm">
				<div class="form-row">
					<label for="nom">Nom de la matière :</label>
					<input type="text" name="nom" id="nom" required>
				</div>
				<button type="submit" class="btn">Ajouter la matière</button>
			</form>
		</div>

		<h3>Liste des matières</h3>
		<div class="table-responsive">
			<table class="table" id="matieresTable">
				<thead>
					<tr>
						<th>Nom</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<!-- Les matières seront chargées dynamiquement -->
				</tbody>
			</table>
		</div>
	</div>
</div>

<script src="assets/js/error-handler.js"></script>
<script>
	// Fonction pour charger les matières
	async function loadMatieres() {
		try {
			console.log('Début du chargement des matières...');
			const response = await fetch('../api/matieres');
			console.log('Réponse reçue:', response);

			if (!response.ok) {
				const errorData = await response.json();
				console.error('Erreur HTTP:', errorData);
				throw new Error(`Erreur HTTP ${response.status}: ${errorData.error || errorData.message || 'Erreur inconnue'}`);
			}

			const result = await response.json();
			console.log('Données reçues:', result);

			if (!result.success) {
				throw new Error(result.error || 'Erreur inconnue');
			}

			const tbody = document.querySelector('#matieresTable tbody');
			tbody.innerHTML = '';

			// Vérification que result.data est un tableau
			if (!Array.isArray(result.data)) {
				console.error('Format de données invalide:', {
					type: typeof result.data,
					value: result.data,
					expected: 'array',
					location: 'loadMatieres() - ligne 45'
				});
				throw new Error(`Format de données invalide : tableau attendu dans result.data, reçu ${typeof result.data}`);
			}

			if (result.data.length === 0) {
				console.log('Aucune matière trouvée');
				tbody.innerHTML = '<tr><td colspan="2">Aucune matière trouvée</td></tr>';
				return;
			}

			result.data.forEach((matiere, index) => {
				console.log(`Traitement de la matière ${index + 1}:`, matiere);
				if (!matiere.id_matiere || !matiere.nom) {
					console.error('Données de matière invalides:', matiere);
					throw new Error(`Données de matière invalides à l'index ${index}: ${JSON.stringify(matiere)}`);
				}
				const tr = document.createElement('tr');
				tr.innerHTML = `
					<td>${matiere.nom}</td>
					<td>
						<button class="btn btn-edit" onclick="editMatiere(${matiere.id_matiere}, '${matiere.nom}')">Modifier</button>
						<button class="btn btn-danger" onclick="deleteMatiere(${matiere.id_matiere})">Supprimer</button>
					</td>
				`;
				tbody.appendChild(tr);
			});
		} catch (error) {
			console.error('Erreur détaillée:', error);
			handleApiError(error);
		}
	}

	// Fonction pour ajouter une matière
	document.getElementById('addMatiereForm').addEventListener('submit', async function(e) {
		e.preventDefault();
		const nom = document.getElementById('nom').value;

		try {
			const response = await fetch('../api/matieres', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					nom
				})
			});

			const result = await response.json();
			console.log('Résultat de la création:', result);

			if (!result.success) {
				throw new Error(result.error || 'Erreur lors de l\'ajout de la matière');
			}

			document.getElementById('nom').value = '';
			showSuccess(result.message || 'Matière ajoutée avec succès');
			loadMatieres();
		} catch (error) {
			handleApiError(error);
		}
	});

	// Fonction pour modifier une matière
	async function editMatiere(id, currentNom) {
		const newNom = prompt('Nouveau nom de la matière:', currentNom);
		if (newNom && newNom !== currentNom) {
			try {
				const response = await fetch(`../api/matieres/${id}`, {
					method: 'PUT',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						nom: newNom
					})
				});

				const result = await response.json();
				console.log('Résultat de la modification:', result);

				if (!result.success) {
					throw new Error(result.error || 'Erreur lors de la modification de la matière');
				}

				showSuccess(result.message || 'Matière modifiée avec succès');
				loadMatieres();
			} catch (error) {
				handleApiError(error);
			}
		}
	}

	// Fonction pour supprimer une matière
	async function deleteMatiere(id) {
		if (confirm('Êtes-vous sûr de vouloir supprimer cette matière ?')) {
			try {
				const response = await fetch(`../api/matieres/${id}`, {
					method: 'DELETE'
				});

				const result = await response.json();
				console.log('Résultat de la suppression:', result);

				if (!result.success) {
					throw new Error(result.error || 'Erreur lors de la suppression de la matière');
				}

				showSuccess(result.message || 'Matière supprimée avec succès');
				loadMatieres();
			} catch (error) {
				handleApiError(error);
			}
		}
	}

	// Charger les matières au chargement de la page
	document.addEventListener('DOMContentLoaded', loadMatieres);
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>