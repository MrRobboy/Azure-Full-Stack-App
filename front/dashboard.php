<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['token'])) {
    // Redirect to login page
    header('Location: login.php');
    exit;
}

// Get user data from session
$user = $_SESSION['user'];
$token = $_SESSION['token'];
$loginTime = $_SESSION['loginTime'] ?? time();

$pageTitle = "Tableau de bord";
ob_start();
?>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Tableau de bord</h1>
        <div class="user-info">
            <span class="welcome-text">Bienvenue, <strong><?= htmlspecialchars($user['name']) ?></strong></span>
            <span class="user-role badge badge-primary"><?= htmlspecialchars($user['role']) ?></span>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="dashboard-card">
            <i class="fas fa-book fa-3x" style="color: var(--secondary-color); margin-bottom: 1rem;"></i>
            <h3>Matières</h3>
            <p id="matieresCount">-</p>
            <a href="gestion_matieres.php" class="btn btn-primary">Gérer les matières</a>
        </div>

        <div class="dashboard-card">
            <i class="fas fa-users fa-3x" style="color: var(--secondary-color); margin-bottom: 1rem;"></i>
            <h3>Classes</h3>
            <p id="classesCount">-</p>
            <a href="gestion_classes.php" class="btn btn-primary">Gérer les classes</a>
        </div>

        <div class="dashboard-card">
            <i class="fas fa-calendar-alt fa-3x" style="color: var(--secondary-color); margin-bottom: 1rem;"></i>
            <h3>Examens</h3>
            <p id="examensCount">-</p>
            <a href="gestion_exams.php" class="btn btn-primary">Gérer les examens</a>
        </div>

        <div class="dashboard-card">
            <i class="fas fa-chalkboard-teacher fa-3x" style="color: var(--secondary-color); margin-bottom: 1rem;"></i>
            <h3>Professeurs</h3>
            <p id="profsCount">-</p>
            <a href="gestion_profs.php" class="btn btn-primary">Gérer les professeurs</a>
        </div>

        <div class="dashboard-card">
            <i class="fas fa-users-cog fa-3x" style="color: var(--secondary-color); margin-bottom: 1rem;"></i>
            <h3>Utilisateurs</h3>
            <p id="usersCount">-</p>
            <a href="gestion_users.php" class="btn btn-primary">Gérer les utilisateurs</a>
        </div>

        <div class="dashboard-card">
            <i class="fas fa-user-circle fa-3x" style="color: var(--secondary-color); margin-bottom: 1rem;"></i>
            <h3>Mon compte</h3>
            <p>Informations et paramètres</p>
            <button id="logout" class="btn btn-danger">Déconnexion</button>
        </div>
    </div>
</div>

<script src="js/cache-buster.js"></script>
<script src="js/config.js?v=1.9"></script>
<script src="js/notification-system.js?v=1.1"></script>
<script>
    // Function to get API URL with proper proxy handling
    function getApiEndpoint(endpoint) {
        return `${appConfig.proxyUrl}?endpoint=${encodeURIComponent(endpoint)}`;
    }

    // Function to load counters
    async function loadCounters() {
        try {
            // Display a global loader or an information message according to capabilities
            const loaderId = 'dashboard-loading';
            if (typeof NotificationSystem.startLoader === 'function') {
                NotificationSystem.startLoader(loaderId, 'Chargement des données...');
            } else {
                NotificationSystem.info('Chargement des données...');
            }

            // Use individual try/catch for each request
            try {
                const matieresRes = await fetch(getApiEndpoint('matieres'));
                const matieresData = await matieresRes.json();
                if (matieresData.success && matieresData.data) {
                    document.getElementById('matieresCount').textContent = matieresData.data.length;
                }
            } catch (error) {
                console.error('Erreur lors du chargement des matières:', error);
                document.getElementById('matieresCount').textContent = 'Erreur';
            }

            try {
                const classesRes = await fetch(getApiEndpoint('classes'));
                const classesData = await classesRes.json();
                if (classesData.success && classesData.data) {
                    document.getElementById('classesCount').textContent = classesData.data.length;
                }
            } catch (error) {
                console.error('Erreur lors du chargement des classes:', error);
                document.getElementById('classesCount').textContent = 'Erreur';
            }

            try {
                const examensRes = await fetch(getApiEndpoint('examens'));
                const examensData = await examensRes.json();
                if (examensData.success && examensData.data) {
                    document.getElementById('examensCount').textContent = examensData.data.length;
                }
            } catch (error) {
                console.error('Erreur lors du chargement des examens:', error);
                document.getElementById('examensCount').textContent = 'Erreur';
            }

            try {
                const profsRes = await fetch(getApiEndpoint('profs'));
                const profsData = await profsRes.json();
                if (profsData.success && profsData.data) {
                    document.getElementById('profsCount').textContent = profsData.data.length;
                }
            } catch (error) {
                console.error('Erreur lors du chargement des professeurs:', error);
                document.getElementById('profsCount').textContent = 'Erreur';
            }

            try {
                const usersRes = await fetch(getApiEndpoint('users'));
                const usersData = await usersRes.json();
                if (usersData.success && usersData.data) {
                    document.getElementById('usersCount').textContent = usersData.data.length;
                }
            } catch (error) {
                console.error('Erreur lors du chargement des utilisateurs:', error);
                document.getElementById('usersCount').textContent = 'Erreur';
            }

            // Stop the loader or display the result
            if (typeof NotificationSystem.stopLoader === 'function') {
                NotificationSystem.stopLoader(loaderId, 'Données chargées avec succès');
            } else {
                NotificationSystem.success('Données chargées avec succès');
            }
        } catch (error) {
            console.error('Erreur globale lors du chargement des données:', error);
            NotificationSystem.error('Erreur lors du chargement des données: ' + error.message);
        }
    }

    // Initialize the dashboard
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Dashboard loaded');
        NotificationSystem.success('Bienvenue sur le tableau de bord');

        // Load data
        loadCounters();

        // Handle logout button
        document.getElementById('logout').addEventListener('click', async function() {
            try {
                const response = await fetch('session-handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'logout'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    NotificationSystem.info('Déconnexion en cours...');
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 1000);
                } else {
                    NotificationSystem.error('Erreur lors de la déconnexion');
                }
            } catch (error) {
                console.error('Logout error:', error);
                NotificationSystem.error('Erreur lors de la déconnexion: ' + error.message);
            }
        });
    });
</script>

<style>
    .dashboard-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .user-role {
        padding: 5px 10px;
        background-color: #007bff;
        color: white;
        border-radius: 20px;
        font-size: 0.8rem;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .dashboard-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 20px;
        text-align: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 7px 15px rgba(0, 0, 0, 0.1);
    }

    .dashboard-card h3 {
        margin: 10px 0;
        color: #333;
    }

    .dashboard-card p {
        font-size: 2rem;
        font-weight: bold;
        margin: 10px 0 20px;
        color: var(--primary-color, #007bff);
    }

    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        display: inline-block;
        text-decoration: none;
    }

    .btn-primary {
        background-color: #007bff;
        color: white;
    }

    .btn-danger {
        background-color: #dc3545;
        color: white;
    }

    .btn-secondary {
        background-color: #6c757d;
        color: white;
    }
</style>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>