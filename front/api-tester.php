<?php
// API Tester - pour explorer et déboguer les endpoints API
$pageTitle = "API Tester";
require_once 'templates/base.php';
?>

<div class="container my-4">
	<h1>API URL Tester</h1>
	<p class="lead">Cet outil vous aide à découvrir et tester la structure correcte des endpoints API.</p>

	<div class="row">
		<div class="col-md-6">
			<div class="card mb-4">
				<div class="card-header bg-primary text-white">
					<h3 class="mb-0">Test d'URL API</h3>
				</div>
				<div class="card-body">
					<form id="apiTestForm">
						<div class="mb-3">
							<label for="apiEndpoint" class="form-label">Endpoint API</label>
							<input type="text" class="form-control" id="apiEndpoint" value="classes">
							<div class="form-text">Saisir seulement le nom de l'endpoint (sans "api/")</div>
						</div>

						<div class="mb-3">
							<label for="urlFormat" class="form-label">Format d'URL</label>
							<select class="form-select" id="urlFormat">
								<option value="direct">Direct (sans api/)</option>
								<option value="api">Avec préfixe api/</option>
								<option value="both" selected>Les deux (test automatique)</option>
							</select>
						</div>

						<div class="mb-3">
							<label for="httpMethod" class="form-label">Méthode HTTP</label>
							<select class="form-select" id="httpMethod">
								<option value="GET" selected>GET</option>
								<option value="POST">POST</option>
								<option value="PUT">PUT</option>
								<option value="DELETE">DELETE</option>
							</select>
						</div>

						<div class="mb-3 form-check">
							<input type="checkbox" class="form-check-input" id="useProxy" checked>
							<label class="form-check-label" for="useProxy">Utiliser le proxy</label>
						</div>

						<button type="submit" class="btn btn-primary">Tester l'endpoint</button>
					</form>
				</div>
			</div>

			<div class="card mb-4">
				<div class="card-header bg-success text-white">
					<h3 class="mb-0">Endpoints communs</h3>
				</div>
				<div class="card-body">
					<div class="d-grid gap-2">
						<button id="btnClasses" class="btn btn-outline-primary">classes</button>
						<button id="btnMatieres" class="btn btn-outline-primary">matieres</button>
						<button id="btnExamens" class="btn btn-outline-primary">examens</button>
						<button id="btnProfs" class="btn btn-outline-primary">profs</button>
						<button id="btnNotes" class="btn btn-outline-primary">notes</button>
						<button id="btnStatus" class="btn btn-outline-info">status</button>
						<button id="btnDbStatus" class="btn btn-outline-info">db-status</button>
					</div>
				</div>
			</div>

			<div class="card mb-4">
				<div class="card-header bg-warning text-white">
					<h3 class="mb-0">Endpoints alternatifs</h3>
				</div>
				<div class="card-body">
					<div class="d-grid gap-2">
						<button id="btnApiTest" class="btn btn-outline-warning">api-test.php</button>
						<button id="btnPureCors" class="btn btn-outline-warning">pure-cors-test.php</button>
						<button id="btnAzureCors" class="btn btn-outline-warning">azure-cors.php</button>
						<button id="btnCorsTest" class="btn btn-outline-warning">test-cors.php</button>
						<button id="btnDirectStatus" class="btn btn-outline-warning">status.php</button>
					</div>
				</div>
			</div>
		</div>

		<div class="col-md-6">
			<div class="card">
				<div class="card-header bg-dark text-white">
					<h3 class="mb-0">Résultats</h3>
				</div>
				<div class="card-body">
					<div id="statusContainer" class="alert alert-info">
						Prêt à tester
					</div>
					<div id="urlTestedContainer" class="mb-3 d-none">
						<h5>URL testée:</h5>
						<div id="urlTested" class="p-2 bg-light rounded"></div>
					</div>
					<div id="responseContainer" class="d-none">
						<h5>Réponse:</h5>
						<pre id="responseData" class="p-2 bg-light rounded" style="max-height:400px;overflow:auto"></pre>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="card mt-4">
		<div class="card-header bg-info text-white">
			<h3 class="mb-0">URL Discovery</h3>
		</div>
		<div class="card-body">
			<p>
				Cet outil va tester automatiquement plusieurs formats d'URL pour trouver la structure correcte.
				Il essaiera les endpoints ci-dessus avec différentes combinaisons.
			</p>
			<button id="btnDiscovery" class="btn btn-info">Lancer l'auto-découverte</button>
			<div id="discoveryResults" class="mt-3"></div>
		</div>
	</div>
</div>

