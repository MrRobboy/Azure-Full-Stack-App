<?php
require_once 'config.php';

// Vérification de la connexion
if (!isset($_SESSION['prof_id'])) {
	header('Location: index.php');
	exit();
}

$prof_id = $_SESSION['prof_id'];

// Traitement des actions sur les examens
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['action'])) {
		switch ($_POST['action']) {
			case 'add':
				$stmt = $pdo->prepare("INSERT INTO EXAM (titre, matiere, classe) VALUES (?, ?, ?)");
				$stmt->execute([$_POST['titre'], $_POST['matiere'], $_POST['classe']]);
				break;
			case 'update':
				$stmt = $pdo->prepare("UPDATE EXAM SET titre = ?, matiere = ?, classe = ? WHERE id_exam = ?");
				$stmt->execute([$_POST['titre'], $_POST['matiere'], $_POST['classe'], $_POST['exam_id']]);
				break;
			case 'delete':
				$stmt = $pdo->prepare("DELETE FROM EXAM WHERE id_exam = ?");
				$stmt->execute([$_POST['exam_id']]);
				break;
		}
		header('Location: gestion_exams.php');
		exit();
	}
}

// Récupération des matières du professeur
$stmt = $pdo->prepare("SELECT * FROM MATIERE WHERE id_matiere IN (SELECT matiere FROM PROF WHERE id_prof = ?)");
$stmt->execute([$prof_id]);
$matieres = $stmt->fetchAll();

// Récupération des classes
$stmt = $pdo->prepare("SELECT * FROM CLASSE");
$stmt->execute();
$classes = $stmt->fetchAll();

// Récupération des examens
$stmt = $pdo->prepare("SELECT * FROM EXAM WHERE matiere IN (SELECT matiere FROM PROF WHERE id_prof = ?)");
$stmt->execute([$prof_id]);
$exams = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Gestion des Examens - Système de Gestion des Notes</title>
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
				<h1>Gestion des Examens</h1>

				<div class="form-container">
					<h3>Ajouter un examen</h3>
					<form action="gestion_exams.php" method="POST">
						<input type="hidden" name="action" value="add">

						<div class="form-row">
							<label for="titre">Titre de l'examen :</label>
							<input type="text" name="titre" id="titre" required>
						</div>

						<div class="form-row">
							<label for="matiere">Matière :</label>
							<select name="matiere" id="matiere" required>
								<?php foreach ($matieres as $matiere): ?>
									<option value="<?php echo $matiere['id_matiere']; ?>">
										<?php echo htmlspecialchars($matiere['nom']); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="form-row">
							<label for="classe">Classe :</label>
							<select name="classe" id="classe" required>
								<?php foreach ($classes as $classe): ?>
									<option value="<?php echo $classe['id_classe']; ?>">
										<?php echo htmlspecialchars($classe['nom_classe']); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<button type="submit" class="btn">Ajouter l'examen</button>
					</form>
				</div>

				<h3>Liste des examens</h3>
				<table>
					<thead>
						<tr>
							<th>Titre</th>
							<th>Matière</th>
							<th>Classe</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($exams as $exam): ?>
							<tr>
								<td><?php echo htmlspecialchars($exam['titre']); ?></td>
								<td>
									<?php
									$stmt = $pdo->prepare("SELECT nom FROM MATIERE WHERE id_matiere = ?");
									$stmt->execute([$exam['matiere']]);
									$matiere = $stmt->fetch();
									echo htmlspecialchars($matiere['nom']);
									?>
								</td>
								<td>
									<?php
									$stmt = $pdo->prepare("SELECT nom_classe FROM CLASSE WHERE id_classe = ?");
									$stmt->execute([$exam['classe']]);
									$classe = $stmt->fetch();
									echo htmlspecialchars($classe['nom_classe']);
									?>
								</td>
								<td>
									<form action="gestion_exams.php" method="POST" style="display: inline;">
										<input type="hidden" name="action" value="update">
										<input type="hidden" name="exam_id" value="<?php echo $exam['id_exam']; ?>">
										<input type="text" name="titre" value="<?php echo htmlspecialchars($exam['titre']); ?>" required>
										<select name="matiere" required>
											<?php foreach ($matieres as $matiere): ?>
												<option value="<?php echo $matiere['id_matiere']; ?>" <?php echo $matiere['id_matiere'] == $exam['matiere'] ? 'selected' : ''; ?>>
													<?php echo htmlspecialchars($matiere['nom']); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<select name="classe" required>
											<?php foreach ($classes as $classe): ?>
												<option value="<?php echo $classe['id_classe']; ?>" <?php echo $classe['id_classe'] == $exam['classe'] ? 'selected' : ''; ?>>
													<?php echo htmlspecialchars($classe['nom_classe']); ?>
												</option>
											<?php endforeach; ?>
										</select>
										<button type="submit" class="btn">Modifier</button>
									</form>
									<form action="gestion_exams.php" method="POST" style="display: inline;">
										<input type="hidden" name="action" value="delete">
										<input type="hidden" name="exam_id" value="<?php echo $exam['id_exam']; ?>">
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