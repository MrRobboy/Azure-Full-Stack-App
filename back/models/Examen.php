<?php
require_once __DIR__ . '/../services/DatabaseService.php';
require_once __DIR__ . '/../services/ErrorService.php';

class Examen
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
				SELECT e.*, m.nom_matiere, c.nom_classe 
				FROM EXAMEN e
				LEFT JOIN MATIERE m ON e.id_matiere = m.id_matiere
				LEFT JOIN CLASSE c ON e.id_classe = c.id_classe
				ORDER BY e.date_examen DESC
			");

			if (!$stmt->execute()) {
				throw new Exception("Erreur lors de l'exécution de la requête");
			}

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if ($result === false) {
				return [];
			}

			return $result;
		} catch (Exception $e) {
			$this->errorService->logError('Examen::getAll', $e->getMessage());
			return [];
		}
	}

	public function getById($id)
	{
		try {
			$stmt = $this->db->prepare("
				SELECT e.*, m.nom_matiere, c.nom_classe 
				FROM EXAMEN e
				LEFT JOIN MATIERE m ON e.id_matiere = m.id_matiere
				LEFT JOIN CLASSE c ON e.id_classe = c.id_classe
				WHERE e.id_examen = ?
			");

			if (!$stmt->execute([$id])) {
				throw new Exception("Erreur lors de l'exécution de la requête");
			}

			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			if ($result === false) {
				return null;
			}

			return $result;
		} catch (Exception $e) {
			$this->errorService->logError('Examen::getById', $e->getMessage());
			return null;
		}
	}

	public function create($titre, $matiere, $classe)
	{
		try {
			error_log("Tentative de création d'un examen avec les données suivantes:");
			error_log("Titre: " . $titre);
			error_log("Matière: " . $matiere);
			error_log("Classe: " . $classe);

			$stmt = $this->db->prepare("
				INSERT INTO EXAMEN (titre, id_matiere, id_classe) 
				VALUES (?, ?, ?)
			");

			if (!$stmt) {
				$error = $this->db->errorInfo();
				error_log("Erreur de préparation de la requête: " . implode(", ", $error));
				throw new Exception("Erreur de préparation de la requête: " . $error[2]);
			}

			if (!$stmt->execute([$titre, $matiere, $classe])) {
				$error = $stmt->errorInfo();
				error_log("Erreur lors de l'insertion de l'examen: " . implode(", ", $error));
				throw new Exception("Erreur lors de l'insertion de l'examen: " . $error[2]);
			}

			$id = $this->db->lastInsertId();
			error_log("Examen créé avec succès, ID: " . $id);
			return $this->getById($id);
		} catch (Exception $e) {
			$this->errorService->logError('Examen::create', $e->getMessage());
			throw $e;
		}
	}

	public function update($id, $titre, $matiere, $classe)
	{
		try {
			$stmt = $this->db->prepare("
				UPDATE EXAMEN 
				SET titre = ?, id_matiere = ?, id_classe = ?
				WHERE id_examen = ?
			");

			if (!$stmt) {
				throw new Exception("Erreur de préparation de la requête");
			}

			if (!$stmt->execute([$titre, $matiere, $classe, $id])) {
				throw new Exception("Erreur lors de la mise à jour de l'examen: " . implode(", ", $stmt->errorInfo()));
			}

			return $this->getById($id);
		} catch (Exception $e) {
			$this->errorService->logError('Examen::update', $e->getMessage());
			return false;
		}
	}

	public function delete($id)
	{
		try {
			$stmt = $this->db->prepare("DELETE FROM EXAMEN WHERE id_examen = ?");

			if (!$stmt) {
				throw new Exception("Erreur de préparation de la requête");
			}

			if (!$stmt->execute([$id])) {
				throw new Exception("Erreur lors de la suppression de l'examen: " . implode(", ", $stmt->errorInfo()));
			}

			return true;
		} catch (Exception $e) {
			$this->errorService->logError('Examen::delete', $e->getMessage());
			return false;
		}
	}
}
