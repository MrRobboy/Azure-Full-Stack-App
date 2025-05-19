<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Accueil - Gestion Scolaire</title>
	<link rel="stylesheet" href="css/common.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
	<header class="header">
		<div class="header-container">
			<a href="index.php" class="logo">
				<img src="images/school-badge.png" alt="Logo École" class="school-badge">
				Gestion Scolaire
			</a>
			<nav class="nav-menu">
				<a href="login.php" class="nav-link">
					<i class="fas fa-sign-in-alt"></i>
					Connexion
				</a>
			</nav>
		</div>
	</header>

	<main class="container">
		<div class="welcome-section">
			<h1>Bienvenue sur la plateforme de gestion scolaire</h1>
			<p class="lead">Un outil complet pour gérer efficacement votre établissement scolaire</p>
		</div>

		<div class="dashboard-stats">
			<div class="stat-card">
				<i class="fas fa-users fa-2x"></i>
				<h3>Élèves</h3>
				<p class="value">250+</p>
				<p>Élèves inscrits</p>
			</div>
			<div class="stat-card">
				<i class="fas fa-chalkboard-teacher fa-2x"></i>
				<h3>Enseignants</h3>
				<p class="value">20+</p>
				<p>Professeurs actifs</p>
			</div>
			<div class="stat-card">
				<i class="fas fa-book fa-2x"></i>
				<h3>Classes</h3>
				<p class="value">12</p>
				<p>Classes actives</p>
			</div>
		</div>

		<div class="features-grid">
			<div class="card">
				<div class="card-header">
					<h3><i class="fas fa-user-graduate"></i> Gestion des élèves</h3>
				</div>
				<div class="card-body">
					<p>Gérez facilement les inscriptions, les notes et les informations des élèves.</p>
					<a href="login.php" class="btn btn-primary">
						<i class="fas fa-arrow-right"></i>
						Accéder
					</a>
				</div>
			</div>

			<div class="card">
				<div class="card-header">
					<h3><i class="fas fa-chalkboard"></i> Gestion des classes</h3>
				</div>
				<div class="card-body">
					<p>Organisez les classes, les emplois du temps et les effectifs.</p>
					<a href="login.php" class="btn btn-primary">
						<i class="fas fa-arrow-right"></i>
						Accéder
					</a>
				</div>
			</div>

			<div class="card">
				<div class="card-header">
					<h3><i class="fas fa-book-open"></i> Gestion des matières</h3>
				</div>
				<div class="card-body">
					<p>Gérez les matières, les programmes et les coefficients.</p>
					<a href="login.php" class="btn btn-primary">
						<i class="fas fa-arrow-right"></i>
						Accéder
					</a>
				</div>
			</div>
		</div>
	</main>

	<footer class="footer">
		<div class="container">
			<p>&copy; 2024 Gestion Scolaire. Tous droits réservés.</p>
		</div>
	</footer>
</body>

</html>