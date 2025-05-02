<?php
require_once __DIR__ . '/../services/DatabaseService.php';

class Prof
{
	private $db;

	public function __construct()
	{
		try {
			$this->db = DatabaseService::getInstance()->getConnection();
		} catch (Exception $e) {
			throw new Exception('Erreur de connexion à la base de données', 500);
		}
	}

	public function authenticate($email, $password)
	{
		try {
			$stmt = $this->db->prepare("SELECT * FROM PROF WHERE email = ?");
			if (!$stmt) {
				throw new Exception('Erreur de préparation de la requête', 500);
			}

			$stmt->execute([$email]);
			$prof = $stmt->fetch();

			if (!$prof) {
				throw new Exception('Email non trouvé', 401);
			}

			if (!password_verify($password, $prof['password'])) {
				throw new Exception('Mot de passe incorrect', 401);
			}

			return $prof;
		} catch (PDOException $e) {
			throw new Exception('Erreur lors de l\'authentification', 500);
		}
	}

	public function getById($id)
	{
		try {
			$stmt = $this->db->prepare("SELECT * FROM PROF WHERE id_prof = ?");
			if (!$stmt) {
				throw new Exception('Erreur de préparation de la requête', 500);
			}

			$stmt->execute([$id]);
			$prof = $stmt->fetch();

			if (!$prof) {
				throw new Exception('Professeur non trouvé', 404);
			}

			return $prof;
		} catch (PDOException $e) {
			throw new Exception('Erreur lors de la récupération du professeur', 500);
		}
	}

	public function getMatieres($profId)
	{
		try {
			$stmt = $this->db->prepare("SELECT * FROM MATIERE WHERE id_matiere IN (SELECT matiere FROM PROF WHERE id_prof = ?)");
			if (!$stmt) {
				throw new Exception('Erreur de préparation de la requête', 500);
			}

			$stmt->execute([$profId]);
			return $stmt->fetchAll();
		} catch (PDOException $e) {
			throw new Exception('Erreur lors de la récupération des matières', 500);
		}
	}

	public function getExams($profId)
	{
		try {
			$stmt = $this->db->prepare("SELECT * FROM EXAM WHERE matiere IN (SELECT matiere FROM PROF WHERE id_prof = ?)");
			if (!$stmt) {
				throw new Exception('Erreur de préparation de la requête', 500);
			}

			$stmt->execute([$profId]);
			return $stmt->fetchAll();
		} catch (PDOException $e) {
			throw new Exception('Erreur lors de la récupération des examens', 500);
		}
	}
}
