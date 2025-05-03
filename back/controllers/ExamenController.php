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
		try {
			$result = $this->examenModel->getAll();
			if ($result === false) {
				throw new Exception("Erreur lors de la récupération des examens");
			}
			if (!is_array($result)) {
				$result = [];
			}
			return [
				'success' => true,
				'data' => $result
			];
		} catch (Exception $e) {
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	public function getExamenById($id)
	{
		try {
			if (!is_numeric($id)) {
				throw new Exception("ID invalide");
			}
			$result = $this->examenModel->getById($id);
			if ($result === false) {
				throw new Exception("Examen non trouvé");
			}
			if (!is_array($result)) {
				$result = [];
			}
			return [
				'success' => true,
				'data' => $result
			];
		} catch (Exception $e) {
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	public function createExamen($nom_examen, $date_examen, $coefficient)
	{
		try {
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

			$result = $this->examenModel->create($nom_examen, $date_examen, $coefficient);
			if ($result === false) {
				throw new Exception("Erreur lors de la création de l'examen");
			}
			return [
				'success' => true,
				'data' => $result,
				'message' => "Examen créé avec succès"
			];
		} catch (Exception $e) {
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	public function updateExamen($id, $nom_examen, $date_examen, $coefficient)
	{
		try {
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

			$result = $this->examenModel->update($id, $nom_examen, $date_examen, $coefficient);
			if ($result === false) {
				throw new Exception("Erreur lors de la mise à jour de l'examen");
			}
			return [
				'success' => true,
				'data' => $result,
				'message' => "Examen mis à jour avec succès"
			];
		} catch (Exception $e) {
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	public function deleteExamen($id)
	{
		try {
			// Vérification de l'existence de l'examen
			if (!$this->examenModel->getById($id)) {
				throw new Exception("L'examen n'existe pas");
			}

			$result = $this->examenModel->delete($id);
			if ($result === false) {
				throw new Exception("Erreur lors de la suppression de l'examen");
			}
			return [
				'success' => true,
				'message' => "Examen supprimé avec succès"
			];
		} catch (Exception $e) {
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}
}
