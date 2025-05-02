<?php
require_once 'config.php';

// Vérification de la connexion
if (!isset($_SESSION['prof_id'])) {
    header('Location: index.php');
    exit();
}

// Récupération des données
$prof_id = $_SESSION['prof_id'];

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
    <title>Tableau de bord - Système de Gestion des Notes</title>
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
                <h1>Bienvenue, <?php echo htmlspecialchars($_SESSION['prof_prenom'] . ' ' . $_SESSION['prof_nom']); ?></h1>

                <div class="stats-container">
                    <div class="stat-card">
                        <h3>Matières</h3>
                        <p><?php echo count($matieres); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Classes</h3>
                        <p><?php echo count($classes); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Examens</h3>
                        <p><?php echo count($exams); ?></p>
                    </div>
                </div>

                <h2>Derniers examens</h2>
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
                                <td><?php echo htmlspecialchars($exam['matiere']); ?></td>
                                <td><?php echo htmlspecialchars($exam['classe']); ?></td>
                                <td>
                                    <a href="gestion_notes.php?exam=<?php echo $exam['id_exam']; ?>" class="btn">Gérer les notes</a>
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