<?php

/**
 * OUTIL DE DIAGNOSTIC ET CORRECTION DES CLASSES
 * Cet outil permet de diagnostiquer les problèmes avec les classes
 * et de forcer leur suppression directement dans la base de données
 */

header('Content-Type: text/html; charset=utf-8');
session_start();

// Configuration
$backendBaseUrl = "https://app-backend-esgi-app.azurewebsites.net";
$classeId = isset($_GET['id']) ? intval($_GET['id']) : 10;
$action = isset($_GET['action']) ? $_GET['action'] : 'info';

// Traiter le formulaire de connexion
if (isset($_POST['db_connect'])) {
	$_SESSION['db_host'] = $_POST['db_host'];
	$_SESSION['db_name'] = $_POST['db_name'];
	$_SESSION['db_user'] = $_POST['db_user'];
	$_SESSION['db_pass'] = $_POST['db_pass'];

	// Rediriger pour éviter la résoumission du formulaire
	header("Location: {$_SERVER['PHP_SELF']}?action=list");
	exit;
}

// Traiter la déconnexion
if (isset($_GET['logout'])) {
	unset($_SESSION['db_host']);
	unset($_SESSION['db_name']);
	unset($_SESSION['db_user']);
	unset($_SESSION['db_pass']);

	// Rediriger vers la page d'accueil
	header("Location: {$_SERVER['PHP_SELF']}");
	exit;
}

