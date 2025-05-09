<?php
session_start();

if (!isset($_SESSION['prof_id'])) {
    header('Location: login.php');
    exit();
}

$pageTitle = "Tableau de bord";
ob_start();
?>

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
</div>

<script src="js/notification-system.js"></script>
<script src="js/error-messages.js"></script>
<script src="js/config.js"></script>
<script>
    // Fonction pour obtenir l'URL de l'API
    function getApiUrl(endpoint) {
        return `api/${endpoint}`;
    }

    // Fonction pour charger les compteurs
    async function loadCounters() {
        try {
            const [matieresRes, classesRes, examensRes, profsRes, usersRes] = await Promise.all([
                fetch(getApiUrl('matieres')),
                fetch(getApiUrl('classes')),
                fetch(getApiUrl('examens')),
                fetch(getApiUrl('profs')),
                fetch(getApiUrl('users'))
            ]);

            const [matieres, classes, examens, profs, users] = await Promise.all([
                matieresRes.json(),
                classesRes.json(),
                examensRes.json(),
                profsRes.json(),
                usersRes.json()
            ]);

            // Mettre à jour les compteurs avec les données de l'API
            if (matieres.success && matieres.data) {
                document.getElementById('matieresCount').textContent = matieres.data.length;
            }
            if (classes.success && classes.data) {
                document.getElementById('classesCount').textContent = classes.data.length;
            }
            if (examens.success && examens.data) {
                document.getElementById('examensCount').textContent = examens.data.length;
            }
            if (profs.success && profs.data) {
                document.getElementById('profsCount').textContent = profs.data.length;
            }
            if (users.success && users.data) {
                document.getElementById('usersCount').textContent = users.data.length;
            }
        } catch (error) {
            console.error('Erreur lors du chargement des compteurs:', error);
            NotificationSystem.error('Erreur lors du chargement des compteurs');
        }
    }

    // Initialisation
    document.addEventListener('DOMContentLoaded', () => {
        loadCounters();
    });
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>