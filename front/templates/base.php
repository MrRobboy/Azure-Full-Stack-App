<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Gestion des Notes - <?php echo $pageTitle ?? 'Accueil'; ?></title>
	<link rel="stylesheet" href="/assets/css/style.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
	<header class="header">
		<div class="header-container">
			<a href="/index.php" class="logo">
				<img src="/assets/images/school-badge.png" alt="Logo" class="school-badge">
				Gestion des Notes
			</a>
			<nav class="nav-menu">
				<?php if (isset($_SESSION['prof_id'])): ?>
					<a href="/dashboard.php" class="nav-link">
						<i class="fas fa-home"></i> Tableau de bord
					</a>
					<a href="/gestion_notes.php" class="nav-link">
						<i class="fas fa-graduation-cap"></i> Notes
					</a>
					<a href="/gestion_matieres.php" class="nav-link">
						<i class="fas fa-book"></i> Matières
					</a>
					<a href="/gestion_classes.php" class="nav-link">
						<i class="fas fa-users"></i> Classes
					</a>
					<a href="/gestion_exams.php" class="nav-link">
						<i class="fas fa-calendar-alt"></i> Examens
					</a>
					<a href="/logout.php" class="nav-link">
						<i class="fas fa-sign-out-alt"></i> Déconnexion
					</a>
				<?php elseif (basename($_SERVER['PHP_SELF']) !== 'login.php'): ?>
					<a href="/login.php" class="nav-link">
						<i class="fas fa-sign-in-alt"></i> Connexion
					</a>
				<?php endif; ?>
			</nav>
		</div>
	</header>

	<main class="container">
		<?php if (isset($pageTitle)): ?>
			<h1 class="page-title"><?php echo $pageTitle; ?></h1>
		<?php endif; ?>

		<?php if (isset($alert)): ?>
			<div class="alert alert-<?php echo $alert['type']; ?>">
				<?php echo $alert['message']; ?>
			</div>
		<?php endif; ?>

		<?php echo $content; ?>
	</main>

	<footer class="footer">
		<div class="container">
			<p>&copy; <?php echo date('Y'); ?> Gestion des Notes - Tous droits réservés</p>
		</div>
	</footer>

	<script src="/assets/js/main.js"></script>
</body>

</html>