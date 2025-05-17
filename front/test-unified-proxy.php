<?php

/**
 * Test du proxy unifié
 * Ce script permet de tester toutes les fonctionnalités du proxy unifié
 */

// En-têtes pour le rendu HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Test du Proxy Unifié</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			max-width: 1200px;
			margin: 0 auto;
			padding: 20px;
			line-height: 1.6;
		}

		h1,
		h2,
		h3 {
			color: #333;
		}

		.test-card {
			border: 1px solid #ddd;
			border-radius: 5px;
			padding: 15px;
			margin-bottom: 20px;
			background-color: #f9f9f9;
		}

		.test-status {
			margin-top: 10px;
			padding: 10px;
			border-radius: 5px;
		}

		.success {
			background-color: #d4edda;
			color: #155724;
		}

		.error {
			background-color: #f8d7da;
			color: #721c24;
		}

		.pending {
			background-color: #fff3cd;
			color: #856404;
		}

		button {
			background-color: #007bff;
			color: white;
			border: none;
			padding: 10px 15px;
			border-radius: 5px;
			cursor: pointer;
		}

		button:hover {
			background-color: #0056b3;
		}

		pre {
			background-color: #f5f5f5;
			padding: 10px;
			border-radius: 5px;
			overflow: auto;
			max-height: 300px;
		}
	</style>
</head>

