<?php
require_once __DIR__ . '/../services/DatabaseService.php';

class Classe
{
	private $db;

	public function __construct()
	{
		$this->db = DatabaseService::getInstance()->getConnection();
	}

	public function getAll()
	{
		try {
			error_log("Tentative de récupération de toutes les classes depuis la base de données");
			$stmt = $this->db->prepare("SELECT * FROM CLASSE");
			$stmt->execute();
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			error_log("Résultat de la requête getAll: " . print_r($result, true));
			return $result;
		} catch (PDOException $e) {
			error_log("Erreur PDO dans getAll: " . $e->getMessage());
			return false;
		}
	}

	public function getById($id)
	{
		try {
			error_log("Tentative de récupération de la classe avec l'ID: " . $id);

			// Vérification de la connexion
			if (!$this->db) {
				error_log("Erreur: Connexion à la base de données non initialisée");
				return false;
			}

			$stmt = $this->db->prepare("SELECT * FROM CLASSE WHERE id_classe = ?");
			if (!$stmt) {
				error_log("Erreur lors de la préparation de la requête: " . print_r($this->db->errorInfo(), true));
				return false;
			}

			$result = $stmt->execute([$id]);
			if (!$result) {
				error_log("Erreur lors de l'exécution de la requête: " . print_r($stmt->errorInfo(), true));
				return false;
			}

			$classe = $stmt->fetch(PDO::FETCH_ASSOC);
			if (!$classe) {
				error_log("Aucune classe trouvée avec l'ID: " . $id);
				return false;
			}

			error_log("Classe trouvée: " . print_r($classe, true));
			return $classe;
		} catch (PDOException $e) {
			error_log("Erreur PDO dans getById: " . $e->getMessage());
			error_log("Trace: " . $e->getTraceAsString());
			return false;
		} catch (Exception $e) {
			error_log("Erreur générale dans getById: " . $e->getMessage());
			error_log("Trace: " . $e->getTraceAsString());
			return false;
		}
	}

	public function create($nom_classe, $niveau, $numero, $rythme)
	{
		try {
			error_log("Tentative de création d'une classe dans la base de données avec les données: " . print_r([
				'nom_classe' => $nom_classe,
				'niveau' => $niveau,
				'numero' => $numero,
				'rythme' => $rythme
			], true));

			$stmt = $this->db->prepare("INSERT INTO CLASSE (nom_classe, niveau, numero, rythme) VALUES (?, ?, ?, ?)");
			$result = $stmt->execute([$nom_classe, $niveau, $numero, $rythme]);

			if ($result) {
				$id = $this->db->lastInsertId();
				error_log("Classe créée avec succès, ID: " . $id);
				return $id;
			} else {
				error_log("Erreur lors de l'exécution de la requête INSERT");
				return false;
			}
		} catch (PDOException $e) {
			error_log("Erreur PDO dans create: " . $e->getMessage());
			return false;
		}
	}

	public function update($id, $nom_classe, $niveau, $numero, $rythme)
	{
		$stmt = $this->db->prepare("UPDATE CLASSE SET nom_classe = ?, niveau = ?, numero = ?, rythme = ? WHERE id_classe = ?");
		return $stmt->execute([$nom_classe, $niveau, $numero, $rythme, $id]);
	}

	public function delete($id)
	{
		$stmt = $this->db->prepare("DELETE FROM CLASSE WHERE id_classe = ?");
		return $stmt->execute([$id]);
	}
}
