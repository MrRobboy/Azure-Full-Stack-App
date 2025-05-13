<?php
require_once __DIR__ . '/../services/DatabaseService.php';
require_once __DIR__ . '/../services/ErrorService.php';

class Note
{
	private $db;
	private $errorService;

	public function __construct()
	{
		$this->db = DatabaseService::getInstance()->getConnection();
		$this->errorService = ErrorService::getInstance();
	}

	public function getAll()
	{
		try {
			$stmt = $this->db->prepare("
				SELECT n.*, e.nom as nom_eleve, e.prenom as prenom_eleve,
					m.nom as nom_matiere, ex.titre as nom_examen 
				FROM NOTE n
				JOIN USER e ON n.id_eleve = e.id_user
				JOIN MATIERE m ON n.id_matiere = m.id_matiere
				JOIN EXAMEN ex ON n.id_examen = ex.id_examen
				ORDER BY ex.date_examen DESC, e.nom ASC
			");

			if (!$stmt->execute()) {
				throw new Exception("Erreur lors de l'exécution de la requête");
			}

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if ($result === false) {
				return [];
			}

			return $result;
		} catch (Exception $e) {
			$this->errorService->logError('Note::getAll', $e->getMessage());
			return [];
		}
	}

	public function getById($id)
	{
		try {
			$stmt = $this->db->prepare("
				SELECT n.*, e.nom as nom_eleve, e.prenom as prenom_eleve,
					m.nom as nom_matiere, ex.titre as nom_examen 
				FROM NOTE n
				JOIN USER e ON n.id_eleve = e.id_user
				JOIN MATIERE m ON n.id_matiere = m.id_matiere
				JOIN EXAMEN ex ON n.id_examen = ex.id_examen
				WHERE n.id_note = ?
			");

			if (!$stmt->execute([$id])) {
				throw new Exception("Erreur lors de l'exécution de la requête");
			}

			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			if ($result === false) {
				return null;
			}

			return $result;
		} catch (Exception $e) {
			$this->errorService->logError('Note::getById', $e->getMessage());
			return null;
		}
	}

	public function getByEleve($id_eleve)
	{
		try {
			$stmt = $this->db->prepare("
				SELECT n.*, m.nom as nom_matiere, ex.titre as nom_examen 
				FROM NOTE n
				JOIN MATIERE m ON n.id_matiere = m.id_matiere
				JOIN EXAMEN ex ON n.id_examen = ex.id_examen
				WHERE n.id_eleve = ?
				ORDER BY ex.date_examen DESC
			");

			if (!$stmt->execute([$id_eleve])) {
				throw new Exception("Erreur lors de l'exécution de la requête");
			}

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if ($result === false) {
				return [];
			}

			return $result;
		} catch (Exception $e) {
			$this->errorService->logError('Note::getByEleve', $e->getMessage());
			return [];
		}
	}

	public function getByExamen($id_examen)
	{
		try {
			$stmt = $this->db->prepare("
				SELECT n.id_note, n.valeur, n.id_eleve, n.id_examen,
					u.nom, u.prenom
				FROM NOTE n
				JOIN USER u ON n.id_eleve = u.id_user
				WHERE n.id_examen = ?
				ORDER BY u.nom ASC, u.prenom ASC
			");

			if (!$stmt->execute([$id_examen])) {
				throw new Exception("Erreur lors de l'exécution de la requête");
			}

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if ($result === false) {
				return [];
			}

			// Transformer les résultats pour correspondre au format attendu
			$formattedResult = array_map(function ($note) {
				return [
					'id_note' => $note['id_note'],
					'valeur' => $note['valeur'],
					'id_eleve' => $note['id_eleve'],
					'id_examen' => $note['id_examen'],
					'nom' => $note['nom'],
					'prenom' => $note['prenom']
				];
			}, $result);

			return $formattedResult;
		} catch (Exception $e) {
			$this->errorService->logError('Note::getByExamen', $e->getMessage());
			return [];
		}
	}

	public function create($id_eleve, $id_matiere, $id_examen, $valeur)
	{
		try {
			$stmt = $this->db->prepare("
				INSERT INTO NOTE (valeur, id_eleve, id_matiere, id_examen) 
				VALUES (?, ?, ?, ?)
			");

			if (!$stmt->execute([$valeur, $id_eleve, $id_matiere, $id_examen])) {
				throw new Exception("Erreur lors de l'insertion de la note");
			}

			$id = $this->db->lastInsertId();
			return $this->getById($id);
		} catch (Exception $e) {
			$this->errorService->logError('Note::create', $e->getMessage());
			return false;
		}
	}

	public function update($id, $valeur)
	{
		try {
			$stmt = $this->db->prepare("
				UPDATE NOTE 
				SET valeur = ?
				WHERE id_note = ?
			");

			if (!$stmt->execute([$valeur, $id])) {
				throw new Exception("Erreur lors de la mise à jour de la note");
			}

			return $this->getById($id);
		} catch (Exception $e) {
			$this->errorService->logError('Note::update', $e->getMessage());
			return false;
		}
	}

	public function delete($id)
	{
		try {
			$stmt = $this->db->prepare("DELETE FROM NOTE WHERE id_note = ?");

			if (!$stmt->execute([$id])) {
				throw new Exception("Erreur lors de la suppression de la note");
			}

			return true;
		} catch (Exception $e) {
			$this->errorService->logError('Note::delete', $e->getMessage());
			return false;
		}
	}
}
