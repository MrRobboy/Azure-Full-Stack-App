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
				const response = await fetch(appConfig.apiBaseUrl + '/status');
				if (response.ok) {
					const data = await response.json();
					updateStatus('backendStatus', true, 'Le backend est accessible', data);

					// Si succès, tester la connexion à la base de données
					testDatabaseConnection();

					// Et tester les endpoints de l'API
					testApiEndpoints();
				} else {
					updateStatus('backendStatus', false, `Impossible d'accéder au backend (${response.status}: ${response.statusText})`);
				}
			} catch (error) {
				updateStatus('backendStatus', false, `Erreur de connexion: ${error.message}`);
				console.error('Erreur lors du test de connexion au backend:', error);
			}
		}

		// Test de connexion à la base de données
		async function testDatabaseConnection() {
			try {
				const response = await fetch(appConfig.apiBaseUrl + '/db-status');
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
					const response = await fetch(getApiUrl(endpoint), {
						credentials: 'include'
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
	});
</script>