<?php
require_once __DIR__ . '/../services/DatabaseService.php';

class Examen
{
	private $db;

	public function __construct()
	{
		$this->db = DatabaseService::getInstance()->getConnection();
	}

	public function getAll()
	{
		$stmt = $this->db->prepare("SELECT * FROM EXAMEN");
		$stmt->execute();
		return $stmt->fetchAll();
	}

	public function getById($id)
	{
		$stmt = $this->db->prepare("SELECT * FROM EXAMEN WHERE id_examen = ?");
		$stmt->execute([$id]);
		return $stmt->fetch();
	}

	public function create($nom_examen, $date_examen, $coefficient)
	{
		$stmt = $this->db->prepare("
            INSERT INTO EXAMEN (nom_examen, date_examen, coefficient) 
            VALUES (?, ?, ?)
        ");
		$stmt->execute([$nom_examen, $date_examen, $coefficient]);
		return $this->db->lastInsertId();
	}

	public function update($id, $nom_examen, $date_examen, $coefficient)
	{
		$stmt = $this->db->prepare("
            UPDATE EXAMEN 
            SET nom_examen = ?, date_examen = ?, coefficient = ? 
            WHERE id_examen = ?
        ");
		return $stmt->execute([$nom_examen, $date_examen, $coefficient, $id]);
	}

	public function delete($id)
	{
		$stmt = $this->db->prepare("DELETE FROM EXAMEN WHERE id_examen = ?");
		return $stmt->execute([$id]);
	}
}
