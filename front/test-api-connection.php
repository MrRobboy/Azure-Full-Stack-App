<?php
$pageTitle = "Test de connexion API";
require_once 'templates/base.php';
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
		</div>
	</div>
</div>

<script src="js/notification-system.js?v=1.1"></script>
<script src="js/config.js?v=1.2"></script>
<script src="js/xdomain-client.js"></script>
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
					const proxyResponse = await fetch('backend-proxy.php?endpoint=azure-cors.php', {
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
						updateStatus('backendStatus', true, 'Le backend est accessible', data);
						// Si succès, tester la connexion à la base de données
						testDatabaseConnection();
						// Et tester les endpoints de l'API
						testApiEndpoints();
						return;
					}
				} catch (statusError) {
					console.error('Erreur lors du test de status:', statusError);
				}

				// Si XDomain échoue aussi, essayer JSONP comme dernier recours
				const baseUrl = appConfig.apiBaseUrl.replace('/api', '');
				const jsonpUrl = `${baseUrl}/jsonp-test.php?callback=handleJsonpResponse`;
				const script = document.createElement('script');
				script.src = jsonpUrl;

				// Définir la fonction de callback
				window.handleJsonpResponse = function(data) {
					updateStatus('backendStatus', true, 'Backend accessible via JSONP', data);
					// Si succès, tester la connexion à la base de données
					testDatabaseConnection();
					// Et tester les endpoints de l'API
					testApiEndpoints();
					// Nettoyer
					document.body.removeChild(script);
					delete window.handleJsonpResponse;
				};

				// Gérer les erreurs de chargement du script
				script.onerror = function() {
					updateStatus('backendStatus', false, 'Impossible d\'accéder au backend via CORS, XDomain ou JSONP');
					document.body.removeChild(script);
					delete window.handleJsonpResponse;
				};

				// Ajouter le script à la page
				document.body.appendChild(script);

			} catch (error) {
				updateStatus('backendStatus', false, `Erreur de connexion: ${error.message}`);
				console.error('Erreur lors du test de connexion au backend:', error);
			}
		}

		// Test de connexion à la base de données
		async function testDatabaseConnection() {
			try {
				// Essayer d'abord via le proxy
				try {
					const proxyResponse = await fetch('backend-proxy.php?endpoint=azure-cors.php?type=db', {
						method: 'GET',
						credentials: 'include',
						headers: {
							'Content-Type': 'application/json',
							'X-Requested-With': 'XMLHttpRequest'
						}
					});

					if (proxyResponse.ok) {
						const data = await proxyResponse.json();
						updateStatus('dbStatus', true, 'Test de base de données via proxy réussi', data);
						return;
					}
				} catch (proxyError) {
					console.error('Erreur lors du test de DB via proxy:', proxyError);
				}

				// Essayer ensuite via notre endpoint Azure CORS
				try {
					const azureCorsResponse = await fetch(appConfig.apiBaseUrl.replace('/api', '') + '/azure-cors.php?type=db', {
						method: 'GET',
						credentials: 'include',
						headers: {
							'Content-Type': 'application/json',
							'X-Requested-With': 'XMLHttpRequest'
						}
					});

					if (azureCorsResponse.ok) {
						const data = await azureCorsResponse.json();
						updateStatus('dbStatus', true, 'Test de base de données via Azure CORS réussi', data);
						return;
					}
				} catch (azureError) {
					console.error('Erreur lors du test de DB via Azure CORS:', azureError);
				}

				// Essayer l'endpoint standard
				const response = await fetch(appConfig.apiBaseUrl + '/db-status', {
					method: 'GET',
					credentials: 'include',
					headers: {
						'Content-Type': 'application/json',
						'X-Requested-With': 'XMLHttpRequest'
					}
				});
				if (response.ok) {
					const data = await response.json();
					updateStatus('dbStatus', data.success, data.message, data.data);
				} else {
					updateStatus('dbStatus', false, `Impossible de vérifier la connexion à la base de données (${response.status}: ${response.statusText})`);
				}
			} catch (error) {
				updateStatus('dbStatus', false, `Erreur lors de la vérification de la base de données: ${error.message}`);
				console.error('Erreur lors du test de connexion à la DB:', error);
			}
		}

		// Test des endpoints de l'API
		async function testApiEndpoints() {
			const endpoints = ['classes', 'matieres', 'examens']; // Endpoints à tester
			const results = document.getElementById('apiTestResults');

			results.innerHTML = ''; // Nettoyer les résultats précédents

			let allSuccess = true;

			for (const endpoint of endpoints) {
				try {
					// Essayer d'abord via le proxy
					try {
						const proxyUrl = `backend-proxy.php?endpoint=azure-cors.php?resource=${endpoint}`;
						const proxyResponse = await fetch(proxyUrl, {
							method: 'GET',
							credentials: 'include',
							headers: {
								'Content-Type': 'application/json',
								'X-Requested-With': 'XMLHttpRequest'
							}
						});

						if (proxyResponse.ok) {
							const data = await proxyResponse.json();
							const resultEl = document.createElement('div');
							resultEl.innerHTML = `
								<div class="alert alert-success mb-2">
									<strong>✅ Endpoint ${endpoint} (via proxy)</strong>: OK (${proxyResponse.status})
								</div>
							`;
							results.appendChild(resultEl);
							continue; // Passer à l'endpoint suivant
						}
					} catch (proxyError) {
						console.error(`Erreur lors du test proxy pour ${endpoint}:`, proxyError);
					}

					// Essayer d'abord via notre endpoint Azure CORS avec le paramètre resource
					try {
						const azureCorsUrl = `${appConfig.apiBaseUrl.replace('/api', '')}/azure-cors.php?resource=${endpoint}`;
						const azureResponse = await fetch(azureCorsUrl, {
							method: 'GET',
							credentials: 'include',
							headers: {
								'Content-Type': 'application/json',
								'X-Requested-With': 'XMLHttpRequest'
							}
						});

						if (azureResponse.ok) {
							const data = await azureResponse.json();
							const resultEl = document.createElement('div');
							resultEl.innerHTML = `
								<div class="alert alert-success mb-2">
									<strong>✅ Endpoint ${endpoint} (via Azure CORS)</strong>: OK (${azureResponse.status})
								</div>
							`;
							results.appendChild(resultEl);
							continue; // Passer à l'endpoint suivant
						}
					} catch (azureError) {
						console.error(`Erreur lors du test Azure CORS pour ${endpoint}:`, azureError);
					}

					// Si Azure CORS échoue, essayer l'API standard
					const response = await fetch(getApiUrl(endpoint), {
						method: 'GET',
						credentials: 'include',
						headers: {
							'Content-Type': 'application/json',
							'X-Requested-With': 'XMLHttpRequest'
						}
					});

					const resultEl = document.createElement('div');
					if (response.ok) {
						const data = await response.json();
						resultEl.innerHTML = `
							<div class="alert alert-success mb-2">
								<strong>✅ Endpoint ${endpoint}</strong>: OK (${response.status})
							</div>
						`;
					} else {
						allSuccess = false;
						resultEl.innerHTML = `
							<div class="alert alert-danger mb-2">
								<strong>❌ Endpoint ${endpoint}</strong>: Échec (${response.status}: ${response.statusText})
							</div>
						`;
					}
					results.appendChild(resultEl);
				} catch (error) {
					allSuccess = false;
					const resultEl = document.createElement('div');
					resultEl.innerHTML = `
						<div class="alert alert-danger mb-2">
							<strong>❌ Endpoint ${endpoint}</strong>: Erreur (${error.message})
						</div>
					`;
					results.appendChild(resultEl);
				}
			}

			updateStatus('apiTestStatus', allSuccess,
				allSuccess ? 'Tous les endpoints API testés avec succès.' : 'Certains endpoints API ont échoué.');
		}

		// Lancer les tests de connexion
		testBackendConnection();

		document.getElementById('manualCorsTestBtn').addEventListener('click', async function() {
			const resultElement = document.getElementById('manualCorsResult');
			const detailsElement = document.getElementById('manualCorsDetails');
			const method = document.getElementById('corsTestMethod').value;
			const useProxy = document.getElementById('useProxyCheck').checked;

			resultElement.className = 'alert alert-info';
			resultElement.textContent = 'Test en cours...';
			detailsElement.innerHTML = '';

			try {
				let url = '';
				if (useProxy) {
					url = 'backend-proxy.php?endpoint=azure-cors.php';
				} else {
					url = 'https://app-backend-esgi-app.azurewebsites.net/azure-cors.php';
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
					const data = await response.json();
					resultElement.className = 'alert alert-success';
					resultElement.textContent = 'Test CORS réussi!';
					detailsElement.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
				} else {
					resultElement.className = 'alert alert-danger';
					resultElement.textContent = `Échec du test CORS: ${response.status} ${response.statusText}`;
				}
			} catch (error) {
				resultElement.className = 'alert alert-danger';
				resultElement.textContent = `Erreur lors du test CORS: ${error.message}`;
				console.error('Erreur du test CORS manuel:', error);
			}
		});

		document.getElementById('pureCorsTestBtn').addEventListener('click', async function() {
			const resultElement = document.getElementById('pureCorsResult');
			const detailsElement = document.getElementById('pureCorsDetails');
			const useProxy = document.getElementById('useProxyCheck').checked;

			resultElement.className = 'alert alert-info';
			resultElement.textContent = 'Test en cours...';
			detailsElement.innerHTML = '';

			try {
				let url = '';
				if (useProxy) {
					url = 'backend-proxy.php?endpoint=pure-cors-test.php';
				} else {
					url = 'https://app-backend-esgi-app.azurewebsites.net/pure-cors-test.php';
				}

				const response = await fetch(url, {
					method: 'GET',
					credentials: 'include',
					headers: {
						'Content-Type': 'application/json',
						'X-Requested-With': 'XMLHttpRequest'
					}
				});

				if (response.ok) {
					const data = await response.json();
					resultElement.className = 'alert alert-success';
					resultElement.textContent = 'Test Pure CORS réussi!';
					detailsElement.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
				} else {
					resultElement.className = 'alert alert-danger';
					resultElement.textContent = `Échec du test Pure CORS: ${response.status} ${response.statusText}`;
				}
			} catch (error) {
				resultElement.className = 'alert alert-danger';
				resultElement.textContent = `Erreur lors du test Pure CORS: ${error.message}`;
				console.error('Erreur du test Pure CORS:', error);
			}
		});
	});
</script>