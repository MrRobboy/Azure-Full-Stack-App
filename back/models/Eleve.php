<?php
require_once __DIR__ . '/../services/DatabaseService.php';

class Eleve
{
	private $db;

	public function __construct()
	{
		$this->db = DatabaseService::getInstance()->getConnection();
	}

	public function getAll()
	{
		$stmt = $this->db->prepare("
            SELECT e.*, c.nom_classe 
            FROM ELEVE e
            JOIN CLASSE c ON e.id_classe = c.id_classe
        ");
		$stmt->execute();
		return $stmt->fetchAll();
	}

	public function getById($id)
	{
		$stmt = $this->db->prepare("
            SELECT e.*, c.nom_classe 
            FROM ELEVE e
            JOIN CLASSE c ON e.id_classe = c.id_classe
            WHERE e.id_eleve = ?
        ");
		$stmt->execute([$id]);
		return $stmt->fetch();
	}

	public function getByClasse($id_classe)
	{
		$stmt = $this->db->prepare("
            SELECT e.*, c.nom_classe 
            FROM ELEVE e
            JOIN CLASSE c ON e.id_classe = c.id_classe
            WHERE e.id_classe = ?
        ");
		$stmt->execute([$id_classe]);
		return $stmt->fetchAll();
	}

	public function create($nom_eleve, $prenom_eleve, $date_naissance, $id_classe)
	{
		$stmt = $this->db->prepare("
            INSERT INTO ELEVE (nom_eleve, prenom_eleve, date_naissance, id_classe) 
            VALUES (?, ?, ?, ?)
        ");
		$stmt->execute([$nom_eleve, $prenom_eleve, $date_naissance, $id_classe]);
		return $this->db->lastInsertId();
	}

	public function update($id, $nom_eleve, $prenom_eleve, $date_naissance, $id_classe)
	{
		$stmt = $this->db->prepare("
            UPDATE ELEVE 
            SET nom_eleve = ?, prenom_eleve = ?, date_naissance = ?, id_classe = ? 
            WHERE id_eleve = ?
        ");
		return $stmt->execute([$nom_eleve, $prenom_eleve, $date_naissance, $id_classe, $id]);
	}

	public function delete($id)
	{
		$stmt = $this->db->prepare("DELETE FROM ELEVE WHERE id_eleve = ?");
		return $stmt->execute([$id]);
	}
}
