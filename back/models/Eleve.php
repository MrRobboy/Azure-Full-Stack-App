<?php
require_once __DIR__ . '/../services/DatabaseService.php';
require_once __DIR__ . '/../services/ErrorService.php';

class Eleve
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
			$stmt = $this->db->prepare("
				SELECT u.*, c.nom_classe 
				FROM USER u
				JOIN CLASSE c ON u.classe = c.id_classe
				ORDER BY u.nom ASC, u.prenom ASC
			");
			$stmt->execute();
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (Exception $e) {
			$this->errorService->logError('Eleve::getAll', $e->getMessage());
			return [];
		}
	}

	public function getById($id)
	{
		try {
			$stmt = $this->db->prepare("
				SELECT u.*, c.nom_classe 
				FROM USER u
				JOIN CLASSE c ON u.classe = c.id_classe
				WHERE u.id_user = ?
			");
			$stmt->execute([$id]);
			return $stmt->fetch(PDO::FETCH_ASSOC);
		} catch (Exception $e) {
			$this->errorService->logError('Eleve::getById', $e->getMessage());
			return null;
		}
	}

	public function getByClasse($id_classe)
	{
		try {
			error_log("Tentative de récupération des élèves pour la classe: " . $id_classe);

			// Requête de débogage pour voir tous les utilisateurs
			$debugStmt = $this->db->query("SELECT * FROM USER");
			$allUsers = $debugStmt->fetchAll(PDO::FETCH_ASSOC);

			// Requête de débogage pour voir la classe
			$debugStmt = $this->db->prepare("SELECT * FROM CLASSE WHERE id_classe = ?");
			$debugStmt->execute([$id_classe]);
			$classe = $debugStmt->fetch(PDO::FETCH_ASSOC);

			// Récupérer les élèves
			$sql = "
				SELECT u.*, c.nom_classe 
				FROM USER u
				JOIN CLASSE c ON u.classe = c.id_classe
				WHERE u.classe = ?
				ORDER BY u.nom ASC, u.prenom ASC
			";

			$stmt = $this->db->prepare($sql);
			$result = $stmt->execute([$id_classe]);
			$eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);

			// Retourner les données de débogage avec les résultats
			return [
				'debug' => [
					'sql' => $sql,
					'params' => [$id_classe],
					'all_users' => $allUsers,
					'classe' => $classe,
					'result_count' => count($eleves)
				],
				'data' => $eleves
			];
		} catch (Exception $e) {
			error_log("Exception dans getByClasse: " . $e->getMessage());
			$this->errorService->logError('Eleve::getByClasse', $e->getMessage());
			return [
				'debug' => [
					'error' => $e->getMessage()
				],
				'data' => []
			];
		}
	}

	public function create($nom, $prenom, $email, $password, $id_classe)
	{
		try {
			$stmt = $this->db->prepare("
				INSERT INTO USER (nom, prenom, email, password, classe) 
				VALUES (?, ?, ?, ?, ?)
			");
			$stmt->execute([$nom, $prenom, $email, password_hash($password, PASSWORD_DEFAULT), $id_classe]);
			return DatabaseService::getInstance()->lastInsertId();
		} catch (Exception $e) {
			$this->errorService->logError('Eleve::create', $e->getMessage());
			return false;
		}
	}

	public function update($id, $nom, $prenom, $email, $id_classe)
	{
		try {
			$stmt = $this->db->prepare("
				UPDATE USER 
				SET nom = ?, prenom = ?, email = ?, classe = ? 
				WHERE id_user = ?
			");
			return $stmt->execute([$nom, $prenom, $email, $id_classe, $id]);
		} catch (Exception $e) {
			$this->errorService->logError('Eleve::update', $e->getMessage());
			return false;
		}
	}

	public function delete($id)
	{
		try {
			$stmt = $this->db->prepare("DELETE FROM USER WHERE id_user = ?");
			return $stmt->execute([$id]);
		} catch (Exception $e) {
			$this->errorService->logError('Eleve::delete', $e->getMessage());
			return false;
		}
	}
}
