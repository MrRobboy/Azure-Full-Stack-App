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
			error_log("Tentative d'authentification pour l'email: " . $email);

			$stmt = $this->db->prepare("SELECT * FROM PROF WHERE email = :email");
			$stmt->bindParam(':email', $email);
			$stmt->execute();

			$prof = $stmt->fetch();

			if (!$prof) {
				error_log("Email non trouvé dans la base de données");
				throw new Exception("Email non trouvé", 404);
			}

			error_log("Utilisateur trouvé, vérification du mot de passe");

			if (!password_verify($password, $prof['password'])) {
				error_log("Mot de passe incorrect");
				throw new Exception("Mot de passe incorrect", 401);
			}

			error_log("Authentification réussie pour l'utilisateur: " . $email);
			return $prof;
		} catch (PDOException $e) {
			error_log("Erreur PDO lors de l'authentification: " . $e->getMessage());
			throw new Exception("Erreur lors de la vérification des identifiants", 500);
		} catch (Exception $e) {
			error_log("Erreur lors de l'authentification: " . $e->getMessage());
			throw $e;
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
