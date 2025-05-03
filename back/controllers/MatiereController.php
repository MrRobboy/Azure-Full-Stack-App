<?php
require_once __DIR__ . '/../models/Matiere.php';
require_once __DIR__ . '/../services/ErrorService.php';

class MatiereController
{
	private $matiere;
	private $errorService;

	public function __construct()
	{
		$this->matiere = new Matiere();
		$this->errorService = ErrorService::getInstance();
	}

	public function getAllMatieres()
	{
		try {
			$result = $this->matiere->getAll();
			if ($result === false) {
				throw new Exception("Erreur lors de la récupération des matières");
			}
			if (!is_array($result)) {
				$result = [];
			}
			return [
				'success' => true,
				'data' => $result
			];
		} catch (Exception $e) {
			$this->errorService->logError($e->getMessage(), 'matiere');
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	public function getMatiereById($id)
	{
		try {
			if (!is_numeric($id)) {
				throw new Exception("ID invalide");
			}
			$result = $this->matiere->getById($id);
			if ($result === false) {
				throw new Exception("Matière non trouvée");
			}
			if (!is_array($result)) {
				$result = [];
			}
			return [
				'success' => true,
				'data' => $result
			];
		} catch (Exception $e) {
			$this->errorService->logError($e->getMessage(), 'matiere');
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	public function createMatiere($data)
	{
		try {
			if (empty($data['nom'])) {
				throw new Exception("Le nom de la matière est requis");
			}
			$result = $this->matiere->create($data);
			if ($result === false) {
				throw new Exception("Erreur lors de la création de la matière");
			}
			return [
				'success' => true,
				'data' => $result,
				'message' => "Matière créée avec succès"
			];
		} catch (Exception $e) {
			$this->errorService->logError($e->getMessage(), 'matiere');
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	public function updateMatiere($id, $data)
	{
		try {
			if (!is_numeric($id)) {
				throw new Exception("ID invalide");
			}
			if (empty($data['nom'])) {
				throw new Exception("Le nom de la matière est requis");
			}
			$result = $this->matiere->update($id, $data);
			if ($result === false) {
				throw new Exception("Erreur lors de la mise à jour de la matière");
			}
			return [
				'success' => true,
				'data' => $result,
				'message' => "Matière mise à jour avec succès"
			];
		} catch (Exception $e) {
			$this->errorService->logError($e->getMessage(), 'matiere');
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	public function deleteMatiere($id)
	{
		try {
			if (!is_numeric($id)) {
				throw new Exception("ID invalide");
			}
			$result = $this->matiere->delete($id);
			if ($result === false) {
				throw new Exception("Erreur lors de la suppression de la matière");
			}
			return [
				'success' => true,
				'message' => "Matière supprimée avec succès"
			];
		} catch (Exception $e) {
			$this->errorService->logError($e->getMessage(), 'matiere');
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}
}
