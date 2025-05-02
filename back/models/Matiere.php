<?php
require_once __DIR__ . '/../services/DatabaseService.php';

class Matiere
{
	private $db;

	public function __construct()
	{
		$this->db = DatabaseService::getInstance()->getConnection();
		error_log("Connexion à la base de données établie dans Matiere");
	}

	public function getAll()
	{
		error_log("Début de la récupération de toutes les matières");
		$stmt = $this->db->prepare("SELECT * FROM MATIERE");
		$stmt->execute();
		$result = $stmt->fetchAll();
		error_log("Résultat de la requête getAll: " . print_r($result, true));
		return $result;
	}

	public function getById($id)
	{
		error_log("Début de la récupération de la matière avec l'ID: " . $id);
		$stmt = $this->db->prepare("SELECT * FROM MATIERE WHERE id_matiere = ?");
		$stmt->execute([$id]);
		$result = $stmt->fetch();
		error_log("Résultat de la requête getById: " . print_r($result, true));
		return $result;
	}

	public function create($nom)
	{
		error_log("Création d'une nouvelle matière: " . $nom);
		$stmt = $this->db->prepare("INSERT INTO MATIERE (nom) VALUES (?)");
		$stmt->execute([$nom]);
		$id = $this->db->lastInsertId();
		error_log("Nouvelle matière créée avec l'ID: " . $id);
		return $id;
	}

	public function update($id, $nom)
	{
		error_log("Mise à jour de la matière " . $id . " avec le nom: " . $nom);
		$stmt = $this->db->prepare("UPDATE MATIERE SET nom = ? WHERE id_matiere = ?");
		$result = $stmt->execute([$nom, $id]);
		error_log("Résultat de la mise à jour: " . ($result ? "succès" : "échec"));
		return $result;
	}

	public function delete($id)
	{
		error_log("Suppression de la matière avec l'ID: " . $id);
		$stmt = $this->db->prepare("DELETE FROM MATIERE WHERE id_matiere = ?");
		$result = $stmt->execute([$id]);
		error_log("Résultat de la suppression: " . ($result ? "succès" : "échec"));
		return $result;
	}
}
