<?php
require_once __DIR__ . '/../models/Prof.php';

class AuthController
{
	private $prof;

	public function __construct()
	{
		$this->prof = new Prof();
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
			}
		} catch (Exception $e) {
			error_log("Erreur dans AuthController::login: " . $e->getMessage());
			throw $e;
		}
	}

	public function logout()
	{
		try {
			error_log("Début du processus de logout");

			if (session_status() === PHP_SESSION_ACTIVE) {
				session_destroy();
				error_log("Session détruite avec succès");
				return ['success' => true, 'message' => 'Déconnexion réussie'];
			}

			error_log("Aucune session active à détruire");
			return ['success' => false, 'message' => 'Aucune session active'];
		} catch (Exception $e) {
			error_log("Erreur lors de la déconnexion: " . $e->getMessage());
			throw new Exception("Erreur lors de la déconnexion", 500);
		}
	}

	public function isLoggedIn()
	{
		try {
			session_start();
			return isset($_SESSION['prof_id']);
		} catch (Exception $e) {
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
						'prenom' => $_SESSION['prof_prenom']
					]
				];
			}
			return [
				'success' => false,
				'error' => 'Utilisateur non connecté',
				'code' => 401
			];
		} catch (Exception $e) {
			return [
				'success' => false,
				'error' => 'Erreur lors de la récupération des informations utilisateur',
				'code' => 500
			];
		}
	}
}
