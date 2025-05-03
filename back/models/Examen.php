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
			error_log("Tentative de récupération de tous les examens");
			$sql = "SELECT e.*, m.nom as nom_matiere, c.nom_classe 
					FROM examens e 
					JOIN matieres m ON e.matiere = m.id_matiere 
					JOIN classes c ON e.classe = c.id_classe 
					ORDER BY e.id_exam DESC";

			error_log("Requête SQL: " . $sql);
			$stmt = $this->db->prepare($sql);

			if ($stmt === false) {
				error_log("Erreur de préparation de la requête: " . print_r($this->db->errorInfo(), true));
				throw new Exception("Erreur lors de la préparation de la requête");
			}

			if (!$stmt->execute()) {
				error_log("Erreur d'exécution de la requête: " . print_r($stmt->errorInfo(), true));
				throw new Exception("Erreur lors de l'exécution de la requête");
			}

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			error_log("Résultat de la requête: " . print_r($result, true));
			return $result;
		} catch (Exception $e) {
			error_log("Erreur dans getAll: " . $e->getMessage());
			return false;
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
			error_log("Tentative de création d'un examen");
			error_log("Données: titre=$titre, matiere=$matiere, classe=$classe");

			$sql = "INSERT INTO examens (titre, matiere, classe) VALUES (?, ?, ?)";
			error_log("Requête SQL: " . $sql);

			$stmt = $this->db->prepare($sql);

			if ($stmt === false) {
				error_log("Erreur de préparation de la requête: " . print_r($this->db->errorInfo(), true));
				throw new Exception("Erreur lors de la préparation de la requête");
			}

			if (!$stmt->execute([$titre, $matiere, $classe])) {
				error_log("Erreur d'exécution de la requête: " . print_r($stmt->errorInfo(), true));
				throw new Exception("Erreur lors de l'exécution de la requête");
			}

			$id = $this->db->lastInsertId();
			error_log("ID de l'examen créé: " . $id);

			return [
				'id_exam' => $id,
				'titre' => $titre,
				'matiere' => $matiere,
				'classe' => $classe
			];
		} catch (Exception $e) {
			error_log("Erreur dans create: " . $e->getMessage());
			return false;
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
