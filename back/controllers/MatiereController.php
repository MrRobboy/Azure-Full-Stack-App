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
				return [
					'success' => false,
					'error' => "Erreur lors de la récupération des matières"
				];
			}
			return [
				'success' => true,
				'data' => $result
			];
		} catch (Exception $e) {
			$this->errorService->logError('MatiereController::getAllMatieres', $e->getMessage());
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
				return [
					'success' => false,
					'error' => "ID invalide"
				];
			}
			$result = $this->matiere->getById($id);
			if ($result === false) {
				return [
					'success' => false,
					'error' => "Matière non trouvée"
				];
			}
			return [
				'success' => true,
				'data' => $result
			];
		} catch (Exception $e) {
			$this->errorService->logError('MatiereController::getMatiereById', $e->getMessage());
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	public function createMatiere($data)
	{
		try {
			if (!isset($data['nom']) || empty($data['nom'])) {
				return [
					'success' => false,
					'error' => "Le nom de la matière est requis"
				];
			}
			$result = $this->matiere->createMatiere($data);
			if ($result === false) {
				return [
					'success' => false,
					'error' => "Erreur lors de la création de la matière"
				];
			}
			return [
				'success' => true,
				'data' => $result,
				'message' => "Matière créée avec succès"
			];
		} catch (Exception $e) {
			$this->errorService->logError('MatiereController::createMatiere', $e->getMessage());
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
				return [
					'success' => false,
					'error' => "ID invalide"
				];
			}
			if (!isset($data['nom']) || empty($data['nom'])) {
				return [
					'success' => false,
					'error' => "Le nom de la matière est requis"
				];
			}
			$result = $this->matiere->updateMatiere($id, $data);
			if ($result === false) {
				return [
					'success' => false,
					'error' => "Erreur lors de la mise à jour de la matière"
				];
			}
			return [
				'success' => true,
				'data' => $result,
				'message' => "Matière mise à jour avec succès"
			];
		} catch (Exception $e) {
			$this->errorService->logError('MatiereController::updateMatiere', $e->getMessage());
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
				return [
					'success' => false,
					'error' => "ID invalide"
				];
			}
			$result = $this->matiere->deleteMatiere($id);
			if ($result === false) {
				return [
					'success' => false,
					'error' => "Erreur lors de la suppression de la matière"
				];
			}
			return [
				'success' => true,
				'message' => "Matière supprimée avec succès"
			];
		} catch (Exception $e) {
			$this->errorService->logError('MatiereController::deleteMatiere', $e->getMessage());
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}
}
