<?php
require_once __DIR__ . '/../services/DatabaseService.php';
require_once __DIR__ . '/../services/ErrorService.php';

class Eleve
{
	private $db;
	private $errorService;

	public function __construct()
	{
		$this->db = DatabaseService::getInstance()->getConnection();
		$this->errorService = ErrorService::getInstance();
	}

	public function getAll()
	{
		try {
			$stmt = $this->db->prepare("
				SELECT u.*, c.nom_classe 
				FROM USER u
				JOIN CLASSE c ON u.id_classe = c.id_classe
				WHERE u.type = 'eleve'
				ORDER BY u.nom ASC, u.prenom ASC
			");
			$stmt->execute();
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (Exception $e) {
			$this->errorService->logError('Eleve::getAll', $e->getMessage());
			return [];
		}
	}

	public function getById($id)
	{
		try {
			$stmt = $this->db->prepare("
				SELECT u.*, c.nom_classe 
				FROM USER u
				JOIN CLASSE c ON u.id_classe = c.id_classe
				WHERE u.id_user = ? AND u.type = 'eleve'
			");
			$stmt->execute([$id]);
			return $stmt->fetch(PDO::FETCH_ASSOC);
		} catch (Exception $e) {
			$this->errorService->logError('Eleve::getById', $e->getMessage());
			return null;
		}
	}

	public function getByClasse($id_classe)
	{
		try {
			$stmt = $this->db->prepare("
				SELECT u.*, c.nom_classe 
				FROM USER u
				JOIN CLASSE c ON u.id_classe = c.id_classe
				WHERE u.id_classe = ? AND u.type = 'eleve'
				ORDER BY u.nom ASC, u.prenom ASC
			");
			$stmt->execute([$id_classe]);
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (Exception $e) {
			$this->errorService->logError('Eleve::getByClasse', $e->getMessage());
			return [];
		}
	}

	public function create($nom, $prenom, $email, $password, $id_classe)
	{
		try {
			$stmt = $this->db->prepare("
				INSERT INTO USER (nom, prenom, email, password, type, id_classe) 
				VALUES (?, ?, ?, ?, 'eleve', ?)
			");
			$stmt->execute([$nom, $prenom, $email, password_hash($password, PASSWORD_DEFAULT), $id_classe]);
			return $this->db->lastInsertId();
		} catch (Exception $e) {
			$this->errorService->logError('Eleve::create', $e->getMessage());
			return false;
		}
	}

	public function update($id, $nom, $prenom, $email, $id_classe)
	{
		try {
			$stmt = $this->db->prepare("
				UPDATE USER 
				SET nom = ?, prenom = ?, email = ?, id_classe = ? 
				WHERE id_user = ? AND type = 'eleve'
			");
			return $stmt->execute([$nom, $prenom, $email, $id_classe, $id]);
		} catch (Exception $e) {
			$this->errorService->logError('Eleve::update', $e->getMessage());
			return false;
		}
	}

	public function delete($id)
	{
		try {
			$stmt = $this->db->prepare("DELETE FROM USER WHERE id_user = ? AND type = 'eleve'");
			return $stmt->execute([$id]);
		} catch (Exception $e) {
			$this->errorService->logError('Eleve::delete', $e->getMessage());
			return false;
		}
	}
}
