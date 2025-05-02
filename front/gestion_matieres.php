<?php
require_once 'config.php';

// Vérification de la connexion
if (!isset($_SESSION['prof_id'])) {
	header('Location: index.php');
	exit();
}

$prof_id = $_SESSION['prof_id'];

// Traitement des actions sur les matières
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['action'])) {
		switch ($_POST['action']) {
			case 'add':
				$stmt = $pdo->prepare("INSERT INTO MATIERE (nom) VALUES (?)");
				$stmt->execute([$_POST['nom']]);
				break;
			case 'update':
				$stmt = $pdo->prepare("UPDATE MATIERE SET nom = ? WHERE id_matiere = ?");
				$stmt->execute([$_POST['nom'], $_POST['matiere_id']]);
				break;
			case 'delete':
				$stmt = $pdo->prepare("DELETE FROM MATIERE WHERE id_matiere = ?");
				$stmt->execute([$_POST['matiere_id']]);
				break;
		}
		header('Location: gestion_matieres.php');
		exit();
	}
}

// Récupération des matières
$stmt = $pdo->prepare("SELECT * FROM MATIERE");
$stmt->execute();
$matieres = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Gestion des Matières - Système de Gestion des Notes</title>
	<link rel="stylesheet" href="style.css">
</head>

<body>
	<div class="container">
		<div class="dashboard">
			<div class="sidebar">
				<h2>Menu</h2>
				<ul class="nav-menu">
					<li><a href="dashboard.php">Tableau de bord</a></li>
					<li><a href="gestion_notes.php">Gestion des notes</a></li>
					<li><a href="gestion_matieres.php">Gestion des matières</a></li>
					<li><a href="gestion_classes.php">Gestion des classes</a></li>
					<li><a href="gestion_exams.php">Gestion des examens</a></li>
					<li><a href="logout.php">Déconnexion</a></li>
				</ul>
			</div>

			<div class="main-content">
				<h1>Gestion des Matières</h1>

				<div class="form-container">
					<h3>Ajouter une matière</h3>
					<form action="gestion_matieres.php" method="POST">
						<input type="hidden" name="action" value="add">

						<div class="form-row">
							<label for="nom">Nom de la matière :</label>
							<input type="text" name="nom" id="nom" required>
						</div>

						<button type="submit" class="btn">Ajouter la matière</button>
					</form>
				</div>

				<h3>Liste des matières</h3>
				<table>
					<thead>
						<tr>
							<th>Nom</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($matieres as $matiere): ?>
							<tr>
								<td><?php echo htmlspecialchars($matiere['nom']); ?></td>
								<td>
									<form action="gestion_matieres.php" method="POST" style="display: inline;">
										<input type="hidden" name="action" value="update">
										<input type="hidden" name="matiere_id" value="<?php echo $matiere['id_matiere']; ?>">
										<input type="text" name="nom" value="<?php echo htmlspecialchars($matiere['nom']); ?>" required>
										<button type="submit" class="btn">Modifier</button>
									</form>
									<form action="gestion_matieres.php" method="POST" style="display: inline;">
										<input type="hidden" name="action" value="delete">
										<input type="hidden" name="matiere_id" value="<?php echo $matiere['id_matiere']; ?>">
										<button type="submit" class="btn btn-danger">Supprimer</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</body>

</html>