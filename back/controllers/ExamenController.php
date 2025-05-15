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
			error_log("Tentative de récupération de tous les examens");
			$result = $this->examenModel->getAll();
			error_log("Résultat de getAll: " . print_r($result, true));

			if ($result === false) {
				throw new Exception("Erreur lors de la récupération des examens");
			}

			if (!is_array($result)) {
				$result = [];
			}

			error_log("Examen retournés: " . count($result));
			return [
				'success' => true,
				'data' => $result,
				'message' => "Examens récupérés avec succès"
			];
		} catch (Exception $e) {
			error_log("Erreur dans getAllExamens: " . $e->getMessage());
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		}
	}

	public function getExamenById($id)
	{
		try {
			error_log("Tentative de récupération de l'examen ID: " . $id);

			if (!is_numeric($id)) {
				throw new Exception("ID invalide");
			}

			$result = $this->examenModel->getById($id);
			error_log("Résultat de getById: " . print_r($result, true));

			if ($result === false) {
				throw new Exception("Examen non trouvé");
			}

			return [
				'success' => true,
				'data' => $result,
				'message' => "Examen récupéré avec succès"
			];
		} catch (Exception $e) {
			error_log("Erreur dans getExamenById: " . $e->getMessage());
			error_log("Trace de l'erreur: " . $e->getTraceAsString());
			return [
				'success' => false,
				'message' => $e->getMessage(),
				'error' => $e->getMessage()
			];
		}
	}

	public function createExamen($titre, $matiere, $classe, $date)
	{
		try {
			error_log("Tentative de création d'un examen");
			error_log("Données reçues: titre=$titre, matiere=$matiere, classe=$classe, date=$date");

			if (empty($titre) || empty($matiere) || empty($classe) || empty($date)) {
				throw new Exception("Tous les champs sont obligatoires");
			}

			// Vérifier le format de la date
			$dateObj = DateTime::createFromFormat('Y-m-d', $date);
			if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
				throw new Exception("Format de date invalide. Utilisez le format YYYY-MM-DD");
			}

			$result = $this->examenModel->create($titre, $matiere, $classe, $date);
			if ($result === false) {
				throw new Exception("Erreur lors de la création de l'examen");
			}

			return [
				'success' => true,
				'message' => 'Examen créé avec succès',
				'data' => $result
			];
		} catch (Exception $e) {
			error_log("Erreur dans createExamen: " . $e->getMessage());
			return [
				'success' => false,
				'message' => $e->getMessage()
			];
		}
	}

	public function updateExamen($id, $titre, $matiere, $classe, $date)
	{
		try {
			error_log("Tentative de mise à jour de l'examen ID: $id");
			error_log("Données reçues: titre=$titre, matiere=$matiere, classe=$classe, date=$date");

			if (empty($titre) || empty($matiere) || empty($classe) || empty($date)) {
				throw new Exception("Tous les champs sont obligatoires");
			}

			// Vérifier le format de la date
			$dateObj = DateTime::createFromFormat('Y-m-d', $date);
			if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
				throw new Exception("Format de date invalide. Utilisez le format YYYY-MM-DD");
			}

			$result = $this->examenModel->update($id, $titre, $matiere, $classe, $date);
			if ($result === false) {
				throw new Exception("Erreur lors de la mise à jour de l'examen");
			}

			return [
				'success' => true,
				'message' => 'Examen mis à jour avec succès',
				'data' => $result
			];
		} catch (Exception $e) {
			error_log("Erreur dans updateExamen: " . $e->getMessage());
			return [
				'success' => false,
				'message' => $e->getMessage()
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
