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

<div class="container mt-5">
	<div class="row justify-content-center">
		<div class="col-md-8">
			<div class="card shadow">
				<div class="card-header bg-primary text-white">
					<h2 class="m-0">Bienvenue sur l'application de gestion des notes</h2>
				</div>
				<div class="card-body">
					<p class="lead">Cette application permet aux enseignants de gérer les notes des élèves de manière simple et efficace.</p>

					<hr>

					<h3>Accès rapide</h3>
					<div class="row mt-4">
						<div class="col-md-6 mb-3">
							<div class="card h-100">
								<div class="card-body">
									<h5 class="card-title">Espace Enseignant</h5>
									<p class="card-text">Accédez à votre espace pour gérer les notes et les examens.</p>
									<a href="login.php" class="btn btn-primary">Se connecter</a>
								</div>
							</div>
						</div>
						<div class="col-md-6 mb-3">
							<div class="card h-100">
								<div class="card-body">
									<h5 class="card-title">Test du Proxy</h5>
									<p class="card-text">Testez le système de proxy unifié pour vérifier la connexion avec le backend.</p>
									<a href="test-unified-proxy.php" class="btn btn-info">Lancer les tests</a>
								</div>
							</div>
						</div>
					</div>

					<hr>

					<h3>Documentation</h3>
					<ul class="list-group mt-3">
						<li class="list-group-item d-flex justify-content-between align-items-center">
							Guide du proxy unifié
							<a href="docs/PROXY-GUIDE.md" class="btn btn-sm btn-outline-primary">Consulter</a>
						</li>
						<li class="list-group-item d-flex justify-content-between align-items-center">
							Guide de migration
							<a href="docs/MIGRATION-GUIDE.md" class="btn btn-sm btn-outline-primary">Consulter</a>
						</li>
						<li class="list-group-item d-flex justify-content-between align-items-center">
							Guide de débogage
							<a href="docs/DEBUGGING-GUIDE.md" class="btn btn-sm btn-outline-primary">Consulter</a>
						</li>
						<li class="list-group-item d-flex justify-content-between align-items-center">
							Changelog
							<a href="docs/CHANGELOG-v3.md" class="btn btn-sm btn-outline-primary">Consulter</a>
						</li>
					</ul>
				</div>
				<div class="card-footer text-muted">
					Version 3.0.2 - Sans données simulées
				</div>
			</div>
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