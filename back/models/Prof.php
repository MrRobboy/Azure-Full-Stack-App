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

	public function getAll()
	{
		try {
			$stmt = $this->db->prepare("SELECT id_prof, nom, prenom, email FROM PROF");
			if (!$stmt) {
				throw new Exception('Erreur de préparation de la requête', 500);
			}

			$stmt->execute();
			return $stmt->fetchAll();
		} catch (PDOException $e) {
			throw new Exception('Erreur lors de la récupération des professeurs', 500);
		}
	}

	public function getByEmail($email)
	{
		try {
			$stmt = $this->db->prepare("SELECT * FROM PROF WHERE email = ?");
			if (!$stmt) {
				throw new Exception('Erreur de préparation de la requête', 500);
			}

			$stmt->execute([$email]);
			return $stmt->fetch();
		} catch (PDOException $e) {
			throw new Exception('Erreur lors de la récupération du professeur', 500);
		}
	}

	public function create($data)
	{
		try {
			$stmt = $this->db->prepare("INSERT INTO PROF (nom, prenom, email, password) VALUES (?, ?, ?, ?)");
			if (!$stmt) {
				throw new Exception('Erreur de préparation de la requête', 500);
			}

			$stmt->execute([
				$data['nom'],
				$data['prenom'],
				$data['email'],
				$data['password']
			]);

			return DatabaseService::getInstance()->lastInsertId();
		} catch (PDOException $e) {
			throw new Exception('Erreur lors de la création du professeur', 500);
		}
	}

	public function update($id, $data)
	{
		try {
			$fields = [];
			$values = [];

			foreach ($data as $key => $value) {
				$fields[] = "$key = ?";
				$values[] = $value;
			}

			$values[] = $id;

			$sql = "UPDATE PROF SET " . implode(', ', $fields) . " WHERE id_prof = ?";
			$stmt = $this->db->prepare($sql);

			if (!$stmt) {
				throw new Exception('Erreur de préparation de la requête', 500);
			}

			$stmt->execute($values);
			return true;
		} catch (PDOException $e) {
			throw new Exception('Erreur lors de la mise à jour du professeur', 500);
		}
	}

	public function delete($id)
	{
		try {
			$stmt = $this->db->prepare("DELETE FROM PROF WHERE id_prof = ?");
			if (!$stmt) {
				throw new Exception('Erreur de préparation de la requête', 500);
			}

			$stmt->execute([$id]);
			return true;
		} catch (PDOException $e) {
			throw new Exception('Erreur lors de la suppression du professeur', 500);
		}
	}
}
