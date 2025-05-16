# État Actuel de l'Application

## Architecture Générale

### Frontend (Azure Frontend)

- **URL**: https://app-frontend-esgi-app.azurewebsites.net
- **Technologies**: PHP, JavaScript, HTML/CSS
- **Structure**: Application web classique avec fichiers PHP et ressources statiques

### Backend (Azure Backend)

- **URL**: https://app-backend-esgi-app.azurewebsites.net
- **API Base URL**: https://app-backend-esgi-app.azurewebsites.net/api
- **Technologies**: API REST

## Architecture Détaillée du Backend

### Structure des Dossiers

- **`/config`**: Configuration de l'application
- **`/controllers`**: Logique métier et gestion des requêtes
- **`/models`**: Modèles de données et interactions avec la base de données
- **`/routes`**: Définition des routes API
- **`/services`**: Services métier et utilitaires
- **`/database`**: Scripts et migrations de base de données

### Détail des Sous-Dossiers

#### 1. Controllers (`/controllers`)

- **`AuthController.php`** (146 lignes)

     - Gestion de l'authentification
     - Login/Logout
     - Vérification des sessions

- **`MatiereController.php`** (164 lignes)

     - Gestion des matières
     - CRUD des matières
     - Association avec les classes

- **`NoteController.php`** (255 lignes)

     - Gestion des notes
     - Calcul des moyennes
     - Historique des notes

- **`UserController.php`** (160 lignes)

     - Gestion des utilisateurs
     - Profils utilisateurs
     - Permissions

- **`ExamenController.php`** (175 lignes)

     - Gestion des examens
     - Planning
     - Résultats

- **`ClasseController.php`** (246 lignes)

     - Gestion des classes
     - Effectifs
     - Emplois du temps

- **`ProfController.php`** (121 lignes)
     - Gestion des professeurs
     - Matières enseignées
     - Disponibilités

#### 2. Models (`/models`)

- **`User.php`** (183 lignes)

     - Modèle utilisateur
     - Authentification
     - Rôles et permissions

- **`Note.php`** (216 lignes)

     - Modèle note
     - Calculs statistiques
     - Historique

- **`Matiere.php`** (166 lignes)

     - Modèle matière
     - Coefficients
     - Programmes

- **`Classe.php`** (175 lignes)

     - Modèle classe
     - Effectifs
     - Niveaux

- **`Eleve.php`** (141 lignes)

     - Modèle élève
     - Informations personnelles
     - Notes et résultats

- **`Examen.php`** (225 lignes)

     - Modèle examen
     - Dates et horaires
     - Salles

- **`Prof.php`** (195 lignes)

     - Modèle professeur
     - Matières
     - Classes

- **`UserPrivilege.php`** (97 lignes)
     - Gestion des privilèges
     - Niveaux d'accès
     - Permissions

#### 3. Routes (`/routes`)

- **`api.php`** (756 lignes)
     - Définition de toutes les routes API
     - Gestion des endpoints
     - Middleware et authentification
     - Validation des requêtes

#### 4. Services (`/services`)

- **`DatabaseService.php`** (137 lignes)

     - Connexion à la base de données
     - Gestion des transactions
     - Requêtes préparées

- **`SqlHelper.php`** (113 lignes)

     - Utilitaires SQL
     - Construction de requêtes
     - Formatage des résultats

- **`ErrorService.php`** (91 lignes)
     - Gestion des erreurs
     - Logging
     - Formatage des réponses d'erreur

#### 5. Config (`/config`)

- **`config.php`** (61 lignes)

     - Configuration générale
     - Variables d'environnement
     - Paramètres système

- **`database.php`** (39 lignes)
     - Configuration base de données
     - Credentials
     - Options de connexion

#### 6. Database (`/database`)

- **`gestion_notes.sql`** (305 lignes)
     - Schéma de la base de données
     - Tables et relations
     - Index et contraintes
     - Données initiales

### Relations entre les Composants

1. **Flux d'Authentification**:

      ```
      Route API → AuthController → User Model → DatabaseService
      ```

2. **Gestion des Notes**:

      ```
      Route API → NoteController → Note Model → DatabaseService
      ```

