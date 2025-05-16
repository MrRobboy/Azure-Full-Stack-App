<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Diagnostic des APIs</title>
	<style>
		body {
			font-family: Arial, sans-serif;
			max-width: 1100px;
			margin: 0 auto;
			padding: 20px;
		}

		.card {
			border: 1px solid #ddd;
			border-radius: 8px;
			padding: 20px;
			margin-bottom: 20px;
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
		}

		pre {
			background-color: #f5f5f5;
			padding: 10px;
			border-radius: 4px;
			overflow-x: auto;
			max-height: 300px;
			overflow-y: auto;
		}

		button {
			background-color: #4CAF50;
			color: white;
			border: none;
			padding: 10px 15px;
			border-radius: 4px;
			cursor: pointer;
			margin-right: 10px;
			margin-bottom: 10px;
		}

		button:hover {
			background-color: #45a049;
		}

		.error {
			color: red;
		}

		.success {
			color: green;
		}

		.warning {
			color: orange;
		}

		table {
			width: 100%;
			border-collapse: collapse;
			margin-bottom: 20px;
		}

		table,
		th,
		td {
			border: 1px solid #ddd;
		}

		th,
		td {
			padding: 12px;
			text-align: left;
		}

		th {
			background-color: #f2f2f2;
		}

		tr:nth-child(even) {
			background-color: #f9f9f9;
		}

		.status-box {
			display: inline-block;
			width: 20px;
			height: 20px;
			border-radius: 50%;
			margin-right: 5px;
			vertical-align: middle;
		}

		.status-success {
			background-color: #4CAF50;
		}

		.status-error {
			background-color: #f44336;
		}

		.status-warning {
			background-color: #ff9800;
		}

		.status-pending {
			background-color: #9e9e9e;
		}
	</style>
</head>

