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

<script src="js/cache-buster.js"></script>
<script src="js/notification-system.js?v=1.1"></script>
<script src="js/error-messages.js"></script>
<script src="js/config.js?v=1.1"></script>
<script>
    // Fonction pour charger les compteurs
    async function loadCounters() {
        try {
            // Afficher un loader global ou un message d'information selon les capacités
            const loaderId = 'dashboard-loading';
            if (typeof NotificationSystem.startLoader === 'function') {
                NotificationSystem.startLoader(loaderId, 'Chargement des données...');
            } else {
                NotificationSystem.info('Chargement des données...');
            }

            // Utiliser try/catch individuels pour chaque requête
            let matieresData, classesData, examensData, profsData, usersData;

            try {
                const matieresRes = await fetch(getApiUrl('matieres'));
                matieresData = await matieresRes.json();
                if (matieresData.success && matieresData.data) {
                    document.getElementById('matieresCount').textContent = matieresData.data.length;
                }
            } catch (error) {
                console.error('Erreur lors du chargement des matières:', error);
                document.getElementById('matieresCount').textContent = 'Erreur';
            }

            try {
                const classesRes = await fetch(getApiUrl('classes'));
                classesData = await classesRes.json();
                if (classesData.success && classesData.data) {
                    document.getElementById('classesCount').textContent = classesData.data.length;
                }
            } catch (error) {
                console.error('Erreur lors du chargement des classes:', error);
                document.getElementById('classesCount').textContent = 'Erreur';
            }

            try {
                const examensRes = await fetch(getApiUrl('examens'));
                examensData = await examensRes.json();
                if (examensData.success && examensData.data) {
                    document.getElementById('examensCount').textContent = examensData.data.length;
                }
            } catch (error) {
                console.error('Erreur lors du chargement des examens:', error);
                document.getElementById('examensCount').textContent = 'Erreur';
            }

            try {
                const profsRes = await fetch(getApiUrl('profs'));
                profsData = await profsRes.json();
                if (profsData.success && profsData.data) {
                    document.getElementById('profsCount').textContent = profsData.data.length;
                }
            } catch (error) {
                console.error('Erreur lors du chargement des professeurs:', error);
                document.getElementById('profsCount').textContent = 'Erreur';
            }

            try {
                const usersRes = await fetch(getApiUrl('users'));
                usersData = await usersRes.json();
                if (usersData.success && usersData.data) {
                    document.getElementById('usersCount').textContent = usersData.data.length;
                }
            } catch (error) {
                console.error('Erreur lors du chargement des utilisateurs:', error);
                document.getElementById('usersCount').textContent = 'Erreur';
            }

            // Arrêter le loader ou afficher le résultat
            if (matieresData?.success && classesData?.success && examensData?.success &&
                profsData?.success && usersData?.success) {
                if (typeof NotificationSystem.stopLoader === 'function') {
                    NotificationSystem.stopLoader(loaderId, 'Données chargées avec succès');
                } else {
                    NotificationSystem.success('Données chargées avec succès');
                }
            } else {
                if (typeof NotificationSystem.stopLoader === 'function') {
                    NotificationSystem.stopLoader(loaderId);
                }
                NotificationSystem.warning('Certaines données n\'ont pas pu être chargées');
            }
        } catch (error) {
            console.error('Erreur globale lors du chargement des données:', error);
            NotificationSystem.error('Erreur lors du chargement des données: ' + error.message);
        }
    }

    // Initialisation
    document.addEventListener('DOMContentLoaded', () => {
        // Initialiser l'interface
        NotificationSystem.info('Bienvenue sur le tableau de bord');

        // Charger les données
        loadCounters();
    });
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>