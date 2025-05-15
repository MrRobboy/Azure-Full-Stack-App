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
			error_log("Début du processus de login dans AuthController");

			if (empty($email) || empty($password)) {
				error_log("Champs manquants dans la requête de login");
				throw new Exception("Veuillez remplir tous les champs", 400);
			}

			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				error_log("Format d'email invalide: " . $email);
				throw new Exception("Format d'email invalide", 400);
			}

			$prof = $this->prof->authenticate($email, $password);

			if ($prof) {
				error_log("Login réussi, création de la session pour l'utilisateur: " . $email);
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
			} else {
				throw new Exception("Email ou mot de passe incorrect", 401);
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
			session_destroy();
			return [
				'success' => true,
				'message' => 'Déconnexion réussie'
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
			return isset($_SESSION['prof_id']);
		} catch (Exception $e) {
			error_log("Erreur dans AuthController::isLoggedIn: " . $e->getMessage());
			return false;
		}
	}

	public function getCurrentUser()
	{
		try {
			if ($this->isLoggedIn()) {
				return [
					'success' => true,
					'data' => [
						'id' => $_SESSION['prof_id'],
						'nom' => $_SESSION['prof_nom'],
						'prenom' => $_SESSION['prof_prenom'],
						'email' => $_SESSION['prof_email']
					]
				];
			} else {
				throw new Exception("Non authentifié", 401);
			}
		} catch (Exception $e) {
			error_log("Erreur dans AuthController::getCurrentUser: " . $e->getMessage());
			throw $e;
		}
	}
}
