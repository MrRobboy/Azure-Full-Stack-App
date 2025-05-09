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
			$stmt = $this->db->prepare("SELECT id_user, nom, prenom, email, classe FROM USER WHERE id_user = ?");
			if (!$stmt) {
				throw new Exception('Erreur de préparation de la requête', 500);
			}

			$stmt->execute([$id]);
			$user = $stmt->fetch(PDO::FETCH_ASSOC);

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
			$stmt = $this->db->prepare("
				SELECT u.id_user, u.nom, u.prenom, u.email, u.classe, c.nom_classe 
				FROM USER u 
				JOIN CLASSE c ON u.classe = c.id_classe 
				WHERE u.classe = ? AND u.type = 'eleve'
				ORDER BY u.nom ASC, u.prenom ASC
			");
			if (!$stmt) {
				throw new Exception('Erreur de préparation de la requête', 500);
			}

			$stmt->execute([$classeId]);
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			throw new Exception('Erreur lors de la récupération des utilisateurs: ' . $e->getMessage(), 500);
		}
	}

	public function getNotes($userId)
	{
		try {
			$stmt = $this->db->prepare("SELECT n.*, e.titre as exam_titre, m.nom as matiere_nom 
									  FROM NOTES n 
									  JOIN EXAM e ON n.exam = e.id_exam 
									  JOIN MATIERE m ON e.matiere = m.id_matiere 
									  WHERE n.user = ?");
			if (!$stmt) {
				throw new Exception('Erreur de préparation de la requête', 500);
			}

			$stmt->execute([$userId]);
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			throw new Exception('Erreur lors de la récupération des notes', 500);
		}
	}

	public function getAll()
	{
		try {
			$stmt = $this->db->prepare("SELECT u.id_user, u.nom, u.prenom, u.email, u.classe, c.nom_classe 
									  FROM USER u 
									  LEFT JOIN CLASSE c ON u.classe = c.id_classe");
			if (!$stmt) {
				throw new Exception('Erreur de préparation de la requête', 500);
			}

			$stmt->execute();
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			throw new Exception('Erreur lors de la récupération des utilisateurs', 500);
		}
	}

	public function getByEmail($email)
	{
		try {
			$stmt = $this->db->prepare("SELECT id_user, nom, prenom, email, classe FROM USER WHERE email = ?");
			if (!$stmt) {
				throw new Exception('Erreur de préparation de la requête', 500);
			}

			$stmt->execute([$email]);
			return $stmt->fetch(PDO::FETCH_ASSOC);
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
				if ($key !== 'id_user') { // Ne pas mettre à jour l'ID
					$fields[] = "$key = ?";
					$values[] = $value;
				}
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
			// Vérifier d'abord si l'utilisateur a des notes
			$stmt = $this->db->prepare("SELECT COUNT(*) FROM NOTES WHERE user = ?");
			$stmt->execute([$id]);
			if ($stmt->fetchColumn() > 0) {
				throw new Exception('Impossible de supprimer l\'utilisateur car il a des notes associées');
			}

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
