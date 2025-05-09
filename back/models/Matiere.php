<?php
require_once __DIR__ . '/../services/DatabaseService.php';
require_once __DIR__ . '/../services/ErrorService.php';

class Matiere
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
			error_log("Tentative de récupération de toutes les matières");
			$stmt = $this->db->prepare("SELECT * FROM MATIERE ORDER BY nom");
			
			if (!$stmt->execute()) {
				error_log("Erreur lors de l'exécution de la requête");
				return [];
			}

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			error_log("Résultat de la requête: " . print_r($result, true));
			return $result;
		} catch (Exception $e) {
			error_log("Erreur dans getAll: " . $e->getMessage());
			$this->errorService->logError('Matiere::getAll', $e->getMessage());
			return [];
		}
	}

	public function getById($id)
	{
		try {
			error_log("Tentative de récupération de la matière avec l'ID: " . $id);
			$stmt = $this->db->prepare("SELECT * FROM MATIERE WHERE id_matiere = ?");
			
			if (!$stmt->execute([$id])) {
				error_log("Erreur lors de l'exécution de la requête");
				return false;
			}

			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			if (!$result) {
				error_log("Aucune matière trouvée avec l'ID: " . $id);
				return false;
			}

			error_log("Matière trouvée: " . print_r($result, true));
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
