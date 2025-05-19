<?php
session_start();

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Créer un fichier de log
$logFile = __DIR__ . '/logs/session-' . date('Y-m-d') . '.log';
if (!is_dir(__DIR__ . '/logs')) {
	mkdir(__DIR__ . '/logs', 0755, true);
}

function logMessage($message, $data = null)
{
	global $logFile;
	$timestamp = date('[Y-m-d H:i:s]');
	$logMessage = $timestamp . ' ' . $message;
	if ($data !== null) {
		$logMessage .= ' ' . json_encode($data, JSON_UNESCAPED_UNICODE);
	}
	file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
}

// Log des données reçues
logMessage('Début de la requête set-session.php');
logMessage('Méthode HTTP:', $_SERVER['REQUEST_METHOD']);

// Récupérer les données JSON envoyées
$json = file_get_contents('php://input');
logMessage('Données JSON reçues:', $json);

$data = json_decode($json, true);
logMessage('Données décodées:', $data);

if ($data && isset($data['id_prof']) && isset($data['nom']) && isset($data['prenom'])) {
	// Stocker les informations dans la session
	$_SESSION['prof_id'] = $data['id_prof'];
	$_SESSION['prof_nom'] = $data['nom'];
	$_SESSION['prof_prenom'] = $data['prenom'];
	$_SESSION['prof_role'] = $data['role'] ?? 'Enseignant';

	logMessage('Session créée avec succès:', $_SESSION);
	http_response_code(200);
	echo json_encode(['success' => true]);
} else {
	logMessage('Données invalides ou champs manquants');
	http_response_code(400);
	echo json_encode([
		'success' => false,
		'message' => 'Données invalides ou champs manquants',
		'debug' => [
			'received_data' => $json,
			'decoded_data' => $data
		]
	]);
}
