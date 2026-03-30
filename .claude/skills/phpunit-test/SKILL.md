---
name: phpunit-test
description: Writes PHPUnit 9 tests matching patterns in tests/PluginTest.php and tests/SourceFileAnalysisTest.php. Use when user says 'add test', 'write unit test', 'test this method', adds new methods to src/, or needs structural assertions via ReflectionClass. Covers reflection-based class/method/property assertions and file_get_contents source analysis. Do NOT use for integration tests requiring a live CloudLinux API connection or database.
---
# PHPUnit Test

## Critical

- **Never** instantiate `Cloudlinux` or call any method that hits the CloudLinux XML-RPC API in tests — tests must run offline.
- **Never** use PDO or database calls in tests; there is no DB available in this test environment.
- All test classes **must** be in namespace `Detain\MyAdminCloudlinux\Tests` and extend `PHPUnit\Framework\TestCase`.
- Tests live in `tests/` and must be autoloaded via `tests/bootstrap.php` (searches up the directory tree for the Composer autoloader).
- Run tests with `composer test` from the project root. Verify bootstrap finds an autoloader before assuming test failures are logic bugs.

## Instructions

### 1. Choose the right test class

Two established patterns exist:

| Goal | Class to add to (or model after) |
|---|---|
| Class structure, static props, method signatures, hook registration | `tests/PluginTest.php` |
| Source file content, function declarations, coding patterns | `tests/SourceFileAnalysisTest.php` |
| `composer.json` metadata | `tests/ComposerConfigTest.php` |

Verify the target file exists before editing: `ls tests/`.

### 2. Reflection-based structural tests (PluginTest pattern)

Use `ReflectionClass` to assert class structure without executing runtime code.

```php
namespace Detain\MyAdminCloudlinux\Tests;

use Detain\MyAdminCloudlinux\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PluginTest extends TestCase
{
    private $reflection;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    public function testMyNewMethodIsPublicAndStatic(): void
    {
        $method = $this->reflection->getMethod('myNewMethod');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
    }

    public function testMyNewMethodAcceptsGenericEvent(): void
    {
        $method = $this->reflection->getMethod('myNewMethod');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $this->assertSame(
            'Symfony\Component\EventDispatcher\GenericEvent',
            $params[0]->getType()->getName()
        );
    }

    public function testMyNewMethodHasNoReturnType(): void
    {
        $method = $this->reflection->getMethod('myNewMethod');
        $this->assertFalse($method->hasReturnType());
    }
}
```

When asserting static properties:
```php
$prop = $this->reflection->getProperty('myProp');
$this->assertTrue($prop->isPublic());
$this->assertTrue($prop->isStatic());
$this->assertSame('expected value', Plugin::$myProp);
```

Verify the method/property name exists in `src/Plugin.php` before writing the test.

### 3. Source file analysis tests (SourceFileAnalysisTest pattern)

Use `file_get_contents` to assert source code content without executing it.

```php
namespace Detain\MyAdminCloudlinux\Tests;

use PHPUnit\Framework\TestCase;

class SourceFileAnalysisTest extends TestCase
{
    private $srcDir;

    protected function setUp(): void
    {
        $this->srcDir = dirname(__DIR__) . '/src';
    }

    public function testNewFunctionIsDeclared(): void
    {
        $content = file_get_contents($this->srcDir . '/cloudlinux.inc.php');
        $this->assertStringContainsString('function my_new_function(', $content);
    }

    public function testNewFunctionHasExpectedSignature(): void
    {
        $content = file_get_contents($this->srcDir . '/cloudlinux.inc.php');
        $this->assertStringContainsString(
            'function my_new_function($ipAddress, $type = false)',
            $content
        );
    }

    public function testNewFunctionCallsStopPropagation(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $count = substr_count($content, 'stopPropagation()');
        $this->assertGreaterThanOrEqual(4, $count);
    }
}
```

Verify the exact string you assert exists in the source file before writing the test — read the file first.

### 4. Hook registration tests

When a new event hook is added to `Plugin::getHooks()` in `src/Plugin.php`, add tests for:
1. The new key exists in the returned array.
2. The handler is a 2-element array `[Plugin::class, 'methodName']`.
3. The count of total hooks is updated (currently `assertCount(10, $hooks)`).