<script>
	document.addEventListener('DOMContentLoaded', function() {
		const statusContainer = document.getElementById('statusContainer');
		const urlTestedContainer = document.getElementById('urlTestedContainer');
		const urlTested = document.getElementById('urlTested');
		const responseContainer = document.getElementById('responseContainer');
		const responseData = document.getElementById('responseData');
		const apiTestForm = document.getElementById('apiTestForm');
		const discoveryResults = document.getElementById('discoveryResults');

		// Fonction pour effectuer un test d'API
		async function testApiEndpoint(endpoint, format, method, useProxy) {
			statusContainer.className = 'alert alert-info';
			statusContainer.textContent = 'Test en cours...';
			urlTestedContainer.classList.add('d-none');
			responseContainer.classList.add('d-none');

			try {
				// Déterminer l'URL en fonction du format et du proxy
				let url;
				let directUrl = endpoint;
				let apiUrl = `api/${endpoint}`;
				let urls = [];

				if (format === 'direct') {
					urls.push(directUrl);
				} else if (format === 'api') {
					urls.push(apiUrl);
				} else if (format === 'both') {
					urls.push(directUrl, apiUrl);
				}

				// Test séquentiel des URLs
				let successResponse = null;
				let lastError = null;
				let testedUrls = [];

				for (const testUrl of urls) {
					try {
						if (useProxy) {
							url = `backend-proxy.php?endpoint=${encodeURIComponent(testUrl)}`;
						} else {
							url = `https://app-backend-esgi-app.azurewebsites.net/${testUrl}`;
						}

						testedUrls.push(url);
						console.log(`Testing URL: ${url}`);

						const response = await fetch(url, {
							method: method,
							headers: {
								'Content-Type': 'application/json',
								'X-Requested-With': 'XMLHttpRequest'
							},
							credentials: 'include'
						});

						const data = await response.json();

						if (response.ok) {
							successResponse = {
								url: url,
								format: testUrl,
								status: response.status,
								data: data
							};
							break;
						} else {
							lastError = {
								url: url,
								format: testUrl,
								status: response.status,
								data: data
							};
						}
					} catch (err) {
						console.error(`Error testing ${testUrl}:`, err);
						lastError = {
							url: url,
							format: testUrl,
							error: err.message
						};
					}
				}

				// Afficher les résultats
				urlTestedContainer.classList.remove('d-none');
				responseContainer.classList.remove('d-none');

				if (successResponse) {
					statusContainer.className = 'alert alert-success';
					statusContainer.textContent = `Succès! Format d'URL qui fonctionne: ${successResponse.format}`;
					urlTested.textContent = successResponse.url;
					responseData.textContent = JSON.stringify(successResponse.data, null, 2);
					return {
						success: true,
						data: successResponse
					};
				} else {
					statusContainer.className = 'alert alert-danger';
					statusContainer.textContent = lastError.error ?
						`Erreur: ${lastError.error}` :
						`Échec (${lastError.status})`;

					urlTested.textContent = testedUrls.join('\n');
					responseData.textContent = lastError.data ?
						JSON.stringify(lastError.data, null, 2) :
						JSON.stringify(lastError, null, 2);
					return {
						success: false,
						data: lastError
					};
				}
			} catch (error) {
				statusContainer.className = 'alert alert-danger';
				statusContainer.textContent = `Erreur: ${error.message}`;
				console.error('Test failed:', error);
				return {
					success: false,
					error: error.message
				};
			}
		}

		// Gestionnaire du formulaire de test
		apiTestForm.addEventListener('submit', async function(event) {
			event.preventDefault();
			const endpoint = document.getElementById('apiEndpoint').value.trim();
			const format = document.getElementById('urlFormat').value;
			const method = document.getElementById('httpMethod').value;
			const useProxy = document.getElementById('useProxy').checked;

			await testApiEndpoint(endpoint, format, method, useProxy);
		});

		// Configurer les boutons d'endpoints communs
		document.getElementById('btnClasses').addEventListener('click', () => {
			document.getElementById('apiEndpoint').value = 'classes';
			apiTestForm.dispatchEvent(new Event('submit'));
		});

		document.getElementById('btnMatieres').addEventListener('click', () => {
			document.getElementById('apiEndpoint').value = 'matieres';
			apiTestForm.dispatchEvent(new Event('submit'));
		});

		document.getElementById('btnExamens').addEventListener('click', () => {
			document.getElementById('apiEndpoint').value = 'examens';
			apiTestForm.dispatchEvent(new Event('submit'));
		});

		document.getElementById('btnProfs').addEventListener('click', () => {
			document.getElementById('apiEndpoint').value = 'profs';
			apiTestForm.dispatchEvent(new Event('submit'));
		});

		document.getElementById('btnNotes').addEventListener('click', () => {
			document.getElementById('apiEndpoint').value = 'notes';
			apiTestForm.dispatchEvent(new Event('submit'));
		});

		document.getElementById('btnStatus').addEventListener('click', () => {
			document.getElementById('apiEndpoint').value = 'status';
			apiTestForm.dispatchEvent(new Event('submit'));
		});

		document.getElementById('btnDbStatus').addEventListener('click', () => {
			document.getElementById('apiEndpoint').value = 'db-status';
			apiTestForm.dispatchEvent(new Event('submit'));
		});

		// Tests avec les endpoints directs
		document.getElementById('btnApiTest').addEventListener('click', () => {
			document.getElementById('apiEndpoint').value = 'api-test.php';
			document.getElementById('urlFormat').value = 'direct';
			apiTestForm.dispatchEvent(new Event('submit'));
		});

		document.getElementById('btnPureCors').addEventListener('click', () => {
			document.getElementById('apiEndpoint').value = 'pure-cors-test.php';
			document.getElementById('urlFormat').value = 'direct';
			apiTestForm.dispatchEvent(new Event('submit'));
		});

		document.getElementById('btnAzureCors').addEventListener('click', () => {
			document.getElementById('apiEndpoint').value = 'azure-cors.php';
			document.getElementById('urlFormat').value = 'direct';
			apiTestForm.dispatchEvent(new Event('submit'));
		});

		document.getElementById('btnCorsTest').addEventListener('click', () => {
			document.getElementById('apiEndpoint').value = 'test-cors.php';
			document.getElementById('urlFormat').value = 'direct';
			apiTestForm.dispatchEvent(new Event('submit'));
		});

		document.getElementById('btnDirectStatus').addEventListener('click', () => {
			document.getElementById('apiEndpoint').value = 'status.php';
			document.getElementById('urlFormat').value = 'direct';
			apiTestForm.dispatchEvent(new Event('submit'));
		});

		// Auto-découverte d'URL
		document.getElementById('btnDiscovery').addEventListener('click', async function() {
			const discovery = document.getElementById('discoveryResults');
			discovery.innerHTML = '<div class="alert alert-info">Découverte en cours...</div>';

			// Test regular API endpoints both with and without api/ prefix
			const apiEndpointsToTest = ['classes', 'matieres', 'examens', 'notes', 'profs', 'status', 'db-status'];

			// Test direct script endpoints
			const directScriptsToTest = [
				'api-test.php',
				'pure-cors-test.php',
				'azure-cors.php',
				'test-cors.php',
				'status.php',
				'index.php',
				'info.php'
			];

			// Test URL formats for Azure rewrite patterns
			const rewritePatternsToTest = [
				'routes/api.php?resource=classes',
				'api/routes/classes',
				'api.php?resource=classes'
			];

			const results = {
				success: [],
				failed: []
			};

			// 1. Test regular API endpoints with both formats
			discovery.innerHTML = '<div class="alert alert-info">Test des endpoints API standards...</div>';
			for (const endpoint of apiEndpointsToTest) {
				const result = await testApiEndpoint(endpoint, 'both', 'GET', true);
				if (result.success) {
					results.success.push({
						endpoint,
						format: result.data.format,
						status: result.data.status
					});
				} else {
					results.failed.push({
						endpoint,
						error: result.error || `Status ${result.data.status}`
					});
				}
			}

			// 2. Test direct script endpoints
			discovery.innerHTML = '<div class="alert alert-info">Test des scripts directs...</div>';
			for (const endpoint of directScriptsToTest) {
				const result = await testApiEndpoint(endpoint, 'direct', 'GET', true);
				if (result.success) {
					results.success.push({
						endpoint,
						format: 'direct',
						status: result.data.status
					});
				} else {
					results.failed.push({
						endpoint,
						error: result.error || `Status ${result.data.status}`
					});
				}
			}

			// 3. Test rewrite patterns
			discovery.innerHTML = '<div class="alert alert-info">Test des modèles de réécriture...</div>';
			for (const endpoint of rewritePatternsToTest) {
				const result = await testApiEndpoint(endpoint, 'direct', 'GET', true);
				if (result.success) {
					results.success.push({
						endpoint,
						format: 'direct',
						status: result.data.status
					});
				} else {
					results.failed.push({
						endpoint,
						error: result.error || `Status ${result.data.status}`
					});
				}
			}

			// Afficher les résultats
			let html = '<h4>Résultats de la découverte</h4>';

			if (results.success.length > 0) {
				html += '<div class="alert alert-success"><strong>Endpoints fonctionnels:</strong></div>';
				html += '<ul class="list-group mb-3">';
				results.success.forEach(item => {
					html += `<li class="list-group-item list-group-item-success">
                        <strong>${item.endpoint}</strong> - Format: ${item.format}, Status: ${item.status}
                    </li>`;
				});
				html += '</ul>';
			}

			if (results.failed.length > 0) {
				html += '<div class="alert alert-danger"><strong>Endpoints non fonctionnels:</strong></div>';
				html += '<ul class="list-group">';
				results.failed.forEach(item => {
					html += `<li class="list-group-item list-group-item-danger">
                        <strong>${item.endpoint}</strong> - Erreur: ${item.error}
                    </li>`;
				});
				html += '</ul>';
			}

			// Conclusion et recommandations
			if (results.success.length > 0) {
				const formats = results.success.map(item => item.format);
				const preferredFormat = formats.includes('direct') ? 'direct' : 'api/';

				html += `<div class="alert alert-info mt-3">
					<h5>Recommandation:</h5>
					<p>Basé sur les résultats, le format d'URL recommandé est: <strong>${preferredFormat}</strong></p>
					<p>Modifiez votre fichier config.js pour utiliser ce format.</p>
				</div>`;
			}

			discovery.innerHTML = html;
		});
	});
</script>