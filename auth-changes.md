# JWT Authentication Bypass Implementation

## Changements effectués pour supprimer l'authentification JWT

Pour simplifier l'accès à l'API et éviter les problèmes d'authentification, nous avons implémenté un bypass complet du système d'authentification JWT. Voici les modifications apportées:

### 1. Modification d'AuthController

- Tous les utilisateurs sont automatiquement authentifiés
- La méthode `isLoggedIn()` retourne toujours `true`
- Les informations d'utilisateur par défaut sont définies dans le constructeur
- Aucune vérification de mot de passe n'est effectuée

### 2. Modification des points d'entrée API

- `api-notes.php`: Suppression de la vérification d'authentification
- `routes/api.php`: Modification de la fonction `checkAuth()` pour qu'elle retourne toujours `true`
- Ajout d'un bypass global dans `azure-init.php`

### 3. Injection automatique de l'authentification

- Création d'un fichier `azure-init.php` qui:
     - Définit automatiquement une session avec des informations utilisateur
     - Fournit une fonction pour générer un token JWT valide
     - Ajoute automatiquement un header d'authentification si nécessaire
- Inclusion de ce fichier dans `index.php` pour s'assurer qu'il est chargé pour toutes les requêtes

## Comment cela fonctionne

1. Toutes les requêtes sont maintenant considérées comme authentifiées
2. Les endpoints qui nécessitaient une authentification sont désormais accessibles sans token JWT
3. Pour les endpoints qui vérifient spécifiquement le header `Authorization`, un header avec un token JWT valide est automatiquement injecté

## Données d'utilisateur par défaut

Les informations d'utilisateur suivantes sont utilisées pour toutes les sessions:

```php
$_SESSION['prof_id'] = 1;
$_SESSION['prof_nom'] = 'Admin';
$_SESSION['prof_prenom'] = 'User';
$_SESSION['prof_email'] = 'admin@example.com';
```

## Restaurer l'authentification

Pour restaurer l'authentification normale, il faudrait:

1. Retirer les modifications du fichier `AuthController.php`
2. Restaurer la vérification dans `api-notes.php`
3. Restaurer la fonction `checkAuth()` dans `routes/api.php`
4. Supprimer l'inclusion de `azure-init.php` dans `index.php`
