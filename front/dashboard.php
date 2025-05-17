<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['token'])) {
    error_log("Session check failed - User or token not set");
    error_log("Session contents: " . json_encode($_SESSION));
    header('Location: login.php');
    exit();
}

// Debug mode - output session info if requested
if (isset($_GET['debug'])) {
    error_log("Debug mode enabled");
    error_log("Session data: " . json_encode($_SESSION));
    error_log("Server variables: " . json_encode($_SERVER));
    echo json_encode([
        'session' => $_SESSION,
        'server' => $_SERVER
    ]);
    exit();
}

$user = $_SESSION['user'];
error_log("User logged in: " . json_encode($user));

$pageTitle = "Tableau de bord";
ob_start(); // Début de la mise en tampon
?>

<div class="container">
    <div class="main-content">
        <div class="page-header">
            <h1>Tableau de bord</h1>
            <div class="alert alert-info">
                Bienvenue, <?php echo htmlspecialchars($user['nom'] ?? 'Utilisateur'); ?>!
                <span class="badge bg-primary"><?php echo htmlspecialchars($user['role'] ?? 'Rôle inconnu'); ?></span>
            </div>
        </div>

        <!-- Nav menu for quick access -->
        <div class="row mt-4 mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Accès rapide</h5>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="gestion_privileges.php" class="btn btn-primary">Gestion des privilèges</a>
                            <a href="gestion_users.php" class="btn btn-primary">Gestion des utilisateurs</a>
                            <a href="gestion_classes.php" class="btn btn-primary">Gestion des classes</a>
                            <a href="gestion_matieres.php" class="btn btn-primary">Gestion des matières</a>
                            <a href="gestion_profs.php" class="btn btn-primary">Gestion des professeurs</a>
                            <a href="gestion_notes.php" class="btn btn-primary">Gestion des notes</a>
                            <a href="gestion_exams.php" class="btn btn-primary">Gestion des examens</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Matières</h5>
                        <p class="card-text" id="matieres-count">Chargement...</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Classes</h5>
                        <p class="card-text" id="classes-count">Chargement...</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Examens</h5>
                        <p class="card-text" id="examens-count">Chargement...</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Professeurs</h5>
                        <p class="card-text" id="professeurs-count">Chargement...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/config.js?v=5.0"></script>
<script src="js/api-service.js?v=2.0"></script>
<script src="js/notification-system.js?v=1.1"></script>
<script>
    console.log('Dashboard initializing...');

    // Function to fetch counts
    async function fetchCount(endpoint, elementId) {
        console.log(`Fetching count for ${endpoint}...`);
        try {
            const response = await ApiService.request(endpoint);
            console.log(`Response for ${endpoint}:`, response);

            if (response.success) {
                const count = response.data.length || 0;
                document.getElementById(elementId).textContent = count;
            } else {
                console.error(`Failed to fetch ${endpoint}:`, response);
                document.getElementById(elementId).textContent = 'Erreur';
            }
        } catch (error) {
            console.error(`Error fetching ${endpoint}:`, error);
            document.getElementById(elementId).textContent = 'Erreur';
        }
    }

    // Load dashboard data
    async function loadDashboard() {
        console.log('Loading dashboard data...');
        try {
            // Fetch user profile
            const userResponse = await ApiService.getCurrentUser();
            console.log('User profile response:', userResponse);

            // Fetch counts in parallel
            await Promise.all([
                fetchCount('matieres', 'matieres-count'),
                fetchCount('classes', 'classes-count'),
                fetchCount('examens', 'examens-count'),
                fetchCount('profs', 'professeurs-count')
            ]);
        } catch (error) {
            console.error('Error loading dashboard:', error);
            NotificationSystem.error('Erreur lors du chargement du tableau de bord');
        }
    }

    // Initialize dashboard
    document.addEventListener('DOMContentLoaded', () => {
        console.log('DOM loaded, initializing dashboard...');
        loadDashboard();
    });
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>