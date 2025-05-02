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
		$prof = $this->profModel->authenticate($email, $password);

		if ($prof) {
			session_start();
			$_SESSION['prof_id'] = $prof['id_prof'];
			$_SESSION['prof_nom'] = $prof['nom'];
			$_SESSION['prof_prenom'] = $prof['prenom'];
			return true;
		}

		return false;
	}

	public function logout()
	{
		session_start();
		session_destroy();
		return true;
	}

	public function isLoggedIn()
	{
		session_start();
		return isset($_SESSION['prof_id']);
	}

	public function getCurrentUser()
	{
		if ($this->isLoggedIn()) {
			return [
				'id' => $_SESSION['prof_id'],
				'nom' => $_SESSION['prof_nom'],
				'prenom' => $_SESSION['prof_prenom']
			];
		}
		return null;
	}
}
