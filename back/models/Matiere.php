<?php
require_once __DIR__ . '/../services/DatabaseService.php';

class Matiere
{
	private $db;

	public function __construct()
	{
		$this->db = DatabaseService::getInstance()->getConnection();
	}

	public function getAll()
	{
		$stmt = $this->db->prepare("SELECT * FROM MATIERE");
		$stmt->execute();
		return $stmt->fetchAll();
	}

	public function getById($id)
	{
		$stmt = $this->db->prepare("SELECT * FROM MATIERE WHERE id_matiere = ?");
		$stmt->execute([$id]);
		return $stmt->fetch();
	}

	public function create($nom)
	{
		$stmt = $this->db->prepare("INSERT INTO MATIERE (nom) VALUES (?)");
		$stmt->execute([$nom]);
		return $this->db->lastInsertId();
	}

	public function update($id, $nom)
	{
		$stmt = $this->db->prepare("UPDATE MATIERE SET nom = ? WHERE id_matiere = ?");
		return $stmt->execute([$nom, $id]);
	}

	public function delete($id)
	{
		$stmt = $this->db->prepare("DELETE FROM MATIERE WHERE id_matiere = ?");
		return $stmt->execute([$id]);
	}
}
