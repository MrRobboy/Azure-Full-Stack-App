<?php
require_once __DIR__ . '/../models/Matiere.php';

class MatiereController
{
	private $matiereModel;

	public function __construct()
	{
		$this->matiereModel = new Matiere();
	}

	public function getAllMatieres()
	{
		return $this->matiereModel->getAll();
	}

	public function getMatiereById($id)
	{
		return $this->matiereModel->getById($id);
	}

	public function createMatiere($nom)
	{
		// Vérification de la validité du nom
		if (empty($nom)) {
			throw new Exception("Le nom de la matière ne peut pas être vide");
		}

		// Vérification de la longueur du nom
		if (strlen($nom) > 255) {
			throw new Exception("Le nom de la matière est trop long");
		}

		return $this->matiereModel->create($nom);
	}

	public function updateMatiere($id, $nom)
	{
		// Vérification de l'existence de la matière
		if (!$this->matiereModel->getById($id)) {
			throw new Exception("La matière n'existe pas");
		}

		// Vérification de la validité du nom
		if (empty($nom)) {
			throw new Exception("Le nom de la matière ne peut pas être vide");
		}

		// Vérification de la longueur du nom
		if (strlen($nom) > 255) {
			throw new Exception("Le nom de la matière est trop long");
		}

		return $this->matiereModel->update($id, $nom);
	}

	public function deleteMatiere($id)
	{
		if (!$this->matiereModel->getById($id)) {
			throw new Exception("La matière n'existe pas");
		}
		return $this->matiereModel->delete($id);
	}
}
