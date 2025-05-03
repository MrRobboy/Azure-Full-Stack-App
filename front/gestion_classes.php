<?php
session_start();

if (!isset($_SESSION['prof_id'])) {
	header('Location: login.php');
	exit();
}

$pageTitle = "Gestion des Classes";
ob_start();
?>

<div class="container">
	<div class="main-content">
		<h1>Gestion des Classes</h1>

		<div class="form-container">
			<h3>Ajouter une classe</h3>
			<form id="addClasseForm">
				<div class="form-row">
					<label for="nom">Nom de la classe :</label>
					<input type="text" name="nom" id="nom" required>
				</div>
				<div class="form-row">
					<label for="niveau">Niveau :</label>
					<input type="text" name="niveau" id="niveau" required>
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

<script>
	// Fonction pour charger les classes
	async function loadClasses() {
		try {
			const response = await fetch('api/classes');
			const classes = await response.json();

			const tbody = document.querySelector('#classesTable tbody');
			tbody.innerHTML = '';

			classes.forEach(classe => {
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
		}
	}

	// Fonction pour ajouter une classe
	document.getElementById('addClasseForm').addEventListener('submit', async function(e) {
		e.preventDefault();
		const nom = document.getElementById('nom').value;
		const niveau = document.getElementById('niveau').value;
		const numero = document.getElementById('numero').value;
		const rythme = document.getElementById('rythme').value;

		try {
			const response = await fetch('api/classes', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					nom_classe: nom,
					niveau,
					numero,
					rythme
				})
			});

			if (response.ok) {
				document.getElementById('nom').value = '';
				document.getElementById('niveau').value = '';
				document.getElementById('numero').value = '';
				document.getElementById('rythme').value = '';
				loadClasses();
			} else {
				const error = await response.json();
				alert(error.message || 'Erreur lors de l\'ajout de la classe');
			}
		} catch (error) {
			console.error('Erreur:', error);
			alert('Erreur lors de l\'ajout de la classe');
		}
	});

	// Fonction pour modifier une classe
	async function editClasse(id, currentNom, currentNiveau, currentNumero, currentRythme) {
		const newNom = prompt('Nouveau nom de la classe:', currentNom);
		const newNiveau = prompt('Nouveau niveau:', currentNiveau);
		const newNumero = prompt('Nouveau numéro:', currentNumero);
		const newRythme = prompt('Nouveau rythme (Alternance ou Initial):', currentRythme);

		if (newNom && newNiveau && newNumero && newRythme &&
			(newNom !== currentNom || newNiveau !== currentNiveau || newNumero !== currentNumero || newRythme !== currentRythme)) {
			try {
				const response = await fetch(`api/classes/${id}`, {
					method: 'PUT',
					headers: {
						'Content-Type': 'application/json'
					},
					body: JSON.stringify({
						nom_classe: newNom,
						niveau: newNiveau,
						numero: newNumero,
						rythme: newRythme
					})
				});

				if (response.ok) {
					loadClasses();
				} else {
					const error = await response.json();
					alert(error.message || 'Erreur lors de la modification de la classe');
				}
			} catch (error) {
				console.error('Erreur:', error);
				alert('Erreur lors de la modification de la classe');
			}
		}
	}

	// Fonction pour supprimer une classe
	async function deleteClasse(id) {
		if (confirm('Êtes-vous sûr de vouloir supprimer cette classe ?')) {
			try {
				const response = await fetch(`api/classes/${id}`, {
					method: 'DELETE'
				});

				if (response.ok) {
					loadClasses();
				} else {
					const error = await response.json();
					alert(error.message || 'Erreur lors de la suppression de la classe');
				}
			} catch (error) {
				console.error('Erreur:', error);
				alert('Erreur lors de la suppression de la classe');
			}
		}
	}

	// Charger les classes au chargement de la page
	document.addEventListener('DOMContentLoaded', loadClasses);
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>