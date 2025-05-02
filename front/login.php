<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = $_POST['email'];
	$password = $_POST['password'];

	// Vérification des identifiants
	$stmt = $pdo->prepare("SELECT * FROM PROF WHERE email = ?");
	$stmt->execute([$email]);
	$prof = $stmt->fetch();

	if ($prof && password_verify($password, $prof['password'])) {
		$_SESSION['prof_id'] = $prof['id_prof'];
		$_SESSION['prof_nom'] = $prof['nom'];
		$_SESSION['prof_prenom'] = $prof['prenom'];

		header('Location: dashboard.php');
		exit();
	} else {
		$error = "Identifiants incorrects";
	}
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Connexion - Système de Gestion des Notes</title>
	<link rel="stylesheet" href="style.css">
</head>

<body>
	<div class="container">
		<div class="login-form">
			<h1>Système de Gestion des Notes</h1>
			<?php if (isset($error)): ?>
				<div class="alert alert-error"><?php echo $error; ?></div>
			<?php endif; ?>
			<form action="login.php" method="POST">
				<div class="form-group">
					<label for="email">Email :</label>
					<input type="email" id="email" name="email" required>
				</div>
				<div class="form-group">
					<label for="password">Mot de passe :</label>
					<input type="password" id="password" name="password" required>
				</div>
				<button type="submit" class="btn">Se connecter</button>
			</form>
		</div>
	</div>
</body>

</html>