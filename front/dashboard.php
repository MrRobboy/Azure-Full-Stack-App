<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Gestion Scolaire</title>
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <header class="header">
        <div class="header-container">
            <a href="dashboard.php" class="logo">
                <img src="images/school-badge.png" alt="Logo École" class="school-badge">
                Gestion Scolaire
            </a>
            <nav class="nav-menu">
                <a href="gestion_classes.php" class="nav-link">
                    <i class="fas fa-chalkboard"></i>
                    Classes
                </a>
                <a href="gestion_matieres.php" class="nav-link">
                    <i class="fas fa-book"></i>
                    Matières
                </a>
                <a href="gestion_eleves.php" class="nav-link">
                    <i class="fas fa-user-graduate"></i>
                    Élèves
                </a>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    Déconnexion
                </a>
            </nav>
        </div>
    </header>

    <main class="container">
        <div class="welcome-section">
            <h1 id="welcomeTitle">Bienvenue</h1>
            <p class="lead" id="userRole">Chargement...</p>
        </div>

        <div class="dashboard-stats">
            <div class="stat-card">
                <i class="fas fa-chalkboard fa-2x"></i>
                <h3>Classes</h3>
                <p class="value">12</p>
                <p>Classes actives</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-user-graduate fa-2x"></i>
                <h3>Élèves</h3>
                <p class="value">250+</p>
                <p>Élèves inscrits</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-book fa-2x"></i>
                <h3>Matières</h3>
                <p class="value">8</p>
                <p>Matières enseignées</p>
            </div>
        </div>

        <div class="features-grid">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chalkboard"></i> Gestion des classes</h3>
                </div>
                <div class="card-body">
                    <p>Gérez les classes, les effectifs et les emplois du temps.</p>
                    <a href="gestion_classes.php" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i>
                        Accéder
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-book"></i> Gestion des matières</h3>
                </div>
                <div class="card-body">
                    <p>Gérez les matières, les programmes et les coefficients.</p>
                    <a href="gestion_matieres.php" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i>
                        Accéder
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-graduate"></i> Gestion des élèves</h3>
                </div>
                <div class="card-body">
                    <p>Gérez les élèves, leurs notes et leurs informations.</p>
                    <a href="gestion_eleves.php" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i>
                        Accéder
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 Gestion Scolaire. Tous droits réservés.</p>
        </div>
    </footer>

    <script>
        // Vérifier si l'utilisateur est connecté
        if (!sessionStorage.getItem('prof_id')) {
            window.location.href = 'login.php';
        }

        // Récupérer les informations de session
        const prof_id = sessionStorage.getItem('prof_id');
        const prof_nom = sessionStorage.getItem('prof_nom') || 'Professeur';
        const prof_prenom = sessionStorage.getItem('prof_prenom') || '';
        const prof_role = sessionStorage.getItem('prof_role') || 'Enseignant';

        // Mettre à jour l'affichage
        document.getElementById('welcomeTitle').textContent = `Bienvenue, ${prof_prenom} ${prof_nom}`;
        document.getElementById('userRole').textContent = prof_role;
    </script>
</body>

</html>