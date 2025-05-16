# Guide de Test du Proxy

## Fichier de Test Unifié

Le fichier `proxy-test.php` est un outil de test unifié qui permet de vérifier tous les aspects du proxy API. Il effectue des tests complets sur la connexion, la sécurité, les performances et la validation des entrées.

## Types de Tests

### 1. Tests de Connexion

Vérifie la connectivité avec tous les endpoints configurés :

- `status.php`
- `auth/login`
- `matieres`
- `notes`

Chaque test vérifie :

- Le code HTTP de la réponse
- La présence d'erreurs de connexion
- Les logs détaillés de la requête

### 2. Tests CORS

Vérifie la présence et la configuration des headers CORS :

- `Access-Control-Allow-Origin`
- `Access-Control-Allow-Methods`
- `Access-Control-Allow-Headers`
- `Access-Control-Max-Age`
- `Access-Control-Allow-Credentials`

### 3. Tests de Sécurité

Vérifie la présence des headers de sécurité :

- `X-Content-Type-Options`
- `X-Frame-Options`
- `X-XSS-Protection`
- `Strict-Transport-Security`
- `Content-Security-Policy`

### 4. Tests de Performance

Mesure le temps de réponse des requêtes :

- Seuil de performance : 1000ms
- Mesure précise en millisecondes
- Timeout configuré à 30 secondes

### 5. Tests de Rate Limit

Simule des requêtes multiples pour vérifier la limitation de taux :

- 10 requêtes consécutives
- Intervalle de 100ms entre les requêtes
- Vérification des réponses 429

### 6. Tests de Validation des Entrées

Vérifie la validation des données :

- Test avec entrée valide
- Test avec entrée invalide (dépassement de limite)
- Vérification des codes HTTP appropriés

## Utilisation

### Exécution des Tests

Pour exécuter tous les tests :

```bash
curl https://app-frontend-esgi-app.azurewebsites.net/proxy-test.php
```

### Format de la Réponse

La réponse est au format JSON et contient :

- Statut de chaque test (✅ ou ❌)
- Messages descriptifs
- Détails techniques
- Logs de débogage

### Exemple de Réponse

```json
{
	"connection_test_status": {
		"success": true,
		"status": "✅",
		"message": "Connection successful",
		"details": {
			"http_code": 200,
			"response": "...",
			"error": "",
			"verbose_log": "..."
		}
	},
	// ... autres tests ...
	"info": {
		"timestamp": "2024-05-16 16:40:38",
		"server": "nginx/1.26.2",
		"php_version": "8.2.27",
		"request_method": "GET",
		"request_uri": "/proxy-test.php",
		"query_string": ""
	}
}
```

## Configuration

Le fichier de test utilise une configuration centralisée dans la variable `$testConfig` :

```php
$testConfig = [
    'base_url' => 'https://app-frontend-esgi-app.azurewebsites.net',
    'endpoints' => [
        'status' => 'status.php',
        'auth' => 'auth/login',
        'matieres' => 'matieres',
        'notes' => 'notes'
    ],
    'security_headers' => [...],
    'cors_headers' => [...]
];
```

## Dépannage

### Erreurs Courantes

1. **Could not resolve host**

      - Vérifier l'URL de base dans la configuration
      - Vérifier la connectivité réseau

2. **Headers CORS manquants**

      - Vérifier la configuration CORS dans le proxy
      - Vérifier les headers dans la réponse

3. **Erreurs de timeout**
      - Augmenter le timeout dans la configuration
      - Vérifier la performance du serveur

### Logs de Débogage

Les logs verbeux sont disponibles dans la réponse pour chaque test :

- Détails de la requête cURL
- Headers de la réponse
- Erreurs éventuelles

## Maintenance

### Ajout de Nouveaux Tests

Pour ajouter un nouveau test :

1. Créer une nouvelle fonction de test
2. Ajouter l'appel dans la fonction `runTests`
3. Mettre à jour la documentation

### Modification de la Configuration

Pour modifier la configuration :

1. Mettre à jour la variable `$testConfig`
2. Ajuster les seuils et timeouts selon les besoins
3. Mettre à jour la documentation
