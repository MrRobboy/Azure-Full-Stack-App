<?php
require_once __DIR__ . '/../services/DatabaseService.php';

class Note
{
	private $db;

	public function __construct()
	{
		$this->db = DatabaseService::getInstance()->getConnection();
	}

	public function getAll()
	{
		$stmt = $this->db->prepare("
            SELECT n.*, e.nom_eleve, m.nom_matiere, ex.nom_examen 
            FROM NOTE n
            JOIN ELEVE e ON n.id_eleve = e.id_eleve
            JOIN MATIERE m ON n.id_matiere = m.id_matiere
            JOIN EXAMEN ex ON n.id_examen = ex.id_examen
        ");
		$stmt->execute();
		return $stmt->fetchAll();
	}

	public function getById($id)
	{
		$stmt = $this->db->prepare("
            SELECT n.*, e.nom_eleve, m.nom_matiere, ex.nom_examen 
            FROM NOTE n
            JOIN ELEVE e ON n.id_eleve = e.id_eleve
            JOIN MATIERE m ON n.id_matiere = m.id_matiere
            JOIN EXAMEN ex ON n.id_examen = ex.id_examen
            WHERE n.id_note = ?
        ");
		$stmt->execute([$id]);
		return $stmt->fetch();
	}

	public function getByEleve($id_eleve)
	{
		$stmt = $this->db->prepare("
            SELECT n.*, m.nom_matiere, ex.nom_examen 
            FROM NOTE n
            JOIN MATIERE m ON n.id_matiere = m.id_matiere
            JOIN EXAMEN ex ON n.id_examen = ex.id_examen
            WHERE n.id_eleve = ?
        ");
		$stmt->execute([$id_eleve]);
		return $stmt->fetchAll();
	}

	public function create($id_eleve, $id_matiere, $id_examen, $valeur)
	{
		$stmt = $this->db->prepare("
            INSERT INTO NOTE (id_eleve, id_matiere, id_examen, valeur) 
            VALUES (?, ?, ?, ?)
        ");
		$stmt->execute([$id_eleve, $id_matiere, $id_examen, $valeur]);
		return $this->db->lastInsertId();
	}

	public function update($id, $id_eleve, $id_matiere, $id_examen, $valeur)
	{
		$stmt = $this->db->prepare("
            UPDATE NOTE 
            SET id_eleve = ?, id_matiere = ?, id_examen = ?, valeur = ? 
            WHERE id_note = ?
        ");
		return $stmt->execute([$id_eleve, $id_matiere, $id_examen, $valeur, $id]);
	}

	public function delete($id)
	{
		$stmt = $this->db->prepare("DELETE FROM NOTE WHERE id_note = ?");
		return $stmt->execute([$id]);
	}
}
