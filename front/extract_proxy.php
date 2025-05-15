<?php
// Script pour extraire les fichiers proxy intégrés dans login.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Noms des fichiers à extraire
$files_to_extract = [
	'simple-proxy.php',
	'api-bridge.php',
	'direct-login.php',
	'local-proxy.php',
	'status.php'
];

// Fonction pour créer les répertoires si nécessaire
function ensureDirectoryExists($path)
{
	if (!is_dir($path)) {
		return mkdir($path, 0755, true);
	}
	return true;
}

// Extraction des contenus des fichiers proxy depuis login.php
$loginContent = file_get_contents(__DIR__ . '/login.php');

// Trouver la section où les fichiers proxy sont définis
preg_match('/\$proxy_files\s*=\s*\[(.*?)\];/s', $loginContent, $matches);

if (empty($matches)) {
	die("Impossible de trouver la section des fichiers proxy dans login.php");
}

$proxy_section = $matches[1];

// Extraire chaque fichier individuellement
$extracted = [];
$extractions_réussies = [];

foreach ($files_to_extract as $filename) {
	// Rechercher le fichier dans la section proxy
	preg_match("/'$filename'\s*=>\s*'(.*?)(?<!\\\\)',/s", $proxy_section, $file_matches);

	if (!empty($file_matches)) {
		$content = $file_matches[1];
		// Traiter les échappements spéciaux
		$content = str_replace("\\'", "'", $content);

		// Écrire le fichier
		if (file_put_contents(__DIR__ . '/' . $filename, $content)) {
			$extractions_réussies[] = $filename . " (taille: " . strlen($content) . " octets)";
		} else {
			$extracted[] = "❌ Erreur lors de l'écriture de $filename";
		}
	} else {
		$extracted[] = "⚠️ Impossible de trouver le contenu pour $filename";
	}
}

// Créer également des copies dans /api et /proxy
$directories = ['/api', '/proxy'];
foreach ($directories as $dir) {
	$dirPath = __DIR__ . $dir;
	if (ensureDirectoryExists($dirPath)) {
		foreach ($extractions_réussies as $filename) {
			$orig_filename = explode(" ", $filename)[0]; // Extraire juste le nom du fichier
			if (copy(__DIR__ . '/' . $orig_filename, $dirPath . '/' . $orig_filename)) {
				$extracted[] = "✅ Copié $orig_filename vers $dir/";
			}
		}
	} else {
		$extracted[] = "❌ Impossible de créer le répertoire $dir";
	}
}

// Générer une réponse HTML
?>
<!DOCTYPE html>
<html>

<head>
	<title>Extraction des fichiers proxy</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			max-width: 800px;
			margin: 0 auto;
			padding: 20px;
		}

		h1 {
			color: #0078D4;
		}

		.success {
			color: green;
		}

		.warning {
			color: orange;
		}

		.error {
			color: red;
		}

		pre {
			background-color: #f5f5f5;
			padding: 10px;
			border-radius: 4px;
		}

		.card {
			border: 1px solid #ddd;
			border-radius: 4px;
			padding: 15px;
			margin-bottom: 20px;
		}

		.actions {
			margin-top: 20px;
		}

		.btn {
			display: inline-block;
			padding: 8px 16px;
			background-color: #0078D4;
			color: white;
			text-decoration: none;
			border: none;
			border-radius: 4px;
			cursor: pointer;
			margin-right: 10px;
		}
	</style>
</head>

<body>
	<h1>Extraction des fichiers proxy</h1>

	<div class="card">
		<h2>Résultats de l'extraction</h2>
		<ul>
			<?php foreach ($extractions_réussies as $file): ?>
				<li class="success">✅ Extrait : <?= htmlspecialchars($file) ?></li>
			<?php endforeach; ?>

			<?php foreach ($extracted as $result): ?>
				<li class="<?= strpos($result, '✅') === 0 ? 'success' : (strpos($result, '⚠️') === 0 ? 'warning' : 'error') ?>">
					<?= htmlspecialchars($result) ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>

	<div class="actions">
		<a href="azure-diagnostics.php" class="btn">Exécuter l'outil de diagnostic</a>
		<a href="login.php" class="btn">Tester la page de connexion</a>
		<a href="/" class="btn">Page d'accueil</a>
	</div>
</body>

</html>