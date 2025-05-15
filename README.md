# Azure-Full-Stack-App

Application de gestion des notes avec Azure

## Migration vers SQL Server

Cette application a été migrée de MariaDB vers Microsoft SQL Server. Les principales modifications incluent:

1. **Configuration de la base de données**: Mise à jour pour supporter SQL Server

      - Nouveau paramètre `DB_TYPE` pour indiquer le type de base de données
      - Support du port SQL Server avec `DB_PORT`

2. **Classe d'abstraction**: Création d'une classe `SqlHelper` pour gérer les différences de syntaxe SQL

      - Adaptation des fonctions de date (NOW() vs GETDATE())
      - Prise en charge des spécificités SQL Server pour la pagination (LIMIT vs OFFSET/FETCH)

3. **Gestion des identifiants**: Méthode améliorée pour récupérer les IDs auto-incrémentés

      - Utilisation de SCOPE_IDENTITY() pour SQL Server
      - Rétrocompatibilité avec MySQL/MariaDB

4. **Documentation**: Ajout d'un guide de migration `sql-server-setup.md`

## Configuration

Voir le fichier `sql-server-setup.md` pour les instructions détaillées de configuration avec SQL Server.

## Développement

Pour tester la connexion à SQL Server, utilisez le script `back/test-sqlserver.php`.
