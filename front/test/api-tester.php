<?php
$pageTitle = "API Tester";
require_once '../templates/base.php';
?>

<div class="container">
	<div class="row mb-4">
		<div class="col-12">
			<h1>Testeur d'API</h1>
			<p>
				Cet outil permet de tester l'API du backend directement depuis le frontend.
				Il sert à déboguer et analyser les problèmes de connexion.
			</p>
		</div>
	</div>

	<div class="row">
		<div class="col-md-4">
			<div class="card mb-4">
				<div class="card-header bg-primary text-white">
					<h3 class="card-title">Configuration</h3>
				</div>
				<div class="card-body">
					<div class="form-group">
						<label for="apiUrl">URL de base de l'API:</label>
						<input type="text" class="form-control" id="apiUrl" value="">
					</div>
					<div class="form-group">
						<label for="endpoint">Endpoint:</label>
						<input type="text" class="form-control" id="endpoint" value="status">
					</div>
					<div class="form-group">
						<label for="method">Méthode:</label>
						<select class="form-control" id="method">
							<option value="GET">GET</option>
							<option value="POST">POST</option>
							<option value="PUT">PUT</option>
							<option value="DELETE">DELETE</option>
						</select>
					</div>
					<div class="form-group">
						<label for="useProxy">
							<input type="checkbox" id="useProxy" checked>
							Utiliser le proxy (pour éviter les problèmes CORS)
						</label>
					</div>
					<div class="form-group">
						<label for="useAuth">
							<input type="checkbox" id="useAuth">
							Authentification
						</label>
					</div>
					<div id="authFields" style="display: none;">
						<div class="form-group">
							<label for="username">Nom d'utilisateur:</label>
							<input type="text" class="form-control" id="username">
						</div>
						<div class="form-group">
							<label for="password">Mot de passe:</label>
							<input type="password" class="form-control" id="password">
						</div>
					</div>
				</div>
			</div>

			<div class="card mb-4">
				<div class="card-header bg-info text-white">
					<h3 class="card-title">Paramètres avancés</h3>
				</div>
				<div class="card-body">
					<div class="form-group">
						<label for="headers">En-têtes (format JSON):</label>
						<textarea class="form-control" id="headers" rows="3">{"Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest"}</textarea>
					</div>
					<div class="form-group">
						<label for="reqBody">Corps de la requête (pour POST/PUT):</label>
						<textarea class="form-control" id="reqBody" rows="3">{}</textarea>
					</div>
					<button class="btn btn-primary" id="sendBtn">Envoyer la requête</button>
				</div>
			</div>

			<div class="card mb-4">
				<div class="card-header bg-secondary text-white">
					<h3 class="card-title">Endpoints courants</h3>
				</div>
				<div class="card-body">
					<div class="list-group">
						<a href="#" class="list-group-item list-group-item-action endpoint-link" data-endpoint="status">Status</a>
						<a href="#" class="list-group-item list-group-item-action endpoint-link" data-endpoint="classes">Classes</a>
						<a href="#" class="list-group-item list-group-item-action endpoint-link" data-endpoint="profs">Professeurs</a>
						<a href="#" class="list-group-item list-group-item-action endpoint-link" data-endpoint="matieres">Matières</a>
						<a href="#" class="list-group-item list-group-item-action endpoint-link" data-endpoint="examens">Examens</a>
						<a href="#" class="list-group-item list-group-item-action endpoint-link" data-endpoint="notes">Notes</a>
						<a href="#" class="list-group-item list-group-item-action endpoint-link" data-endpoint="eleves">Élèves</a>
						<a href="#" class="list-group-item list-group-item-action endpoint-link" data-endpoint="azure-cors.php">Azure CORS</a>
					</div>
				</div>
			</div>
		</div>

		<div class="col-md-8">
			<div class="card mb-4">
				<div class="card-header bg-success text-white">
					<div class="d-flex justify-content-between align-items-center">
						<h3 class="card-title mb-0">Résultats</h3>
						<button class="btn btn-sm btn-light" id="clearBtn">Effacer</button>
					</div>
				</div>
				<div class="card-body">
					<div id="loading" class="alert alert-info d-none">
						<div class="spinner-border spinner-border-sm" role="status">
							<span class="sr-only">Chargement...</span>
						</div>
						Envoi de la requête...
					</div>
					<div id="statusCode" class="d-none"></div>
					<div id="responseTime" class="d-none"></div>
					<ul class="nav nav-tabs" id="resultTabs" role="tablist">
						<li class="nav-item">
							<a class="nav-link active" id="response-tab" data-toggle="tab" href="#response" role="tab" aria-controls="response" aria-selected="true">Réponse</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" id="headers-tab" data-toggle="tab" href="#respHeaders" role="tab" aria-controls="headers" aria-selected="false">En-têtes</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" id="request-tab" data-toggle="tab" href="#request" role="tab" aria-controls="request" aria-selected="false">Requête</a>
						</li>
					</ul>
					<div class="tab-content mt-2" id="resultTabsContent">
						<div class="tab-pane fade show active" id="response" role="tabpanel" aria-labelledby="response-tab">
							<pre id="responseBody" class="bg-light p-3 rounded">Aucune requête envoyée.</pre>
						</div>
						<div class="tab-pane fade" id="respHeaders" role="tabpanel" aria-labelledby="headers-tab">
							<pre id="responseHeaders" class="bg-light p-3 rounded">-</pre>
						</div>
						<div class="tab-pane fade" id="request" role="tabpanel" aria-labelledby="request-tab">
							<pre id="requestDetails" class="bg-light p-3 rounded">-</pre>
						</div>
					</div>
				</div>
			</div>

			<div class="card mb-4">
				<div class="card-header bg-warning text-dark">
					<h3 class="card-title">Historique des requêtes</h3>
				</div>
				<div class="card-body">
					<div class="table-responsive">
						<table class="table table-striped table-sm" id="historyTable">
							<thead>
								<tr>
									<th>Méthode</th>
									<th>URL</th>
									<th>Statut</th>
									<th>Temps</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody id="historyBody">
								<tr>
									<td colspan="5" class="text-center">Aucun historique</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script src="../js/config.js"></script>
