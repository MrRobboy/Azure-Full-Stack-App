<?php
require_once __DIR__ . '/../services/DatabaseService.php';

class Prof
{
	private $db;

	public function __construct()
	{
		$this->db = DatabaseService::getInstance()->getConnection();
	}

	public function authenticate($email, $password)
	{
		$stmt = $this->db->prepare("SELECT * FROM PROF WHERE email = ?");
		$stmt->execute([$email]);
		$prof = $stmt->fetch();

		if ($prof && password_verify($password, $prof['password'])) {
			return $prof;
		}
		return false;
	}

	public function getById($id)
	{
		$stmt = $this->db->prepare("SELECT * FROM PROF WHERE id_prof = ?");
		$stmt->execute([$id]);
		return $stmt->fetch();
	}

	public function getMatieres($profId)
	{
		$stmt = $this->db->prepare("SELECT * FROM MATIERE WHERE id_matiere IN (SELECT matiere FROM PROF WHERE id_prof = ?)");
		$stmt->execute([$profId]);
		return $stmt->fetchAll();
	}

	public function getExams($profId)
	{
		$stmt = $this->db->prepare("SELECT * FROM EXAM WHERE matiere IN (SELECT matiere FROM PROF WHERE id_prof = ?)");
		$stmt->execute([$profId]);
		return $stmt->fetchAll();
	}
}