3. **Gestion des Classes**:
      ```
      Route API → ClasseController → Classe Model → DatabaseService
      ```

### Points d'Attention Backend

1. **Sécurité**:

      - Validation des entrées dans les controllers
      - Gestion des sessions dans AuthController
      - Protection contre les injections SQL dans DatabaseService

2. **Performance**:

      - Indexation de la base de données
      - Mise en cache des requêtes fréquentes
      - Optimisation des requêtes SQL

3. **Maintenance**:
      - Documentation des API dans routes/api.php
      - Gestion des erreurs centralisée
      - Logging des opérations critiques

## Flux de Fonctionnement du Site Web

### 1. Flux de Requête (Navigateur → Base de Données)

#### A. Requête Initiale (Navigateur → Frontend)

```
Navigateur
  ↓ (Requête HTTP)
Frontend (Azure Frontend)
  ↓ (JavaScript)
api-service.js
  ↓ (Configuration)
config.js
  ↓ (URL Proxy)
Proxy (api-bridge.php)
```

#### B. Traitement Proxy (Frontend → Backend)

```
api-bridge.php
  ↓ (Vérification CORS)
Headers de Sécurité
  ↓ (Construction URL)
URL Backend + Endpoint
  ↓ (Requête cURL)
Backend API
```

#### C. Traitement Backend (API → Base de Données)

```
Backend API
  ↓ (Route API)
api.php (Router)
  ↓ (Controller)
Controller Spécifique
  ↓ (Model)
Model Correspondant
  ↓ (Service)
DatabaseService
  ↓ (Requête SQL)
Base de Données
```

### 2. Flux de Réponse (Base de Données → Navigateur)

#### A. Traitement Données (Base de Données → Backend)

```
Base de Données
  ↓ (Résultat SQL)
DatabaseService
  ↓ (Formatage)
Model
  ↓ (Logique Métier)
Controller
  ↓ (Format API)
Router API
  ↓ (Réponse HTTP)
Backend API
```

#### B. Traitement Proxy (Backend → Frontend)

```
Backend API
  ↓ (Réponse cURL)
api-bridge.php
  ↓ (Formatage)
Headers CORS
  ↓ (JSON)
Frontend
```

#### C. Affichage (Frontend → Navigateur)

```
Frontend
  ↓ (Traitement)
api-service.js
  ↓ (Mise à jour UI)
JavaScript
  ↓ (Rendu)
Navigateur
```

### 3. Exemple Concret : Processus de Login

1. **Requête Initiale**:

      ```
      Navigateur
        ↓ (POST /login)
      login.php
        ↓ (Form Submit)
      api-service.js
        ↓ (POST /api-bridge.php?endpoint=auth/login)
      api-bridge.php
      ```

2. **Traitement Backend**:

      ```
      api-bridge.php
        ↓ (POST /api/auth/login)
      Backend API
        ↓ (Route)
      AuthController
        ↓ (Validation)
      User Model
        ↓ (Vérification)
      DatabaseService
        ↓ (SELECT)
      Base de Données
      ```

3. **Réponse**:
      ```
      Base de Données
        ↓ (Résultat)
      User Model
        ↓ (Format)
      AuthController
        ↓ (Token)
      Backend API
        ↓ (JSON)
      api-bridge.php
        ↓ (CORS)
      Frontend
        ↓ (Session)
      Navigateur
      ```

### 4. Gestion des Erreurs

1. **Niveau Base de Données**:

      ```
      Erreur SQL
        ↓ (DatabaseService)
      Formatage Erreur
        ↓ (Model)
      Message d'Erreur
      ```

2. **Niveau Backend**:

      ```
      Erreur API
        ↓ (Controller)
      Format JSON
        ↓ (Headers)
      Réponse HTTP
      ```

3. **Niveau Proxy**:

      ```
      Erreur Backend
        ↓ (api-bridge.php)
      Format CORS
        ↓ (Headers)
      Réponse Frontend
      ```

4. **Niveau Frontend**:
      ```
      Erreur Proxy
        ↓ (api-service.js)
      Notification
        ↓ (UI)
      Affichage Erreur
      ```

