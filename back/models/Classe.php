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
		$stmt = $this->db->prepare("SELECT * FROM CLASSE WHERE id_classe = ?");
		$stmt->execute([$id]);
		return $stmt->fetch();
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
