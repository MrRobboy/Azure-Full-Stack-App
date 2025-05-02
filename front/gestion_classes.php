<?php
require_once 'config.php';

// Vérification de la connexion
if (!isset($_SESSION['prof_id'])) {
	header('Location: index.php');
	exit();
}

// Traitement des actions sur les classes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['action'])) {
		switch ($_POST['action']) {
			case 'add':
				$stmt = $pdo->prepare("INSERT INTO CLASSE (nom_classe, niveau, numero) VALUES (?, ?, ?)");
				$stmt->execute([$_POST['nom_classe'], $_POST['niveau'], $_POST['numero']]);
				break;
			case 'update':
				$stmt = $pdo->prepare("UPDATE CLASSE SET nom_classe = ?, niveau = ?, numero = ? WHERE id_classe = ?");
				$stmt->execute([$_POST['nom_classe'], $_POST['niveau'], $_POST['numero'], $_POST['classe_id']]);
				break;
			case 'delete':
				$stmt = $pdo->prepare("DELETE FROM CLASSE WHERE id_classe = ?");
				$stmt->execute([$_POST['classe_id']]);
				break;
		}
		header('Location: gestion_classes.php');
		exit();
	}
}

// Récupération des classes
$stmt = $pdo->prepare("SELECT * FROM CLASSE");
$stmt->execute();
$classes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Gestion des Classes - Système de Gestion des Notes</title>
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
				<h1>Gestion des Classes</h1>

				<div class="form-container">
					<h3>Ajouter une classe</h3>
					<form action="gestion_classes.php" method="POST">
						<input type="hidden" name="action" value="add">

						<div class="form-row">
							<label for="nom_classe">Nom de la classe :</label>
							<input type="text" name="nom_classe" id="nom_classe" required>
						</div>

						<div class="form-row">
							<label for="niveau">Niveau :</label>
							<input type="text" name="niveau" id="niveau" required>
						</div>

						<div class="form-row">
							<label for="numero">Numéro :</label>
							<input type="text" name="numero" id="numero" required>
						</div>

						<button type="submit" class="btn">Ajouter la classe</button>
					</form>
				</div>

				<h3>Liste des classes</h3>
				<table>
					<thead>
						<tr>
							<th>Nom</th>
							<th>Niveau</th>
							<th>Numéro</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($classes as $classe): ?>
							<tr>
								<td><?php echo htmlspecialchars($classe['nom_classe']); ?></td>
								<td><?php echo htmlspecialchars($classe['niveau']); ?></td>
								<td><?php echo htmlspecialchars($classe['numero']); ?></td>
								<td>
									<form action="gestion_classes.php" method="POST" style="display: inline;">
										<input type="hidden" name="action" value="update">
										<input type="hidden" name="classe_id" value="<?php echo $classe['id_classe']; ?>">
										<input type="text" name="nom_classe" value="<?php echo htmlspecialchars($classe['nom_classe']); ?>" required>
										<input type="text" name="niveau" value="<?php echo htmlspecialchars($classe['niveau']); ?>" required>
										<input type="text" name="numero" value="<?php echo htmlspecialchars($classe['numero']); ?>" required>
										<button type="submit" class="btn">Modifier</button>
									</form>
									<form action="gestion_classes.php" method="POST" style="display: inline;">
										<input type="hidden" name="action" value="delete">
										<input type="hidden" name="classe_id" value="<?php echo $classe['id_classe']; ?>">
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