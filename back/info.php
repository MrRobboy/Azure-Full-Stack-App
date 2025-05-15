<?php
// info.php - Information page sur le backend
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backend ESGI - Statut</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
        }
        .status {
            margin: 20px 0;
            padding: 15px;
            border-radius: 4px;
            background-color: #d4edda;
            color: #155724;
        }
        .info-item {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 180px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Backend ESGI - Statut du serveur</h1>
        
        <div class="status">
            Le serveur backend est actif et fonctionne correctement.
        </div>
        
        <h2>Informations sur l'environnement</h2>
        
        <div class="info-item">
            <span class="info-label">Version PHP :</span>
            <?php echo phpversion(); ?>
        </div>
        
        <div class="info-item">
            <span class="info-label">Serveur :</span>
            <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Information non disponible'; ?>
        </div>
        
        <div class="info-item">
            <span class="info-label">Date et heure :</span>
            <?php echo date('Y-m-d H:i:s'); ?>
        </div>
        
        <div class="info-item">
            <span class="info-label">Fuseau horaire :</span>
            <?php echo date_default_timezone_get(); ?>
        </div>
        
        <h2>Test de la base de données</h2>
        
        <div class="info-item">
            <?php
            $dbStatus = 'Non vérifié';
            $dbMessage = '';
            
            if (file_exists(__DIR__ . '/config/database.php')) {
                try {
                    include_once __DIR__ . '/config/database.php';
                    if (function_exists('getConnection')) {
                        $conn = getConnection();
                        if ($conn) {
                            echo '<span style="color: green;">✓ Connexion à la base de données réussie</span>';
                        } else {
                            echo '<span style="color: red;">✗ Impossible d\'établir une connexion à la base de données</span>';
                        }
                    } else {
                        echo '<span style="color: orange;">⚠ Fonction de connexion à la base de données non disponible</span>';
                    }
                } catch (Exception $e) {
                    echo '<span style="color: red;">✗ Erreur: ' . $e->getMessage() . '</span>';
                }
            } else {
                echo '<span style="color: orange;">⚠ Configuration de base de données non trouvée</span>';
            }
            ?>
        </div>
        
        <h2>Informations sur le déploiement</h2>
        
        <div class="info-item">
            <span class="info-label">Plateforme :</span>
            Azure Web App
        </div>
        
        <div class="info-item">
            <span class="info-label">Version :</span>
            1.0.0
        </div>
        
        <div class="info-item">
            <span class="info-label">API Status URL :</span>
            <a href="status.php" target="_blank">/status.php</a>
        </div>
    </div>
</body>
</html>
