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

$pageTitle = "Tableau de bord";
ob_start();
?>

<head>
    <!-- ... existing head content ... -->
    <!-- Load our API service -->
    <script src="js/api-service.js"></script>
    <!-- ... other scripts ... -->
</head>

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

<script>
    // Dashboard initialization
    document.addEventListener('DOMContentLoaded', function() {
        // Show loading indicator
        const loadingIndicator = document.getElementById('loading-indicator');
        if (loadingIndicator) loadingIndicator.style.display = 'block';

        // Load dashboard data
        loadDashboardData();
    });

    // Function to load all dashboard data
    async function loadDashboardData() {
        try {
            // Get user profile
            const userResult = await ApiService.getCurrentUser();
            console.log('User profile API result:', userResult);

            let userData;

            // If API call fails, use session data from PHP
            if (!userResult.success) {
                console.error('User profile API error:', userResult);

                // Create a user object from PHP session data
                userData = {
                    user: <?php echo json_encode($user); ?>
                };
                console.log('Using PHP session data as fallback:', userData);
                NotificationSystem.info('Utilisation des données de session locales (API non disponible)');
            } else {
                userData = userResult.data;
                console.log('API user data:', userData);
            }

            // Update user information on the page
            updateUserInfo(userData);

            // Load additional data based on user role
            if (userData.user.role === 'ELEVE' && userData.user.id) {
                await loadStudentNotes(userData.user.id);
            }
            // Load teacher data if we have a teacher ID
            else if (userData.user.role === 'PROF' && userData.user.id) {
                await loadTeacherData(userData.user.id);
            }
            // Load admin data
            else if (userData.user.role === 'ADMIN') {
                await loadAdminDashboard();
            }

            // Hide loading indicator
            const loadingIndicator = document.getElementById('loading-indicator');
            if (loadingIndicator) loadingIndicator.style.display = 'none';

        } catch (error) {
            console.error('Error loading dashboard data:', error);
            NotificationSystem.error('Erreur de chargement des données');

            // Hide loading indicator
            const loadingIndicator = document.getElementById('loading-indicator');
            if (loadingIndicator) loadingIndicator.style.display = 'none';
        }
    }

    // Update user information on the page
    function updateUserInfo(userData) {
        console.log('Updating UI with user data:', userData);

        if (!userData || !userData.user) {
            console.error('Invalid user data format:', userData);
            return;
        }

        // Get user object - handle both direct user object and nested user object
        const user = userData.user;

        // Update user name - handle different user data formats
        const userNameElements = document.querySelectorAll('.user-name');
        userNameElements.forEach(el => {
            // Try different name formats
            if (user.prenom && user.nom) {
                el.textContent = `${user.prenom} ${user.nom}`;
            } else if (user.name) {
                el.textContent = user.name;
            } else if (user.email) {
                el.textContent = user.email;
            } else {
                el.textContent = "Utilisateur";
            }
        });

        // Update user role
        const userRoleElements = document.querySelectorAll('.user-role');
        userRoleElements.forEach(el => {
            el.textContent = user.role || "Utilisateur";
        });

        // Update profile picture if available
        if (user.photo) {
            const userPhotoElements = document.querySelectorAll('.user-photo');
            userPhotoElements.forEach(el => {
                el.src = user.photo;
            });
        }

        // Update welcome text with name if available
        const welcomeTextElements = document.querySelectorAll('.welcome-text');
        welcomeTextElements.forEach(el => {
            let displayName = "Utilisateur";

            // Try different name formats
            if (user.prenom && user.nom) {
                displayName = `${user.prenom} ${user.nom}`;
            } else if (user.name) {
                displayName = user.name;
            } else if (user.email) {
                displayName = user.email;
            }

            el.innerHTML = `Bienvenue, <strong>${displayName}</strong>`;
        });
    }

    // Load student notes
    async function loadStudentNotes(studentId) {
        try {
            const notesResult = await ApiService.notes.getByStudent(studentId);

            if (!notesResult.success) {
                console.error('Error loading student notes:', notesResult);
                return;
            }

            // Display the notes data in the appropriate section
            displayStudentNotes(notesResult.data);

        } catch (error) {
            console.error('Error in loadStudentNotes:', error);
        }
    }

    // Load teacher dashboard data
    async function loadTeacherData(teacherId) {
        try {
            // Load classes taught by this teacher
            const classesResult = await ApiService.request(`api/professeurs/${teacherId}/classes`, 'GET');

            if (!classesResult.success) {
                console.error('Error loading teacher classes:', classesResult);
                return;
            }

            // Display classes
            displayTeacherClasses(classesResult.data);

            // Load recent notes given by this teacher
            const notesResult = await ApiService.request(`api/professeurs/${teacherId}/notes`, 'GET');

            if (notesResult.success) {
                displayTeacherNotes(notesResult.data);
            }

        } catch (error) {
            console.error('Error in loadTeacherData:', error);
        }
    }

    // Load admin dashboard
    async function loadAdminDashboard() {
        try {
            // Load summary data
            const summaryResult = await ApiService.request('api/admin/summary', 'GET');

            if (!summaryResult.success) {
                console.error('Error loading admin summary:', summaryResult);
                return;
            }

            // Display admin summary
            displayAdminSummary(summaryResult.data);

            // Load recent activity
            const activityResult = await ApiService.request('api/admin/activity', 'GET');

            if (activityResult.success) {
                displayAdminActivity(activityResult.data);
            }

        } catch (error) {
            console.error('Error in loadAdminDashboard:', error);
        }
    }

    // Display functions for different data types
    function displayStudentNotes(notesData) {
        const notesContainer = document.getElementById('notes-container');
        if (!notesContainer) return;

        // Clear container
        notesContainer.innerHTML = '';

        // Check if we have notes
        if (!notesData || !notesData.notes || notesData.notes.length === 0) {
            notesContainer.innerHTML = '<div class="alert alert-info">Aucune note disponible.</div>';
            return;
        }

        // Build notes table
        const table = document.createElement('table');
        table.className = 'table table-striped';

        // Create header
        const thead = document.createElement('thead');
        thead.innerHTML = `
            <tr>
                <th>Matière</th>
                <th>Note</th>
                <th>Examen</th>
                <th>Date</th>
            </tr>
        `;
        table.appendChild(thead);

        // Create body
        const tbody = document.createElement('tbody');

        notesData.notes.forEach(note => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${note.matiere}</td>
                <td>${note.valeur}/20</td>
                <td>${note.examen}</td>
                <td>${new Date(note.date).toLocaleDateString()}</td>
            `;
            tbody.appendChild(row);
        });

        table.appendChild(tbody);
        notesContainer.appendChild(table);
    }

    function displayTeacherClasses(classesData) {
        const classesContainer = document.getElementById('classes-container');
        if (!classesContainer) return;

        // Clear container
        classesContainer.innerHTML = '';

        // Check if we have classes
        if (!classesData || !classesData.classes || classesData.classes.length === 0) {
            classesContainer.innerHTML = '<div class="alert alert-info">Aucune classe attribuée.</div>';
            return;
        }

        // Display classes
        classesData.classes.forEach(classe => {
            const classCard = document.createElement('div');
            classCard.className = 'card mb-3';
            classCard.innerHTML = `
                <div class="card-header">
                    <h5>${classe.nom}</h5>
                </div>
                <div class="card-body">
                    <p><strong>Nombre d'élèves:</strong> ${classe.nb_eleves}</p>
                    <p><strong>Matières:</strong> ${classe.matieres.join(', ')}</p>
                    <a href="classe.php?id=${classe.id}" class="btn btn-primary">Voir détails</a>
                </div>
            `;
            classesContainer.appendChild(classCard);
        });
    }

    function displayTeacherNotes(notesData) {
        const notesContainer = document.getElementById('recent-notes-container');
        if (!notesContainer) return;

        // Clear container
        notesContainer.innerHTML = '';

        // Check if we have notes
        if (!notesData || !notesData.notes || notesData.notes.length === 0) {
            notesContainer.innerHTML = '<div class="alert alert-info">Aucune note récente.</div>';
            return;
        }

        // Build notes table
        const table = document.createElement('table');
        table.className = 'table table-striped';

        // Create header
        const thead = document.createElement('thead');
        thead.innerHTML = `
            <tr>
                <th>Élève</th>
                <th>Classe</th>
                <th>Matière</th>
                <th>Note</th>
                <th>Date</th>
            </tr>
        `;
        table.appendChild(thead);

        // Create body
        const tbody = document.createElement('tbody');

        notesData.notes.forEach(note => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${note.eleve}</td>
                <td>${note.classe}</td>
                <td>${note.matiere}</td>
                <td>${note.valeur}/20</td>
                <td>${new Date(note.date).toLocaleDateString()}</td>
            `;
            tbody.appendChild(row);
        });

        table.appendChild(tbody);
        notesContainer.appendChild(table);
    }

    function displayAdminSummary(summaryData) {
        const summaryContainer = document.getElementById('admin-summary-container');
        if (!summaryContainer) return;

        // Clear container
        summaryContainer.innerHTML = '';

        // Build summary cards
        const row = document.createElement('div');
        row.className = 'row';

        // Create cards for each type of data
        const items = [{
                title: 'Élèves',
                count: summaryData.nb_eleves,
                icon: 'users'
            },
            {
                title: 'Professeurs',
                count: summaryData.nb_profs,
                icon: 'chalkboard-teacher'
            },
            {
                title: 'Classes',
                count: summaryData.nb_classes,
                icon: 'school'
            },
            {
                title: 'Matières',
                count: summaryData.nb_matieres,
                icon: 'book'
            }
        ];

        items.forEach(item => {
            const col = document.createElement('div');
            col.className = 'col-md-3 col-sm-6 mb-3';
            col.innerHTML = `
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <i class="fas fa-${item.icon} fa-3x mb-3"></i>
                        <h5 class="card-title">${item.title}</h5>
                        <h3>${item.count}</h3>
                    </div>
                </div>
            `;
            row.appendChild(col);
        });

        summaryContainer.appendChild(row);
    }

    function displayAdminActivity(activityData) {
        const activityContainer = document.getElementById('admin-activity-container');
        if (!activityContainer) return;

        // Clear container
        activityContainer.innerHTML = '';

        // Check if we have activity
        if (!activityData || !activityData.activities || activityData.activities.length === 0) {
            activityContainer.innerHTML = '<div class="alert alert-info">Aucune activité récente.</div>';
            return;
        }

        // Create activity list
        const list = document.createElement('ul');
        list.className = 'list-group';

        activityData.activities.forEach(activity => {
            const item = document.createElement('li');
            item.className = 'list-group-item d-flex justify-content-between align-items-center';
            item.innerHTML = `
                <div>
                    <strong>${activity.user}</strong> ${activity.action}
                    <small class="text-muted">${activity.target_type}: ${activity.target}</small>
                </div>
                <span class="badge bg-primary rounded-pill">${new Date(activity.date).toLocaleString()}</span>
            `;
            list.appendChild(item);
        });

        activityContainer.appendChild(list);
    }

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