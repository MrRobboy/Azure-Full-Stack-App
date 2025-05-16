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
			error_log("Début du processus de login dans AuthController (BYPASS ACTIF)");

			// BYPASS: Accept any credentials
			$mockProf = [
				'id_prof' => 1,
				'nom' => 'Admin',
				'prenom' => 'User',
				'email' => $email ?: 'admin@example.com'
			];

			error_log("Login réussi (BYPASS), création de la session pour l'utilisateur: " . $email);
			$_SESSION['prof_id'] = $mockProf['id_prof'];
			$_SESSION['prof_nom'] = $mockProf['nom'];
			$_SESSION['prof_prenom'] = $mockProf['prenom'];
			$_SESSION['prof_email'] = $mockProf['email'];

			// Générer un token JWT
			$token = $this->generateJWT($mockProf['id_prof'], $mockProf['email']);
			error_log("Token JWT généré pour l'utilisateur: " . $email);

			// Structure de réponse adaptée au front-end
			return [
				'success' => true,
				'message' => 'Connexion réussie (BYPASS ACTIF)',
				'user' => [
					'id' => $mockProf['id_prof'],
					'nom' => $mockProf['nom'],
					'prenom' => $mockProf['prenom'],
					'email' => $mockProf['email']
				],
				'token' => $token
			];
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
		// Always return true to bypass authentication
		return true;
	}

	public function getCurrentUser()
	{
		try {
			// Always return a default user
			return [
				'success' => true,
				'data' => [
					'id' => $_SESSION['prof_id'] ?? 1,
					'nom' => $_SESSION['prof_nom'] ?? 'Admin',
					'prenom' => $_SESSION['prof_prenom'] ?? 'User',
					'email' => $_SESSION['prof_email'] ?? 'admin@example.com'
				]
			];
		} catch (Exception $e) {
			error_log("Erreur dans AuthController::getCurrentUser: " . $e->getMessage());
			throw $e;
		}
	}
}
