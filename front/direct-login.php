<?php

/**
 * Direct Login - Script de communication directe serveur-à-serveur
 * 
 * Ce script contourne les limitations CORS en effectuant les requêtes
 * directement du serveur frontend vers le backend sans passer par le navigateur.
 */

// Configuration des en-têtes
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Logs pour le débogage
error_log('Direct-login called with method: ' . $_SERVER['REQUEST_METHOD']);
error_log('Query string: ' . $_SERVER['QUERY_STRING']);

// Gérer les requêtes OPTIONS (pre-flight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
	http_response_code(200);
	exit;
}

// Configuration backend
$api_base_url = 'https://app-backend-esgi-app.azurewebsites.net';
$login_endpoint = '/api/auth/login';

// Récupérer les données JSON envoyées en POST
$post_data = file_get_contents('php://input');
$json_data = json_decode($post_data, true);

// Log des données reçues (anonymisées pour la sécurité)
if ($json_data) {
	$log_data = $json_data;
	if (isset($log_data['password'])) {
		$log_data['password'] = '******';
	}
	error_log('Données reçues: ' . json_encode($log_data));
} else {
	error_log('Aucune donnée JSON valide reçue');
}

// Vérifier si les données sont valides
if (!$json_data || !isset($json_data['email']) || !isset($json_data['password'])) {
	echo json_encode([
		'success' => false,
		'message' => 'Données manquantes ou format invalide'
	]);
	exit;
}

// Fonction pour essayer plusieurs méthodes de requête
function try_multiple_request_methods($url, $data)
{
	$result = null;
	$errors = [];

	// Méthode 1: cURL standard
	try {
		error_log("Essai méthode 1: cURL standard");

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'User-Agent: AzureAppService/1.0',
			'Accept: application/json'
		]);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curl_error = curl_error($ch);
		curl_close($ch);

		error_log("Résultat méthode 1: HTTP $http_code");

		if ($response && $http_code >= 200 && $http_code < 300) {
			$json_response = json_decode($response, true);
			if ($json_response) {
				return [
					'success' => true,
					'method' => 'curl_standard',
					'status' => $http_code,
					'response' => $json_response
				];
			}
		}

		$errors[] = "cURL standard: HTTP $http_code, Erreur: $curl_error";
	} catch (Exception $e) {
		$errors[] = "cURL standard exception: " . $e->getMessage();
	}

	// Méthode 2: file_get_contents avec context
	try {
		error_log("Essai méthode 2: file_get_contents");

		$options = [
			'http' => [
				'method' => 'POST',
				'header' => "Content-Type: application/json\r\n",
				'content' => json_encode($data),
				'timeout' => 15
			]
		];

		$context = stream_context_create($options);
		$response = @file_get_contents($url, false, $context);

		if ($response !== false) {
			$json_response = json_decode($response, true);
			if ($json_response) {
				return [
					'success' => true,
					'method' => 'file_get_contents',
					'response' => $json_response
				];
			}
		}

		$errors[] = "file_get_contents: Échec";
	} catch (Exception $e) {
		$errors[] = "file_get_contents exception: " . $e->getMessage();
	}

	// Méthode 3: cURL avec en-têtes spécifiques Azure
	try {
		error_log("Essai méthode 3: cURL avec en-têtes Azure");

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'X-MS-SITE-RESTRICTED-TOKEN: true',
			'X-ARR-SSL: true',
			'X-MS-REQUEST-ID: ' . uniqid(),
			'X-Original-URL: /api/auth/login'
		]);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curl_error = curl_error($ch);
		curl_close($ch);

		error_log("Résultat méthode 3: HTTP $http_code");

		if ($response && $http_code >= 200 && $http_code < 300) {
			$json_response = json_decode($response, true);
			if ($json_response) {
				return [
					'success' => true,
					'method' => 'curl_azure_headers',
					'status' => $http_code,
					'response' => $json_response
				];
			}
		}

		$errors[] = "cURL Azure: HTTP $http_code, Erreur: $curl_error";
	} catch (Exception $e) {
		$errors[] = "cURL Azure exception: " . $e->getMessage();
	}

	// Méthode 4: cURL avec méthode alternative (X-HTTP-Method-Override)
	try {
		error_log("Essai méthode 4: cURL avec X-HTTP-Method-Override");

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'X-HTTP-Method-Override: POST',
			'User-Agent: AzureWebApp/1.0',
			'Accept: application/json'
		]);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curl_error = curl_error($ch);
		curl_close($ch);

		error_log("Résultat méthode 4: HTTP $http_code");

		if ($response && $http_code >= 200 && $http_code < 300) {
			$json_response = json_decode($response, true);
			if ($json_response) {
				return [
					'success' => true,
					'method' => 'curl_method_override',
					'status' => $http_code,
					'response' => $json_response
				];
			}
		}

		$errors[] = "cURL Method Override: HTTP $http_code, Erreur: $curl_error";
	} catch (Exception $e) {
		$errors[] = "cURL Method Override exception: " . $e->getMessage();
	}

	// Si toutes les méthodes ont échoué
	return [
		'success' => false,
		'message' => 'Toutes les méthodes de requête ont échoué',
		'errors' => $errors
	];
}

