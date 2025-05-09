<?php
require_once __DIR__ . '/../services/DatabaseService.php';

class User
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

	public function getById($id)
	{
		try {
			$stmt = $this->db->prepare("SELECT * FROM USER WHERE id_user = ?");
			if (!$stmt) {
				throw new Exception('Erreur de préparation de la requête', 500);
			}

			$stmt->execute([$id]);
			$user = $stmt->fetch();

			if (!$user) {
				throw new Exception('Utilisateur non trouvé', 404);
			}

			return $user;
		} catch (PDOException $e) {
			throw new Exception('Erreur lors de la récupération de l\'utilisateur', 500);
		}
	}

	public function getByClasse($classeId)
	{
		try {
			$stmt = $this->db->prepare("SELECT * FROM USER WHERE classe = ?");
			if (!$stmt) {
				throw new Exception('Erreur de préparation de la requête', 500);
			}

			$stmt->execute([$classeId]);
			return $stmt->fetchAll();
		} catch (PDOException $e) {
			throw new Exception('Erreur lors de la récupération des utilisateurs', 500);
		}
	}

	public function getNotes($userId)
	{
		try {
			$stmt = $this->db->prepare("SELECT * FROM NOTES WHERE user = ?");
			if (!$stmt) {
				throw new Exception('Erreur de préparation de la requête', 500);
			}

			$stmt->execute([$userId]);
			return $stmt->fetchAll();
		} catch (PDOException $e) {
			throw new Exception('Erreur lors de la récupération des notes', 500);
		}
	}

	public function getAll()
	{
		try {
			$stmt = $this->db->prepare("SELECT id_user, nom, prenom, email, classe FROM USER");
			if (!$stmt) {
				throw new Exception('Erreur de préparation de la requête', 500);
			}

			$stmt->execute();
			return $stmt->fetchAll();
		} catch (PDOException $e) {
			throw new Exception('Erreur lors de la récupération des utilisateurs', 500);
		}
	}

	public function getByEmail($email)
	{
		try {
			$stmt = $this->db->prepare("SELECT * FROM USER WHERE email = ?");
			if (!$stmt) {
				throw new Exception('Erreur de préparation de la requête', 500);
			}

			$stmt->execute([$email]);
			return $stmt->fetch();
		} catch (PDOException $e) {
			throw new Exception('Erreur lors de la récupération de l\'utilisateur', 500);
		}
	}

	public function create($data)
	{
		try {
			$stmt = $this->db->prepare("INSERT INTO USER (nom, prenom, email, password, classe) VALUES (?, ?, ?, ?, ?)");
			if (!$stmt) {
				throw new Exception('Erreur de préparation de la requête', 500);
			}

			$stmt->execute([
				$data['nom'],
				$data['prenom'],
				$data['email'],
				$data['password'],
				$data['classe']
			]);

			return $this->db->lastInsertId();
		} catch (PDOException $e) {
			throw new Exception('Erreur lors de la création de l\'utilisateur', 500);
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

			$sql = "UPDATE USER SET " . implode(', ', $fields) . " WHERE id_user = ?";
			$stmt = $this->db->prepare($sql);

			if (!$stmt) {
				throw new Exception('Erreur de préparation de la requête', 500);
			}

			$stmt->execute($values);
			return true;
		} catch (PDOException $e) {
			throw new Exception('Erreur lors de la mise à jour de l\'utilisateur', 500);
		}
	}

	public function delete($id)
	{
		try {
			$stmt = $this->db->prepare("DELETE FROM USER WHERE id_user = ?");
			if (!$stmt) {
				throw new Exception('Erreur de préparation de la requête', 500);
			}

			$stmt->execute([$id]);
			return true;
		} catch (PDOException $e) {
			throw new Exception('Erreur lors de la suppression de l\'utilisateur', 500);
		}
	}
}
