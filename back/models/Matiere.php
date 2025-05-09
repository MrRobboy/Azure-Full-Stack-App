<?php
require_once __DIR__ . '/../services/DatabaseService.php';
require_once __DIR__ . '/../services/ErrorService.php';

class Matiere
{
	private $db;
	private $errorService;

	public function __construct()
	{
		$this->db = DatabaseService::getInstance();
		$this->errorService = ErrorService::getInstance();
	}

	public function getAllMatieres()
	{
		try {
			$conn = $this->db->getConnection();
			$stmt = $conn->query("SELECT * FROM MATIERE ORDER BY nom");
			$result = $stmt->fetchAll();

			if ($result === false) {
				throw new Exception("Erreur lors de la récupération des matières");
			}

			return $result;
		} catch (Exception $e) {
			$this->errorService->logError("Erreur dans getAllMatieres: " . $e->getMessage(), 'database');
			throw $e;
		}
	}

	public function getMatiereById($id)
	{
		try {
			$conn = $this->db->getConnection();
			$stmt = $conn->prepare("SELECT * FROM MATIERE WHERE id = ?");
			$stmt->execute([$id]);
			$result = $stmt->fetch();

			if ($result === false) {
				throw new Exception("Matière non trouvée");
			}

			return $result;
		} catch (Exception $e) {
			$this->errorService->logError("Erreur dans getMatiereById: " . $e->getMessage(), 'database');
			throw $e;
		}
	}

	public function createMatiere($data)
	{
		try {
			$conn = $this->db->getConnection();
			$stmt = $conn->prepare("INSERT INTO MATIERE (nom, coefficient) VALUES (?, ?)");
			$result = $stmt->execute([$data['nom'], $data['coefficient']]);

			if (!$result) {
				throw new Exception("Erreur lors de la création de la matière");
			}

			return $conn->lastInsertId();
		} catch (Exception $e) {
			$this->errorService->logError("Erreur dans createMatiere: " . $e->getMessage(), 'database');
			throw $e;
		}
	}

	public function updateMatiere($id, $data)
	{
		try {
			$conn = $this->db->getConnection();
			$stmt = $conn->prepare("UPDATE MATIERE SET nom = ?, coefficient = ? WHERE id = ?");
			$result = $stmt->execute([$data['nom'], $data['coefficient'], $id]);

			if (!$result) {
				throw new Exception("Erreur lors de la mise à jour de la matière");
			}

			return true;
		} catch (Exception $e) {
			$this->errorService->logError("Erreur dans updateMatiere: " . $e->getMessage(), 'database');
			throw $e;
		}
	}

	public function deleteMatiere($id)
	{
		try {
			$conn = $this->db->getConnection();
			$stmt = $conn->prepare("DELETE FROM MATIERE WHERE id = ?");
			$result = $stmt->execute([$id]);

			if (!$result) {
				throw new Exception("Erreur lors de la suppression de la matière");
			}

			return true;
		} catch (Exception $e) {
			$this->errorService->logError("Erreur dans deleteMatiere: " . $e->getMessage(), 'database');
			throw $e;
		}
	}
}
