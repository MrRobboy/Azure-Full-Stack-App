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

	public function createExamen($titre, $matiere, $classe)
	{
		try {
			error_log("Tentative de création d'un examen");
			error_log("Titre: " . $titre);
			error_log("Matière: " . $matiere);
			error_log("Classe: " . $classe);

			// Vérification de la validité des données
			if (empty($titre) || empty($matiere) || empty($classe)) {
				error_log("Données manquantes");
				throw new Exception("Tous les champs sont obligatoires");
			}

			// Vérification de la longueur du titre
			if (strlen($titre) > 255) {
				error_log("Titre trop long");
				throw new Exception("Le titre de l'examen est trop long");
			}

			// Vérification de l'existence de la matière
			if (!$this->matiereModel->getById($matiere)) {
				error_log("Matière non trouvée");
				throw new Exception("La matière n'existe pas");
			}

			// Vérification de l'existence de la classe
			if (!$this->classeModel->getById($classe)) {
				error_log("Classe non trouvée");
				throw new Exception("La classe n'existe pas");
			}

			$result = $this->examenModel->create($titre, $matiere, $classe);
			if ($result === false) {
				error_log("Erreur lors de la création de l'examen");
				throw new Exception("Erreur lors de la création de l'examen");
			}

			error_log("Examen créé avec succès");
			return [
				'success' => true,
				'data' => $result,
				'message' => "Examen créé avec succès"
			];
		} catch (Exception $e) {
			error_log("Erreur dans createExamen: " . $e->getMessage());
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	public function updateExamen($id, $titre, $matiere, $classe)
	{
		try {
			// Vérification de l'existence de l'examen
			if (!$this->examenModel->getById($id)) {
				throw new Exception("L'examen n'existe pas");
			}

			// Vérification de la validité des données
			if (empty($titre) || empty($matiere) || empty($classe)) {
				throw new Exception("Tous les champs sont obligatoires");
			}

			// Vérification de la longueur du titre
			if (strlen($titre) > 255) {
				throw new Exception("Le titre de l'examen est trop long");
			}

			// Vérification de l'existence de la matière
			if (!$this->matiereModel->getById($matiere)) {
				throw new Exception("La matière n'existe pas");
			}

			// Vérification de l'existence de la classe
			if (!$this->classeModel->getById($classe)) {
				throw new Exception("La classe n'existe pas");
			}

			$result = $this->examenModel->update($id, $titre, $matiere, $classe);
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
