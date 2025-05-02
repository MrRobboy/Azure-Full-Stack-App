<?php
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/Matiere.php';
require_once __DIR__ . '/../models/Examen.php';

class NoteController
{
	private $noteModel;
	private $eleveModel;
	private $matiereModel;
	private $examenModel;

	public function __construct()
	{
		$this->noteModel = new Note();
		$this->eleveModel = new Eleve();
		$this->matiereModel = new Matiere();
		$this->examenModel = new Examen();
	}

	public function getAllNotes()
	{
		return $this->noteModel->getAll();
	}

	public function getNoteById($id)
	{
		return $this->noteModel->getById($id);
	}

	public function getNotesByEleve($id_eleve)
	{
		return $this->noteModel->getByEleve($id_eleve);
	}

	public function createNote($id_eleve, $id_matiere, $id_examen, $valeur)
	{
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
		if ($valeur < 0 || $valeur > 20) {
			throw new Exception("La note doit être comprise entre 0 et 20");
		}

		return $this->noteModel->create($id_eleve, $id_matiere, $id_examen, $valeur);
	}

	public function updateNote($id, $id_eleve, $id_matiere, $id_examen, $valeur)
	{
		// Vérification de l'existence de la note
		if (!$this->noteModel->getById($id)) {
			throw new Exception("La note n'existe pas");
		}

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
		if ($valeur < 0 || $valeur > 20) {
			throw new Exception("La note doit être comprise entre 0 et 20");
		}

		return $this->noteModel->update($id, $id_eleve, $id_matiere, $id_examen, $valeur);
	}

	public function deleteNote($id)
	{
		if (!$this->noteModel->getById($id)) {
			throw new Exception("La note n'existe pas");
		}
		return $this->noteModel->delete($id);
	}
}
