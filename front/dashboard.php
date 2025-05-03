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
        <i class="fas fa-graduation-cap fa-3x" style="color: var(--secondary-color); margin-bottom: 1rem;"></i>
        <h3>Notes</h3>
        <p id="notesCount">-</p>
        <a href="gestion_notes.php" class="btn btn-primary">Gérer les notes</a>
    </div>

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
            <i class="fas fa-calendar"></i> Prochains examens
        </h2>
    </div>
    <div class="card-body">
        <div class="calendar">
            <div class="calendar-header">
                <button id="prevMonth" class="btn btn-primary">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <h3 id="currentMonth">Chargement...</h3>
                <button id="nextMonth" class="btn btn-primary">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="calendar-grid" id="calendarGrid">
                <!-- Le calendrier sera généré dynamiquement -->
            </div>
        </div>
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

<script>
    // Fonction pour charger les compteurs
    async function loadCounters() {
        try {
            const [notesRes, matieresRes, classesRes, examensRes, profsRes, usersRes] = await Promise.all([
                fetch('api/notes'),
                fetch('api/matieres'),
                fetch('api/classes'),
                fetch('api/examens'),
                fetch('api/profs'),
                fetch('api/users')
            ]);

            const [notes, matieres, classes, examens, profs, users] = await Promise.all([
                notesRes.json(),
                matieresRes.json(),
                classesRes.json(),
                examensRes.json(),
                profsRes.json(),
                usersRes.json()
            ]);

            // Ne mettre à jour que si des données sont trouvées
            if (notes.data && notes.data.length > 0) {
                document.getElementById('notesCount').textContent = notes.data.length;
            }
            if (matieres.data && matieres.data.length > 0) {
                document.getElementById('matieresCount').textContent = matieres.data.length;
            }
            if (classes.data && classes.data.length > 0) {
                document.getElementById('classesCount').textContent = classes.data.length;
            }
            if (examens.data && examens.data.length > 0) {
                document.getElementById('examensCount').textContent = examens.data.length;
            }
            if (profs.data && profs.data.length > 0) {
                document.getElementById('profsCount').textContent = profs.data.length;
            }
            if (users.data && users.data.length > 0) {
                document.getElementById('usersCount').textContent = users.data.length;
            }
        } catch (error) {
            console.error('Erreur lors du chargement des compteurs:', error);
        }
    }

    // Fonction pour charger les statistiques
    async function loadStats() {
        try {
            const response = await fetch('/api/notes');
            const notes = await response.json();

            // Regrouper les notes par matière
            const stats = notes.reduce((acc, note) => {
                if (!acc[note.nom_matiere]) {
                    acc[note.nom_matiere] = {
                        notes: [],
                        moyenne: 0
                    };
                }
                acc[note.nom_matiere].notes.push(note.valeur);
                return acc;
            }, {});

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
        }
    }

    // Fonction pour générer le calendrier
    function generateCalendar(year, month) {
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();
        const startingDay = firstDay.getDay();

        const calendarGrid = document.getElementById('calendarGrid');
        calendarGrid.innerHTML = '';

        // Ajouter les en-têtes des jours
        const days = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
        days.forEach(day => {
            const dayHeader = document.createElement('div');
            dayHeader.className = 'calendar-day';
            dayHeader.textContent = day;
            calendarGrid.appendChild(dayHeader);
        });

        // Ajouter les jours vides au début
        for (let i = 0; i < startingDay; i++) {
            const emptyDay = document.createElement('div');
            emptyDay.className = 'calendar-day';
            calendarGrid.appendChild(emptyDay);
        }

        // Ajouter les jours du mois
        for (let day = 1; day <= daysInMonth; day++) {
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day';
            dayElement.textContent = day;

            // Vérifier si c'est aujourd'hui
            const today = new Date();
            if (year === today.getFullYear() && month === today.getMonth() && day === today.getDate()) {
                dayElement.classList.add('today');
            }

            calendarGrid.appendChild(dayElement);
        }
    }

    // Charger les données au chargement de la page
    document.addEventListener('DOMContentLoaded', () => {
        loadCounters();
        loadStats();

        // Initialiser le calendrier
        const today = new Date();
        document.getElementById('currentMonth').textContent =
            today.toLocaleString('fr-FR', {
                month: 'long',
                year: 'numeric'
            });
        generateCalendar(today.getFullYear(), today.getMonth());

        // Gérer les boutons de navigation du calendrier
        document.getElementById('prevMonth').addEventListener('click', () => {
            today.setMonth(today.getMonth() - 1);
            document.getElementById('currentMonth').textContent =
                today.toLocaleString('fr-FR', {
                    month: 'long',
                    year: 'numeric'
                });
            generateCalendar(today.getFullYear(), today.getMonth());
        });

        document.getElementById('nextMonth').addEventListener('click', () => {
            today.setMonth(today.getMonth() + 1);
            document.getElementById('currentMonth').textContent =
                today.toLocaleString('fr-FR', {
                    month: 'long',
                    year: 'numeric'
                });
            generateCalendar(today.getFullYear(), today.getMonth());
        });
    });
</script>

<?php
$content = ob_get_clean();
require_once 'templates/base.php';
?>