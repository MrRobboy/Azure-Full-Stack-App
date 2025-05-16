<?php
session_start();

// Debug session
error_log('Dashboard session check: ' . json_encode($_SESSION));

// Check for debug mode
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';

// More detailed session check
if (!isset($_SESSION['user'])) {
    error_log('Session user data missing');
}

if (!isset($_SESSION['token'])) {
    error_log('Session token missing');
}

// In debug mode, display session info instead of redirecting
if ($debug_mode && (!isset($_SESSION['user']) || !isset($_SESSION['token']))) {
    header('Content-Type: application/json');
    echo json_encode([
        'debug' => true,
        'session_exists' => session_status() === PHP_SESSION_ACTIVE,
        'session_id' => session_id(),
        'session_data' => $_SESSION,
        'cookies' => $_COOKIE,
        'server' => [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'http_host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
            'path' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ]
    ]);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['token'])) {
    // Redirect to login page
    error_log('Dashboard redirecting to login due to incomplete session');
    header('Location: login.php');
    exit;
}

// Get user data from session
$user = $_SESSION['user'];
$token = $_SESSION['token'];
$loginTime = $_SESSION['loginTime'] ?? time();

error_log('User logged in: ' . json_encode($user));

// Function to get count from database
function getCount($table)
{
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    } catch (PDOException $e) {
        error_log("Error getting count for $table: " . $e->getMessage());
        return 0;
    }
}

// Get counts for dashboard
$counts = [
    'matieres' => getCount('matieres'),
    'classes' => getCount('classes'),
    'examens' => getCount('examens'),
    'professeurs' => getCount('professeurs'),
    'users' => getCount('users')
];

$pageTitle = "Tableau de bord";
ob_start();
?>

<head>
    <!-- ... existing head content ... -->
    <!-- Load our API service -->
    <script src="js/api-service.js"></script>
    <script src="js/notification-system.js"></script>
    <!-- ... other scripts ... -->
</head>

<div class="dashboard-container">
    <div class="dashboard-header">
        <h1>Tableau de bord</h1>
        <div class="user-info">
            <span id="user-name">Chargement...</span>
            <span id="user-role" class="role-badge">Chargement...</span>
        </div>
    </div>

    <div class="dashboard-stats">
        <div class="stat-card" id="matieres-count">
            <i class="fas fa-book"></i>
            <div class="stat-content">
                <h3>Matières</h3>
                <p class="stat-number">Chargement...</p>
            </div>
        </div>
        <div class="stat-card" id="classes-count">
            <i class="fas fa-chalkboard"></i>
            <div class="stat-content">
                <h3>Classes</h3>
                <p class="stat-number">Chargement...</p>
            </div>
        </div>
        <div class="stat-card" id="examens-count">
            <i class="fas fa-file-alt"></i>
            <div class="stat-content">
                <h3>Examens</h3>
                <p class="stat-number">Chargement...</p>
            </div>
        </div>
        <div class="stat-card" id="professeurs-count">
            <i class="fas fa-user-tie"></i>
            <div class="stat-content">
                <h3>Professeurs</h3>
                <p class="stat-number">Chargement...</p>
            </div>
        </div>
    </div>
</div>

<script>
    // Fallback for NotificationSystem if not loaded
    if (typeof NotificationSystem === 'undefined') {
        console.warn('Creating fallback NotificationSystem');
        window.NotificationSystem = {
            init: function() {
                console.log('Fallback NotificationSystem initialized');
            },
            info: function(msg) {
                console.log('INFO: ' + msg);
            },
            error: function(msg) {
                console.error('ERROR: ' + msg);
            },
            warning: function(msg) {
                console.warn('WARNING: ' + msg);
            },
            success: function(msg) {
                console.log('SUCCESS: ' + msg);
            }
        };
    }

    // Dashboard initialization
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize NotificationSystem
        if (typeof NotificationSystem !== 'undefined') {
            NotificationSystem.init();
        } else {
            console.error('NotificationSystem module not loaded!');
        }

        // Show loading indicator
        const loadingIndicator = document.getElementById('loading-indicator');
        if (loadingIndicator) loadingIndicator.style.display = 'block';

        // Load dashboard data
        loadDashboardData();
    });

    // Function to fetch count for a specific entity
    async function fetchCountFor(elementId, endpoint) {
        const element = document.getElementById(elementId);
        if (!element) {
            console.warn(`Element ${elementId} not found`);
            return;
        }

        // Show loading state
        element.querySelector('.stat-number').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        try {
            const response = await ApiService.request(endpoint);
            console.log(`Response for ${endpoint}:`, response);

            if (response.success && response.data) {
                const count = Array.isArray(response.data) ? response.data.length : 0;
                element.querySelector('.stat-number').textContent = count;

                // If using fallback data, show warning
                if (response.isFallback) {
                    element.classList.add('using-fallback');
                    NotificationSystem.warning(`Données de ${endpoint} en mode dégradé`);
                }
            } else {
                throw new Error('Invalid response format');
            }
        } catch (error) {
            console.error(`Error fetching ${endpoint}:`, error);
            element.querySelector('.stat-number').textContent = '0';
            element.classList.add('error');
            NotificationSystem.error(`Erreur lors du chargement de ${endpoint}`);
        }
    }

    // Function to load all dashboard data
    async function loadDashboardData() {
        try {
            // Get user profile
            const userResponse = await ApiService.getCurrentUser();
            console.log('User profile response:', userResponse);

            if (userResponse.success && userResponse.data) {
                const userData = userResponse.data.user || userResponse.data;
                document.getElementById('user-name').textContent = `${userData.prenom} ${userData.nom}`;
                document.getElementById('user-role').textContent = userData.role;
                document.getElementById('user-role').classList.add(userData.role.toLowerCase());
            } else {
                throw new Error('Invalid user data');
            }

            // Fetch all counts
            await Promise.all([
                fetchCountFor('matieres-count', 'api/matieres'),
                fetchCountFor('classes-count', 'api/classes'),
                fetchCountFor('examens-count', 'api/examens'),
                fetchCountFor('professeurs-count', 'api/professeurs')
            ]);

        } catch (error) {
            console.error('Error loading dashboard data:', error);
            NotificationSystem.error('Erreur lors du chargement du tableau de bord');
        } finally {
            // Hide loading indicator
            document.getElementById('loading-indicator').style.display = 'none';
        }
    }

    // Handle logout button
    document.getElementById('logout')?.addEventListener('click', async function() {
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

    .role-badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.9em;
        font-weight: bold;
    }

    .role-badge.admin {
        background-color: #dc3545;
        color: white;
    }

    .role-badge.prof {
        background-color: #0d6efd;
        color: white;
    }

    .role-badge.eleve {
        background-color: #198754;
        color: white;
    }

    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .stat-card i {
        font-size: 2em;
        color: #0d6efd;
    }

    .stat-content h3 {
        margin: 0;
        font-size: 1.1em;
        color: #666;
    }

    .stat-number {
        margin: 5px 0 0;
        font-size: 1.5em;
        font-weight: bold;
        color: #333;
    }

    .stat-card.using-fallback {
        border: 2px solid #ffc107;
    }

    .stat-card.error {
        border: 2px solid #dc3545;
    }
</style>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>