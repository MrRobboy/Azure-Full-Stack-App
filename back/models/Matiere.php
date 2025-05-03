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
			$stmt = $this->db->prepare("SELECT * FROM MATIERE ORDER BY nom ASC");

			if (!$stmt->execute()) {
				throw new Exception("Erreur lors de l'exécution de la requête");
			}

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if ($result === false) {
				return [];
			}

			return $result;
		} catch (Exception $e) {
			$this->errorService->logError('Matiere::getAll', $e->getMessage());
			return [];
		}
	}

	public function getById($id)
	{
		try {
			$stmt = $this->db->prepare("SELECT * FROM MATIERE WHERE id_matiere = ?");
			if ($stmt === false) {
				throw new Exception("Erreur lors de la préparation de la requête SQL");
			}
			$stmt->execute([$id]);
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			if (!is_array($result)) {
				$result = [];
			}
			return $result;
		} catch (Exception $e) {
			$this->errorService->logError($e->getMessage(), 'matiere');
			return [];
		}
	}

	public function create($data)
	{
		try {
			$stmt = $this->db->prepare("INSERT INTO MATIERE (nom) VALUES (?)");
			if ($stmt === false) {
				throw new Exception("Erreur lors de la préparation de la requête SQL");
			}
			$result = $stmt->execute([$data['nom']]);
			if ($result === false) {
				throw new Exception("Erreur lors de l'exécution de la requête SQL");
			}
			$id = $this->db->lastInsertId();
			return [
				'id_matiere' => $id,
				'nom' => $data['nom']
			];
		} catch (Exception $e) {
			$this->errorService->logError($e->getMessage(), 'matiere');
			return [];
		}
	}

	public function update($id, $data)
	{
		try {
			$stmt = $this->db->prepare("UPDATE MATIERE SET nom = ? WHERE id_matiere = ?");
			if ($stmt === false) {
				throw new Exception("Erreur lors de la préparation de la requête SQL");
			}
			$result = $stmt->execute([$data['nom'], $id]);
			if ($result === false) {
				throw new Exception("Erreur lors de l'exécution de la requête SQL");
			}
			return [
				'id_matiere' => $id,
				'nom' => $data['nom']
			];
		} catch (Exception $e) {
			$this->errorService->logError($e->getMessage(), 'matiere');
			return [];
		}
	}

	public function delete($id)
	{
		try {
			$stmt = $this->db->prepare("DELETE FROM MATIERE WHERE id_matiere = ?");
			if ($stmt === false) {
				throw new Exception("Erreur lors de la préparation de la requête SQL");
			}
			$result = $stmt->execute([$id]);
			if ($result === false) {
				throw new Exception("Erreur lors de l'exécution de la requête SQL");
			}
			return true;
		} catch (Exception $e) {
			$this->errorService->logError($e->getMessage(), 'matiere');
			return false;
		}
	}
}
