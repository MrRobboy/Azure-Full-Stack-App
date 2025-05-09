<?php
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Eleve.php';

class ClasseController
{
	private $classeModel;
	private $eleveModel;

	public function __construct()
	{
		$this->classeModel = new Classe();
		$this->eleveModel = new Eleve();
	}

	public function getAllClasses()
	{
		try {
			error_log("Tentative de récupération de toutes les classes");
			$classes = $this->classeModel->getAll();
			error_log("Résultat de getAll: " . print_r($classes, true));

			if ($classes === false) {
				error_log("Erreur lors de la récupération des classes");
				return [
					'success' => false,
					'message' => 'Erreur lors de la récupération des classes',
					'data' => []
				];
			}

			error_log("Classes récupérées avec succès: " . count($classes) . " classes trouvées");
			return [
				'success' => true,
				'message' => 'Classes récupérées avec succès',
				'data' => $classes
			];
		} catch (Exception $e) {
			error_log("Exception dans getAllClasses: " . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Erreur serveur: ' . $e->getMessage(),
				'data' => []
			];
		}
	}

	public function getClasseById($id)
	{
		try {
			error_log("Tentative de récupération de la classe avec l'ID: " . $id);
			$classe = $this->classeModel->getById($id);

			if ($classe === false) {
				error_log("Erreur lors de la récupération de la classe avec l'ID: " . $id);
				return [
					'success' => false,
					'message' => 'Erreur lors de la récupération de la classe',
					'data' => null
				];
			}

			if (!$classe) {
				error_log("Aucune classe trouvée avec l'ID: " . $id);
				return [
					'success' => false,
					'message' => 'Classe non trouvée',
					'data' => null
				];
			}

			error_log("Classe récupérée avec succès: " . print_r($classe, true));
			return [
				'success' => true,
				'message' => 'Classe récupérée avec succès',
				'data' => $classe
			];
		} catch (Exception $e) {
			error_log("Exception dans getClasseById: " . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Erreur serveur: ' . $e->getMessage(),
				'data' => null
			];
		}
	}

	public function createClasse($nom_classe, $niveau, $numero, $rythme)
	{
		try {
			error_log("Tentative de création d'une classe avec les données: " . print_r([
				'nom_classe' => $nom_classe,
				'niveau' => $niveau,
				'numero' => $numero,
				'rythme' => $rythme
			], true));

			if (empty($nom_classe) || empty($niveau) || empty($numero) || empty($rythme)) {
				error_log("Erreur: Champs manquants");
				return [
					'success' => false,
					'message' => 'Tous les champs sont obligatoires',
					'data' => null
				];
			}

			if (!in_array($rythme, ['Alternance', 'Initial'])) {
				error_log("Erreur: Rythme invalide: " . $rythme);
				return [
					'success' => false,
					'message' => 'Le rythme doit être soit "Alternance" soit "Initial"',
					'data' => null
				];
			}

			$result = $this->classeModel->create($nom_classe, $niveau, $numero, $rythme);
			error_log("Résultat de la création: " . print_r($result, true));

			if ($result === false) {
				error_log("Erreur lors de la création de la classe");
				return [
					'success' => false,
					'message' => 'Erreur lors de la création de la classe',
					'data' => null
				];
			}

			error_log("Classe créée avec succès, ID: " . $result);
			return [
				'success' => true,
				'message' => 'Classe créée avec succès',
				'data' => $result
			];
		} catch (Exception $e) {
			error_log("Exception dans createClasse: " . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Erreur serveur: ' . $e->getMessage(),
				'data' => null
			];
		}
	}

	public function updateClasse($id, $nom_classe, $niveau, $numero, $rythme)
	{
		try {
			if (empty($nom_classe) || empty($niveau) || empty($numero) || empty($rythme)) {
				return [
					'success' => false,
					'message' => 'Tous les champs sont obligatoires',
					'data' => null
				];
			}

			if (!in_array($rythme, ['Alternance', 'Initial'])) {
				return [
					'success' => false,
					'message' => 'Le rythme doit être soit "Alternance" soit "Initial"',
					'data' => null
				];
			}

			$result = $this->classeModel->update($id, $nom_classe, $niveau, $numero, $rythme);
			if ($result === false) {
				return [
					'success' => false,
					'message' => 'Erreur lors de la modification de la classe',
					'data' => null
				];
			}

			return [
				'success' => true,
				'message' => 'Classe modifiée avec succès',
				'data' => $result
			];
		} catch (Exception $e) {
			return [
				'success' => false,
				'message' => 'Erreur serveur: ' . $e->getMessage(),
				'data' => null
			];
		}
	}

	public function deleteClasse($id)
	{
		try {
			$result = $this->classeModel->delete($id);
			if ($result === false) {
				return [
					'success' => false,
					'message' => 'Erreur lors de la suppression de la classe',
					'data' => null
				];
			}

			return [
				'success' => true,
				'message' => 'Classe supprimée avec succès',
				'data' => null
			];
		} catch (Exception $e) {
			return [
				'success' => false,
				'message' => 'Erreur serveur: ' . $e->getMessage(),
				'data' => null
			];
		}
	}

	public function getElevesByClasse($id_classe)
	{
		try {
			error_log("Tentative de récupération des élèves de la classe: " . $id_classe);
			$eleves = $this->eleveModel->getByClasse($id_classe);
			error_log("Résultat de getByClasse: " . print_r($eleves, true));

			if ($eleves === false) {
				error_log("Erreur lors de la récupération des élèves");
				return [
					'success' => false,
					'message' => 'Erreur lors de la récupération des élèves',
					'data' => []
				];
			}

			error_log("Élèves récupérés avec succès: " . count($eleves) . " élèves trouvés");
			return [
				'success' => true,
				'message' => 'Élèves récupérés avec succès',
				'data' => $eleves
			];
		} catch (Exception $e) {
			error_log("Exception dans getElevesByClasse: " . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Erreur serveur: ' . $e->getMessage(),
				'data' => []
			];
		}
	}
}
