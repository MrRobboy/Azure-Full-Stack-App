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
				// Récupérer un examen spécifique avec sa matière et sa classe
				$stmt = $conn->prepare("
                    SELECT e.*, m.nom as nom_matiere, c.nom_classe, c.niveau, c.numero 
                    FROM EXAM e
                    LEFT JOIN MATIERE m ON e.matiere = m.id_matiere
                    LEFT JOIN CLASSE c ON e.classe = c.id_classe
                    WHERE e.id_exam = ?
                ");
				$stmt->execute([$id]);
				$exam = $stmt->fetch(PDO::FETCH_ASSOC);

				if ($exam) {
					echo json_encode(['success' => true, 'data' => $exam]);
				} else {
					http_response_code(404);
					echo json_encode(['success' => false, 'message' => 'Examen non trouvé']);
				}
			} else {
				// Récupérer tous les examens avec leurs matières et classes
				$stmt = $conn->query("
                    SELECT e.*, m.nom as nom_matiere, c.nom_classe, c.niveau, c.numero 
                    FROM EXAM e
                    LEFT JOIN MATIERE m ON e.matiere = m.id_matiere
                    LEFT JOIN CLASSE c ON e.classe = c.id_classe
                    ORDER BY e.date DESC
                ");
				$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
				echo json_encode(['success' => true, 'data' => $exams]);
			}
			break;

		case 'POST':
			// Créer un nouvel examen
			$data = json_decode(file_get_contents('php://input'), true);

			if (!isset($data['titre']) || !isset($data['matiere']) || !isset($data['classe']) || !isset($data['date'])) {
				http_response_code(400);
				echo json_encode(['success' => false, 'message' => 'Données manquantes']);
				exit;
			}

			$stmt = $conn->prepare("INSERT INTO EXAM (titre, matiere, classe, date) VALUES (?, ?, ?, ?)");
			$stmt->execute([$data['titre'], $data['matiere'], $data['classe'], $data['date']]);

			echo json_encode(['success' => true, 'message' => 'Examen créé avec succès']);
			break;

		case 'PUT':
			// Mettre à jour un examen
			if (!$id) {
				http_response_code(400);
				echo json_encode(['success' => false, 'message' => 'ID manquant']);
				exit;
			}

			$data = json_decode(file_get_contents('php://input'), true);

			if (!isset($data['titre']) || !isset($data['matiere']) || !isset($data['classe']) || !isset($data['date'])) {
				http_response_code(400);
				echo json_encode(['success' => false, 'message' => 'Données manquantes']);
				exit;
			}

			$stmt = $conn->prepare("UPDATE EXAM SET titre = ?, matiere = ?, classe = ?, date = ? WHERE id_exam = ?");
			$stmt->execute([$data['titre'], $data['matiere'], $data['classe'], $data['date'], $id]);

			echo json_encode(['success' => true, 'message' => 'Examen mis à jour avec succès']);
			break;

		case 'DELETE':
			// Supprimer un examen
			if (!$id) {
				http_response_code(400);
				echo json_encode(['success' => false, 'message' => 'ID manquant']);
				exit;
			}

			$stmt = $conn->prepare("DELETE FROM EXAM WHERE id_exam = ?");
			$stmt->execute([$id]);

			echo json_encode(['success' => true, 'message' => 'Examen supprimé avec succès']);
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
