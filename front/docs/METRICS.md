# Guide des Métriques

## Vue d'ensemble

Le système de métriques permet de suivre les performances, la santé et l'utilisation de l'API. Les métriques sont stockées au format JSON dans le répertoire `metrics/`.

## Types de Métriques

### 1. Performance

#### Temps de Réponse

```json
{
	"type": "response_time",
	"endpoint": "string",
	"method": "string",
	"timestamp": "string",
	"duration": "number",
	"status": "number"
}
```

#### Utilisation Mémoire

```json
{
	"type": "memory_usage",
	"timestamp": "string",
	"peak": "number",
	"current": "number",
	"limit": "number"
}
```

### 2. Cache

#### Taux de Cache

```json
{
	"type": "cache_hits",
	"endpoint": "string",
	"timestamp": "string",
	"hits": "number",
	"misses": "number",
	"ratio": "number"
}
```

#### Taille du Cache

```json
{
	"type": "cache_size",
	"timestamp": "string",
	"size": "number",
	"items": "number"
}
```

### 3. Sécurité

#### Rate Limiting

```json
{
	"type": "rate_limit",
	"ip": "string",
	"timestamp": "string",
	"requests": "number",
	"limit": "number",
	"remaining": "number"
}
```

#### Erreurs de Validation

```json
{
	"type": "validation_errors",
	"endpoint": "string",
	"timestamp": "string",
	"count": "number",
	"types": {
		"length": "number",
		"format": "number",
		"required": "number"
	}
}
```

### 4. Compression

#### Taux de Compression

```json
{
	"type": "compression_ratio",
	"endpoint": "string",
	"timestamp": "string",
	"original_size": "number",
	"compressed_size": "number",
	"ratio": "number"
}
```

## Format des Fichiers

### Structure

```
metrics/
├── performance/
│   ├── response_time.json
│   └── memory_usage.json
├── cache/
│   ├── hits.json
│   └── size.json
├── security/
│   ├── rate_limit.json
│   └── validation.json
└── compression/
    └── ratio.json
```

### Format JSON

```json
{
	"metadata": {
		"version": "1.0",
		"generated": "string",
		"retention": "number"
	},
	"data": [
		{
			"type": "string",
			"timestamp": "string",
			"values": {}
		}
	]
}
```

## Collecte des Métriques

### Fréquence

- Temps de réponse : À chaque requête
- Utilisation mémoire : Toutes les 5 minutes
- Cache : À chaque hit/miss
- Rate limiting : À chaque requête
- Compression : À chaque réponse compressée

### Rétention

- Par défaut : 7 jours
- Configurable dans `config/performance.php`
- Nettoyage automatique

## Visualisation

### Dashboard

```bash
# Lancer le dashboard
php tools/metrics-dashboard.php
```

### Exports

```bash
# Exporter en CSV
php tools/metrics-export.php --format=csv --type=response_time

# Exporter en JSON
php tools/metrics-export.php --format=json --type=all
```

## Alertes

### Configuration

```php
// config/performance.php
define('ALERT_THRESHOLDS', [
    'response_time' => 1000, // ms
    'memory_usage' => 0.8,   // 80%
    'cache_ratio' => 0.5,    // 50%
    'error_rate' => 0.1      // 10%
]);
```

### Types d'Alertes

1. Performance

      - Temps de réponse > 1s
      - Utilisation mémoire > 80%
      - Taux de cache < 50%

2. Sécurité

      - Taux d'erreurs > 10%
      - Rate limit atteint
      - Erreurs de validation fréquentes

3. Système
      - Espace disque < 10%
      - CPU > 90%
      - Mémoire swap utilisée

## Exemples d'Utilisation

### Analyse des Performances

```bash
# Voir les temps de réponse moyens
php tools/metrics-analyze.php --type=response_time --period=day

# Voir l'utilisation mémoire
php tools/metrics-analyze.php --type=memory_usage --period=week
```

### Surveillance en Temps Réel

```bash
# Surveiller les métriques en temps réel
php tools/metrics-monitor.php --types=all --interval=60
```

### Génération de Rapports

```bash
# Générer un rapport quotidien
php tools/metrics-report.php --period=day --format=html

# Générer un rapport hebdomadaire
php tools/metrics-report.php --period=week --format=pdf
```

## Intégration

### Prometheus

```yaml
# prometheus.yml
scrape_configs:
        - job_name: "api_metrics"
          static_configs:
                  - targets: ["localhost:9090"]
          metrics_path: "/metrics"
          scheme: "http"
```

### Grafana

```json
{
	"dashboard": {
		"title": "API Metrics",
		"panels": [
			{
				"title": "Response Time",
				"type": "graph",
				"datasource": "Prometheus",
				"targets": [
					{
						"expr": "rate(response_time_seconds_sum[5m])"
					}
				]
			}
		]
	}
}
```

## Support

Pour toute question ou problème :

1. Consulter la documentation
2. Vérifier les logs
3. Utiliser les outils d'analyse
4. Contacter l'équipe de support
