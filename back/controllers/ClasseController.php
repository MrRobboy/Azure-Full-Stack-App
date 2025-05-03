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
			$classes = $this->classeModel->getAll();
			if ($classes === false) {
				return [
					'success' => false,
					'message' => 'Erreur lors de la récupération des classes',
					'data' => []
				];
			}
			return [
				'success' => true,
				'message' => 'Classes récupérées avec succès',
				'data' => $classes
			];
		} catch (Exception $e) {
			return [
				'success' => false,
				'message' => 'Erreur serveur: ' . $e->getMessage(),
				'data' => []
			];
		}
	}

	public function getClasseById($id)
	{
		return $this->classeModel->getById($id);
	}

	public function createClasse($nom_classe, $niveau, $numero, $rythme)
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

			$result = $this->classeModel->create($nom_classe, $niveau, $numero, $rythme);
			if ($result === false) {
				return [
					'success' => false,
					'message' => 'Erreur lors de la création de la classe',
					'data' => null
				];
			}

			return [
				'success' => true,
				'message' => 'Classe créée avec succès',
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
		return $this->eleveModel->getByClasse($id_classe);
	}
}
