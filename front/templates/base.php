<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Gestion des Notes - <?php echo $pageTitle ?? 'Accueil'; ?></title>
	<link rel="stylesheet" href="/assets/css/style.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
	<style>
		.alert {
			padding: 15px;
			margin-bottom: 20px;
			border: 1px solid transparent;
			border-radius: 4px;
			display: none;
		}

		.alert-error {
			color: #721c24;
			background-color: #f8d7da;
			border-color: #f5c6cb;
		}

		.alert-success {
			color: #155724;
			background-color: #d4edda;
			border-color: #c3e6cb;
		}

		#error-container,
		#success-container {
			position: fixed;
			top: 20px;
			left: 50%;
			transform: translateX(-50%);
			z-index: 1000;
			min-width: 300px;
			max-width: 80%;
			text-align: center;
		}

		.logo {
			display: flex;
			align-items: center;
			gap: 10px;
			text-decoration: none;
			color: inherit;
		}

		.school-badge {
			height: 30px;
			width: auto;
			vertical-align: middle;
		}
	</style>
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
					<a href="/gestion_profs.php" class="nav-link">
						<i class="fas fa-chalkboard-teacher"></i> Professeurs
					</a>
					<a href="/gestion_users.php" class="nav-link">
						<i class="fas fa-users-cog"></i> Utilisateurs
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
		<div id="error-container" class="alert alert-error"></div>
		<div id="success-container" class="alert alert-success"></div>

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

	<script src="/assets/js/error-handler.js"></script>
	<script src="/assets/js/main.js"></script>
</body>

</html>