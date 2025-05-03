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

				return [
					'success' => true,
					'message' => 'Connexion réussie',
					'data' => [
						'id' => $prof['id_prof'],
						'nom' => $prof['nom'],
						'prenom' => $prof['prenom'],
						'email' => $prof['email']
					]
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
