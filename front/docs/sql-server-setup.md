# Guide de configuration SQL Server pour Azure-Full-Stack-App

Ce guide vous aidera à configurer Microsoft SQL Server pour l'application Azure-Full-Stack-App, qui a été migrée depuis MariaDB.

## Prérequis

1. **Microsoft SQL Server** - SQL Server 2019 ou supérieur recommandé
2. **SQL Server Management Studio (SSMS)** ou **Azure Data Studio** pour gérer la base de données
3. **PHP 8.0+** avec l'extension SQL Server (sqlsrv)

## Installation de l'extension PHP pour SQL Server

### Windows

1. Téléchargez les pilotes Microsoft PHP pour SQL Server depuis [le site officiel](https://docs.microsoft.com/fr-fr/sql/connect/php/download-drivers-php-sql-server)
2. Décompressez et copiez les fichiers `.dll` appropriés dans le dossier `ext` de votre installation PHP
3. Éditez votre fichier `php.ini` et ajoutez:
      ```ini
      extension=php_sqlsrv_xx_ts.dll
      extension=php_pdo_sqlsrv_xx_ts.dll
      ```
      (remplacez `xx` par votre version de PHP, par exemple `80` pour PHP 8.0)

### Linux

```bash
# Pour Ubuntu/Debian
sudo apt-get update
sudo apt-get install php-pdo php-sqlsrv

# Pour CentOS/RHEL
sudo yum install msodbcsql17 mssql-tools
sudo pecl install sqlsrv pdo_sqlsrv
```

## Configuration de la base de données

1. Créez une nouvelle base de données nommée `gestion_notes`:

```sql
CREATE DATABASE gestion_notes;
GO
USE gestion_notes;
GO
```

2. Exécutez le script SQL fourni dans `BDD/azureT-sql.sql` pour créer les tables et insérer les données initiales.

## Configuration de l'application

1. Modifiez le fichier `back/config/config.php` pour utiliser SQL Server:

```php
// Configuration de la base de données SQL Server
define('DB_TYPE', 'sqlsrv');
define('DB_HOST', 'localhost'); // Ou l'adresse IP du serveur SQL
define('DB_NAME', 'gestion_notes');
define('DB_USER', 'sa'); // Remplacez par votre utilisateur SQL Server
define('DB_PASS', 'Votre_Mot_De_Passe');
define('DB_PORT', '1433'); // Port par défaut pour SQL Server
```

## Vérification de l'installation

1. Ouvrez l'application dans votre navigateur
2. Essayez de vous connecter et vérifiez que toutes les fonctionnalités sont opérationnelles
3. Vérifiez les journaux d'erreur PHP si vous rencontrez des problèmes

## Résolution des problèmes courants

### Erreur de connexion à la base de données

Si vous obtenez une erreur de connexion:

- Vérifiez que SQL Server est en cours d'exécution
- Vérifiez que les identifiants de connexion sont corrects
- Assurez-vous que l'authentification SQL est activée sur le serveur

### Erreurs relatives aux requêtes SQL

Certaines requêtes peuvent nécessiter des ajustements pour être compatibles avec SQL Server:

- Remplacez `NOW()` par `GETDATE()`
- Les requêtes avec `LIMIT` doivent être remplacées par `OFFSET ... ROWS FETCH NEXT ... ROWS ONLY`
- Les fonctions de date peuvent différer

## Maintenance

### Sauvegarde de la base de données

```sql
BACKUP DATABASE gestion_notes
TO DISK = 'C:\Path\To\Backup\gestion_notes.bak'
WITH FORMAT, MEDIANAME = 'GestionNotesBackup',
NAME = 'Sauvegarde complète de gestion_notes';
```

### Restauration

```sql
RESTORE DATABASE gestion_notes
FROM DISK = 'C:\Path\To\Backup\gestion_notes.bak'
WITH REPLACE;
```
