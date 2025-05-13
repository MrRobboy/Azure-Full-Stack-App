<?php
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/Matiere.php';
require_once __DIR__ . '/../models/Examen.php';
require_once __DIR__ . '/../models/UserPrivilege.php';
require_once __DIR__ . '/../services/ErrorService.php';

class NoteController
{
	private $noteModel;
	private $eleveModel;
	private $matiereModel;
	private $examenModel;
	private $userPrivilegeModel;
	private $errorService;

	public function __construct()
	{
		$this->noteModel = new Note();
		$this->eleveModel = new Eleve();
		$this->matiereModel = new Matiere();
		$this->examenModel = new Examen();
		$this->userPrivilegeModel = new UserPrivilege();
		$this->errorService = ErrorService::getInstance();
	}

	private function checkNotePrivilege($id_eleve, $valeur)
	{
		$min_note = $this->userPrivilegeModel->getMinNoteForUser($id_eleve);
		if ($min_note !== null && $valeur < $min_note) {
			throw new Exception("Cet étudiant ne peut pas avoir une note inférieure à " . $min_note);
		}
		return true;
	}

	public function getAllNotes()
	{
		try {
			$result = $this->noteModel->getAll();
			if ($result === false) {
				throw new Exception("Erreur lors de la récupération des notes");
			}
			if (!is_array($result)) {
				$result = [];
			}
			return [
				'success' => true,
				'data' => $result
			];
		} catch (Exception $e) {
			$this->errorService->logError('NoteController::getAllNotes', $e->getMessage());
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	public function getNoteById($id)
	{
		try {
			if (!is_numeric($id)) {
				throw new Exception("ID invalide");
			}
			$result = $this->noteModel->getById($id);
			if ($result === false) {
				throw new Exception("Note non trouvée");
			}
			return [
				'success' => true,
				'data' => $result
			];
		} catch (Exception $e) {
			$this->errorService->logError('NoteController::getNoteById', $e->getMessage());
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	public function getNotesByEleve($id_eleve)
	{
		try {
			if (!is_numeric($id_eleve)) {
				throw new Exception("ID élève invalide");
			}
			$result = $this->noteModel->getByEleve($id_eleve);
			if ($result === false) {
				throw new Exception("Erreur lors de la récupération des notes de l'élève");
			}
			if (!is_array($result)) {
				$result = [];
			}
			return [
				'success' => true,
				'data' => $result
			];
		} catch (Exception $e) {
			$this->errorService->logError('NoteController::getNotesByEleve', $e->getMessage());
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	public function getNotesByExamen($id_examen)
	{
		try {
			error_log("NoteController::getNotesByExamen - Début avec id_examen: " . $id_examen);

			if (!is_numeric($id_examen)) {
				error_log("NoteController::getNotesByExamen - ID examen invalide: " . $id_examen);
				throw new Exception("ID examen invalide");
			}

			error_log("NoteController::getNotesByExamen - Appel du modèle");
			$result = $this->noteModel->getByExamen($id_examen);
			error_log("NoteController::getNotesByExamen - Résultat du modèle: " . print_r($result, true));

			if ($result === false) {
				error_log("NoteController::getNotesByExamen - Erreur lors de la récupération des notes");
				throw new Exception("Erreur lors de la récupération des notes de l'examen");
			}

			if (!is_array($result)) {
				error_log("NoteController::getNotesByExamen - Le résultat n'est pas un tableau, conversion en tableau vide");
				$result = [];
			}

			error_log("NoteController::getNotesByExamen - Retour du résultat final: " . print_r($result, true));
			return [
				'success' => true,
				'data' => $result
			];
		} catch (Exception $e) {
			error_log("NoteController::getNotesByExamen - Exception: " . $e->getMessage());
			$this->errorService->logError('NoteController::getNotesByExamen', $e->getMessage());
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	public function createNote($id_eleve, $id_matiere, $id_examen, $valeur)
	{
		try {
			// Vérification de l'existence des entités
			if (!$this->eleveModel->getById($id_eleve)) {
				throw new Exception("L'élève n'existe pas");
			}
			if (!$this->matiereModel->getById($id_matiere)) {
				throw new Exception("La matière n'existe pas");
			}
			if (!$this->examenModel->getById($id_examen)) {
				throw new Exception("L'examen n'existe pas");
			}

			// Vérification de la validité de la note
			if (!is_numeric($valeur) || $valeur < 0 || $valeur > 20) {
				throw new Exception("La note doit être un nombre compris entre 0 et 20");
			}

			// Vérification des privilèges
			$this->checkNotePrivilege($id_eleve, $valeur);

			$result = $this->noteModel->create($id_eleve, $id_matiere, $id_examen, $valeur);
			if ($result === false) {
				throw new Exception("Erreur lors de la création de la note");
			}

			return [
				'success' => true,
				'data' => $result,
				'message' => 'Note créée avec succès'
			];
		} catch (Exception $e) {
			$this->errorService->logError('NoteController::createNote', $e->getMessage());
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	public function updateNote($id, $valeur)
	{
		try {
			// Vérification de l'existence de la note
			$note = $this->noteModel->getById($id);
			if (!$note) {
				throw new Exception("La note n'existe pas");
			}

			// Vérification de la validité de la note
			if (!is_numeric($valeur) || $valeur < 0 || $valeur > 20) {
				throw new Exception("La note doit être un nombre compris entre 0 et 20");
			}

			// Vérification des privilèges
			$this->checkNotePrivilege($note['id_eleve'], $valeur);

			$result = $this->noteModel->update($id, $valeur);

			if ($result === false) {
				throw new Exception("Erreur lors de la mise à jour de la note");
			}

			return [
				'success' => true,
				'data' => $result,
				'message' => 'Note mise à jour avec succès'
			];
		} catch (Exception $e) {
			$this->errorService->logError('NoteController::updateNote', $e->getMessage());
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	public function deleteNote($id)
	{
		try {
			if (!is_numeric($id)) {
				throw new Exception("ID invalide");
			}

			if (!$this->noteModel->getById($id)) {
				throw new Exception("La note n'existe pas");
			}

			$result = $this->noteModel->delete($id);
			if ($result === false) {
				throw new Exception("Erreur lors de la suppression de la note");
			}

			return [
				'success' => true,
				'message' => 'Note supprimée avec succès'
			];
		} catch (Exception $e) {
			$this->errorService->logError('NoteController::deleteNote', $e->getMessage());
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}
}
