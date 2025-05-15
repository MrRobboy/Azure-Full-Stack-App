<?php
require_once __DIR__ . '/../services/DatabaseService.php';
require_once __DIR__ . '/../services/ErrorService.php';

class UserPrivilege
{
	private $db;
	private $errorService;

	public function __construct()
	{
		$this->db = DatabaseService::getInstance()->getConnection();
		$this->errorService = ErrorService::getInstance();
	}

	public function getMinNoteForUser($id_user)
	{
		try {
			$stmt = $this->db->prepare("
				SELECT min_note
				FROM USER_PRIVILEGES
				WHERE id_user = ?
			");

			if (!$stmt->execute([$id_user])) {
				throw new Exception("Erreur lors de la récupération du privilège");
			}

			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			return $result ? $result['min_note'] : null;
		} catch (Exception $e) {
			$this->errorService->logError('UserPrivilege::getMinNoteForUser', $e->getMessage());
			return null;
		}
	}

	public function addPrivilege($id_user, $min_note = 18.00)
	{
		try {
			$stmt = $this->db->prepare("
				INSERT INTO USER_PRIVILEGES (id_user, min_note)
				VALUES (?, ?)
				ON DUPLICATE KEY UPDATE min_note = ?
			");

			if (!$stmt->execute([$id_user, $min_note, $min_note])) {
				throw new Exception("Erreur lors de l'ajout du privilège");
			}

			return true;
		} catch (Exception $e) {
			$this->errorService->logError('UserPrivilege::addPrivilege', $e->getMessage());
			return false;
		}
	}

	public function removePrivilege($id_user)
	{
		try {
			$stmt = $this->db->prepare("
				DELETE FROM USER_PRIVILEGES
				WHERE id_user = ?
			");

			if (!$stmt->execute([$id_user])) {
				throw new Exception("Erreur lors de la suppression du privilège");
			}

			return true;
		} catch (Exception $e) {
			$this->errorService->logError('UserPrivilege::removePrivilege', $e->getMessage());
			return false;
		}
	}

	public function getAllPrivileges()
	{
		try {
			$stmt = $this->db->prepare("
				SELECT up.*, u.nom, u.prenom
				FROM USER_PRIVILEGES up
				JOIN USER u ON up.id_user = u.id_user
				ORDER BY u.nom ASC, u.prenom ASC
			");

			if (!$stmt->execute()) {
				throw new Exception("Erreur lors de la récupération des privilèges");
			}

			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (Exception $e) {
			$this->errorService->logError('UserPrivilege::getAllPrivileges', $e->getMessage());
			return [];
		}
	}
}
