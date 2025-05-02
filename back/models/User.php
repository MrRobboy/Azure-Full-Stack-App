<?php
require_once __DIR__ . '/../services/DatabaseService.php';

class User
{
	private $db;

	public function __construct()
	{
		$this->db = DatabaseService::getInstance()->getConnection();
	}

	public function getById($id)
	{
		$stmt = $this->db->prepare("SELECT * FROM USER WHERE id_user = ?");
		$stmt->execute([$id]);
		return $stmt->fetch();
	}

	public function getByClasse($classeId)
	{
		$stmt = $this->db->prepare("SELECT * FROM USER WHERE classe = ?");
		$stmt->execute([$classeId]);
		return $stmt->fetchAll();
	}

	public function getNotes($userId)
	{
		$stmt = $this->db->prepare("SELECT * FROM NOTES WHERE user = ?");
		$stmt->execute([$userId]);
		return $stmt->fetchAll();
	}
}
