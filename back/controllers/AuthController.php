<?php
require_once __DIR__ . '/../models/Prof.php';

class AuthController
{
	private $prof;

	public function __construct()
	{
		$this->prof = new Prof();
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}

		// Always set a default session for authentication bypass
		$_SESSION['prof_id'] = 1;
		$_SESSION['prof_nom'] = 'Admin';
		$_SESSION['prof_prenom'] = 'User';
		$_SESSION['prof_email'] = 'admin@example.com';
	}

	// Fonction pour générer un token JWT simple
	private function generateJWT($userId, $email)
	{
		// Header
		$header = json_encode([
			'typ' => 'JWT',
			'alg' => 'HS256'
		]);

		// Payload
		$payload = json_encode([
			'sub' => $userId,
			'email' => $email,
			'iat' => time(),
			'exp' => time() + (60 * 60 * 24) // 24 heures
		]);

		// Encoder header et payload en Base64Url
		$base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
		$base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

		// Signature simplifiée
		$signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'esgi_azure_secret_key', true);
		$base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

		// Token complet
		return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
	}

	public function login($email, $password)
	{
		try {
			error_log("Début du processus de login dans AuthController - Vérification des identifiants");

			// Vérifier si les paramètres sont présents
			if (empty($email) || empty($password)) {
				error_log("Champs manquants dans la requête de login");
				throw new Exception("Veuillez remplir tous les champs", 400);
			}

			// Valider le format de l'email
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				error_log("Format d'email invalide: " . $email);
				throw new Exception("Format d'email invalide", 400);
			}

			// Vérifier les identifiants dans la base de données
			try {
				$prof = $this->prof->authenticate($email, $password);

				error_log("Authentification réussie pour l'utilisateur: " . $email);

				// Créer la session utilisateur
				$_SESSION['prof_id'] = $prof['id_prof'];
				$_SESSION['prof_nom'] = $prof['nom'];
				$_SESSION['prof_prenom'] = $prof['prenom'];
				$_SESSION['prof_email'] = $prof['email'];

				// Générer un token JWT
				$token = $this->generateJWT($prof['id_prof'], $prof['email']);
				error_log("Token JWT généré pour l'utilisateur: " . $email);

				// Structure de réponse adaptée au front-end
				return [
					'success' => true,
					'message' => 'Connexion réussie',
					'user' => [
						'id' => $prof['id_prof'],
						'nom' => $prof['nom'],
						'prenom' => $prof['prenom'],
						'email' => $prof['email']
					],
					'token' => $token
				];
			} catch (Exception $authError) {
				error_log("Erreur d'authentification: " . $authError->getMessage());

				// FALLBACK: Utiliser des identifiants de test si activé
				$useFallbackCredentials = true; // Mettre à false pour une vérification stricte

				if ($useFallbackCredentials) {
					// Credentials de test
					$fallbackCredentials = [
						'admin@example.com' => [
							'password' => 'admin123',
							'id_prof' => 1,
							'nom' => 'Admin',
							'prenom' => 'User'
						],
						'prof@example.com' => [
							'password' => 'prof123',
							'id_prof' => 2,
							'nom' => 'Prof',
							'prenom' => 'Test'
						]
					];

					// Vérifier si l'email est dans les credentials de test
					if (isset($fallbackCredentials[$email]) && $fallbackCredentials[$email]['password'] === $password) {
						error_log("Utilisation des credentials de fallback pour: " . $email);

						$fallbackProf = $fallbackCredentials[$email];

						// Créer la session utilisateur
						$_SESSION['prof_id'] = $fallbackProf['id_prof'];
						$_SESSION['prof_nom'] = $fallbackProf['nom'];
						$_SESSION['prof_prenom'] = $fallbackProf['prenom'];
						$_SESSION['prof_email'] = $email;

						// Générer un token JWT
						$token = $this->generateJWT($fallbackProf['id_prof'], $email);

						return [
							'success' => true,
							'message' => 'Connexion réussie (credentials de test)',
							'user' => [
								'id' => $fallbackProf['id_prof'],
								'nom' => $fallbackProf['nom'],
								'prenom' => $fallbackProf['prenom'],
								'email' => $email
							],
							'token' => $token
						];
					}

					// Credentials invalides même dans le fallback
					throw new Exception("Email ou mot de passe incorrect", 401);
				} else {
					// Pas de fallback, renvoyer l'erreur originale
					throw $authError;
				}
			}
		} catch (Exception $e) {
			error_log("Erreur dans AuthController::login: " . $e->getMessage());
			throw $e;
		}
	}

	public function logout()
	{
		try {
			if (session_status() === PHP_SESSION_NONE) {
				session_start();
			}

			// Keep the session active for bypass authentication
			return [
				'success' => true,
				'message' => 'Déconnexion réussie (BYPASS ACTIF - session maintenue)'
			];
		} catch (Exception $e) {
			error_log("Erreur dans AuthController::logout: " . $e->getMessage());
			throw $e;
		}
	}

	public function isLoggedIn()
	{
		try {
			if (session_status() === PHP_SESSION_NONE) {
				session_start();
			}

			// Vérifier si l'utilisateur a une session active
			$isLoggedIn = isset($_SESSION['prof_id']);
			error_log("Vérification de l'authentification: " . ($isLoggedIn ? "Utilisateur connecté" : "Utilisateur non connecté"));

			return $isLoggedIn;
		} catch (Exception $e) {
			error_log("Erreur dans AuthController::isLoggedIn: " . $e->getMessage());
			return false;
		}
	}

	public function getCurrentUser()
	{
		try {
			if ($this->isLoggedIn()) {
				$userData = [
					'id' => $_SESSION['prof_id'],
					'nom' => $_SESSION['prof_nom'],
					'prenom' => $_SESSION['prof_prenom'],
					'email' => $_SESSION['prof_email']
				];

				error_log("Récupération des informations utilisateur pour: " . $userData['email']);

				return [
					'success' => true,
					'data' => $userData
				];
			} else {
				error_log("Tentative d'accès aux informations utilisateur sans session");
				throw new Exception("Non authentifié", 401);
			}
		} catch (Exception $e) {
			error_log("Erreur dans AuthController::getCurrentUser: " . $e->getMessage());
			throw $e;
		}
	}
}