### 5. Points de Contrôle et Sécurité

1. **CORS**:

      - Vérification des origines
      - Headers de sécurité
      - Méthodes autorisées

2. **Authentification**:

      - Validation des tokens
      - Sessions PHP
      - Permissions utilisateur

3. **Validation**:

      - Données entrantes
      - Format des requêtes
      - Types de données

4. **Logging**:
      - Requêtes API
      - Erreurs système
      - Actions utilisateur

## Formats de Données par Étape

### 1. Format des Requêtes

#### A. Navigateur → Frontend (login.php)

```javascript
// Format de la requête de login
{
    "email": "utilisateur@exemple.com",
    "password": "motdepasse123"
}

// Headers HTTP
{
    "Content-Type": "application/json",
    "Accept": "application/json"
}
```

#### B. Frontend → Proxy (api-service.js)

```javascript
// URL format
"api-bridge.php?endpoint=auth/login"

// Headers HTTP
{
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-Requested-With": "XMLHttpRequest"
}

// Corps de la requête (inchangé)
{
    "email": "utilisateur@exemple.com",
    "password": "motdepasse123"
}
```

#### C. Proxy → Backend (api-bridge.php)

```php
// URL format
"https://app-backend-esgi-app.azurewebsites.net/api/auth/login"

// Headers HTTP
{
    "Content-Type": "application/json",
    "Accept": "application/json",
    "Authorization": "Bearer {token}" // Si authentifié
}

// Corps de la requête (inchangé)
{
    "email": "utilisateur@exemple.com",
    "password": "motdepasse123"
}
```

#### D. Backend → Base de Données

```sql
-- Requête SQL (exemple)
SELECT * FROM users
WHERE email = :email
AND password_hash = :password_hash
LIMIT 1;
```

### 2. Format des Réponses

#### A. Base de Données → Backend

```php
// Résultat SQL (format array PHP)
[
    'id' => 1,
    'email' => 'utilisateur@exemple.com',
    'nom' => 'Dupont',
    'prenom' => 'Jean',
    'role' => 'professeur',
    'created_at' => '2024-01-01 00:00:00'
]
```

#### B. Backend → Proxy

```json
// Format JSON
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "email": "utilisateur@exemple.com",
            "nom": "Dupont",
            "prenom": "Jean",
            "role": "professeur"
        },
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
        "expires_in": 3600
    },
    "message": "Connexion réussie"
}

// Headers HTTP
{
    "Content-Type": "application/json",
    "Access-Control-Allow-Origin": "*",
    "Access-Control-Allow-Methods": "GET, POST, OPTIONS",
    "Access-Control-Allow-Headers": "Content-Type, Authorization"
}
```

#### C. Proxy → Frontend

```json
// Format JSON (inchangé)
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "email": "utilisateur@exemple.com",
            "nom": "Dupont",
            "prenom": "Jean",
            "role": "professeur"
        },
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
        "expires_in": 3600
    },
    "message": "Connexion réussie"
}

// Headers HTTP
{
    "Content-Type": "application/json",
    "Access-Control-Allow-Origin": "*",
    "Access-Control-Allow-Methods": "GET, POST, OPTIONS",
    "Access-Control-Allow-Headers": "Content-Type, Authorization"
}
```

#### D. Frontend → Navigateur

```javascript
// Données stockées en session
{
    user: {
        id: 1,
        email: "utilisateur@exemple.com",
        nom: "Dupont",
        prenom: "Jean",
        role: "professeur"
    },
    token: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    expiresAt: "2024-01-01T01:00:00Z"
}

// Notification UI
{
    type: "success",
    message: "Connexion réussie",
    duration: 3000
}
```

### 3. Format des Erreurs

#### A. Erreur Base de Données

```php
// Format PHP
[
    'error' => true,
    'code' => 'DB_ERROR',
    'message' => 'Erreur de connexion à la base de données',
    'details' => [
        'sql_state' => 'HY000',
        'error_code' => 1045,
        'error_message' => 'Access denied for user...'
    ]
]
```

#### B. Erreur Backend

