<?php
require_once 'config.php';

// Vérification de la connexion
if (!isset($_SESSION['prof_id'])) {
	header('Location: index.php');
	exit();
}

$prof_id = $_SESSION['prof_id'];
$exam_id = isset($_GET['exam']) ? $_GET['exam'] : null;

// Traitement de l'ajout/modification de note
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['action'])) {
		switch ($_POST['action']) {
			case 'add':
				$stmt = $pdo->prepare("INSERT INTO NOTES (user, exam, note) VALUES (?, ?, ?)");
				$stmt->execute([$_POST['user_id'], $_POST['exam_id'], $_POST['note']]);
				break;
			case 'update':
				$stmt = $pdo->prepare("UPDATE NOTES SET note = ? WHERE id_note = ?");
				$stmt->execute([$_POST['note'], $_POST['note_id']]);
				break;
			case 'delete':
				$stmt = $pdo->prepare("DELETE FROM NOTES WHERE id_note = ?");
				$stmt->execute([$_POST['note_id']]);
				break;
		}
		header('Location: gestion_notes.php?exam=' . $_POST['exam_id']);
		exit();
	}
}

// Récupération des données
if ($exam_id) {
	// Récupération des informations de l'examen
	$stmt = $pdo->prepare("SELECT * FROM EXAM WHERE id_exam = ? AND matiere IN (SELECT matiere FROM PROF WHERE id_prof = ?)");
	$stmt->execute([$exam_id, $prof_id]);
	$exam = $stmt->fetch();

	if ($exam) {
		// Récupération des étudiants de la classe
		$stmt = $pdo->prepare("SELECT * FROM USER WHERE classe = ?");
		$stmt->execute([$exam['classe']]);
		$etudiants = $stmt->fetchAll();

		// Récupération des notes existantes
		$stmt = $pdo->prepare("SELECT * FROM NOTES WHERE exam = ?");
		$stmt->execute([$exam_id]);
		$notes = $stmt->fetchAll();
	}
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Gestion des Notes - Système de Gestion des Notes</title>
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
				<h1>Gestion des Notes</h1>

				<?php if ($exam_id && $exam): ?>
					<h2>Examen : <?php echo htmlspecialchars($exam['titre']); ?></h2>
					<p>Matière : <?php echo htmlspecialchars($exam['matiere']); ?></p>
					<p>Classe : <?php echo htmlspecialchars($exam['classe']); ?></p>

					<div class="form-container">
						<h3>Ajouter une note</h3>
						<form action="gestion_notes.php" method="POST">
							<input type="hidden" name="action" value="add">
							<input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">

							<div class="form-row">
								<label for="user_id">Étudiant :</label>
								<select name="user_id" id="user_id" required>
									<?php foreach ($etudiants as $etudiant): ?>
										<option value="<?php echo $etudiant['id_user']; ?>">
											<?php echo htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="form-row">
								<label for="note">Note :</label>
								<input type="number" name="note" id="note" min="0" max="20" step="0.5" required>
							</div>

							<button type="submit" class="btn">Ajouter la note</button>
						</form>
					</div>

					<h3>Notes existantes</h3>
					<table>
						<thead>
							<tr>
								<th>Étudiant</th>
								<th>Note</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($notes as $note): ?>
								<tr>
									<td>
										<?php
										$stmt = $pdo->prepare("SELECT * FROM USER WHERE id_user = ?");
										$stmt->execute([$note['user']]);
										$etudiant = $stmt->fetch();
										echo htmlspecialchars($etudiant['prenom'] . ' ' . $etudiant['nom']);
										?>
									</td>
									<td><?php echo htmlspecialchars($note['note']); ?></td>
									<td>
										<form action="gestion_notes.php" method="POST" style="display: inline;">
											<input type="hidden" name="action" value="update">
											<input type="hidden" name="note_id" value="<?php echo $note['id_note']; ?>">
											<input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
											<input type="number" name="note" value="<?php echo $note['note']; ?>" min="0" max="20" step="0.5" required>
											<button type="submit" class="btn">Modifier</button>
										</form>
										<form action="gestion_notes.php" method="POST" style="display: inline;">
											<input type="hidden" name="action" value="delete">
											<input type="hidden" name="note_id" value="<?php echo $note['id_note']; ?>">
											<input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
											<button type="submit" class="btn btn-danger">Supprimer</button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else: ?>
					<p>Veuillez sélectionner un examen pour gérer les notes.</p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</body>

</html>