<?php
$pageTitle = "Test de connexion API";
require_once '../templates/base.php';
?>

<div class="container">
	<div class="row">
		<div class="col-12">
			<h1>Test de connexion à l'API</h1>
			<div class="card mb-4">
				<div class="card-header">
					<h3>Test manuel de CORS</h3>
				</div>
				<div class="card-body">
					<p>Si les tests automatiques échouent, essayez de tester directement l'endpoint CORS Azure :</p>
					<select id="corsTestMethod" class="form-select mb-2">
						<option value="GET">GET</option>
						<option value="POST">POST</option>
						<option value="OPTIONS">OPTIONS</option>
					</select>
					<div class="form-check mb-2">
						<input type="checkbox" class="form-check-input" id="useProxyCheck" checked>
						<label class="form-check-label" for="useProxyCheck">Utiliser le proxy (recommandé pour éviter les problèmes CORS)</label>
					</div>
					<button id="manualCorsTestBtn" class="btn btn-primary mb-3">Tester CORS Manuellement</button>
					<div id="manualCorsResult" class="alert alert-info">Aucun test effectué</div>
					<div id="manualCorsDetails" class="mt-3"></div>

					<hr class="mt-4">
					<h4>Test CORS pur (sans web.config)</h4>
					<p>Ce test utilise un script PHP direct qui définit les en-têtes CORS sans utiliser web.config :</p>
					<button id="pureCorsTestBtn" class="btn btn-success mb-3">Tester Pure CORS</button>
					<a href="pure-cors-test.html" target="_blank" class="btn btn-outline-secondary mb-3 ms-2">
						Ouvrir la page de test CORS avancé
					</a>
					<div id="pureCorsResult" class="alert alert-info">Aucun test effectué</div>
					<div id="pureCorsDetails" class="mt-3"></div>
				</div>
			</div>

			<div class="card mb-4">
				<div class="card-header">
					<h3>Configuration</h3>
				</div>
				<div class="card-body">
					<p>Frontend URL: <strong><?php echo isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'Non disponible'; ?></strong></p>
					<p>API URL: <span id="apiBaseUrl">Chargement...</span></p>
				</div>
			</div>

			<div class="card mb-4">
				<div class="card-header">
					<h3>Test de connexion backend</h3>
				</div>
				<div class="card-body">
					<div id="backendStatus">Vérification de la connexion au backend...</div>
					<div id="backendDetails" class="mt-3"></div>
				</div>
			</div>

			<div class="card mb-4">
				<div class="card-header">
					<h3>Test de connexion à la base de données</h3>
				</div>
				<div class="card-body">
					<div id="dbStatus">Vérification de la connexion à la base de données...</div>
					<div id="dbDetails" class="mt-3"></div>
				</div>
			</div>

			<div class="card mb-4" id="apiTestsCard">
				<div class="card-header">
					<h3>Tests des endpoints API</h3>
				</div>
				<div class="card-body">
					<div id="apiTestStatus">Test des endpoints...</div>
					<div id="apiTestResults" class="mt-3"></div>
				</div>
			</div>

			<div class="card mb-4">
				<div class="card-header">
					<h3>Test de découverte des endpoints</h3>
				</div>
				<div class="card-body">
					<p>Ce test vérifie quels endpoints API sont disponibles.</p>
					<button id="discoverEndpointsBtn" class="btn btn-primary">Découvrir les endpoints</button>
					<div id="endpointDiscoveryResult" class="alert alert-info mt-3">Aucun test effectué</div>
					<div id="endpointsList" class="mt-3"></div>
				</div>
			</div>
		</div>
	</div>
</div>