<body>
	<h1>Diagnostic des APIs</h1>

	<div class="card">
		<h2>Description</h2>
		<p>
			Cet outil teste tous les endpoints du backend via le proxy amélioré et affiche les résultats.
			Il vous permet de vérifier la communication avec le backend et d'identifier les éventuels problèmes.
		</p>
	</div>

	<div class="card" id="troubleshooting" style="display: none; border-color: #ff9800;">
		<h2>⚠️ Guide de dépannage</h2>
		<p id="troubleshootingText">Des problèmes ont été détectés avec la configuration.</p>
		<div id="troubleshootingSteps">
			<h3>Vérifications à effectuer:</h3>
			<ol>
				<li>Vérifiez que le fichier <code>enhanced-proxy.php</code> existe dans le même répertoire que cette page</li>
				<li>Ouvrez <a href="enhanced-proxy.php?endpoint=status.php" target="_blank">enhanced-proxy.php?endpoint=status.php</a> directement pour tester le proxy</li>
				<li>Consultez les logs d'erreur dans le répertoire <code>logs/</code></li>
			</ol>
		</div>
	</div>

	<div class="card">
		<h2>Authentification</h2>
		<p>Un token JWT valide est nécessaire pour tester les endpoints protégés.</p>
		<div class="form-group">
			<label for="tokenInput">JWT Token (si vous en avez déjà un):</label>
			<input type="text" id="tokenInput" style="width: 80%;" placeholder="eyJ0eXAiOiJKV1QiLCJhbGci...">
			<button onclick="useStoredToken()">Utiliser le token du localStorage</button>
			<button onclick="saveTokenInput()">Sauvegarder ce token</button>
		</div>
	</div>

	<div class="card">
		<h2>Endpoints disponibles</h2>
		<table id="endpointsTable">
			<thead>
				<tr>
					<th>Endpoint</th>
					<th>Type</th>
					<th>Méthode</th>
					<th>URL complète</th>
					<th>Statut</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<!-- Les endpoints seront ajoutés ici par JavaScript -->
			</tbody>
		</table>
		<button onclick="testAllEndpoints()">Tester tous les endpoints</button>
	</div>

	<div class="card">
		<h2>Résultat détaillé</h2>
		<div>
			<select id="resultEndpoint" style="width: 300px; padding: 8px; margin-bottom: 10px;">
				<option value="">Sélectionnez un endpoint pour voir les détails...</option>
			</select>
		</div>
		<pre id="resultOutput" style="min-height: 200px;">Sélectionnez un endpoint pour voir les résultats détaillés...</pre>
	</div>

	<script>
		// Information sur l'environnement
		console.log("Base URL: " + window.location.origin);
		console.log("Path: " + window.location.pathname);

		// Fonction pour vérifier l'accès au proxy
		async function checkProxyAccess() {
			try {
				const response = await fetch('enhanced-proxy.php?endpoint=status.php');
				console.log("Proxy check status:", response.status);
				return response.status >= 200 && response.status < 400;
			} catch (error) {
				console.error("Erreur de connexion au proxy:", error);
				return false;
			}
		}

		// Vérifier l'accès au démarrage
		window.addEventListener('DOMContentLoaded', async function() {
			// Vérifier si le proxy est accessible
			const proxyOk = await checkProxyAccess();
			if (!proxyOk) {
				document.getElementById('troubleshooting').style.display = 'block';
				document.getElementById('troubleshootingText').innerHTML =
					"<strong>Le proxy amélioré n'est pas accessible.</strong> Vérifiez que le fichier enhanced-proxy.php existe dans le même répertoire que cette page et qu'il fonctionne correctement.";
			}

			initEndpointsTable();

			// Essayer de récupérer le token du localStorage au chargement
			const token = localStorage.getItem('jwt_token');
			if (token) {
				document.getElementById('tokenInput').value = token;
			}
		});

		// Définition des endpoints à tester
		const endpoints = [
			// Endpoints publics
			{
				path: 'status.php',
				type: 'Public',
				method: 'GET',
				description: 'Statut du serveur'
			},
			{
				path: 'api-auth-login.php',
				type: 'Public',
				method: 'POST',
				description: 'Authentification',
				body: {
					email: 'admin@example.com',
					password: 'password123'
				}
			},

			// Endpoints REST (protégés)
			{
				path: 'api/users',
				type: 'Protégé',
				method: 'GET',
				description: 'Liste des utilisateurs'
			},
			{
				path: 'api/notes',
				type: 'Protégé',
				method: 'GET',
				description: 'Liste des notes'
			},
			{
				path: 'api/classes',
				type: 'Protégé',
				method: 'GET',
				description: 'Liste des classes'
			},
			{
				path: 'api/profs',
				type: 'Protégé',
				method: 'GET',
				description: 'Liste des professeurs'
			},
			{
				path: 'api/matieres',
				type: 'Protégé',
				method: 'GET',
				description: 'Liste des matières'
			},
			{
				path: 'api/examens',
				type: 'Protégé',
				method: 'GET',
				description: 'Liste des examens'
			},
			{
				path: 'api/privileges',
				type: 'Protégé',
				method: 'GET',
				description: 'Liste des privilèges'
			},

			// Endpoints avec ID
			{
				path: 'api/users/1',
				type: 'Protégé',
				method: 'GET',
				description: 'Utilisateur spécifique'
			},
			{
				path: 'api/notes/1',
				type: 'Protégé',
				method: 'GET',
				description: 'Note spécifique'
			},
			{
				path: 'api/classes/1',
				type: 'Protégé',
				method: 'GET',
				description: 'Classe spécifique'
			},

			// Endpoints avec filtres
			{
				path: 'api/users/classe/1',
				type: 'Protégé',
				method: 'GET',
				description: 'Utilisateurs d\'une classe'
			},
			{
				path: 'api/notes?matiere=1',
				type: 'Protégé',
				method: 'GET',
				description: 'Notes filtrées par matière'
			}
		];

		// Résultats des tests
		const testResults = {};

		// Initialiser le tableau des endpoints
		function initEndpointsTable() {
			const tbody = document.querySelector('#endpointsTable tbody');
			tbody.innerHTML = '';

			endpoints.forEach((endpoint, index) => {
				const row = document.createElement('tr');
				const proxyUrl = `enhanced-proxy.php?endpoint=${endpoint.path}`;

				row.innerHTML = `
                    <td>${endpoint.path}</td>
                    <td>${endpoint.type}</td>
                    <td>${endpoint.method}</td>
                    <td>${proxyUrl}</td>
                    <td><div class="status-box status-pending"></div> En attente</td>
                    <td>
                        <button onclick="testEndpoint(${index})">Tester</button>
                    </td>
                `;

				tbody.appendChild(row);
			});

			// Initialiser le sélecteur de résultats
			const select = document.getElementById('resultEndpoint');
			select.innerHTML = '<option value="">Sélectionnez un endpoint pour voir les détails...</option>';

			endpoints.forEach((endpoint, index) => {
				const option = document.createElement('option');
				option.value = index;
				option.textContent = endpoint.path;
				select.appendChild(option);
			});

			select.addEventListener('change', function() {
				if (this.value !== '') {
					showResultDetails(parseInt(this.value));
				} else {
					document.getElementById('resultOutput').textContent = 'Sélectionnez un endpoint pour voir les résultats détaillés...';
				}
			});
		}

		// Tester un endpoint spécifique
		async function testEndpoint(index) {
			const endpoint = endpoints[index];
			const row = document.querySelectorAll('#endpointsTable tbody tr')[index];
			const statusCell = row.querySelector('td:nth-child(5)');

			statusCell.innerHTML = '<div class="status-box status-pending"></div> Test en cours...';

			try {
				const token = document.getElementById('tokenInput').value || localStorage.getItem('jwt_token');
				// Utiliser un chemin relatif au lieu d'une URL absolue
				const proxyUrl = `enhanced-proxy.php?endpoint=${endpoint.path}`;

				const options = {
					method: endpoint.method,
					headers: {
						'Content-Type': 'application/json'
					}
				};

				// Ajouter l'en-tête d'autorisation pour les endpoints protégés
				if (endpoint.type === 'Protégé' && token) {
					options.headers['Authorization'] = 'Bearer ' + token;
				} else if (endpoint.type === 'Protégé' && !token) {
					// Si endpoint protégé mais pas de token, avertir et continuer quand même
					console.warn("Tentative d'accès à un endpoint protégé sans token");
					document.getElementById('troubleshooting').style.display = 'block';
					document.getElementById('troubleshootingText').innerHTML =
						"<strong>Attention:</strong> Vous tentez d'accéder à des ressources protégées sans token d'authentification valide. " +
						"Utilisez d'abord l'authentification pour obtenir un token.";
				}

				// Ajouter le corps pour les requêtes POST/PUT
				if ((endpoint.method === 'POST' || endpoint.method === 'PUT') && endpoint.body) {
					options.body = JSON.stringify(endpoint.body);
				}

				console.log(`Fetching: ${proxyUrl} with method: ${endpoint.method}`); // Debug log
				const response = await fetch(proxyUrl, options);
				const contentType = response.headers.get('content-type') || '';

				let data;
				if (contentType.includes('application/json')) {
					data = await response.json();
				} else {
					data = await response.text();
				}

				// Enregistrer le résultat
				testResults[index] = {
					status: response.status,
					statusText: response.statusText,
					data: data,
					headers: Array.from(response.headers.entries()),
					timestamp: new Date().toLocaleString()
				};

				// Mettre à jour le statut dans le tableau
				if (response.status >= 200 && response.status < 300) {
					statusCell.innerHTML = `<div class="status-box status-success"></div> OK (${response.status})`;
				} else if (response.status === 401) {
					statusCell.innerHTML = `<div class="status-box status-warning"></div> Non autorisé (${response.status})`;
				} else {
					statusCell.innerHTML = `<div class="status-box status-error"></div> Erreur (${response.status})`;
				}

				// Afficher les résultats
				showResultDetails(index);

			} catch (error) {
				// Enregistrer l'erreur
				testResults[index] = {
					error: error.message,
					timestamp: new Date().toLocaleString()
				};

				statusCell.innerHTML = `<div class="status-box status-error"></div> Erreur`;
				showResultDetails(index);
			}
		}

		// Tester tous les endpoints
		async function testAllEndpoints() {
			for (let i = 0; i < endpoints.length; i++) {
				await testEndpoint(i);
				// Petite pause pour éviter de surcharger le serveur
				await new Promise(resolve => setTimeout(resolve, 500));
			}
		}

		// Afficher les détails du résultat
		function showResultDetails(index) {
			const resultOutput = document.getElementById('resultOutput');
			document.getElementById('resultEndpoint').value = index;

			if (!testResults[index]) {
				resultOutput.textContent = `Aucun résultat disponible pour ${endpoints[index].path}. Lancez le test d'abord.`;
				return;
			}

			const result = testResults[index];

			if (result.error) {
				resultOutput.innerHTML = `<span class="error">ERREUR: ${result.error}</span>\nTimestamp: ${result.timestamp}`;
				return;
			}

			let output = `Endpoint: ${endpoints[index].path}\n`;
			output += `Méthode: ${endpoints[index].method}\n`;
			output += `Statut: ${result.status} ${result.statusText}\n`;
			output += `Timestamp: ${result.timestamp}\n\n`;

			output += '== En-têtes ==\n';
			result.headers.forEach(([key, value]) => {
				output += `${key}: ${value}\n`;
			});

			output += '\n== Données ==\n';
			if (typeof result.data === 'object') {
				output += JSON.stringify(result.data, null, 2);
			} else {
				output += result.data;
			}

			resultOutput.textContent = output;
		}

		// Utiliser le token stocké dans localStorage
		function useStoredToken() {
			const token = localStorage.getItem('jwt_token');
			if (token) {
				document.getElementById('tokenInput').value = token;
				alert('Token récupéré du localStorage !');
			} else {
				alert('Aucun token trouvé dans le localStorage. Veuillez vous authentifier d\'abord.');
			}
		}

		// Sauvegarder le token saisi
		function saveTokenInput() {
			const token = document.getElementById('tokenInput').value;
			if (token) {
				localStorage.setItem('jwt_token', token);
				alert('Token sauvegardé dans le localStorage !');
			} else {
				alert('Veuillez saisir un token avant de sauvegarder.');
			}
		}
	</script>
</body>

</html>