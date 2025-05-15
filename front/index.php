<?php
session_start();

// Si l'utilisateur est déjà connecté, rediriger vers le tableau de bord
if (isset($_SESSION['prof_id'])) {
	header('Location: dashboard.php');
	exit();
}

$pageTitle = "Accueil";
ob_start();
?>

<div class="hero">
	<div class="hero-content">
		<h1>Bienvenue sur le site web des meilleurs étudiants de l'ESGI</h1>
		<p class="hero-description">
			Une plateforme complète pour gérer les notes, les matières, les classes et les examens.
			Simplifiez votre travail d'enseignant avec notre outil intuitif.
		</p>
		<div class="hero-features">
			<div class="feature-card">
				<i class="fas fa-graduation-cap fa-3x"></i>
				<h3>Gestion des notes</h3>
				<p>Enregistrez et gérez facilement les notes de vos élèves</p>
			</div>
			<div class="feature-card">
				<i class="fas fa-book fa-3x"></i>
				<h3>Matières</h3>
				<p>Organisez vos matières et vos cours</p>
			</div>
			<div class="feature-card">
				<i class="fas fa-users fa-3x"></i>
				<h3>Classes</h3>
				<p>Gérez vos classes et vos élèves</p>
			</div>
			<div class="feature-card">
				<i class="fas fa-calendar-alt fa-3x"></i>
				<h3>Examens</h3>
				<p>Planifiez et gérez vos examens</p>
			</div>
		</div>
		<div class="hero-actions">
			<a href="login.php" class="btn btn-primary btn-lg">
				<i class="fas fa-sign-in-alt"></i> Se connecter
			</a>
		</div>
	</div>
</div>

<style>
	.hero {
		background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
		color: white;
		padding: 4rem 2rem;
		text-align: center;
		border-radius: var(--border-radius);
		margin-bottom: 2rem;
	}

	.hero-content {
		max-width: 1200px;
		margin: 0 auto;
	}

	.hero h1 {
		font-size: 2.5rem;
		margin-bottom: 1.5rem;
	}

	.hero-description {
		font-size: 1.2rem;
		margin-bottom: 3rem;
		max-width: 800px;
		margin-left: auto;
		margin-right: auto;
	}

	.hero-features {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
		gap: 2rem;
		margin-bottom: 3rem;
	}

	.feature-card {
		background: rgba(255, 255, 255, 0.1);
		padding: 2rem;
		border-radius: var(--border-radius);
		transition: transform 0.3s ease;
	}

	.feature-card:hover {
		transform: translateY(-5px);
	}

	.feature-card i {
		margin-bottom: 1rem;
		color: var(--accent-color);
	}

	.feature-card h3 {
		margin-bottom: 0.5rem;
		font-size: 1.2rem;
	}

	.feature-card p {
		font-size: 0.9rem;
		opacity: 0.9;
	}

	.hero-actions {
		margin-top: 2rem;
	}

	.btn-lg {
		padding: 1rem 2rem;
		font-size: 1.2rem;
	}

	@media (max-width: 768px) {
		.hero {
			padding: 2rem 1rem;
		}

		.hero h1 {
			font-size: 2rem;
		}

		.hero-description {
			font-size: 1rem;
		}

		.hero-features {
			grid-template-columns: 1fr;
		}
	}
</style>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>