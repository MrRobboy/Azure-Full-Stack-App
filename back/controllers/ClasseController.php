<?php
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../services/DatabaseService.php';

class ClasseController
{
	private $classeModel;
	private $eleveModel;
	private $db;

	public function __construct()
	{
		$this->classeModel = new Classe();
		$this->eleveModel = new Eleve();
		$this->db = DatabaseService::getInstance()->getConnection();
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
			error_log("Tentative de suppression de la classe ID: " . $id);

			// Vérifier si la classe existe
			$classe = $this->classeModel->getById($id);
			if (!$classe) {
				error_log("Classe non trouvée pour suppression, ID: " . $id);
				return [
					'success' => false,
					'message' => 'Classe non trouvée',
					'data' => null
				];
			}

			// Vérifier si la classe a des élèves avant de tenter la suppression
			$stmt = $this->db->prepare("SELECT COUNT(*) FROM USER WHERE classe = ?");
			if ($stmt) {
				$stmt->execute([$id]);
				if ($stmt->fetchColumn() > 0) {
					error_log("Impossible de supprimer la classe ID: " . $id . " car elle contient des élèves");
					return [
						'success' => false,
						'message' => 'Impossible de supprimer cette classe car elle contient des élèves. Veuillez d\'abord supprimer ou réaffecter les élèves.',
						'data' => null
					];
				}
			}

			// Tentative de suppression
			$result = $this->classeModel->delete($id);

			// Vérification supplémentaire après suppression
			if ($result === false) {
				error_log("Échec de la suppression de la classe ID: " . $id);

				// Double vérification pour s'assurer que la classe existe toujours
				$classeVerif = $this->classeModel->getById($id);
				if ($classeVerif) {
					error_log("La classe existe toujours après tentative de suppression: " . print_r($classeVerif, true));
					return [
						'success' => false,
						'message' => 'Erreur lors de la suppression de la classe. Veuillez réessayer.',
						'data' => null
					];
				} else {
					// Si la classe n'existe plus malgré l'erreur, la considérer comme supprimée
					error_log("La classe n'existe plus après tentative de suppression (faux négatif)");
					return [
						'success' => true,
						'message' => 'Classe supprimée avec succès (récupéré)',
						'data' => null
					];
				}
			}

			error_log("Classe supprimée avec succès, ID: " . $id);
			return [
				'success' => true,
				'message' => 'Classe supprimée avec succès',
				'data' => null
			];
		} catch (Exception $e) {
			error_log("Exception dans deleteClasse: " . $e->getMessage());
			error_log("Trace: " . $e->getTraceAsString());
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

			// Récupérer les élèves
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
				'message' => count($eleves) . ' élèves trouvés',
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
