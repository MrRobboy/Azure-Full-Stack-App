<?php

/**
 * Gestionnaire d'erreurs centralisé
 * 
 * Ce fichier gère :
 * - La capture et le formatage des erreurs
 * - La rotation des logs
 * - Les métriques de performance
 * - Les alertes automatiques
 */

// Configuration des erreurs
define('ERROR_LOG_DIR', __DIR__ . '/../logs');
define('ERROR_LOG_FILE', ERROR_LOG_DIR . '/error.log');
define('MAX_LOG_SIZE', 10 * 1024 * 1024); // 10MB
define('MAX_LOG_FILES', 5);

// Niveaux d'erreur
define('ERROR_LEVELS', [
	E_ERROR => 'ERROR',
	E_WARNING => 'WARNING',
	E_PARSE => 'PARSE',
	E_NOTICE => 'NOTICE',
	E_CORE_ERROR => 'CORE_ERROR',
	E_CORE_WARNING => 'CORE_WARNING',
	E_COMPILE_ERROR => 'COMPILE_ERROR',
	E_COMPILE_WARNING => 'COMPILE_WARNING',
	E_USER_ERROR => 'USER_ERROR',
	E_USER_WARNING => 'USER_WARNING',
	E_USER_NOTICE => 'USER_NOTICE',
	E_STRICT => 'STRICT',
	E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
	E_DEPRECATED => 'DEPRECATED',
	E_USER_DEPRECATED => 'USER_DEPRECATED'
]);

// Métriques de performance
$metrics = [
	'errors' => [
		'total' => 0,
		'by_type' => [],
		'by_hour' => []
	],
	'performance' => [
		'response_times' => [],
		'memory_usage' => []
	]
];

/**
 * Initialise le gestionnaire d'erreurs
 */
function initErrorHandler()
{
	// Créer le dossier de logs s'il n'existe pas
	if (!file_exists(ERROR_LOG_DIR)) {
		mkdir(ERROR_LOG_DIR, 0755, true);
	}

	// Configurer le gestionnaire d'erreurs
	set_error_handler('handleError');
	set_exception_handler('handleException');
	register_shutdown_function('handleShutdown');

	// Vérifier la rotation des logs
	checkLogRotation();
}

/**
 * Gère les erreurs PHP
 */
function handleError($errno, $errstr, $errfile, $errline)
{
	$error = [
		'type' => ERROR_LEVELS[$errno] ?? 'UNKNOWN',
		'message' => $errstr,
		'file' => $errfile,
		'line' => $errline,
		'timestamp' => date('Y-m-d H:i:s'),
		'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
	];

	// Mettre à jour les métriques
	updateMetrics($error);

	// Logger l'erreur
	logError($error);

	// Vérifier si c'est une erreur fatale
	if (in_array($errno, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
		sendAlert($error);
	}

	return true;
}

/**
 * Gère les exceptions non capturées
 */
function handleException($exception)
{
	$error = [
		'type' => 'EXCEPTION',
		'message' => $exception->getMessage(),
		'file' => $exception->getFile(),
		'line' => $exception->getLine(),
		'timestamp' => date('Y-m-d H:i:s'),
		'trace' => $exception->getTrace()
	];

	// Mettre à jour les métriques
	updateMetrics($error);

	// Logger l'erreur
	logError($error);

	// Envoyer une alerte
	sendAlert($error);

	// Afficher une page d'erreur appropriée
	displayErrorPage($error);
}

/**
 * Gère les erreurs fatales
 */
function handleShutdown()
{
	$error = error_get_last();
	if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
		handleError($error['type'], $error['message'], $error['file'], $error['line']);
	}
}

/**
 * Enregistre une erreur dans le fichier de log
 */
function logError($error)
{
	$logEntry = json_encode($error) . "\n";

	// Vérifier la taille du fichier de log
	if (file_exists(ERROR_LOG_FILE) && filesize(ERROR_LOG_FILE) > MAX_LOG_SIZE) {
		rotateLogs();
	}

	file_put_contents(ERROR_LOG_FILE, $logEntry, FILE_APPEND);
}

/**
 * Effectue la rotation des fichiers de log
 */
function rotateLogs()
{
	// Supprimer le plus ancien fichier si nécessaire
	$oldestLog = ERROR_LOG_DIR . '/error.' . MAX_LOG_FILES . '.log';
	if (file_exists($oldestLog)) {
		unlink($oldestLog);
	}

	// Déplacer les fichiers existants
	for ($i = MAX_LOG_FILES - 1; $i >= 1; $i--) {
		$oldFile = ERROR_LOG_DIR . '/error.' . $i . '.log';
		$newFile = ERROR_LOG_DIR . '/error.' . ($i + 1) . '.log';
		if (file_exists($oldFile)) {
			rename($oldFile, $newFile);
		}
	}

	// Renommer le fichier actuel
	rename(ERROR_LOG_FILE, ERROR_LOG_DIR . '/error.1.log');
}

/**
 * Vérifie si la rotation des logs est nécessaire
 */
function checkLogRotation()
{
	if (file_exists(ERROR_LOG_FILE) && filesize(ERROR_LOG_FILE) > MAX_LOG_SIZE) {
		rotateLogs();
	}
}

/**
 * Met à jour les métriques
 */
function updateMetrics($error)
{
	global $metrics;

	// Incrémenter le compteur total d'erreurs
	$metrics['errors']['total']++;

	// Mettre à jour les erreurs par type
	$type = $error['type'];
	if (!isset($metrics['errors']['by_type'][$type])) {
		$metrics['errors']['by_type'][$type] = 0;
	}
	$metrics['errors']['by_type'][$type]++;

	// Mettre à jour les erreurs par heure
	$hour = date('H');
	if (!isset($metrics['errors']['by_hour'][$hour])) {
		$metrics['errors']['by_hour'][$hour] = 0;
	}
	$metrics['errors']['by_hour'][$hour]++;
}

/**
 * Envoie une alerte
 */
function sendAlert($error)
{
	// TODO: Implémenter l'envoi d'alertes (email, SMS, etc.)
	// Pour l'instant, on log juste l'alerte
	$alert = [
		'type' => 'ALERT',
		'error' => $error,
		'timestamp' => date('Y-m-d H:i:s')
	];
	file_put_contents(ERROR_LOG_DIR . '/alerts.log', json_encode($alert) . "\n", FILE_APPEND);
}

/**
 * Affiche une page d'erreur appropriée
 */
function displayErrorPage($error)
{
	if (php_sapi_name() === 'cli') {
		echo "Error: " . $error['message'] . "\n";
		echo "File: " . $error['file'] . " (line " . $error['line'] . ")\n";
	} else {
		header('HTTP/1.1 500 Internal Server Error');
		header('Content-Type: application/json');
		echo json_encode([
			'success' => false,
			'error' => [
				'message' => 'Une erreur est survenue',
				'code' => 'INTERNAL_ERROR'
			]
		]);
	}
}

/**
 * Récupère les métriques actuelles
 */
function getMetrics()
{
	global $metrics;
	return $metrics;
}

// Initialiser le gestionnaire d'erreurs
initErrorHandler();
