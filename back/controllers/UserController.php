<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/ErrorService.php';

class UserController
{
	private $userModel;
	private $errorService;

	public function __construct()
	{
		$this->userModel = new User();
		$this->errorService = ErrorService::getInstance();
	}

	public function getAllUsers()
	{
		try {
			$users = $this->userModel->getAll();
			return [
				'success' => true,
				'data' => $users
			];
		} catch (Exception $e) {
			$this->errorService->logError('user', $e->getMessage());
			throw new Exception($e->getMessage());
		}
	}

	public function getUserById($id)
	{
		try {
			$user = $this->userModel->getById($id);
			if (!$user) {
				throw new Exception("Utilisateur non trouvé");
			}
			return [
				'success' => true,
				'data' => $user
			];
		} catch (Exception $e) {
			$this->errorService->logError('user', $e->getMessage());
			throw new Exception($e->getMessage());
		}
	}

	public function createUser($data)
	{
		try {
			// Vérification des champs requis
			$requiredFields = ['nom', 'prenom', 'email', 'password', 'classe'];
			foreach ($requiredFields as $field) {
				if (!isset($data[$field]) || empty($data[$field])) {
					throw new Exception("Le champ '$field' est requis");
				}
			}

			// Validation de l'email
			if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
				throw new Exception("L'adresse email n'est pas valide");
			}

			// Vérifier si l'email existe déjà
			if ($this->userModel->getByEmail($data['email'])) {
				throw new Exception("Cet email est déjà utilisé");
			}

			// Création de l'utilisateur
			$id = $this->userModel->create([
				'nom' => $data['nom'],
				'prenom' => $data['prenom'],
				'email' => $data['email'],
				'password' => password_hash($data['password'], PASSWORD_DEFAULT),
				'classe' => $data['classe']
			]);

			return [
				'success' => true,
				'message' => 'Utilisateur créé avec succès',
				'id' => $id
			];
		} catch (Exception $e) {
			$this->errorService->logError('user', $e->getMessage());
			throw new Exception($e->getMessage());
		}
	}

	public function updateUser($id, $data)
	{
		try {
			if (!$this->userModel->getById($id)) {
				throw new Exception("Utilisateur non trouvé");
			}

			// Validation des données
			if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
				throw new Exception("L'adresse email n'est pas valide");
			}

			// Vérifier si l'email existe déjà (sauf pour l'utilisateur actuel)
			if (isset($data['email'])) {
				$existingUser = $this->userModel->getByEmail($data['email']);
				if ($existingUser && $existingUser['id_user'] != $id) {
					throw new Exception("Cet email est déjà utilisé");
				}
			}

			$updateData = [];
			if (isset($data['nom'])) $updateData['nom'] = $data['nom'];
			if (isset($data['prenom'])) $updateData['prenom'] = $data['prenom'];
			if (isset($data['email'])) $updateData['email'] = $data['email'];
			if (isset($data['password'])) $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
			if (isset($data['classe'])) $updateData['classe'] = $data['classe'];

			$this->userModel->update($id, $updateData);

			return [
				'success' => true,
				'message' => 'Utilisateur mis à jour avec succès'
			];
		} catch (Exception $e) {
			$this->errorService->logError('user', $e->getMessage());
			throw new Exception($e->getMessage());
		}
	}

	public function deleteUser($id)
	{
		try {
			if (!$this->userModel->getById($id)) {
				throw new Exception("Utilisateur non trouvé");
			}

			$this->userModel->delete($id);

			return [
				'success' => true,
				'message' => 'Utilisateur supprimé avec succès'
			];
		} catch (Exception $e) {
			$this->errorService->logError('user', $e->getMessage());
			throw new Exception($e->getMessage());
		}
	}
}
