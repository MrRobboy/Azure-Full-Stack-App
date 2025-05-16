# Guide de Contribution

## Vue d'ensemble

Ce guide explique comment contribuer au projet Azure-Full-Stack-App. Nous apprécions toutes les contributions, qu'il s'agisse de rapports de bugs, d'améliorations de la documentation ou de nouvelles fonctionnalités.

## Processus de Contribution

### 1. Préparation

#### Fork et Clone

```bash
# Fork le repository
# Cloner votre fork
git clone https://github.com/your-username/Azure-Full-Stack-App.git
cd Azure-Full-Stack-App

# Ajouter le repository original
git remote add upstream https://github.com/original-owner/Azure-Full-Stack-App.git
```

#### Branches

```bash
# Créer une nouvelle branche
git checkout -b feature/your-feature-name

# Ou pour un bug fix
git checkout -b fix/your-bug-fix
```

### 2. Développement

#### Environnement

```bash
# Installer les dépendances
composer install

# Configurer l'environnement
cp .env.example .env
nano .env
```

#### Tests

```bash
# Exécuter les tests
php tools/proxy-test-suite.php

# Vérifier le style de code
composer run-script phpcs
```

#### Commits

```bash
# Ajouter les changements
git add .

# Créer un commit
git commit -m "feat: add new feature"
```

### 3. Pull Request

#### Préparation

```bash
# Mettre à jour votre branche
git fetch upstream
git rebase upstream/master

# Pousser les changements
git push origin feature/your-feature-name
```

#### Template

```markdown
## Description

Description détaillée des changements

## Type de changement

- [ ] Bug fix
- [ ] Nouvelle fonctionnalité
- [ ] Amélioration de la documentation
- [ ] Refactoring

## Tests

- [ ] Tests unitaires
- [ ] Tests d'intégration
- [ ] Tests de performance

## Checklist

- [ ] Mon code suit les standards de style
- [ ] J'ai mis à jour la documentation
- [ ] J'ai ajouté des tests
- [ ] Les tests passent
- [ ] J'ai vérifié les performances
```

## Standards de Code

### 1. PHP

#### Style

```php
// PSR-12
namespace App;

use App\Config;
use App\Utils;

class Example
{
    private const MAX_LENGTH = 100;

    public function __construct(
        private Config $config,
        private Utils $utils
    ) {
    }

    public function process(string $input): string
    {
        if (strlen($input) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException('Input too long');
        }

        return $this->utils->format($input);
    }
}
```

#### Documentation

```php
/**
 * Description de la classe
 *
 * @package App
 */
class Example
{
    /**
     * Description de la méthode
     *
     * @param string $input Description du paramètre
     * @return string Description du retour
     * @throws \InvalidArgumentException Description de l'exception
     */
    public function process(string $input): string
    {
    }
}
```

### 2. Tests

#### Unitaires

```php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Example;

class ExampleTest extends TestCase
{
    public function testProcess(): void
    {
        $example = new Example();
        $result = $example->process('test');
        $this->assertEquals('expected', $result);
    }
}
```

#### Intégration

```php
namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Api\Client;

class ApiTest extends TestCase
{
    public function testApiCall(): void
    {
        $client = new Client();
        $response = $client->get('/endpoint');
        $this->assertEquals(200, $response->getStatusCode());
    }
}
```

### 3. Documentation

#### Markdown

````markdown
# Titre

## Sous-titre

### Section

- Liste
- À
- Puces

1. Liste
2. Numérotée
3. Points

```code
Code block
```
````

> Citation

````

#### Commentaires
```php
// Commentaire sur une ligne

/*
 * Commentaire
 * sur plusieurs
 * lignes
 */

/**
 * Documentation
 * de fonction
 */
````

## Workflow Git

### 1. Branches

#### Types

- `master` : Production
- `develop` : Développement
- `feature/*` : Nouvelles fonctionnalités
- `fix/*` : Corrections de bugs
- `docs/*` : Documentation
- `test/*` : Tests

#### Naming

```bash
# Fonctionnalité
feature/user-authentication

# Bug fix
fix/login-error

# Documentation
docs/api-guide

# Tests
test/performance
```

### 2. Commits

#### Format

```
type(scope): description

[optional body]

[optional footer]
```

#### Types

- `feat` : Nouvelle fonctionnalité
- `fix` : Correction de bug
- `docs` : Documentation
- `style` : Formatage
- `refactor` : Refactoring
- `test` : Tests
- `chore` : Maintenance

#### Exemples

```bash
git commit -m "feat(auth): add OAuth2 support"
git commit -m "fix(api): handle timeout errors"
git commit -m "docs(readme): update installation guide"
```

### 3. Pull Requests

#### Process

1. Fork le repository
2. Créer une branche
3. Développer
4. Tester
5. Documenter
6. Soumettre PR

#### Review

1. Tests passent
2. Code style OK
3. Documentation à jour
4. Pas de conflits
5. Description claire

## Communication

### 1. Issues

#### Template

```markdown
## Description

Description détaillée du problème

## Reproduction

1. Étape 1
2. Étape 2
3. Étape 3

## Comportement attendu

Description du comportement attendu

## Comportement actuel

Description du comportement actuel

## Environnement

- OS :
- PHP :
- Version :
```

### 2. Discussions

#### Forums

- GitHub Discussions
- Slack
- Email

#### Réunions

- Weekly sync
- Planning
- Review

## Support

### 1. Ressources

- Documentation
- Wiki
- Exemples
- Templates

### 2. Contact

- Maintainers
- Community
- Support

### 3. Contribution

- Code
- Documentation
- Tests
- Review