<body>
	<h1>Test du Proxy Unifié</h1>
	<p>Cette page permet de tester toutes les fonctionnalités du proxy unifié pour s'assurer qu'il fonctionne correctement.</p>

	<div class="test-card">
		<h2>1. Test de statut</h2>
		<p>Vérifie que le proxy peut accéder à l'endpoint de statut du backend.</p>
		<button id="test-status">Exécuter le test</button>
		<div id="status-result" class="test-status pending">En attente...</div>
		<pre id="status-details"></pre>
	</div>

	<div class="test-card">
		<h2>2. Test d'authentification</h2>
		<p>Vérifie que le proxy peut authentifier un utilisateur.</p>
		<div>
			<input type="email" id="auth-email" placeholder="Email" value="admin@example.com">
			<input type="password" id="auth-password" placeholder="Mot de passe" value="admin123">
		</div>
		<button id="test-auth">Exécuter le test</button>
		<div id="auth-result" class="test-status pending">En attente...</div>
		<pre id="auth-details"></pre>
	</div>

	<div class="test-card">
		<h2>3. Test de récupération de données (GET)</h2>
		<p>Vérifie que le proxy peut récupérer des données via une requête GET.</p>
		<button id="test-get">Exécuter le test</button>
		<div id="get-result" class="test-status pending">En attente...</div>
		<pre id="get-details"></pre>
	</div>

	<div class="test-card">
		<h2>4. Test d'envoi de données (POST)</h2>
		<p>Vérifie que le proxy peut envoyer des données via une requête POST.</p>
		<button id="test-post">Exécuter le test</button>
		<div id="post-result" class="test-status pending">En attente...</div>
		<pre id="post-details"></pre>
	</div>

	<div class="test-section">
		<h2>5. Test de l'outil de diagnostic d'URL</h2>
		<p>Vérifie les différentes constructions d'URL pour identifier la meilleure approche.</p>

		<div class="action-buttons">
			<button id="url-debug-btn" class="btn btn-info">Exécuter le test d'URL</button>
		</div>

		<div id="url-debug-result" class="result-container">
			<div class="waiting">En attente de l'exécution du test...</div>
		</div>
	</div>

	<h2>Résumé des tests</h2>
	<div id="test-summary" class="test-status pending">Aucun test exécuté</div>

	<script src="js/config.js?v=5.0"></script>
	<script src="js/api-service.js?v=2.0"></script>
	<script src="js/debug-utils.js"></script>
	<script>
		// Fonction utilitaire pour mettre à jour un résultat de test
		function updateTestResult(id, success, message, details = null) {
			const resultElement = document.getElementById(`${id}-result`);
			const detailsElement = document.getElementById(`${id}-details`);

			resultElement.className = `test-status ${success ? 'success' : 'error'}`;
			resultElement.textContent = message;

			if (details) {
				detailsElement.textContent = typeof details === 'string' ?
					details :
					JSON.stringify(details, null, 2);
			}

			updateTestSummary();
		}

		// Fonction pour mettre à jour le résumé des tests
		function updateTestSummary() {
			const statusElements = document.querySelectorAll('.test-status:not(#test-summary)');
			let successes = 0;
			let failures = 0;
			let pending = 0;

			statusElements.forEach(element => {
				if (element.classList.contains('success')) successes++;
				else if (element.classList.contains('error')) failures++;
				else if (element.classList.contains('pending')) pending++;
			});

			const summaryElement = document.getElementById('test-summary');

			if (pending === statusElements.length) {
				summaryElement.className = 'test-status pending';
				summaryElement.textContent = 'Aucun test exécuté';
				return;
			}

			if (failures === 0 && pending === 0) {
				summaryElement.className = 'test-status success';
				summaryElement.textContent = 'Tous les tests ont réussi!';
			} else if (failures > 0) {
				summaryElement.className = 'test-status error';
				summaryElement.textContent = `${failures} test(s) en échec, ${successes} test(s) réussis, ${pending} test(s) en attente`;
			} else {
				summaryElement.className = 'test-status pending';
				summaryElement.textContent = `${successes} test(s) réussis, ${pending} test(s) en attente`;
			}
		}

		// Test 1: Statut du backend
		document.getElementById('test-status').addEventListener('click', async () => {
			try {
				const result = await ApiService.request('status');
				console.log('Status test result:', result);

				if (result.success && result.data.success) {
					updateTestResult('status', true, 'Le proxy a bien accédé au statut du backend', result.data);
				} else {
					updateTestResult('status', false, 'Échec de l\'accès au statut', result);
				}
			} catch (error) {
				console.error('Status test error:', error);
				updateTestResult('status', false, `Erreur: ${error.message}`);
			}
		});

		// Test 2: Authentification
		document.getElementById('test-auth').addEventListener('click', async () => {
			const email = document.getElementById('auth-email').value;
			const password = document.getElementById('auth-password').value;

			try {
				const result = await ApiService.login(email, password);
				console.log('Auth test result:', result);

				if (result.success && result.data.success) {
					updateTestResult('auth', true, 'Authentification réussie', result.data);
				} else {
					updateTestResult('auth', false, 'Échec de l\'authentification', result.data);
				}
			} catch (error) {
				console.error('Auth test error:', error);
				updateTestResult('auth', false, `Erreur: ${error.message}`);
			}
		});

		// Test 3: Récupération de données (GET)
		document.getElementById('test-get').addEventListener('click', async () => {
			try {
				// Tester plusieurs endpoints pour voir lesquels fonctionnent
				const endpoints = ['matieres', 'classes', 'examens', 'profs'];
				let successfulEndpoint = null;
				let result = null;

				for (const endpoint of endpoints) {
					console.log(`Trying GET request to ${endpoint}...`);
					result = await ApiService.request(endpoint);

					if (result.success && result.data.success) {
						successfulEndpoint = endpoint;
						break;
					}
				}

				if (successfulEndpoint) {
					updateTestResult('get', true, `Récupération réussie des données de ${successfulEndpoint}`, result.data);
				} else {
					updateTestResult('get', false, 'Échec de la récupération des données', result);
				}
			} catch (error) {
				console.error('GET test error:', error);
				updateTestResult('get', false, `Erreur: ${error.message}`);
			}
		});

		// Test 4: Envoi de données (POST)
		document.getElementById('test-post').addEventListener('click', async () => {
			try {
				// Tester l'envoi de données via POST
				// Note: Ceci est un test qui peut échouer si les données ne sont pas valides
				// pour le backend, mais nous voulons juste tester que le proxy transmet la requête.
				const testData = {
					nom: "Test Proxy Unifié",
					description: `Test créé le ${new Date().toISOString()}`
				};

				const result = await ApiService.request('matieres', 'POST', testData);
				console.log('POST test result:', result);

				// Même si le backend renvoie une erreur, nous considérons le test comme réussi
				// si le proxy a correctement transmis la requête.
				if (result.status !== 0) {
					updateTestResult('post', true, 'Le proxy a correctement transmis la requête POST', {
						sent: testData,
						received: result
					});
				} else {
					updateTestResult('post', false, 'Échec de la transmission de la requête POST', result);
				}
			} catch (error) {
				console.error('POST test error:', error);
				updateTestResult('post', false, `Erreur: ${error.message}`);
			}
		});

		// Ajout du test d'URL
		document.getElementById('url-debug-btn').addEventListener('click', async function() {
			const resultContainer = document.getElementById('url-debug-result');
			resultContainer.innerHTML = '<div class="loading">Test en cours...</div>';

			try {
				const response = await fetch('url-debug.php');
				const data = await response.json();

				let html = '<div class="success">Test d\'URL complété</div>';
				html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';

				resultContainer.innerHTML = html;
			} catch (error) {
				resultContainer.innerHTML = '<div class="error">Erreur: ' + error.message + '</div>';
			}
		});

		// Log des résultats détaillés dans la console
		window.addEventListener('load', function() {
			console.log('🔍 Débogage activé. Ouvrez la console pour plus de détails.');
			console.log('📋 Instructions: Utilisez le bouton Debug en bas à droite pour voir les détails des requêtes');
		});
	</script>
</body>

</html>