<script src="../js/notification-system.js?v=1.1"></script>
<script src="../js/config.js?v=1.2"></script>
<script src="../js/xdomain-client.js"></script>
<script>
	document.addEventListener('DOMContentLoaded', function() {
		// Afficher l'URL de l'API
		document.getElementById('apiBaseUrl').textContent = appConfig.apiBaseUrl;

		// Fonction pour mettre à jour le statut
		function updateStatus(elementId, success, message, details = null) {
			const element = document.getElementById(elementId);
			element.innerHTML = `
            <div class="alert alert-${success ? 'success' : 'danger'}">
                <strong>${success ? '✅ Succès' : '❌ Échec'}</strong>: ${message}
            </div>
        `;

			if (details) {
				const detailsElement = document.getElementById(elementId + 'Details');
				if (detailsElement) {
					detailsElement.innerHTML = `<pre>${JSON.stringify(details, null, 2)}</pre>`;
				}
			}
		}

		// Test de connexion au backend
		async function testBackendConnection() {
			try {
				// Essayer d'abord via le proxy backend
				try {
					const proxyResponse = await fetch('../backend-proxy.php?endpoint=azure-cors.php', {
						method: 'GET',
						credentials: 'include',
						headers: {
							'Content-Type': 'application/json',
							'X-Requested-With': 'XMLHttpRequest'
						}
					});

					if (proxyResponse.ok) {
						const data = await proxyResponse.json();
						updateStatus('backendStatus', true, 'Test via proxy réussi - Backend accessible', data);
						// Si succès, tester la connexion à la base de données
						testDatabaseConnection();
						// Et tester les endpoints de l'API
						testApiEndpoints();
						return;
					}
				} catch (proxyError) {
					console.error('Erreur lors du test via proxy:', proxyError);
				}

				// Essayer d'abord notre nouvel endpoint Azure CORS spécifique
				try {
					const azureCorsResponse = await fetch(appConfig.apiBaseUrl.replace('/api', '') + '/azure-cors.php', {
						method: 'GET',
						credentials: 'include',
						headers: {
							'Content-Type': 'application/json',
							'X-Requested-With': 'XMLHttpRequest'
						}
					});

					if (azureCorsResponse.ok) {
						const azureCorsData = await azureCorsResponse.json();
						updateStatus('backendStatus', true, 'Test CORS Azure réussi - Backend accessible', azureCorsData);
						// Si succès, tester la connexion à la base de données
						testDatabaseConnection();
						// Et tester les endpoints de l'API
						testApiEndpoints();
						return;
					}
				} catch (azureCorsError) {
					console.error('Erreur lors du test CORS Azure:', azureCorsError);
				}

				// Essayer ensuite notre test CORS standard
				try {
					const corsResponse = await fetch(appConfig.apiBaseUrl.replace('/api', '') + '/test-cors.php', {
						method: 'GET',
						credentials: 'include',
						headers: {
							'Content-Type': 'application/json',
							'X-Requested-With': 'XMLHttpRequest'
						}
					});

					if (corsResponse.ok) {
						const corsData = await corsResponse.json();
						updateStatus('backendStatus', true, 'Test CORS réussi - Le backend est accessible', corsData);
						// Si succès, tester la connexion à la base de données
						testDatabaseConnection();
						// Et tester les endpoints de l'API
						testApiEndpoints();
						return;
					}
				} catch (corsError) {
					console.error('Erreur lors du test CORS:', corsError);
				}

				// Si le test CORS échoue, essayer l'endpoint status
				try {
					const response = await fetch(appConfig.apiBaseUrl + '/status', {
						method: 'GET',
						credentials: 'include',
						headers: {
							'Content-Type': 'application/json',
							'X-Requested-With': 'XMLHttpRequest'
						}
					});

					if (response.ok) {
						const data = await response.json();
						updateStatus('backendStatus', true, 'Test API réussi - Backend accessible via API', data);
						// Si succès, tester la connexion à la base de données
						testDatabaseConnection();
						// Et tester les endpoints de l'API
						testApiEndpoints();
						return;
					}
				} catch (apiError) {
					console.error('Erreur lors du test API:', apiError);
				}

				// En dernier recours, utiliser le proxy
				try {
					const proxyResponse = await fetch('../backend-proxy.php?endpoint=status.php', {
						method: 'GET',
						credentials: 'include',
						headers: {
							'Content-Type': 'application/json',
							'X-Requested-With': 'XMLHttpRequest'
						}
					});

					if (proxyResponse.ok) {
						const data = await proxyResponse.json();
						updateStatus('backendStatus', true, 'Test via proxy status.php réussi', data);
						// Si succès, tester la connexion à la base de données
						testDatabaseConnection();
						// Et tester les endpoints de l'API
						testApiEndpoints();
						return;
					}
				} catch (proxyError) {
					console.error('Erreur lors du test via proxy status.php:', proxyError);
				}

				// Si tous les tests ont échoué
				updateStatus('backendStatus', false, 'Tous les tests de connexion au backend ont échoué');
			} catch (error) {
				updateStatus('backendStatus', false, 'Erreur lors du test de connexion au backend: ' + error.message);
			}
		}

		// Test de connexion à la base de données
		async function testDatabaseConnection() {
			try {
				const response = await fetch(appConfig.useProxy ? '../backend-proxy.php?endpoint=api/db-status' : appConfig.apiBaseUrl + '/db-status', {
					method: 'GET',
					credentials: 'include',
					headers: {
						'Content-Type': 'application/json',
						'X-Requested-With': 'XMLHttpRequest'
					}
				});

				if (response.ok) {
					const data = await response.json();
					if (data.success) {
						updateStatus('dbStatus', true, 'Connexion à la base de données réussie', data);
					} else {
						updateStatus('dbStatus', false, 'Échec de connexion à la base de données: ' + data.message, data);
					}
				} else {
					updateStatus('dbStatus', false, 'Échec du test DB-Status (HTTP ' + response.status + ')', {
						status: response.status,
						statusText: response.statusText
					});
				}
			} catch (error) {
				updateStatus('dbStatus', false, 'Erreur lors du test de connexion à la base de données: ' + error.message);
			}
		}

		// Test des endpoints API
		async function testApiEndpoints() {
			try {
				// Liste des endpoints à tester
				const endpoints = [{
						name: 'Status',
						url: 'status'
					},
					{
						name: 'Classes',
						url: 'classes'
					},
					{
						name: 'Profs',
						url: 'profs'
					},
					{
						name: 'Matières',
						url: 'matieres'
					},
					{
						name: 'Examens',
						url: 'examens'
					},
					{
						name: 'Notes',
						url: 'notes'
					}
				];

				let results = [];
				let anySuccess = false;

				for (const endpoint of endpoints) {
					try {
						const url = appConfig.useProxy ?
							`../backend-proxy.php?endpoint=api/${endpoint.url}` :
							`${appConfig.apiBaseUrl}/${endpoint.url}`;

						const response = await fetch(url, {
							method: 'GET',
							credentials: 'include',
							headers: {
								'Content-Type': 'application/json',
								'X-Requested-With': 'XMLHttpRequest'
							}
						});

						const result = {
							endpoint: endpoint.name,
							url: endpoint.url,
							status: response.status,
							ok: response.ok
						};

						if (response.ok) {
							anySuccess = true;
							try {
								const data = await response.json();
								result.data = data;
							} catch (jsonError) {
								result.error = 'Erreur de parsing JSON';
							}
						} else {
							result.statusText = response.statusText;
						}

						results.push(result);
					} catch (endpointError) {
						results.push({
							endpoint: endpoint.name,
							url: endpoint.url,
							error: endpointError.message,
							ok: false
						});
					}
				}

				// Mettre à jour le statut global
				if (anySuccess) {
					updateStatus('apiTestStatus', true, 'Certains endpoints API sont accessibles');
				} else {
					updateStatus('apiTestStatus', false, 'Tous les tests d\'endpoints API ont échoué');
				}

				// Afficher les résultats détaillés
				const resultsElement = document.getElementById('apiTestResults');
				resultsElement.innerHTML = '';

				results.forEach(result => {
					const card = document.createElement('div');
					card.className = `card mb-2 ${result.ok ? 'border-success' : 'border-danger'}`;

					const cardHeader = document.createElement('div');
					cardHeader.className = `card-header ${result.ok ? 'bg-success' : 'bg-danger'} text-white`;
					cardHeader.textContent = `${result.endpoint} (${result.url})`;

					const cardBody = document.createElement('div');
					cardBody.className = 'card-body';

					const statusText = document.createElement('p');
					statusText.innerHTML = result.ok ?
						`<strong>✅ Succès</strong> (HTTP ${result.status})` :
						`<strong>❌ Échec</strong> (${result.error || 'HTTP ' + result.status + ' ' + result.statusText})`;

					cardBody.appendChild(statusText);

					if (result.data) {
						const dataPre = document.createElement('pre');
						dataPre.className = 'mt-2';
						dataPre.style.maxHeight = '200px';
						dataPre.style.overflow = 'auto';
						dataPre.textContent = JSON.stringify(result.data, null, 2);
						cardBody.appendChild(dataPre);
					}

					card.appendChild(cardHeader);
					card.appendChild(cardBody);
					resultsElement.appendChild(card);
				});

			} catch (error) {
				updateStatus('apiTestStatus', false, 'Erreur lors du test des endpoints API: ' + error.message);
			}
		}

		// Test manuel de CORS
		document.getElementById('manualCorsTestBtn').addEventListener('click', async function() {
			const method = document.getElementById('corsTestMethod').value;
			const useProxy = document.getElementById('useProxyCheck').checked;

			try {
				let url = '';
				if (useProxy) {
					url = '../backend-proxy.php?endpoint=azure-cors.php';
				} else {
					url = appConfig.apiBaseUrl.replace('/api', '') + '/azure-cors.php';
				}

				const response = await fetch(url, {
					method: method,
					credentials: 'include',
					headers: {
						'Content-Type': 'application/json',
						'X-Requested-With': 'XMLHttpRequest'
					}
				});

				if (response.ok) {
					try {
						const data = await response.json();
						updateStatus('manualCorsResult', true, `Test CORS ${method} réussi` + (useProxy ? ' via proxy' : ''), data);
					} catch (jsonError) {
						updateStatus('manualCorsResult', true, `Test CORS ${method} réussi, mais impossible de parser le JSON`, {
							error: jsonError.message,
							responseText: await response.text()
						});
					}
				} else {
					updateStatus('manualCorsResult', false, `Échec du test CORS ${method} (HTTP ${response.status})`, {
						status: response.status,
						statusText: response.statusText
					});
				}
			} catch (error) {
				updateStatus('manualCorsResult', false, `Erreur lors du test CORS ${method}: ${error.message}`);
			}
		});

		// Test CORS pur
		document.getElementById('pureCorsTestBtn').addEventListener('click', async function() {
			try {
				const url = appConfig.apiBaseUrl.replace('/api', '') + '/pure-cors-test.php';
				const response = await fetch(url, {
					method: 'GET',
					credentials: 'include',
					headers: {
						'Content-Type': 'application/json',
						'X-Requested-With': 'XMLHttpRequest'
					}
				});

				if (response.ok) {
					try {
						const data = await response.json();
						updateStatus('pureCorsResult', true, 'Test Pure CORS réussi', data);
					} catch (jsonError) {
						updateStatus('pureCorsResult', true, 'Test Pure CORS réussi, mais impossible de parser le JSON', {
							error: jsonError.message,
							responseText: await response.text()
						});
					}
				} else {
					updateStatus('pureCorsResult', false, `Échec du test Pure CORS (HTTP ${response.status})`, {
						status: response.status,
						statusText: response.statusText
					});
				}
			} catch (error) {
				updateStatus('pureCorsResult', false, `Erreur lors du test Pure CORS: ${error.message}`);
			}
		});

		// Découverte des endpoints
		document.getElementById('discoverEndpointsBtn').addEventListener('click', async function() {
			// Liste des endpoints potentiels
			const potentialEndpoints = [
				'classes',
				'profs',
				'matieres',
				'examens',
				'notes',
				'eleves',
				'users',
				'status',
				'db-status'
			];

			try {
				const endpointsList = document.getElementById('endpointsList');
				endpointsList.innerHTML = '';
				document.getElementById('endpointDiscoveryResult').innerHTML = `
                <div class="alert alert-info">
                    Test en cours, veuillez patienter...
                </div>
            `;

				let discoveredEndpoints = [];

				for (const endpoint of potentialEndpoints) {
					try {
						const url = appConfig.useProxy ?
							`../backend-proxy.php?endpoint=api/${endpoint}` :
							`${appConfig.apiBaseUrl}/${endpoint}`;

						const response = await fetch(url, {
							method: 'GET',
							credentials: 'include',
							headers: {
								'Content-Type': 'application/json',
								'X-Requested-With': 'XMLHttpRequest'
							}
						});

						const result = {
							name: endpoint,
							url: url,
							status: response.status,
							ok: response.ok
						};

						if (response.ok) {
							try {
								const data = await response.json();
								result.data = data;
							} catch (jsonError) {
								result.error = 'Erreur de parsing JSON';
							}
						} else {
							result.statusText = response.statusText;
						}

						discoveredEndpoints.push(result);
					} catch (endpointError) {
						discoveredEndpoints.push({
							name: endpoint,
							error: endpointError.message,
							ok: false
						});
					}
				}

				// Trier les résultats: d'abord les succès, puis les échecs
				discoveredEndpoints.sort((a, b) => {
					if (a.ok && !b.ok) return -1;
					if (!a.ok && b.ok) return 1;
					return 0;
				});

				// Afficher le résultat
				const successCount = discoveredEndpoints.filter(e => e.ok).length;
				if (successCount > 0) {
					document.getElementById('endpointDiscoveryResult').innerHTML = `
                    <div class="alert alert-success">
                        <strong>✅ ${successCount} endpoint(s) découvert(s)</strong> sur ${potentialEndpoints.length} testés
                    </div>
                `;
				} else {
					document.getElementById('endpointDiscoveryResult').innerHTML = `
                    <div class="alert alert-danger">
                        <strong>❌ Aucun endpoint découvert</strong> sur ${potentialEndpoints.length} testés
                    </div>
                `;
				}

				// Afficher les détails
				discoveredEndpoints.forEach(endpoint => {
					const card = document.createElement('div');
					card.className = `card mb-2 ${endpoint.ok ? 'border-success' : 'border-danger'}`;

					const cardHeader = document.createElement('div');
					cardHeader.className = `card-header ${endpoint.ok ? 'bg-success' : 'bg-danger'} text-white`;
					cardHeader.textContent = endpoint.name;

					const cardBody = document.createElement('div');
					cardBody.className = 'card-body';

					if (endpoint.ok) {
						cardBody.innerHTML = `<p><strong>Status:</strong> ${endpoint.status} OK</p>`;
						if (endpoint.data) {
							const dataPre = document.createElement('pre');
							dataPre.className = 'mt-2';
							dataPre.style.maxHeight = '200px';
							dataPre.style.overflow = 'auto';
							dataPre.textContent = JSON.stringify(endpoint.data, null, 2);
							cardBody.appendChild(dataPre);
						}
					} else {
						cardBody.innerHTML = `
                        <p><strong>Status:</strong> ${endpoint.status || 'Erreur'}</p>
                        <p><strong>Message:</strong> ${endpoint.statusText || endpoint.error || 'Indisponible'}</p>
                    `;
					}

					card.appendChild(cardHeader);
					card.appendChild(cardBody);
					endpointsList.appendChild(card);
				});

			} catch (error) {
				document.getElementById('endpointDiscoveryResult').innerHTML = `
                <div class="alert alert-danger">
                    <strong>❌ Erreur lors de la découverte des endpoints:</strong> ${error.message}
                </div>
            `;
			}
		});

		// Lancer les tests automatiques au chargement
		testBackendConnection();
	});
</script>