```php
public function testGetHooksContainsNewEvent(): void
{
    $hooks = Plugin::getHooks();
    $this->assertArrayHasKey('licenses.new_event', $hooks);
    $this->assertSame([Plugin::class, 'getNewEvent'], $hooks['licenses.new_event']);
}
```

Update `testGetHooksCount()` to reflect the new total.

### 5. Run tests and verify

```
composer test
```

Expected output ends with `OK (N tests, N assertions)`. If it ends with `FAILURES` or `ERRORS`, fix before committing.

## Examples

**User says:** "I added a `getReactivate` method to Plugin — add a test for it."

**Actions taken:**
1. Read `src/Plugin.php` to confirm `getReactivate` signature: `public static function getReactivate(GenericEvent $event)`.
2. Add to `tests/PluginTest.php`:

```php
public function testGetReactivateIsPublicAndStatic(): void
{
    $method = $this->reflection->getMethod('getReactivate');
    $this->assertTrue($method->isStatic());
    $this->assertTrue($method->isPublic());
}

public function testGetReactivateAcceptsGenericEvent(): void
{
    $method = $this->reflection->getMethod('getReactivate');
    $params = $method->getParameters();
    $this->assertCount(1, $params);
    $this->assertSame('event', $params[0]->getName());
    $this->assertSame(
        'Symfony\Component\EventDispatcher\GenericEvent',
        $params[0]->getType()->getName()
    );
}

public function testGetReactivateHasNoReturnType(): void
{
    $method = $this->reflection->getMethod('getReactivate');
    $this->assertFalse($method->hasReturnType());
}
```

3. Run `composer test` — all tests pass.

**Result:** 3 new reflection-based assertions covering the new method's visibility, parameter type, and return type contract.

---

**User says:** "I added `activate_cloudlinux()` to cloudlinux.inc.php — write a source analysis test."

**Actions taken:**
1. Read `src/cloudlinux.inc.php` to confirm exact declaration: `function activate_cloudlinux($ipAddress, $typeId)`.
2. Add to `tests/SourceFileAnalysisTest.php`:

```php
public function testCloudlinuxIncDefinesActivateFunction(): void
{
    $content = file_get_contents($this->srcDir . '/cloudlinux.inc.php');
    $this->assertStringContainsString('function activate_cloudlinux(', $content);
}

public function testActivateCloudlinuxHasCorrectSignature(): void
{
    $content = file_get_contents($this->srcDir . '/cloudlinux.inc.php');
    $this->assertStringContainsString(
        'function activate_cloudlinux($ipAddress, $typeId)',
        $content
    );
}
```

3. Run `composer test` — passes.

## Common Issues

**`Composer autoloader not found. Run 'composer install' first.`**
Run `composer install` in the project root. The `tests/bootstrap.php` searches up the directory tree for the autoloader — it is missing entirely.

**`ReflectionException: Class "Detain\MyAdminCloudlinux\Plugin" does not exist`**
The autoloader isn't finding the class. Verify `composer.json` has `"Detain\\MyAdminCloudlinux\\": "src/"` under `autoload.psr-4` and that `composer dump-autoload` has been run.

**`assertCount(11, $publicMethods) failed, got 12`**
A new public method was added to `Plugin`. Update `testPublicMethodCount()` to the new count AND add a method-signature test for the new method.

**`assertCount(10, $hooks) failed, got 11`**
A new hook was added to `getHooks()` in `src/Plugin.php`. Update `testGetHooksCount()` to the new count AND add it to the `$expectedKeys` array in `testGetHooksReturnsExpectedKeys()`.

**`assertCount(5, $staticProps) failed`**
A static property was added or removed from `Plugin`. Update `testStaticPropertyCount()` to the actual count.

**`assertStringContainsString` fails for exact string match**
Read the source file directly and copy the exact string — whitespace, argument names, and default values must match character-for-character. Use `assertMatchesRegularExpression` for flexible matching: `$this->assertMatchesRegularExpression('/function\s+my_func\s*\(/', $content);`
