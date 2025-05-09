<?php
require_once __DIR__ . '/../services/DatabaseService.php';
require_once __DIR__ . '/../services/ErrorService.php';

class Examen
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
			error_log("Tentative de récupération de tous les examens");
			$sql = "SELECT e.id_exam as id_examen, e.titre, e.matiere as id_matiere, e.classe as id_classe, e.date,
					m.nom as nom_matiere, c.nom_classe 
					FROM EXAM e 
					JOIN MATIERE m ON e.matiere = m.id_matiere 
					JOIN CLASSE c ON e.classe = c.id_classe 
					ORDER BY e.date DESC, e.id_exam DESC";

			error_log("Requête SQL: " . $sql);
			$stmt = $this->db->prepare($sql);

			if ($stmt === false) {
				error_log("Erreur de préparation de la requête: " . print_r($this->db->errorInfo(), true));
				throw new Exception("Erreur lors de la préparation de la requête");
			}

			if (!$stmt->execute()) {
				error_log("Erreur d'exécution de la requête: " . print_r($stmt->errorInfo(), true));
				throw new Exception("Erreur lors de l'exécution de la requête");
			}

			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			error_log("Résultat de la requête: " . print_r($result, true));
			return $result;
		} catch (Exception $e) {
			error_log("Erreur dans getAll: " . $e->getMessage());
			return false;
		}
	}

	public function getById($id)
	{
		try {
			error_log("Tentative de récupération de l'examen ID: " . $id);

			$sql = "
				SELECT e.id_exam as id_examen, e.titre, e.matiere as id_matiere, e.classe as id_classe, e.date,
					m.nom as nom_matiere, c.nom_classe 
				FROM EXAM e
				JOIN MATIERE m ON e.matiere = m.id_matiere
				JOIN CLASSE c ON e.classe = c.id_classe
				WHERE e.id_exam = ?
			";

			error_log("Requête SQL: " . $sql);
			error_log("Paramètres: " . print_r([$id], true));

			$stmt = $this->db->prepare($sql);
			if ($stmt === false) {
				$error = $this->db->errorInfo();
				error_log("Erreur de préparation de la requête: " . print_r($error, true));
				throw new Exception("Erreur lors de la préparation de la requête: " . $error[2]);
			}

			if (!$stmt->execute([$id])) {
				$error = $stmt->errorInfo();
				error_log("Erreur d'exécution de la requête: " . print_r($error, true));
				throw new Exception("Erreur lors de l'exécution de la requête: " . $error[2]);
			}

			$result = $stmt->fetch(PDO::FETCH_ASSOC);
			error_log("Résultat de la requête: " . print_r($result, true));

			if ($result === false) {
				error_log("Aucun examen trouvé avec l'ID: " . $id);
				return false;
			}

			return $result;
		} catch (Exception $e) {
			error_log("Erreur dans getById: " . $e->getMessage());
			error_log("Trace de l'erreur: " . $e->getTraceAsString());
			$this->errorService->logError('Examen::getById', $e->getMessage());
			throw $e; // Propager l'erreur au contrôleur
		}
	}

	public function create($titre, $matiere, $classe, $date)
	{
		try {
			error_log("Tentative de création d'un examen");
			error_log("Données reçues: titre=$titre, matiere=$matiere, classe=$classe, date=$date");

			// Vérifier la connexion à la base de données
			if (!$this->db) {
				error_log("Erreur: Connexion à la base de données non établie");
				throw new Exception("Erreur de connexion à la base de données");
			}

			$sql = "INSERT INTO EXAM (titre, matiere, classe, date) VALUES (?, ?, ?, ?)";
			error_log("Requête SQL: " . $sql);

			$stmt = $this->db->prepare($sql);

			if ($stmt === false) {
				$error = $this->db->errorInfo();
				error_log("Erreur de préparation de la requête: " . print_r($error, true));
				throw new Exception("Erreur lors de la préparation de la requête: " . $error[2]);
			}

			error_log("Exécution de la requête avec les paramètres: " . print_r([$titre, $matiere, $classe, $date], true));

			if (!$stmt->execute([$titre, $matiere, $classe, $date])) {
				$error = $stmt->errorInfo();
				error_log("Erreur d'exécution de la requête: " . print_r($error, true));
				throw new Exception("Erreur lors de l'exécution de la requête: " . $error[2]);
			}

			$id = $this->db->lastInsertId();
			error_log("ID de l'examen créé: " . $id);

			// Vérifier que l'insertion a réussi
			if (!$id) {
				error_log("Erreur: Aucun ID retourné après l'insertion");
				throw new Exception("Erreur lors de la création de l'examen: aucun ID retourné");
			}

			return [
				'id_examen' => $id,
				'titre' => $titre,
				'id_matiere' => $matiere,
				'id_classe' => $classe,
				'date' => $date
			];
		} catch (Exception $e) {
			error_log("Erreur dans create: " . $e->getMessage());
			error_log("Trace de l'erreur: " . $e->getTraceAsString());
			return false;
		}
	}

	public function update($id, $titre, $matiere, $classe, $date)
	{
		try {
			error_log("Tentative de mise à jour de l'examen ID: $id");
			error_log("Données reçues: titre=$titre, matiere=$matiere, classe=$classe, date=$date");

			$stmt = $this->db->prepare("
				UPDATE EXAM 
				SET titre = ?, matiere = ?, classe = ?, date = ?
				WHERE id_exam = ?
			");

			if ($stmt === false) {
				$error = $this->db->errorInfo();
				error_log("Erreur de préparation de la requête: " . print_r($error, true));
				throw new Exception("Erreur lors de la préparation de la requête: " . $error[2]);
			}

			if (!$stmt->execute([$titre, $matiere, $classe, $date, $id])) {
				$error = $stmt->errorInfo();
				error_log("Erreur d'exécution de la requête: " . print_r($error, true));
				throw new Exception("Erreur lors de la mise à jour de l'examen: " . $error[2]);
			}

			$result = $this->getById($id);
			if ($result === null) {
				throw new Exception("L'examen n'a pas été trouvé après la mise à jour");
			}

			error_log("Mise à jour réussie pour l'examen ID: $id");
			return $result;
		} catch (Exception $e) {
			error_log("Erreur dans update: " . $e->getMessage());
			error_log("Trace de l'erreur: " . $e->getTraceAsString());
			$this->errorService->logError('Examen::update', $e->getMessage());
			return false;
		}
	}

	public function delete($id)
	{
		try {
			error_log("Tentative de suppression de l'examen ID: $id");

			// Vérifier si l'examen existe
			$examen = $this->getById($id);
			if ($examen === null) {
				throw new Exception("L'examen n'existe pas");
			}

			$stmt = $this->db->prepare("DELETE FROM EXAM WHERE id_exam = ?");

			if ($stmt === false) {
				$error = $this->db->errorInfo();
				error_log("Erreur de préparation de la requête: " . print_r($error, true));
				throw new Exception("Erreur lors de la préparation de la requête: " . $error[2]);
			}

			if (!$stmt->execute([$id])) {
				$error = $stmt->errorInfo();
				error_log("Erreur d'exécution de la requête: " . print_r($error, true));
				throw new Exception("Erreur lors de la suppression de l'examen: " . $error[2]);
			}

			error_log("Suppression réussie de l'examen ID: $id");
			return true;
		} catch (Exception $e) {
			error_log("Erreur dans delete: " . $e->getMessage());
			error_log("Trace de l'erreur: " . $e->getTraceAsString());
			$this->errorService->logError('Examen::delete', $e->getMessage());
			return false;
		}
	}
}