```json
// Format JSON
{
	"success": false,
	"error": {
		"code": "AUTH_ERROR",
		"message": "Identifiants invalides",
		"details": {
			"field": "email",
			"reason": "not_found"
		}
	},
	"timestamp": "2024-01-01T00:00:00Z"
}
```

#### C. Erreur Proxy

```json
// Format JSON
{
	"success": false,
	"error": {
		"code": "PROXY_ERROR",
		"message": "Impossible de joindre le backend",
		"details": {
			"url": "https://app-backend-esgi-app.azurewebsites.net/api/auth/login",
			"status": 503,
			"response": "Service Unavailable"
		}
	},
	"timestamp": "2024-01-01T00:00:00Z"
}
```

#### D. Erreur Frontend

```javascript
// Format JavaScript
{
    type: "error",
    message: "Impossible de se connecter au serveur",
    details: {
        code: "NETWORK_ERROR",
        status: 503,
        url: "api-bridge.php?endpoint=auth/login"
    },
    timestamp: "2024-01-01T00:00:00Z"
}

// Notification UI
{
    type: "error",
    message: "Impossible de se connecter au serveur",
    duration: 5000,
    showDetails: true
}
```

### 4. Formats Spéciaux

#### A. Requêtes de Fichiers

```javascript
// Upload de fichier
FormData {
    "file": File,
    "type": "image/jpeg",
    "size": 1024,
    "name": "photo.jpg"
}

// Headers
{
    "Content-Type": "multipart/form-data",
    "X-File-Name": "photo.jpg",
    "X-File-Type": "image/jpeg"
}
```

#### B. Requêtes de Streaming

```javascript
// Configuration
{
    "stream": true,
    "chunkSize": 8192,
    "contentType": "application/octet-stream"
}

// Headers
{
    "Transfer-Encoding": "chunked",
    "Content-Type": "application/octet-stream"
}
```

#### C. WebSocket

```javascript
// Format de message
{
    "type": "notification",
    "action": "new_message",
    "data": {
        "message": "Nouveau message reçu",
        "from": "user123",
        "timestamp": "2024-01-01T00:00:00Z"
    }
}
```

### Points d'Entrée API

- **`api-auth-login.php`**: Gestion de l'authentification
- **`api-notes.php`**: Gestion des notes
- **`api-router.php`**: Routeur principal de l'API
- **`api-cors.php`**: Configuration CORS
- **`status.php`**: Endpoint de statut et santé de l'API

### Configuration Serveur

- **`web.config`**: Configuration IIS
- **`.htaccess`**: Règles Apache
- **`default.conf`**: Configuration Nginx
- **`nginx-adapter.php`**: Adaptateur pour Nginx
- **`.user.ini`**: Configuration PHP

### Fichiers de Support

- **`azure-init.php`**: Initialisation Azure
- **`deployment-complete.php`**: Script de déploiement
- **`cors-test.php`**: Tests CORS
- **`test-api.php`**: Tests API
- **`info.php`**: Informations système

### Endpoints API Principaux

1. **Authentification**:

      - `POST /api/auth/login`: Connexion utilisateur
      - `POST /api/auth/logout`: Déconnexion
      - `GET /api/auth/status`: État de l'authentification

2. **Notes**:

      - `GET /api/notes`: Liste des notes
      - `POST /api/notes`: Création d'une note
      - `PUT /api/notes/{id}`: Mise à jour d'une note
      - `DELETE /api/notes/{id}`: Suppression d'une note

3. **Statut**:
      - `GET /api/status`: État du serveur
      - `GET /api/health`: Santé de l'application

### Sécurité et Configuration

1. **CORS**:

      - Configuration dans `api-cors.php`
      - Headers de sécurité
      - Gestion des origines autorisées

2. **Authentification**:

      - JWT pour les tokens
      - Sessions PHP
      - Validation des entrées

3. **Base de Données**:
      - Configuration dans `/config`
      - Migrations dans `/database`
      - Modèles dans `/models`

## Composants Fonctionnels

### ✅ Système de Proxy

