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
		return $this->classeModel->getAll();
	}

	public function getClasseById($id)
	{
		return $this->classeModel->getById($id);
	}

	public function createClasse($nom_classe, $niveau, $numero)
	{
		// Vérification de la validité des données
		if (empty($nom_classe) || empty($niveau) || empty($numero)) {
			throw new Exception("Tous les champs sont obligatoires");
		}

		// Vérification de la longueur des champs
		if (strlen($nom_classe) > 255) {
			throw new Exception("Le nom de la classe est trop long");
		}
		if (strlen($niveau) > 50) {
			throw new Exception("Le niveau est trop long");
		}
		if (strlen($numero) > 50) {
			throw new Exception("Le numéro est trop long");
		}

		return $this->classeModel->create($nom_classe, $niveau, $numero);
	}

	public function updateClasse($id, $nom_classe, $niveau, $numero)
	{
		// Vérification de l'existence de la classe
		if (!$this->classeModel->getById($id)) {
			throw new Exception("La classe n'existe pas");
		}

		// Vérification de la validité des données
		if (empty($nom_classe) || empty($niveau) || empty($numero)) {
			throw new Exception("Tous les champs sont obligatoires");
		}

		// Vérification de la longueur des champs
		if (strlen($nom_classe) > 255) {
			throw new Exception("Le nom de la classe est trop long");
		}
		if (strlen($niveau) > 50) {
			throw new Exception("Le niveau est trop long");
		}
		if (strlen($numero) > 50) {
			throw new Exception("Le numéro est trop long");
		}

		return $this->classeModel->update($id, $nom_classe, $niveau, $numero);
	}

	public function deleteClasse($id)
	{
		// Vérification de l'existence de la classe
		if (!$this->classeModel->getById($id)) {
			throw new Exception("La classe n'existe pas");
		}

		// Vérification qu'il n'y a pas d'élèves dans la classe
		$eleves = $this->eleveModel->getByClasse($id);
		if (!empty($eleves)) {
			throw new Exception("Impossible de supprimer la classe car elle contient des élèves");
		}

		return $this->classeModel->delete($id);
	}

	public function getElevesByClasse($id_classe)
	{
		return $this->eleveModel->getByClasse($id_classe);
	}
}
