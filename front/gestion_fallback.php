<?php

/**
 * Fallback for Gestion pages when backend is unavailable
 */
session_start();

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['token'])) {
	// Redirect to login page
	header('Location: login.php');
	exit;
}

// Get the requested management page from URL
$page = isset($_GET['page']) ? $_GET['page'] : 'default';

// Validate allowed pages
$allowed_pages = ['matieres', 'classes', 'exams', 'profs', 'users'];
if (!in_array($page, $allowed_pages)) {
	$page = 'default';
}

// Set page title based on requested page
switch ($page) {
	case 'matieres':
		$pageTitle = "Gestion des matières";
		break;
	case 'classes':
		$pageTitle = "Gestion des classes";
		break;
	case 'exams':
		$pageTitle = "Gestion des examens";
		break;
	case 'profs':
		$pageTitle = "Gestion des professeurs";
		break;
	case 'users':
		$pageTitle = "Gestion des utilisateurs";
		break;
	default:
		$pageTitle = "Gestion";
		break;
}

// Start output buffer
ob_start();
?>

<div class="container mt-4">
	<div class="row">
		<div class="col-12">
			<div class="d-flex justify-content-between align-items-center mb-4">
				<h1><?php echo $pageTitle; ?></h1>
				<a href="dashboard.php" class="btn btn-secondary">
					<i class="fas fa-arrow-left"></i> Retour au tableau de bord
				</a>
			</div>

			<div class="alert alert-warning">
				<i class="fas fa-exclamation-triangle"></i>
				<strong>Backend temporairement indisponible</strong>
				<p>Le serveur backend est actuellement indisponible. Nous vous invitons à réessayer ultérieurement.</p>
				<p>Cette page est une version de secours avec des fonctionnalités limitées.</p>
			</div>

			<div class="card">
				<div class="card-header">
					<h5>Informations</h5>
				</div>
				<div class="card-body">
					<p>Vous êtes connecté en tant que <strong><?php echo htmlspecialchars($_SESSION['user']['name'] ?? $_SESSION['user']['email'] ?? 'Utilisateur'); ?></strong>.</p>
					<p>Rôle: <span class="badge badge-primary"><?php echo htmlspecialchars($_SESSION['user']['role'] ?? 'Utilisateur'); ?></span></p>
					<p>Session active depuis: <?php echo date('d/m/Y H:i:s', $_SESSION['loginTime'] ?? time()); ?></p>
				</div>
			</div>

			<div class="card mt-4">
				<div class="card-header">
					<h5>Actions disponibles</h5>
				</div>
				<div class="card-body">
					<div class="list-group">
						<a href="dashboard.php" class="list-group-item list-group-item-action">
							<i class="fas fa-tachometer-alt"></i> Retour au tableau de bord
						</a>
						<a href="api-test.php" class="list-group-item list-group-item-action">
							<i class="fas fa-vial"></i> Tester la connexion API
						</a>
						<a href="session-debug.php?format=html" class="list-group-item list-group-item-action">
							<i class="fas fa-bug"></i> Déboguer la session
						</a>
						<a href="javascript:void(0)" onclick="reloadPage()" class="list-group-item list-group-item-action">
							<i class="fas fa-sync"></i> Recharger la page
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	function reloadPage() {
		// Add cache busting parameter
		window.location.href = window.location.href.split('?')[0] + '?_=' + Date.now();
	}

	// Check backend availability
	document.addEventListener('DOMContentLoaded', async function() {
		try {
			const result = await fetch('api-test.php');
			const testData = await result.json();

			// Add diagnostic info
			const diagnosticsDiv = document.createElement('div');
			diagnosticsDiv.className = 'card mt-4';
			diagnosticsDiv.innerHTML = `
                <div class="card-header">
                    <h5>Diagnostics du backend</h5>
                </div>
                <div class="card-body">
                    <p>Timestamp: ${testData.timestamp}</p>
                    <p>URL Backend: ${testData.backend_url}</p>
                    <p>Tests réussis: ${testData.success_count}/${testData.tests_run} (${testData.success_rate.toFixed(1)}%)</p>
                    <div class="mt-3">
                        <button class="btn btn-info" onclick="showDetailedDiagnostics()">Afficher les détails</button>
                    </div>
                    <div id="detailed-diagnostics" style="display: none; margin-top: 15px;">
                        <pre>${JSON.stringify(testData.results, null, 2)}</pre>
                    </div>
                </div>
            `;

			document.querySelector('.container').appendChild(diagnosticsDiv);
		} catch (error) {
			console.error('Error checking backend:', error);
		}
	});

	function showDetailedDiagnostics() {
		const details = document.getElementById('detailed-diagnostics');
		if (details) {
			details.style.display = details.style.display === 'none' ? 'block' : 'none';
		}
	}
</script>

<?php
// Get content and include base template
$content = ob_get_clean();
require_once 'templates/base.php';
?>