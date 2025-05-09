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

<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-chart-line"></i> Statistiques récentes
        </h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Matière</th>
                        <th>Moyenne</th>
                        <th>Meilleure note</th>
                        <th>Plus basse note</th>
                    </tr>
                </thead>
                <tbody id="statsTable">
                    <!-- Les statistiques seront chargées dynamiquement -->
                </tbody>
            </table>
        </div>
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

    // Fonction pour charger les statistiques
    async function loadStats() {
        try {
            const response = await fetch(getApiUrl('notes'));
            const result = await response.json();

            if (!result.success || !result.data) {
                throw new Error('Données invalides reçues de l\'API');
            }

            const notes = result.data;

            // Regrouper les notes par matière
            const stats = {};
            for (const note of notes) {
                if (!stats[note.nom_matiere]) {
                    stats[note.nom_matiere] = {
                        notes: [],
                        moyenne: 0
                    };
                }
                stats[note.nom_matiere].notes.push(parseFloat(note.note));
            }

            // Calculer les statistiques
            const statsTable = document.getElementById('statsTable');
            statsTable.innerHTML = '';

            for (const [matiere, data] of Object.entries(stats)) {
                const moyenne = data.notes.reduce((a, b) => a + b, 0) / data.notes.length;
                const meilleure = Math.max(...data.notes);
                const plusBasse = Math.min(...data.notes);

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${matiere}</td>
                    <td>${moyenne.toFixed(2)}</td>
                    <td>${meilleure}</td>
                    <td>${plusBasse}</td>
                `;
                statsTable.appendChild(row);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des statistiques:', error);
            NotificationSystem.error('Erreur lors du chargement des statistiques');
        }
    }

    // Initialisation
    document.addEventListener('DOMContentLoaded', () => {
        loadCounters();
        loadStats();
    });
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>