// Tenter la connexion avec les différentes méthodes
$login_url = $api_base_url . $login_endpoint;
$result = try_multiple_request_methods($login_url, $json_data);

// Si toutes les méthodes ont échoué avec l'URL principale, essayer des chemins alternatifs
if (!$result['success']) {
	error_log("Toutes les méthodes ont échoué avec l'URL principale, essai avec des chemins alternatifs");

	// Liste des endpoints d'authentification alternatifs à essayer, dans l'ordre de priorité
	$alternative_endpoints = [
		// Endpoints qui devraient fonctionner selon les routes définies dans api.php
		'/api/auth/check-credentials', // Endpoint GET spécial pour vérification (voir api.php)
		'/api/status', // Route de statut qui devrait fonctionner

		// Autres tentatives (moins prioritaires)
		'/api/login',
		'/auth/login',
		'/login',
		'/api/user/login',
		'/api/v1/auth/login',
		'/api/authenticate'
	];

	foreach ($alternative_endpoints as $alt_endpoint) {
		error_log("Essai avec l'endpoint alternatif: " . $alt_endpoint);
		$alt_url = $api_base_url . $alt_endpoint;

		// Si c'est l'endpoint de vérification par GET, utiliser une approche spéciale
		if ($alt_endpoint === '/api/auth/check-credentials') {
			error_log("Tentative avec GET auth/check-credentials comme solution de secours");

			// Construire l'URL avec les paramètres
			$check_url = $alt_url . '?email=' . urlencode($json_data['email']) . '&password=' . urlencode($json_data['password']);

			// Tentative avec GET
			$ch = curl_init($check_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPGET, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 15);

			$response = curl_exec($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$curl_error = curl_error($ch);
			curl_close($ch);

			if ($response && $http_code >= 200 && $http_code < 300) {
				$json_response = json_decode($response, true);
				if ($json_response && isset($json_response['token'])) {
					error_log("Endpoint GET auth/check-credentials a fonctionné!");
					$result = [
						'success' => true,
						'method' => 'get_check_credentials',
						'status' => $http_code,
						'response' => $json_response
					];
					break;
				}
			}

			error_log("Échec avec GET auth/check-credentials: HTTP $http_code, Erreur: $curl_error");
			continue;
		}

		// Pour les autres endpoints, utiliser l'approche standard
		$alt_result = try_multiple_request_methods($alt_url, $json_data);

		if ($alt_result['success']) {
			error_log("Endpoint alternatif fonctionnel trouvé: " . $alt_endpoint);
			$result = $alt_result;
			break;
		}
	}
}

// Traiter le résultat
if ($result['success']) {
	$response_data = $result['response'];

	// Log de succès (anonymisé)
	error_log('Login réussi avec méthode: ' . $result['method']);

	// Renvoyer la réponse au client
	echo json_encode($response_data);
} else {
	// Log d'échec
	error_log('Échec de toutes les méthodes de login: ' . json_encode($result['errors']));

	// Renvoyer un message d'erreur adapté
	echo json_encode([
		'success' => false,
		'message' => 'Erreur de connexion au serveur backend',
		'details' => $result['errors']
	]);
}
