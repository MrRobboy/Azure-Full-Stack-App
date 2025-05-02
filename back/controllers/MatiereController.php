<?php
require_once __DIR__ . '/../models/Matiere.php';

class MatiereController
{
	private $matiereModel;

	public function __construct()
	{
		error_log("Initialisation du contrôleur des matières");
		$this->matiereModel = new Matiere();
	}

	public function getAllMatieres()
	{
		error_log("Début de getAllMatieres");
		try {
			$result = $this->matiereModel->getAll();
			error_log("Résultat de getAllMatieres: " . print_r($result, true));

			// Formatage des données
			$formattedResult = array_map(function ($row) {
				return [
					'id_matiere' => (int)$row['id_matiere'],
					'nom' => $row['nom']
				];
			}, $result);

			return $formattedResult;
		} catch (Exception $e) {
			error_log("Erreur dans getAllMatieres: " . $e->getMessage());
			throw $e;
		}
	}

	public function getMatiereById($id)
	{
		error_log("Début de getMatiereById pour l'ID: " . $id);
		try {
			$result = $this->matiereModel->getById($id);
			error_log("Résultat de getMatiereById: " . print_r($result, true));

			if (!$result) {
				throw new Exception("Matière non trouvée", 404);
			}

			// Formatage des données
			return [
				'id_matiere' => (int)$result['id_matiere'],
				'nom' => $result['nom']
			];
		} catch (Exception $e) {
			error_log("Erreur dans getMatiereById: " . $e->getMessage());
			throw $e;
		}
	}

	public function createMatiere($nom)
	{
		error_log("Début de createMatiere avec le nom: " . $nom);
		try {
			// Vérification de la validité du nom
			if (empty($nom)) {
				throw new Exception("Le nom de la matière ne peut pas être vide");
			}

			// Vérification de la longueur du nom
			if (strlen($nom) > 255) {
				throw new Exception("Le nom de la matière est trop long");
			}

			$id = $this->matiereModel->create($nom);
			error_log("Nouvelle matière créée avec l'ID: " . $id);
			return $id;
		} catch (Exception $e) {
			error_log("Erreur dans createMatiere: " . $e->getMessage());
			throw $e;
		}
	}

	public function updateMatiere($id, $nom)
	{
		error_log("Début de updateMatiere pour l'ID: " . $id . " avec le nom: " . $nom);
		try {
			// Vérification de l'existence de la matière
			if (!$this->matiereModel->getById($id)) {
				throw new Exception("La matière n'existe pas", 404);
			}

			// Vérification de la validité du nom
			if (empty($nom)) {
				throw new Exception("Le nom de la matière ne peut pas être vide");
			}

			// Vérification de la longueur du nom
			if (strlen($nom) > 255) {
				throw new Exception("Le nom de la matière est trop long");
			}

			$result = $this->matiereModel->update($id, $nom);
			error_log("Résultat de updateMatiere: " . ($result ? "succès" : "échec"));
			return $result;
		} catch (Exception $e) {
			error_log("Erreur dans updateMatiere: " . $e->getMessage());
			throw $e;
		}
	}

	public function deleteMatiere($id)
	{
		error_log("Début de deleteMatiere pour l'ID: " . $id);
		try {
			if (!$this->matiereModel->getById($id)) {
				throw new Exception("La matière n'existe pas", 404);
			}
			$result = $this->matiereModel->delete($id);
			error_log("Résultat de deleteMatiere: " . ($result ? "succès" : "échec"));
			return $result;
		} catch (Exception $e) {
			error_log("Erreur dans deleteMatiere: " . $e->getMessage());
			throw $e;
		}
	}
}
