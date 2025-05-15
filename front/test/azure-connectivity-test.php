<?php
$pageTitle = "Test de connectivité Azure";
require_once '../templates/base.php';
?>

<div class="container">
	<div class="row">
		<div class="col-12">
			<h1>Test de Connectivité Azure</h1>
			<p>Cette page teste spécifiquement la connectivité entre les services d'application Azure.</p>

			<div class="card mb-4">
				<div class="card-header bg-primary text-white">
					<h3>Configuration</h3>
				</div>
				<div class="card-body">
					<p>Frontend URL: <strong id="frontendUrl"></strong></p>
					<p>Backend URL: <strong id="backendUrl"></strong></p>
					<p>Type de connection: <span id="connectionType" class="badge bg-info">Inconnu</span></p>
				</div>
			</div>

			<div class="card mb-4">
				<div class="card-header bg-primary text-white">
					<h3>Test de connexion CORS</h3>
				</div>
				<div class="card-body">
					<p>Ce test vérifie si les en-têtes CORS sont correctement configurés.</p>
					<button id="testDirectCorsBtn" class="btn btn-primary">Tester CORS Direct</button>
					<div id="corsResult" class="alert alert-info mt-3">Aucun test effectué</div>
					<div id="corsDetails" class="mt-3"></div>
				</div>
			</div>

			<div class="card mb-4">
				<div class="card-header bg-primary text-white">
					<h3>Test d'API principal</h3>
				</div>
				<div class="card-body">
					<p>Ce test vérifie si l'API renvoie des données correctement.</p>
					<button id="testApiBtn" class="btn btn-success">Tester l'API</button>
					<div id="apiResult" class="alert alert-info mt-3">Aucun test effectué</div>
					<div id="apiDetails" class="mt-3"></div>
				</div>
			</div>

			<div class="card mb-4">
				<div class="card-header bg-primary text-white">
					<h3>Test de Base de Données</h3>
				</div>
				<div class="card-body">
					<p>Ce test vérifie la connectivité avec la base de données Azure SQL.</p>
					<button id="testDbBtn" class="btn btn-warning">Tester la BDD</button>
					<div id="dbResult" class="alert alert-info mt-3">Aucun test effectué</div>
					<div id="dbDetails" class="mt-3"></div>
				</div>
			</div>

			<div class="card mb-4">
				<div class="card-header bg-primary text-white">
					<h3>Actions</h3>
				</div>
				<div class="card-body">
					<button id="toggleProxyBtn" class="btn btn-secondary">Changer de mode (Proxy/Direct)</button>
					<div id="actionResult" class="alert alert-info mt-3">Aucune action effectuée</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script src="../js/config.js?v=1.6"></script>
<script>
	document.addEventListener('DOMContentLoaded', function() {
		// Afficher les URLs
		document.getElementById('frontendUrl').textContent = window.location.origin;
		document.getElementById('backendUrl').textContent = appConfig.backendBaseUrl;
		updateConnectionType();

		// Fonction pour mettre à jour l'affichage du type de connexion
		function updateConnectionType() {
			const connectionType = document.getElementById('connectionType');
			connectionType.textContent = appConfig.useProxy ? 'Via Proxy' : 'Direct (CORS)';
			connectionType.className = appConfig.useProxy ? 'badge bg-warning' : 'badge bg-success';
		}

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

		// Test de connexion CORS directe
		document.getElementById('testDirectCorsBtn').addEventListener('click', async function() {
			try {
				// Sauvegarder l'état actuel
				const originalUseProxy = appConfig.useProxy;
				// Forcer le mode direct pour ce test
				appConfig.useProxy = false;

				const response = await fetch(`${appConfig.backendBaseUrl}/azure-cors.php`, {
					method: 'GET',
					credentials: 'include',
					headers: {
						'Content-Type': 'application/json',
						'X-Requested-With': 'XMLHttpRequest'
					}
				});

				// Restaurer l'état original
				appConfig.useProxy = originalUseProxy;
				updateConnectionType();

				if (response.ok) {
					const data = await response.json();
					updateStatus('corsResult', true, 'Test CORS direct réussi - La connexion avec le backend fonctionne', data);
				} else {
					updateStatus('corsResult', false, `Échec du test CORS direct - Status: ${response.status} ${response.statusText}`);
				}
			} catch (error) {
				updateStatus('corsResult', false, `Erreur de connexion: ${error.message}`);
			}
		});

		// Test d'API
		document.getElementById('testApiBtn').addEventListener('click', async function() {
			try {
				const url = appConfig.useProxy ?
					`../backend-proxy.php?endpoint=api/status` :
					`${appConfig.apiBaseUrl}/status`;

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
					updateStatus('apiResult', true, 'Test API réussi - L\'API fonctionne correctement', data);
				} else {
					updateStatus('apiResult', false, `Échec du test API - Status: ${response.status} ${response.statusText}`);
				}
			} catch (error) {
				updateStatus('apiResult', false, `Erreur de connexion API: ${error.message}`);
			}
		});

		// Test de Base de Données
		document.getElementById('testDbBtn').addEventListener('click', async function() {
			try {
				const url = appConfig.useProxy ?
					`../backend-proxy.php?endpoint=api/db-status` :
					`${appConfig.apiBaseUrl}/db-status`;

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
					updateStatus('dbResult', true, 'Test de BDD réussi - La connexion à la base de données fonctionne', data);
				} else {
					updateStatus('dbResult', false, `Échec du test de BDD - Status: ${response.status} ${response.statusText}`);
				}
			} catch (error) {
				updateStatus('dbResult', false, `Erreur de connexion BDD: ${error.message}`);
			}
		});

		// Changer le mode de connexion
		document.getElementById('toggleProxyBtn').addEventListener('click', function() {
			appConfig.useProxy = !appConfig.useProxy;
			updateConnectionType();
			document.getElementById('actionResult').innerHTML = `
            <div class="alert alert-success">
                Mode changé avec succès. Maintenant en mode <strong>${appConfig.useProxy ? 'Proxy' : 'Direct (CORS)'}</strong>
            </div>
        `;
		});
	});
</script>