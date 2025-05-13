<?php
require_once __DIR__ . '/../models/UserPrivilege.php';
require_once __DIR__ . '/../services/ErrorService.php';

class UserPrivilegeController
{
	private $userPrivilegeModel;
	private $errorService;

	public function __construct()
	{
		$this->userPrivilegeModel = new UserPrivilege();
		$this->errorService = ErrorService::getInstance();
	}

	public function getAllPrivileges()
	{
		try {
			$result = $this->userPrivilegeModel->getAllPrivileges();
			return [
				'success' => true,
				'data' => $result
			];
		} catch (Exception $e) {
			$this->errorService->logError('UserPrivilegeController::getAllPrivileges', $e->getMessage());
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	public function addPrivilege($id_user, $min_note = 18.00)
	{
		try {
			if (!is_numeric($id_user)) {
				throw new Exception("ID utilisateur invalide");
			}

			if (!is_numeric($min_note) || $min_note < 0 || $min_note > 20) {
				throw new Exception("La note minimale doit être un nombre compris entre 0 et 20");
			}

			$result = $this->userPrivilegeModel->addPrivilege($id_user, $min_note);
			if (!$result) {
				throw new Exception("Erreur lors de l'ajout du privilège");
			}

			return [
				'success' => true,
				'message' => 'Privilège ajouté avec succès'
			];
		} catch (Exception $e) {
			$this->errorService->logError('UserPrivilegeController::addPrivilege', $e->getMessage());
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	public function removePrivilege($id_user)
	{
		try {
			if (!is_numeric($id_user)) {
				throw new Exception("ID utilisateur invalide");
			}

			$result = $this->userPrivilegeModel->removePrivilege($id_user);
			if (!$result) {
				throw new Exception("Erreur lors de la suppression du privilège");
			}

			return [
				'success' => true,
				'message' => 'Privilège supprimé avec succès'
			];
		} catch (Exception $e) {
			$this->errorService->logError('UserPrivilegeController::removePrivilege', $e->getMessage());
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}
}
