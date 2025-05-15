<?php
// status.php - Fichier pour vérifier l'état du backend

// Définir l'en-tête pour indiquer que la réponse est au format JSON
header('Content-Type: application/json');
// Permettre l'accès CORS pour que le front puisse appeler cette API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Informations sur l'environnement
$phpVersion = phpversion();
$serverInfo = $_SERVER['SERVER_SOFTWARE'] ?? 'Information serveur non disponible';
$timestamp = date('Y-m-d H:i:s');

// Vérifier si l'on peut accéder à la base de données (si applicable)
$dbStatus = 'Non vérifié';
$dbMessage = '';

// Si vous avez un fichier de configuration pour la base de données, incluez-le et testez la connexion
if (file_exists(__DIR__ . '/config/database.php')) {
    try {
        include_once __DIR__ . '/config/database.php';
        // Supposons que votre fichier database.php définit une fonction getConnection() ou similaire
        // Adaptez selon votre structure de code
        if (function_exists('getConnection')) {
            $conn = getConnection();
            if ($conn) {
                $dbStatus = 'Connecté';
                $dbMessage = 'Connexion à la base de données réussie';
            } else {
                $dbStatus = 'Erreur';
                $dbMessage = 'Impossible d\'établir une connexion';
            }
        } else {
            $dbStatus = 'Non configuré';
            $dbMessage = 'Fonction de connexion non disponible';
        }
    } catch (Exception $e) {
        $dbStatus = 'Erreur';
        $dbMessage = 'Exception: ' . $e->getMessage();
    }
}

// Créer un tableau avec toutes les informations
$response = [
    'status' => 'online',
    'message' => 'Le backend ESGI fonctionne correctement',
    'environment' => [
        'php_version' => $phpVersion,
        'server' => $serverInfo,
        'timestamp' => $timestamp,
        'timezone' => date_default_timezone_get()
    ],
    'database' => [
        'status' => $dbStatus,
        'message' => $dbMessage
    ],
    'deployment_info' => [
        'version' => '1.0.0',
        'last_deploy' => $timestamp,
        'platform' => 'Azure Web App'
    ]
];

// Retourner la réponse au format JSON
echo json_encode($response, JSON_PRETTY_PRINT);
