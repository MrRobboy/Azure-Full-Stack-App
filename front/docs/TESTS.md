# Guide de Tests

## Vue d'ensemble

Ce guide détaille les différents types de tests et les bonnes pratiques à suivre pour assurer la qualité du code.

## Types de Tests

### 1. Tests Unitaires

#### Configuration

```php
// phpunit.xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php"
         colors="true"
         verbose="true"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">src</directory>
        </include>
    </coverage>
</phpunit>
```

#### Exemple

```php
// tests/Unit/ProxyTest.php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Proxy;

class ProxyTest extends TestCase
{
    private Proxy $proxy;

    protected function setUp(): void
    {
        $this->proxy = new Proxy();
    }

    public function testValidateInput(): void
    {
        $this->assertTrue($this->proxy->validateInput('valid'));
        $this->assertFalse($this->proxy->validateInput(''));
    }

    public function testCheckRateLimit(): void
    {
        $this->assertTrue($this->proxy->checkRateLimit('127.0.0.1'));
        $this->assertFalse($this->proxy->checkRateLimit('127.0.0.1'));
    }
}
```

### 2. Tests d'Intégration

#### Configuration

```php
// phpunit.xml
<testsuites>
    <testsuite name="Integration">
        <directory>tests/Integration</directory>
    </testsuite>
</testsuites>
```

#### Exemple

```php
// tests/Integration/ApiTest.php
namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Api\Client;

class ApiTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $this->client = new Client();
    }

    public function testApiCall(): void
    {
        $response = $this->client->get('/status.php');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getBody());
    }

    public function testApiError(): void
    {
        $response = $this->client->get('/invalid');
        $this->assertEquals(404, $response->getStatusCode());
    }
}
```

### 3. Tests de Performance

#### Configuration

```php
// tests/Performance/PerformanceTest.php
namespace Tests\Performance;

use PHPUnit\Framework\TestCase;
use App\Proxy;

class PerformanceTest extends TestCase
{
    private Proxy $proxy;
    private const ITERATIONS = 100;

    protected function setUp(): void
    {
        $this->proxy = new Proxy();
    }

    public function testResponseTime(): void
    {
        $times = [];
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $start = microtime(true);
            $this->proxy->handleRequest('/status.php');
            $times[] = microtime(true) - $start;
        }
        $avg = array_sum($times) / count($times);
        $this->assertLessThan(0.1, $avg);
    }

    public function testMemoryUsage(): void
    {
        $start = memory_get_usage();
        $this->proxy->handleRequest('/status.php');
        $end = memory_get_usage();
        $this->assertLessThan(1024 * 1024, $end - $start);
    }
}
```

### 4. Tests de Sécurité

#### Configuration

```php
// tests/Security/SecurityTest.php
namespace Tests\Security;

use PHPUnit\Framework\TestCase;
use App\Proxy;

class SecurityTest extends TestCase
{
    private Proxy $proxy;

    protected function setUp(): void
    {
        $this->proxy = new Proxy();
    }

    public function testXssProtection(): void
    {
        $input = '<script>alert("xss")</script>';
        $output = $this->proxy->sanitizeOutput($input);
        $this->assertNotEquals($input, $output);
        $this->assertStringNotContainsString('<script>', $output);
    }

    public function testSqlInjection(): void
    {
        $input = "' OR '1'='1";
        $this->assertFalse($this->proxy->validateInput($input));
    }
}
```

## Exécution des Tests

### 1. Commandes

#### Tous les Tests

```bash
# Exécuter tous les tests
php vendor/bin/phpunit

# Exécuter avec couverture
php vendor/bin/phpunit --coverage-html coverage
```

#### Tests Spécifiques

```bash
# Tests unitaires
php vendor/bin/phpunit tests/Unit

# Tests d'intégration
php vendor/bin/phpunit tests/Integration

# Tests de performance
php vendor/bin/phpunit tests/Performance

# Tests de sécurité
php vendor/bin/phpunit tests/Security
```

### 2. Configuration

#### PHPUnit

```xml
<!-- phpunit.xml -->
<phpunit>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
    </php>
</phpunit>
```

#### Environnement

```bash
# .env.testing
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

## Bonnes Pratiques

### 1. Structure

#### Organisation

```
tests/
├── Unit/
│   ├── ProxyTest.php
│   └── ValidatorTest.php
├── Integration/
│   ├── ApiTest.php
│   └── DatabaseTest.php
├── Performance/
│   ├── ResponseTimeTest.php
│   └── MemoryUsageTest.php
└── Security/
    ├── XssTest.php
    └── SqlInjectionTest.php
```

#### Naming

```php
// Tests unitaires
class UserTest extends TestCase
{
    public function testValidateEmail(): void
    {
    }
}

// Tests d'intégration
class ApiIntegrationTest extends TestCase
{
    public function testApiResponse(): void
    {
    }
}
```

### 2. Assertions

#### Types

```php
// Égalité
$this->assertEquals($expected, $actual);
$this->assertSame($expected, $actual);

// Contient
$this->assertContains($needle, $haystack);
$this->assertStringContainsString($needle, $haystack);

// Type
$this->assertIsString($value);
$this->assertIsArray($value);

// Exception
$this->expectException(InvalidArgumentException::class);
```

#### Messages

```php
$this->assertEquals(
    $expected,
    $actual,
    'Le résultat ne correspond pas à la valeur attendue'
);
```

### 3. Mocks

#### Configuration

```php
// Configuration
use PHPUnit\Framework\MockObject\MockObject;

class ServiceTest extends TestCase
{
    private MockObject $service;

    protected function setUp(): void
    {
        $this->service = $this->createMock(Service::class);
    }
}
```

#### Utilisation

```php
// Configuration
$this->service->expects($this->once())
    ->method('getData')
    ->with('id')
    ->willReturn(['data']);

// Vérification
$result = $this->service->getData('id');
$this->assertEquals(['data'], $result);
```

## CI/CD

### 1. GitHub Actions

#### Configuration

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
        test:
                runs-on: ubuntu-latest
                steps:
                        - uses: actions/checkout@v2
                        - name: Setup PHP
                          uses: shivammathur/setup-php@v2
                          with:
                                  php-version: "7.4"
                        - name: Install dependencies
                          run: composer install
                        - name: Execute tests
                          run: vendor/bin/phpunit
```

### 2. Jenkins

#### Configuration

```groovy
// Jenkinsfile
pipeline {
    agent any
    stages {
        stage('Test') {
            steps {
                sh 'composer install'
                sh 'vendor/bin/phpunit'
            }
        }
    }
}
```

## Support

### 1. Documentation

#### Ressources

- Guide de tests
- Procédures de test
- Checklist de test
- Templates de test

#### Formation

- Tests unitaires
- Tests d'intégration
- Tests de performance
- Tests de sécurité

### 2. Contact

- Email : tests@example.com
- Slack : #testing
- Jira : PROJ-123
