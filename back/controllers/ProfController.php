<?php
require_once __DIR__ . '/../models/Prof.php';
require_once __DIR__ . '/../services/ErrorService.php';

class ProfController
{
	private $profModel;
	private $errorService;

	public function __construct()
	{
		$this->profModel = new Prof();
		$this->errorService = ErrorService::getInstance();
	}

	public function getAllProfs()
	{
		try {
			$profs = $this->profModel->getAll();
			return [
				'success' => true,
				'data' => $profs
			];
		} catch (Exception $e) {
			$this->errorService->logError('prof', $e->getMessage());
			throw new Exception($e->getMessage());
		}
	}

	public function getProfById($id)
	{
		try {
			$prof = $this->profModel->getById($id);
			if (!$prof) {
				throw new Exception("Professeur non trouvé");
			}
			return [
				'success' => true,
				'data' => $prof
			];
		} catch (Exception $e) {
			$this->errorService->logError('prof', $e->getMessage());
			throw new Exception($e->getMessage());
		}
	}

	public function createProf($data)
	{
		try {
			if (!isset($data['nom']) || !isset($data['prenom']) || !isset($data['email']) || !isset($data['password'])) {
				throw new Exception("Tous les champs sont requis");
			}

			// Vérifier si l'email existe déjà
			if ($this->profModel->getByEmail($data['email'])) {
				throw new Exception("Cet email est déjà utilisé");
			}

			$id = $this->profModel->create([
				'nom' => $data['nom'],
				'prenom' => $data['prenom'],
				'email' => $data['email'],
				'password' => password_hash($data['password'], PASSWORD_DEFAULT)
			]);

			return [
				'success' => true,
				'message' => 'Professeur créé avec succès',
				'id' => $id
			];
		} catch (Exception $e) {
			$this->errorService->logError('prof', $e->getMessage());
			throw new Exception($e->getMessage());
		}
	}

	public function updateProf($id, $data)
	{
		try {
			if (!$this->profModel->getById($id)) {
				throw new Exception("Professeur non trouvé");
			}

			$updateData = [];
			if (isset($data['nom'])) $updateData['nom'] = $data['nom'];
			if (isset($data['prenom'])) $updateData['prenom'] = $data['prenom'];
			if (isset($data['email'])) $updateData['email'] = $data['email'];
			if (isset($data['password'])) $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

			$this->profModel->update($id, $updateData);

			return [
				'success' => true,
				'message' => 'Professeur mis à jour avec succès'
			];
		} catch (Exception $e) {
			$this->errorService->logError('prof', $e->getMessage());
			throw new Exception($e->getMessage());
		}
	}

	public function deleteProf($id)
	{
		try {
			if (!$this->profModel->getById($id)) {
				throw new Exception("Professeur non trouvé");
			}

			$this->profModel->delete($id);

			return [
				'success' => true,
				'message' => 'Professeur supprimé avec succès'
			];
		} catch (Exception $e) {
			$this->errorService->logError('prof', $e->getMessage());
			throw new Exception($e->getMessage());
		}
	}
}
