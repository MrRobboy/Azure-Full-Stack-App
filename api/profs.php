<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Vérifier la méthode HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Récupérer l'ID de la ressource si présent dans l'URL
$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', $url);
$id = isset($segments[3]) ? $segments[3] : null;

try {
	$db = new Database();
	$conn = $db->getConnection();

	switch ($method) {
		case 'GET':
			if ($id) {
				// Récupérer un professeur spécifique
				$stmt = $conn->prepare("SELECT * FROM PROF WHERE id_prof = ?");
				$stmt->execute([$id]);
				$prof = $stmt->fetch(PDO::FETCH_ASSOC);

				if ($prof) {
					echo json_encode(['success' => true, 'data' => $prof]);
				} else {
					http_response_code(404);
					echo json_encode(['success' => false, 'message' => 'Professeur non trouvé']);
				}
			} else {
				// Récupérer tous les professeurs
				$stmt = $conn->query("SELECT * FROM PROF");
				$profs = $stmt->fetchAll(PDO::FETCH_ASSOC);
				echo json_encode(['success' => true, 'data' => $profs]);
			}
			break;

		case 'POST':
			// Créer un nouveau professeur
			$data = json_decode(file_get_contents('php://input'), true);

			if (!isset($data['nom']) || !isset($data['prenom']) || !isset($data['email'])) {
				http_response_code(400);
				echo json_encode(['success' => false, 'message' => 'Données manquantes']);
				exit;
			}

			$stmt = $conn->prepare("INSERT INTO PROF (nom, prenom, email) VALUES (?, ?, ?)");
			$stmt->execute([$data['nom'], $data['prenom'], $data['email']]);

			echo json_encode(['success' => true, 'message' => 'Professeur créé avec succès']);
			break;

		case 'PUT':
			// Mettre à jour un professeur
			if (!$id) {
				http_response_code(400);
				echo json_encode(['success' => false, 'message' => 'ID manquant']);
				exit;
			}

			$data = json_decode(file_get_contents('php://input'), true);

			if (!isset($data['nom']) || !isset($data['prenom']) || !isset($data['email'])) {
				http_response_code(400);
				echo json_encode(['success' => false, 'message' => 'Données manquantes']);
				exit;
			}

			$stmt = $conn->prepare("UPDATE PROF SET nom = ?, prenom = ?, email = ? WHERE id_prof = ?");
			$stmt->execute([$data['nom'], $data['prenom'], $data['email'], $id]);

			echo json_encode(['success' => true, 'message' => 'Professeur mis à jour avec succès']);
			break;

		case 'DELETE':
			// Supprimer un professeur
			if (!$id) {
				http_response_code(400);
				echo json_encode(['success' => false, 'message' => 'ID manquant']);
				exit;
			}

			$stmt = $conn->prepare("DELETE FROM PROF WHERE id_prof = ?");
			$stmt->execute([$id]);

			echo json_encode(['success' => true, 'message' => 'Professeur supprimé avec succès']);
			break;

		default:
			http_response_code(405);
			echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
			break;
	}
} catch (PDOException $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
} catch (Exception $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'message' => 'Une erreur est survenue sur le serveur']);
}