<script>
	document.addEventListener('DOMContentLoaded', function() {
		// Initialiser les éléments
		const apiUrlInput = document.getElementById('apiUrl');
		const endpointInput = document.getElementById('endpoint');
		const methodSelect = document.getElementById('method');
		const useProxyCheckbox = document.getElementById('useProxy');
		const useAuthCheckbox = document.getElementById('useAuth');
		const authFieldsDiv = document.getElementById('authFields');
		const usernameInput = document.getElementById('username');
		const passwordInput = document.getElementById('password');
		const headersTextarea = document.getElementById('headers');
		const reqBodyTextarea = document.getElementById('reqBody');
		const sendBtn = document.getElementById('sendBtn');
		const clearBtn = document.getElementById('clearBtn');
		const loadingDiv = document.getElementById('loading');
		const statusCodeDiv = document.getElementById('statusCode');
		const responseTimeDiv = document.getElementById('responseTime');
		const responseBodyPre = document.getElementById('responseBody');
		const responseHeadersPre = document.getElementById('responseHeaders');
		const requestDetailsPre = document.getElementById('requestDetails');
		const historyBody = document.getElementById('historyBody');

		// Historique des requêtes
		let requestHistory = [];

		// Initialiser l'URL de l'API
		apiUrlInput.value = window.appConfig.apiBaseUrl;

		// Afficher/masquer les champs d'authentification
		useAuthCheckbox.addEventListener('change', function() {
			authFieldsDiv.style.display = this.checked ? 'block' : 'none';
		});

		// Liens d'endpoints rapides
		document.querySelectorAll('.endpoint-link').forEach(function(link) {
			link.addEventListener('click', function(e) {
				e.preventDefault();
				endpointInput.value = this.getAttribute('data-endpoint');
			});
		});

		// Effacer les résultats
		clearBtn.addEventListener('click', function() {
			responseBodyPre.textContent = 'Aucune requête envoyée.';
			responseHeadersPre.textContent = '-';
			requestDetailsPre.textContent = '-';
			statusCodeDiv.classList.add('d-none');
			responseTimeDiv.classList.add('d-none');
		});

		// Fonction pour ajouter une entrée à l'historique
		function addToHistory(method, url, statusCode, responseTime, requestDetails, responseHeaders, responseBody) {
			// Limiter l'historique à 10 entrées
			if (requestHistory.length >= 10) {
				requestHistory.shift();
			}

			const historyEntry = {
				method,
				url,
				statusCode,
				responseTime,
				requestDetails,
				responseHeaders,
				responseBody,
				timestamp: new Date().toISOString()
			};

			requestHistory.push(historyEntry);
			updateHistoryTable();
		}

		// Mettre à jour la table d'historique
		function updateHistoryTable() {
			if (requestHistory.length === 0) {
				historyBody.innerHTML = '<tr><td colspan="5" class="text-center">Aucun historique</td></tr>';
				return;
			}

			historyBody.innerHTML = '';

			requestHistory.slice().reverse().forEach(function(entry, index) {
				const reversedIndex = requestHistory.length - 1 - index;
				const row = document.createElement('tr');

				// Méthode
				const methodCell = document.createElement('td');
				methodCell.textContent = entry.method;
				row.appendChild(methodCell);

				// URL
				const urlCell = document.createElement('td');
				urlCell.textContent = entry.url.length > 30 ? entry.url.substring(0, 30) + '...' : entry.url;
				urlCell.title = entry.url;
				row.appendChild(urlCell);

				// Statut
				const statusCell = document.createElement('td');
				const statusClass = entry.statusCode < 400 ? 'text-success' : 'text-danger';
				statusCell.innerHTML = `<span class="${statusClass}">${entry.statusCode}</span>`;
				row.appendChild(statusCell);

				// Temps
				const timeCell = document.createElement('td');
				timeCell.textContent = entry.responseTime + 'ms';
				row.appendChild(timeCell);

				// Actions
				const actionsCell = document.createElement('td');
				const replayBtn = document.createElement('button');
				replayBtn.className = 'btn btn-sm btn-primary mr-1';
				replayBtn.textContent = 'Rejouer';
				replayBtn.addEventListener('click', function() {
					replayRequest(reversedIndex);
				});

				const viewBtn = document.createElement('button');
				viewBtn.className = 'btn btn-sm btn-info';
				viewBtn.textContent = 'Voir';
				viewBtn.addEventListener('click', function() {
					viewRequest(reversedIndex);
				});

				actionsCell.appendChild(replayBtn);
				actionsCell.appendChild(viewBtn);
				row.appendChild(actionsCell);

				historyBody.appendChild(row);
			});
		}

		// Rejouer une requête depuis l'historique
		function replayRequest(index) {
			const entry = requestHistory[index];

			// Extraire l'endpoint de l'URL
			let endpoint = entry.url;
			const baseUrl = apiUrlInput.value;
			if (endpoint.startsWith(baseUrl)) {
				endpoint = endpoint.substring(baseUrl.length);
				if (endpoint.startsWith('/')) {
					endpoint = endpoint.substring(1);
				}
			}

			// Remplir les champs du formulaire
			endpointInput.value = endpoint;
			methodSelect.value = entry.method;

			try {
				headersTextarea.value = JSON.stringify(JSON.parse(entry.requestDetails).headers, null, 2);
				if (entry.method === 'POST' || entry.method === 'PUT') {
					reqBodyTextarea.value = JSON.stringify(JSON.parse(entry.requestDetails).body, null, 2);
				}
			} catch (error) {
				console.error('Erreur lors du parsing des détails de la requête:', error);
			}

			// Faire défiler jusqu'au formulaire
			document.querySelector('.card-header').scrollIntoView({
				behavior: 'smooth'
			});
		}

		// Afficher les détails d'une requête depuis l'historique
		function viewRequest(index) {
			const entry = requestHistory[index];

			// Afficher les détails
			statusCodeDiv.innerHTML = `
            <div class="alert ${entry.statusCode < 400 ? 'alert-success' : 'alert-danger'}">
                Statut: ${entry.statusCode} | Temps: ${entry.responseTime}ms
            </div>
        `;
			statusCodeDiv.classList.remove('d-none');

			responseBodyPre.textContent = entry.responseBody;
			responseHeadersPre.textContent = entry.responseHeaders;
			requestDetailsPre.textContent = entry.requestDetails;

			// Activer l'onglet de réponse
			document.getElementById('response-tab').click();

			// Faire défiler jusqu'aux résultats
			document.getElementById('resultTabs').scrollIntoView({
				behavior: 'smooth'
			});
		}

		// Formater JSON pour l'affichage
		function formatJSON(jsonString) {
			try {
				return JSON.stringify(JSON.parse(jsonString), null, 2);
			} catch (e) {
				return jsonString;
			}
		}

		// Envoyer la requête
		sendBtn.addEventListener('click', async function() {
			// Obtenir les paramètres
			const baseUrl = apiUrlInput.value.trim();
			let endpoint = endpointInput.value.trim();
			const method = methodSelect.value;
			const useProxy = useProxyCheckbox.checked;
			const useAuth = useAuthCheckbox.checked;

			// Vérifier l'URL
			if (!baseUrl) {
				alert('Veuillez entrer une URL de base');
				return;
			}

			// Construire l'URL complète
			let fullUrl;
			if (endpoint.startsWith('http')) {
				// L'endpoint est déjà une URL complète
				fullUrl = endpoint;
			} else {
				// Combiner base et endpoint
				fullUrl = baseUrl;
				if (!fullUrl.endsWith('/') && !endpoint.startsWith('/')) {
					fullUrl += '/';
				}
				fullUrl += endpoint;
			}

			// Préparer les en-têtes
			let headers;
			try {
				headers = JSON.parse(headersTextarea.value);
			} catch (e) {
				alert('Format JSON invalide pour les en-têtes: ' + e.message);
				return;
			}

			// Préparer le corps pour POST/PUT
			let body = null;
			if (method === 'POST' || method === 'PUT') {
				try {
					body = JSON.parse(reqBodyTextarea.value);
				} catch (e) {
					alert('Format JSON invalide pour le corps: ' + e.message);
					return;
				}
			}

			// Ajouter l'authentification si nécessaire
			if (useAuth) {
				const username = usernameInput.value.trim();
				const password = passwordInput.value.trim();
				if (username && password) {
					headers['Authorization'] = 'Basic ' + btoa(username + ':' + password);
				}
			}

			// Afficher le chargement
			loadingDiv.classList.remove('d-none');
			responseBodyPre.textContent = 'Envoi de la requête...';
			responseHeadersPre.textContent = '-';
			requestDetailsPre.textContent = '-';

			// Préparer les options de la requête
			const options = {
				method: method,
				headers: headers,
				credentials: 'include'
			};

			if (body) {
				options.body = JSON.stringify(body);
			}

			// Détails de la requête pour l'affichage
			const requestDetails = {
				url: fullUrl,
				method: method,
				headers: headers,
				body: body
			};

			requestDetailsPre.textContent = JSON.stringify(requestDetails, null, 2);

			// Horodatage pour calculer le temps de réponse
			const startTime = performance.now();

			try {
				let response;

				if (useProxy) {
					// Utiliser le proxy backend pour contourner CORS
					const proxyUrl = '../backend-proxy.php';
					const proxyBody = {
						url: fullUrl,
						method: method,
						headers: headers
					};

					if (body) {
						proxyBody.data = body;
					}

					response = await fetch(proxyUrl, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json'
						},
						body: JSON.stringify(proxyBody)
					});
				} else {
					// Requête directe
					response = await fetch(fullUrl, options);
				}

				// Calculer le temps de réponse
				const endTime = performance.now();
				const responseTime = Math.round(endTime - startTime);

				// Obtenir les en-têtes de réponse
				const responseHeaders = {};
				response.headers.forEach((value, name) => {
					responseHeaders[name] = value;
				});

				// Afficher les informations de statut
				statusCodeDiv.innerHTML = `
                <div class="alert ${response.ok ? 'alert-success' : 'alert-danger'}">
                    Statut: ${response.status} ${response.statusText} | Temps: ${responseTime}ms
                </div>
            `;
				statusCodeDiv.classList.remove('d-none');

				// Afficher les en-têtes
				responseHeadersPre.textContent = JSON.stringify(responseHeaders, null, 2);

				// Traiter le corps de la réponse
				const responseText = await response.text();
				let responseBody;

				try {
					// Essayer de formater comme JSON
					responseBody = JSON.stringify(JSON.parse(responseText), null, 2);
				} catch (e) {
					// Si ce n'est pas du JSON, afficher le texte brut
					responseBody = responseText;
				}

				responseBodyPre.textContent = responseBody;

				// Ajouter à l'historique
				addToHistory(
					method,
					fullUrl,
					response.status,
					responseTime,
					JSON.stringify(requestDetails, null, 2),
					JSON.stringify(responseHeaders, null, 2),
					responseBody
				);

			} catch (error) {
				console.error('Erreur de fetch:', error);

				statusCodeDiv.innerHTML = `
                <div class="alert alert-danger">
                    Erreur: ${error.message}
                </div>
            `;
				statusCodeDiv.classList.remove('d-none');

				responseBodyPre.textContent = 'Erreur: ' + error.message;

				// Ajouter à l'historique même en cas d'erreur
				addToHistory(
					method,
					fullUrl,
					0,
					0,
					JSON.stringify(requestDetails, null, 2),
					'-',
					'Erreur: ' + error.message
				);
			} finally {
				// Masquer le chargement
				loadingDiv.classList.add('d-none');
			}
		});

		// Initialiser l'historique
		updateHistoryTable();
	});
</script>