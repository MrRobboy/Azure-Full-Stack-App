# Guide de Déploiement

## Prérequis

### 1. Environnement

- PHP 7.4 ou supérieur
- Apache/Nginx
- SSL/TLS
- Accès SSH
- Azure CLI

### 2. Extensions PHP

- curl
- json
- openssl
- mbstring
- zip

### 3. Permissions

```bash
# Créer les répertoires
mkdir -p cache logs metrics

# Définir les permissions
chmod 755 cache logs metrics
chown www-data:www-data cache logs metrics
```

## Configuration

### 1. Variables d'Environnement

```bash
# .env
BACKEND_URL=https://app-backend-esgi-app.azurewebsites.net
FRONTEND_URL=https://app-frontend-esgi-app.azurewebsites.net
ENVIRONMENT=production
DEBUG=false
```

### 2. Configuration Apache

```apache
# /etc/apache2/sites-available/api.conf
<VirtualHost *:80>
    ServerName app-frontend-esgi-app.azurewebsites.net
    DocumentRoot /var/www/html

    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/api-error.log
    CustomLog ${APACHE_LOG_DIR}/api-access.log combined
</VirtualHost>
```

### 3. Configuration Nginx

```nginx
# /etc/nginx/sites-available/api
server {
    listen 80;
    server_name app-frontend-esgi-app.azurewebsites.net;
    root /var/www/html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

## Déploiement

### 1. Préparation

```bash
# Cloner le repository
git clone https://github.com/username/Azure-Full-Stack-App.git
cd Azure-Full-Stack-App/front

# Installer les dépendances
composer install --no-dev

# Configurer l'environnement
cp .env.example .env
nano .env
```

### 2. Build

```bash
# Nettoyer le cache
rm -rf cache/*

# Optimiser l'autoloader
composer dump-autoload -o

# Vérifier les permissions
chmod -R 755 .
chown -R www-data:www-data .
```

### 3. Déploiement Azure

```bash
# Login Azure
az login

# Créer le groupe de ressources
az group create --name esgi-app --location westeurope

# Créer l'app service
az appservice plan create --name esgi-app-plan --resource-group esgi-app --sku B1

# Créer l'app web
az webapp create --name app-frontend-esgi-app --resource-group esgi-app --plan esgi-app-plan

# Configurer l'app
az webapp config set --name app-frontend-esgi-app --resource-group esgi-app --php-version 7.4

# Déployer
az webapp deployment source config-local-git --name app-frontend-esgi-app --resource-group esgi-app
git remote add azure <git-url>
git push azure master
```

## Vérification

### 1. Tests

```bash
# Exécuter les tests
php tools/proxy-test-suite.php

# Vérifier les logs
tail -f logs/error.log
```

### 2. Monitoring

```bash
# Vérifier les métriques
php tools/metrics-dashboard.php

# Vérifier le cache
ls -l cache/
```

### 3. Sécurité

```bash
# Vérifier SSL
curl -I https://app-frontend-esgi-app.azurewebsites.net

# Vérifier les headers
curl -I -X OPTIONS https://app-frontend-esgi-app.azurewebsites.net/api-bridge.php
```

## Maintenance

### 1. Mise à Jour

```bash
# Pull les changements
git pull origin master

# Mettre à jour les dépendances
composer update

# Nettoyer le cache
rm -rf cache/*

# Redémarrer les services
sudo systemctl restart apache2
# ou
sudo systemctl restart nginx
```

### 2. Backup

```bash
# Backup des fichiers
tar -czf backup-$(date +%Y%m%d).tar.gz .

# Backup de la base de données
mysqldump -u user -p database > backup-$(date +%Y%m%d).sql
```

### 3. Restore

```bash
# Restore des fichiers
tar -xzf backup-20240516.tar.gz

# Restore de la base de données
mysql -u user -p database < backup-20240516.sql
```

## Monitoring

### 1. Logs

```bash
# Apache
tail -f /var/log/apache2/api-error.log
tail -f /var/log/apache2/api-access.log

# Application
tail -f logs/error.log
```

### 2. Métriques

```bash
# Voir les métriques en temps réel
php tools/metrics-monitor.php

# Générer un rapport
php tools/metrics-report.php --period=day
```

### 3. Alertes

```bash
# Configurer les alertes
php tools/metrics-alerts.php --setup

# Tester les alertes
php tools/metrics-alerts.php --test
```

## Dépannage

### 1. Problèmes Courants

- Erreur 404 : Vérifier les règles de réécriture
- Erreur 500 : Vérifier les logs d'erreur
- Problèmes de performance : Vérifier le cache et les métriques
- Problèmes de sécurité : Vérifier les headers et les logs

### 2. Commandes Utiles

```bash
# Vérifier les services
systemctl status apache2
systemctl status nginx
systemctl status php7.4-fpm

# Vérifier les logs
journalctl -u apache2
journalctl -u nginx
journalctl -u php7.4-fpm

# Vérifier l'espace disque
df -h

# Vérifier la mémoire
free -m
```

## Support

### 1. Documentation

- Guide d'installation
- Guide d'API
- Guide de métriques
- Guide d'architecture

### 2. Contact

- Email : support@example.com
- Slack : #deployment-support
- Jira : PROJ-123

### 3. Procédures

- Incident majeur
- Mise à jour critique
- Rollback
- Maintenance planifiée