// Définir les entêtes HTML
echo "<!DOCTYPE html>
<html>
<head>
	<meta charset='utf-8'>
	<title>Diagnostic et Correction des Classes</title>
	<style>
		body { font-family: Arial, sans-serif; margin: 20px; }
		pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
		.success { color: green; font-weight: bold; }
		.error { color: red; font-weight: bold; }
		.warning { color: orange; font-weight: bold; }
		.info { color: blue; }
		.container { max-width: 900px; margin: 0 auto; }
		.actions { margin: 20px 0; }
		.actions a { display: inline-block; margin-right: 10px; padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
		.actions a.danger { background: #dc3545; }
		.actions a:hover { opacity: 0.8; }
		table { width: 100%; border-collapse: collapse; margin: 20px 0; }
		table, th, td { border: 1px solid #ddd; }
		th, td { padding: 10px; text-align: left; }
		th { background-color: #f5f5f5; }
		form { background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
		label { display: block; margin-bottom: 5px; font-weight: bold; }
		input[type=text], input[type=password] { width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; }
		button { padding: 10px 15px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
		button:hover { background: #218838; }
		.logout { position: absolute; top: 20px; right: 20px; }
	</style>
</head>
<body>
	<div class='container'>
		<h1>Diagnostic et Correction des Classes</h1>";

// Afficher un lien de déconnexion si connecté
if (isset($_SESSION['db_user'])) {
	echo "<div class='logout'><a href='?logout=1' class='danger'>Déconnexion</a></div>";
}

// Fonction pour les logs HTML
function logMessage($message, $type = 'info')
{
	echo "<div class='$type'>" . htmlspecialchars($message) . "</div>";
}

// Fonction pour exécuter une requête SQL directe
function executeSQL($sql, $params = [])
{
	// Utiliser les identifiants de la session
	global $_SESSION;

	if (
		!isset($_SESSION['db_host']) || !isset($_SESSION['db_name']) ||
		!isset($_SESSION['db_user']) || !isset($_SESSION['db_pass'])
	) {
		logMessage("Erreur: Informations de connexion manquantes", "error");
		return [
			'success' => false,
			'error' => 'Informations de connexion à la base de données manquantes'
		];
	}

	// Connexion directe à la base de données
	try {
		$host = $_SESSION['db_host'];
		$dbname = $_SESSION['db_name'];
		$username = $_SESSION['db_user'];
		$password = $_SESSION['db_pass'];

		$dsn = "sqlsrv:Server=$host;Database=$dbname";
		$options = [
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES => false,
		];

		$pdo = new PDO($dsn, $username, $password, $options);

		// Préparation et exécution de la requête
		$stmt = $pdo->prepare($sql);
		$stmt->execute($params);

		// Retourne différents résultats selon le type de requête
		if (stripos($sql, 'SELECT') === 0) {
			return $stmt->fetchAll();
		} elseif (stripos($sql, 'INSERT') === 0 || stripos($sql, 'UPDATE') === 0 || stripos($sql, 'DELETE') === 0) {
			return [
				'success' => true,
				'rowCount' => $stmt->rowCount(),
				'message' => "Requête exécutée avec succès. " . $stmt->rowCount() . " ligne(s) affectée(s)."
			];
		}

		return true;
	} catch (PDOException $e) {
		logMessage("Erreur SQL: " . $e->getMessage(), "error");
		return [
			'success' => false,
			'error' => $e->getMessage()
		];
	}
}

// Fonction pour récupérer une classe par ID
function getClasseById($id)
{
	$result = executeSQL("SELECT * FROM CLASSE WHERE id_classe = ?", [$id]);
	return $result;
}

// Fonction pour lister toutes les classes
function getAllClasses()
{
	return executeSQL("SELECT * FROM CLASSE ORDER BY nom_classe");
}

// Fonction pour vérifier les références à une classe
function checkClassReferences($id)
{
	$references = [];

	// Vérifier les élèves associés
	$result = executeSQL("SELECT COUNT(*) AS count FROM USER WHERE classe = ?", [$id]);
	$references['users'] = $result[0]['count'] ?? 0;

	// Vérifier les examens associés
	$result = executeSQL("SELECT COUNT(*) AS count FROM EXAM WHERE classe = ?", [$id]);
	$references['exams'] = $result[0]['count'] ?? 0;

	// Vérifier d'autres tables potentielles (à compléter selon le schéma de la BDD)

	return $references;
}

// Fonction pour supprimer une classe en force (BY FORCE)
function forceDeleteClass($id)
{
	// Utiliser les identifiants de la session
	global $_SESSION;

	if (
		!isset($_SESSION['db_host']) || !isset($_SESSION['db_name']) ||
		!isset($_SESSION['db_user']) || !isset($_SESSION['db_pass'])
	) {
		logMessage("Erreur: Informations de connexion manquantes", "error");
		return [
			'success' => false,
			'error' => 'Informations de connexion à la base de données manquantes'
		];
	}

	// Commencer une transaction
	try {
		$host = $_SESSION['db_host'];
		$dbname = $_SESSION['db_name'];
		$username = $_SESSION['db_user'];
		$password = $_SESSION['db_pass'];

		$dsn = "sqlsrv:Server=$host;Database=$dbname";
		$pdo = new PDO($dsn, $username, $password);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$pdo->beginTransaction();

		// 1. Vérifier les références et les nettoyer si nécessaire
		$references = checkClassReferences($id);
		$referencesCleared = false;

		if ($references['users'] > 0) {
			logMessage("Désassociation de {$references['users']} utilisateur(s) de la classe", "warning");
			$stmt = $pdo->prepare("UPDATE USER SET classe = NULL WHERE classe = ?");
			$stmt->execute([$id]);
			logMessage("{$stmt->rowCount()} utilisateur(s) désassocié(s)", "success");
			$referencesCleared = true;
		}

		if ($references['exams'] > 0) {
			logMessage("Suppression de {$references['exams']} examen(s) associé(s) à la classe", "warning");
			$stmt = $pdo->prepare("DELETE FROM EXAM WHERE classe = ?");
			$stmt->execute([$id]);
			logMessage("{$stmt->rowCount()} examen(s) supprimé(s)", "success");
			$referencesCleared = true;
		}

		// 2. Supprimer directement la classe
		$stmt = $pdo->prepare("DELETE FROM CLASSE WHERE id_classe = ?");
		$stmt->execute([$id]);
		$rowCount = $stmt->rowCount();

		if ($rowCount > 0) {
			$pdo->commit();
			logMessage("SUPPRESSION FORCÉE RÉUSSIE: Classe ID $id supprimée avec succès ($rowCount ligne(s) affectée(s))", "success");
			return [
				'success' => true,
				'rowCount' => $rowCount,
				'referencesCleared' => $referencesCleared
			];
		} else {
			$pdo->rollBack();
			logMessage("ÉCHEC: Aucune ligne supprimée pour la classe ID $id", "error");
			return [
				'success' => false,
				'message' => "Aucune ligne supprimée. La classe n'existe peut-être pas."
			];
		}
	} catch (PDOException $e) {
		if (isset($pdo) && $pdo->inTransaction()) {
			$pdo->rollBack();
		}
		logMessage("ERREUR CRITIQUE: " . $e->getMessage(), "error");
		return [
			'success' => false,
			'error' => $e->getMessage()
		];
	}
}

// Vérifier si l'utilisateur est connecté à la base de données
if (
	!isset($_SESSION['db_host']) || !isset($_SESSION['db_name']) ||
	!isset($_SESSION['db_user']) || !isset($_SESSION['db_pass'])
) {

	// Afficher le formulaire de connexion
	echo "<h2>Connexion à la base de données</h2>
	<p>Veuillez entrer les informations de connexion à la base de données pour continuer.</p>
	<form method='post' action=''>
		<div>
			<label for='db_host'>Hôte de la base de données:</label>
			<input type='text' id='db_host' name='db_host' value='sql-esgi-app.database.windows.net' required>
		</div>
		<div>
			<label for='db_name'>Nom de la base de données:</label>
			<input type='text' id='db_name' name='db_name' value='sqldb-esgi-app' required>
		</div>
		<div>
			<label for='db_user'>Nom d'utilisateur:</label>
			<input type='text' id='db_user' name='db_user' required>
		</div>
		<div>
			<label for='db_pass'>Mot de passe:</label>
			<input type='password' id='db_pass' name='db_pass' required>
		</div>
		<button type='submit' name='db_connect'>Se connecter</button>
	</form>";
} else {
	// L'utilisateur est connecté, afficher les actions et le contenu
	logMessage("Connecté à la base de données " . $_SESSION['db_name'] . " en tant que " . $_SESSION['db_user'], "success");

	// ACTIONS PRINCIPALES
	echo "<div class='actions'>
		<a href='?action=info&id={$classeId}'>Informations</a>
		<a href='?action=list'>Liste des classes</a>
		<a href='?action=check&id={$classeId}'>Vérifier les références</a>
		<a href='?action=force_delete&id={$classeId}' class='danger' onclick='return confirm(\"ATTENTION: Cette action supprimera définitivement la classe et pourrait modifier les données associées. Continuer?\")'>Supprimer en force</a>
	</div>";

	// Exécution de l'action demandée
	switch ($action) {
		case 'info':
			echo "<h2>Informations sur la classe ID: {$classeId}</h2>";
			$classe = getClasseById($classeId);

			if (empty($classe)) {
				logMessage("Aucune classe trouvée avec l'ID {$classeId}", "warning");
			} else {
				echo "<table>
					<tr><th>Propriété</th><th>Valeur</th></tr>";
				foreach ($classe[0] as $key => $value) {
					echo "<tr><td>" . htmlspecialchars($key) . "</td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
				}
				echo "</table>";
			}
			break;

		case 'list':
			echo "<h2>Liste de toutes les classes</h2>";
			$classes = getAllClasses();

			if (empty($classes)) {
				logMessage("Aucune classe trouvée dans la base de données", "warning");
			} else {
				echo "<table>
					<tr><th>ID</th><th>Nom</th><th>Niveau</th><th>Numéro</th><th>Rythme</th><th>Actions</th></tr>";
				foreach ($classes as $classe) {
					echo "<tr>
						<td>" . htmlspecialchars($classe['id_classe']) . "</td>
						<td>" . htmlspecialchars($classe['nom_classe']) . "</td>
						<td>" . htmlspecialchars($classe['niveau']) . "</td>
						<td>" . htmlspecialchars($classe['numero']) . "</td>
						<td>" . htmlspecialchars($classe['rythme']) . "</td>
						<td>
							<a href='?action=info&id={$classe['id_classe']}'>Info</a> |
							<a href='?action=check&id={$classe['id_classe']}'>Vérifier</a> |
							<a href='?action=force_delete&id={$classe['id_classe']}' onclick='return confirm(\"ATTENTION: Supprimer définitivement?\")' style='color:red'>Supprimer</a>
						</td>
					</tr>";
				}
				echo "</table>";
			}
			break;

		case 'check':
			echo "<h2>Vérification des références pour la classe ID: {$classeId}</h2>";

			// D'abord vérifier si la classe existe
			$classe = getClasseById($classeId);
			if (empty($classe)) {
				logMessage("Aucune classe trouvée avec l'ID {$classeId}", "warning");
				break;
			}

			$references = checkClassReferences($classeId);

			echo "<h3>Résultats de la vérification</h3>";
			echo "<table>
				<tr><th>Table</th><th>Nombre de références</th><th>État</th></tr>
				<tr>
					<td>USER (élèves)</td>
					<td>{$references['users']}</td>
					<td>" . ($references['users'] > 0 ? "<span class='warning'>Références trouvées</span>" : "<span class='success'>Aucune référence</span>") . "</td>
				</tr>
				<tr>
					<td>EXAM (examens)</td>
					<td>{$references['exams']}</td>
					<td>" . ($references['exams'] > 0 ? "<span class='warning'>Références trouvées</span>" : "<span class='success'>Aucune référence</span>") . "</td>
				</tr>
			</table>";

			if ($references['users'] > 0 || $references['exams'] > 0) {
				logMessage("Cette classe a des références dans d'autres tables, ce qui peut empêcher sa suppression normale.", "warning");
				echo "<p><a href='?action=force_delete&id={$classeId}' class='danger' onclick='return confirm(\"ATTENTION: Cette action supprimera définitivement la classe et désassociera/supprimera les données liées. Continuer?\")'>Supprimer en force (nettoiera les références)</a></p>";
			} else {
				logMessage("Aucune référence trouvée. La classe devrait pouvoir être supprimée normalement.", "success");
			}
			break;

		case 'force_delete':
			echo "<h2>Suppression forcée de la classe ID: {$classeId}</h2>";

			// D'abord vérifier si la classe existe
			$classe = getClasseById($classeId);
			if (empty($classe)) {
				logMessage("Aucune classe trouvée avec l'ID {$classeId}. Rien à supprimer.", "warning");
				break;
			}

			echo "<div style='background:#ffe6e6; padding:15px; border-radius:5px; margin-bottom:20px;'>
				<h3 style='color:#cc0000'>ATTENTION: OPÉRATION DESTRUCTIVE</h3>
				<p>Vous êtes sur le point de forcer la suppression de la classe suivante:</p>
				<ul>
					<li><strong>ID:</strong> {$classe[0]['id_classe']}</li>
					<li><strong>Nom:</strong> {$classe[0]['nom_classe']}</li>
					<li><strong>Niveau:</strong> {$classe[0]['niveau']}</li>
					<li><strong>Numéro:</strong> {$classe[0]['numero']}</li>
				</ul>
			</div>";

			// Vérifier une dernière fois avec l'utilisateur
			if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
				$result = forceDeleteClass($classeId);
				if ($result['success']) {
					echo "<div class='success' style='padding:15px; margin:20px 0;'>
						<h3>SUPPRESSION RÉUSSIE!</h3>
						<p>La classe a été définitivement supprimée de la base de données.</p>
						" . ($result['referencesCleared'] ? "<p>Des références dans d'autres tables ont été nettoyées.</p>" : "") . "
					</div>";
				} else {
					echo "<div class='error' style='padding:15px; margin:20px 0;'>
						<h3>ÉCHEC DE LA SUPPRESSION</h3>
						<p>" . ($result['message'] ?? $result['error'] ?? "Une erreur inconnue s'est produite") . "</p>
					</div>";
				}
			} else {
				echo "<p>Cliquez sur le bouton ci-dessous pour confirmer la suppression forcée:</p>
				<div class='actions'>
					<a href='?action=force_delete&id={$classeId}&confirm=yes' class='danger'>CONFIRMER LA SUPPRESSION FORCÉE</a>
					<a href='?action=info&id={$classeId}'>Annuler</a>
				</div>";
			}
			break;

		default:
			logMessage("Action non reconnue: {$action}", "error");
	}
}

echo "</div></body></html>";
