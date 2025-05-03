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
		$stmt = $this->db->prepare("SELECT * FROM CLASSE");
		$stmt->execute();
		return $stmt->fetchAll();
	}

	public function getById($id)
	{
		$stmt = $this->db->prepare("SELECT * FROM CLASSE WHERE id_classe = ?");
		$stmt->execute([$id]);
		return $stmt->fetch();
	}

	public function create($nom_classe, $niveau, $numero, $rythme)
	{
		$stmt = $this->db->prepare("INSERT INTO CLASSE (nom_classe, niveau, numero, rythme) VALUES (?, ?, ?, ?)");
		$stmt->execute([$nom_classe, $niveau, $numero, $rythme]);
		return $this->db->lastInsertId();
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
