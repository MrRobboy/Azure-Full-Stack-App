<?php
require_once __DIR__ . '/../models/Examen.php';
require_once __DIR__ . '/../models/Matiere.php';
require_once __DIR__ . '/../models/Classe.php';

class ExamenController
{
	private $examenModel;
	private $matiereModel;
	private $classeModel;

	public function __construct()
	{
		$this->examenModel = new Examen();
		$this->matiereModel = new Matiere();
		$this->classeModel = new Classe();
	}

	public function getAllExamens()
	{
		return $this->examenModel->getAll();
	}

	public function getExamenById($id)
	{
		return $this->examenModel->getById($id);
	}

	public function createExamen($nom_examen, $date_examen, $coefficient)
	{
		// Vérification de la validité des données
		if (empty($nom_examen) || empty($date_examen) || empty($coefficient)) {
			throw new Exception("Tous les champs sont obligatoires");
		}

		// Vérification de la longueur du nom
		if (strlen($nom_examen) > 255) {
			throw new Exception("Le nom de l'examen est trop long");
		}

		// Vérification du format de la date
		if (!strtotime($date_examen)) {
			throw new Exception("Format de date invalide");
		}

		// Vérification du coefficient
		if ($coefficient <= 0) {
			throw new Exception("Le coefficient doit être positif");
		}

		return $this->examenModel->create($nom_examen, $date_examen, $coefficient);
	}

	public function updateExamen($id, $nom_examen, $date_examen, $coefficient)
	{
		// Vérification de l'existence de l'examen
		if (!$this->examenModel->getById($id)) {
			throw new Exception("L'examen n'existe pas");
		}

		// Vérification de la validité des données
		if (empty($nom_examen) || empty($date_examen) || empty($coefficient)) {
			throw new Exception("Tous les champs sont obligatoires");
		}

		// Vérification de la longueur du nom
		if (strlen($nom_examen) > 255) {
			throw new Exception("Le nom de l'examen est trop long");
		}

		// Vérification du format de la date
		if (!strtotime($date_examen)) {
			throw new Exception("Format de date invalide");
		}

		// Vérification du coefficient
		if ($coefficient <= 0) {
			throw new Exception("Le coefficient doit être positif");
		}

		return $this->examenModel->update($id, $nom_examen, $date_examen, $coefficient);
	}

	public function deleteExamen($id)
	{
		// Vérification de l'existence de l'examen
		if (!$this->examenModel->getById($id)) {
			throw new Exception("L'examen n'existe pas");
		}

		return $this->examenModel->delete($id);
	}
}
