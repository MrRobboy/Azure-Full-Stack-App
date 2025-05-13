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
			error_log("Tentative de création d'une matière avec les données: " . print_r($data, true));

			$stmt = $this->db->prepare("INSERT INTO MATIERE (nom) VALUES (?)");
			if (!$stmt) {
				error_log("Erreur de préparation de la requête: " . print_r($this->db->errorInfo(), true));
				throw new Exception("Erreur lors de la préparation de la requête");
			}

			$result = $stmt->execute([$data['nom']]);
			if (!$result) {
				error_log("Erreur d'exécution de la requête: " . print_r($stmt->errorInfo(), true));
				throw new Exception("Erreur lors de la création de la matière");
			}

			$id = $this->db->lastInsertId();
			error_log("Matière créée avec succès, ID: " . $id);
			return $id;
		} catch (Exception $e) {
			error_log("Erreur dans createMatiere: " . $e->getMessage());
			$this->errorService->logError("Erreur dans createMatiere: " . $e->getMessage(), 'database');
			throw $e;
		}
	}

	public function updateMatiere($id, $data)
	{
		try {
			error_log("Tentative de mise à jour de la matière ID: " . $id . " avec les données: " . print_r($data, true));

			$stmt = $this->db->prepare("UPDATE MATIERE SET nom = ? WHERE id_matiere = ?");
			if (!$stmt) {
				error_log("Erreur de préparation de la requête: " . print_r($this->db->errorInfo(), true));
				throw new Exception("Erreur lors de la préparation de la requête");
			}

			$result = $stmt->execute([$data['nom'], $id]);
			if (!$result) {
				error_log("Erreur d'exécution de la requête: " . print_r($stmt->errorInfo(), true));
				throw new Exception("Erreur lors de la mise à jour de la matière");
			}

			error_log("Matière mise à jour avec succès");
			return true;
		} catch (Exception $e) {
			error_log("Erreur dans updateMatiere: " . $e->getMessage());
			$this->errorService->logError("Erreur dans updateMatiere: " . $e->getMessage(), 'database');
			throw $e;
		}
	}

	public function deleteMatiere($id)
	{
		try {
			error_log("Tentative de suppression de la matière ID: " . $id);

			// Vérifier si la matière existe
			$matiere = $this->getById($id);
			if (!$matiere) {
				error_log("Matière non trouvée avec l'ID: " . $id);
				throw new Exception("La matière n'existe pas");
			}

			// Vérifier si la matière est utilisée dans d'autres tables
			$stmt = $this->db->prepare("
				SELECT 
					(SELECT COUNT(*) FROM EXAM WHERE matiere = ?) as exam_count,
					(SELECT COUNT(*) FROM PROF WHERE matiere = ?) as prof_count
			");
			if (!$stmt->execute([$id, $id])) {
				throw new Exception("Erreur lors de la vérification des références");
			}
			$counts = $stmt->fetch(PDO::FETCH_ASSOC);

			if ($counts['exam_count'] > 0) {
				throw new Exception("Impossible de supprimer cette matière car elle est utilisée dans des examens");
			}
			if ($counts['prof_count'] > 0) {
				throw new Exception("Impossible de supprimer cette matière car elle est assignée à des professeurs");
			}

			$stmt = $this->db->prepare("DELETE FROM MATIERE WHERE id_matiere = ?");
			if (!$stmt) {
				error_log("Erreur de préparation de la requête: " . print_r($this->db->errorInfo(), true));
				throw new Exception("Erreur lors de la préparation de la requête");
			}

			$result = $stmt->execute([$id]);
			if (!$result) {
				error_log("Erreur d'exécution de la requête: " . print_r($stmt->errorInfo(), true));
				throw new Exception("Erreur lors de la suppression de la matière");
			}

			error_log("Matière supprimée avec succès");
			return true;
		} catch (Exception $e) {
			error_log("Erreur dans deleteMatiere: " . $e->getMessage());
			$this->errorService->logError("Erreur dans deleteMatiere: " . $e->getMessage(), 'database');
			throw $e;
		}
	}
}
