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
			$result = $this->matiere->getAllMatieres();
			if (!is_array($result)) {
				throw new Exception("Format de réponse invalide");
			}
			return $result;
		} catch (Exception $e) {
			$this->errorService->logError("Erreur dans getAllMatieres: " . $e->getMessage(), 'controller');
			throw $e;
		}
	}

	public function getMatiereById($id)
	{
		try {
			if (!is_numeric($id)) {
				throw new Exception("ID invalide");
			}
			return $this->matiere->getMatiereById($id);
		} catch (Exception $e) {
			$this->errorService->logError("Erreur dans getMatiereById: " . $e->getMessage(), 'controller');
			throw $e;
		}
	}

	public function createMatiere($data)
	{
		try {
			if (!isset($data['nom']) || empty($data['nom'])) {
				throw new Exception("Le nom de la matière est requis");
			}
			if (!isset($data['coefficient']) || !is_numeric($data['coefficient'])) {
				throw new Exception("Le coefficient est requis et doit être un nombre");
			}
			return $this->matiere->createMatiere($data);
		} catch (Exception $e) {
			$this->errorService->logError("Erreur dans createMatiere: " . $e->getMessage(), 'controller');
			throw $e;
		}
	}

	public function updateMatiere($id, $data)
	{
		try {
			if (!is_numeric($id)) {
				throw new Exception("ID invalide");
			}
			if (!isset($data['nom']) || empty($data['nom'])) {
				throw new Exception("Le nom de la matière est requis");
			}
			if (!isset($data['coefficient']) || !is_numeric($data['coefficient'])) {
				throw new Exception("Le coefficient est requis et doit être un nombre");
			}
			return $this->matiere->updateMatiere($id, $data);
		} catch (Exception $e) {
			$this->errorService->logError("Erreur dans updateMatiere: " . $e->getMessage(), 'controller');
			throw $e;
		}
	}

	public function deleteMatiere($id)
	{
		try {
			if (!is_numeric($id)) {
				throw new Exception("ID invalide");
			}
			return $this->matiere->deleteMatiere($id);
		} catch (Exception $e) {
			$this->errorService->logError("Erreur dans deleteMatiere: " . $e->getMessage(), 'controller');
			throw $e;
		}
	}
}
