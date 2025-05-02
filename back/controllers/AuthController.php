<?php
require_once __DIR__ . '/../models/Prof.php';

class AuthController
{
	private $profModel;

	public function __construct()
	{
		$this->profModel = new Prof();
	}

	public function login($email, $password)
	{
		try {
			// Vérification des champs vides
			if (empty($email) || empty($password)) {
				throw new Exception('Veuillez remplir tous les champs', 400);
			}

			// Vérification du format de l'email
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				throw new Exception('Format d\'email invalide', 400);
			}

			$prof = $this->profModel->authenticate($email, $password);

			if ($prof) {
				session_start();
				$_SESSION['prof_id'] = $prof['id_prof'];
				$_SESSION['prof_nom'] = $prof['nom'];
				$_SESSION['prof_prenom'] = $prof['prenom'];
				return [
					'success' => true,
					'message' => 'Connexion réussie'
				];
			}

			throw new Exception('Email ou mot de passe incorrect', 401);
		} catch (Exception $e) {
			return [
				'success' => false,
				'error' => $e->getMessage(),
				'code' => $e->getCode() ?: 500
			];
		}
	}

	public function logout()
	{
		try {
			session_start();
			session_destroy();
			return [
				'success' => true,
				'message' => 'Déconnexion réussie'
			];
		} catch (Exception $e) {
			return [
				'success' => false,
				'error' => 'Erreur lors de la déconnexion',
				'code' => 500
			];
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