- **Fichiers Principaux**:

     - `api-bridge.php`: Proxy principal pour la communication avec le backend
     - `test-proxy.php`: Fichier de test pour vérifier le fonctionnement du proxy
     - `matieres-proxy.php`: Proxy spécifique pour les requêtes liées aux matières
     - `simple-proxy.php`: Proxy alternatif
     - `unified-proxy.php`: Proxy original (déprécié)

- **Configuration**:
     - `web.config`: Configuration IIS pour le routage et le traitement PHP
     - `config.js`: Configuration côté client pour la gestion des URLs et des proxies

### ✅ Système de Notification

- **Fichier**: `notification-system.js`
- **Fonctionnalités**:
     - Affichage des notifications de succès/erreur
     - Gestion des messages système
     - Intégration avec le système de login

### ✅ Système d'Authentification

- **Fichiers**:
     - `login.php`: Page de connexion
     - `session-handler.php`: Gestion des sessions
     - `logout.php`: Déconnexion

## État des Communications

### Frontend → Backend

1. **Mécanisme de Proxy**:

      - Le frontend utilise `api-bridge.php` comme proxy principal
      - Les requêtes sont formatées: `api-bridge.php?endpoint=auth/login`
      - Le proxy ajoute l'URL de base du backend: `https://app-backend-esgi-app.azurewebsites.net/api`

2. **Gestion des Erreurs**:
      - Erreurs 404: Fichier proxy non trouvé
      - Erreurs de communication: Gérées par le système de notification
      - Erreurs de session: Redirection vers la page de login

### Composants Interdépendants

1. **Login Flow**:

      ```
      login.php
      → api-service.js (gestion des requêtes)
      → api-bridge.php (proxy)
      → Backend API
      → session-handler.php (stockage session)
      → dashboard.php (redirection)
      ```

2. **Système de Proxy**:
      ```
      config.js (configuration)
      → api-service.js (requêtes)
      → api-bridge.php (proxy principal)
      → Backend API
      ```

## Problèmes Actuels

### ❌ Problèmes de Proxy

1. **404 sur api-bridge.php**:

      - Le fichier est présent mais non accessible
      - Problème de configuration IIS résolu
      - Problème de déploiement en cours de résolution

2. **Communication Backend**:
      - Les requêtes n'atteignent pas le backend
      - Erreurs de format de réponse (HTML au lieu de JSON)
      - Configuration CORS en cours d'amélioration

### ⚠️ Points d'Attention

1. **Configuration IIS**:

      - Règles de réécriture complexes
      - Gestion des fichiers PHP
      - Headers CORS
      - Permissions des fichiers proxy

2. **Sécurité**:
      - Validation des entrées
      - Gestion des sessions
      - Protection CORS
      - Headers de sécurité

## Prochaines Étapes Suggérées

1. **Résolution du Proxy**:

      - Vérifier le déploiement des fichiers proxy
      - Simplifier la configuration IIS
      - Tester l'accès direct aux fichiers proxy
      - Mettre à jour les permissions dans `.htaccess`

2. **Améliorations**:

      - Centraliser la gestion des erreurs
      - Améliorer la documentation
      - Ajouter des tests automatisés
      - Mettre à jour les configurations CORS

3. **Sécurité**:

      - Renforcer la validation des entrées
      - Améliorer la gestion des sessions
      - Configurer correctement CORS
      - Ajouter des headers de sécurité

4. **Documentation**:
      - Mettre à jour le guide d'installation
      - Documenter les configurations CORS
      - Ajouter des exemples de déploiement
      - Créer un guide de dépannage

## Fichiers Clés et Leurs Rôles

### Configuration

- `web.config`: Configuration IIS et routage
- `config.js`: Configuration côté client
- `api-service.js`: Service de communication API

### Proxy

- `api-bridge.php`: Proxy principal
- `test-proxy.php`: Tests de proxy
- `matieres-proxy.php`: Proxy spécifique

### Authentification

- `login.php`: Interface de connexion
- `session-handler.php`: Gestion des sessions
- `logout.php`: Déconnexion

### Templates

- `templates/base.php`: Template de base
- `templates/header.php`: En-tête
- `templates/footer.php`: Pied de page
