<?php

namespace Detain\MyAdminCloudlinux\Tests;

use Detain\MyAdminCloudlinux\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests for the Plugin class.
 *
 * Covers class structure, static properties, hook registration,
 * and event handler method signatures using reflection.
 */
class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass
     */
    private $reflection;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    /**
     * Tests that the Plugin class exists and can be reflected.
     * Verifies the fully qualified class name is correct.
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
        $this->assertSame('Detain\MyAdminCloudlinux\Plugin', $this->reflection->getName());
    }

    /**
     * Tests that the Plugin class resides in the correct namespace.
     */
    public function testClassNamespace(): void
    {
        $this->assertSame('Detain\MyAdminCloudlinux', $this->reflection->getNamespaceName());
    }

    /**
     * Tests that Plugin can be instantiated without arguments.
     * The constructor is a no-op, so this should always succeed.
     */
    public function testCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Tests that the $name static property exists, is public, and has the expected value.
     */
    public function testNameProperty(): void
    {
        $prop = $this->reflection->getProperty('name');
        $this->assertTrue($prop->isPublic());
        $this->assertTrue($prop->isStatic());
        $this->assertSame('Cloudlinux Licensing', Plugin::$name);
    }

    /**
     * Tests that the $description static property exists, is public,
     * and contains a meaningful description string.
     */
    public function testDescriptionProperty(): void
    {
        $prop = $this->reflection->getProperty('description');
        $this->assertTrue($prop->isPublic());
        $this->assertTrue($prop->isStatic());
        $this->assertIsString(Plugin::$description);
        $this->assertStringContainsString('Cloudlinux', Plugin::$description);
    }

    /**
     * Tests that the $help static property exists, is public,
     * and contains help text.
     */
    public function testHelpProperty(): void
    {
        $prop = $this->reflection->getProperty('help');
        $this->assertTrue($prop->isPublic());
        $this->assertTrue($prop->isStatic());
        $this->assertIsString(Plugin::$help);
        $this->assertNotEmpty(Plugin::$help);
    }

    /**
     * Tests that the $module static property is set to 'licenses'.
     */
    public function testModuleProperty(): void
    {
        $prop = $this->reflection->getProperty('module');
        $this->assertTrue($prop->isPublic());
        $this->assertTrue($prop->isStatic());
        $this->assertSame('licenses', Plugin::$module);
    }

    /**
     * Tests that the $type static property is set to 'service'.
     */
    public function testTypeProperty(): void
    {
        $prop = $this->reflection->getProperty('type');
        $this->assertTrue($prop->isPublic());
        $this->assertTrue($prop->isStatic());
        $this->assertSame('service', Plugin::$type);
    }

    /**
     * Tests that the class has exactly 5 static properties.
     */
    public function testStaticPropertyCount(): void
    {
        $staticProps = array_filter(
            $this->reflection->getProperties(),
            fn($p) => $p->isStatic()
        );
        $this->assertCount(5, $staticProps);
    }

    /**
     * Tests that getHooks() returns an array with the expected event keys.
     * Each hook must map to a callable array [className, methodName].
     */
    public function testGetHooksReturnsExpectedKeys(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);

        $expectedKeys = [
            'plugin.install',
            'plugin.uninstall',
            'licenses.settings',
            'licenses.activate',
            'licenses.reactivate',
            'licenses.deactivate',
            'licenses.deactivate_ip',
            'licenses.change_ip',
            'function.requirements',
            'ui.menu',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $hooks, "Missing hook key: {$key}");
        }
    }

    /**
     * Tests that getHooks() returns exactly 10 hooks.
     */
    public function testGetHooksCount(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertCount(10, $hooks);
    }

    /**
     * Tests that each hook value is a valid callable-style array
     * pointing to an existing static method on the Plugin class.
     */
    public function testGetHooksValuesAreCallableArrays(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $event => $handler) {
            $this->assertIsArray($handler, "Handler for '{$event}' should be an array");
            $this->assertCount(2, $handler, "Handler for '{$event}' should have 2 elements");
            $this->assertSame(Plugin::class, $handler[0], "Handler class for '{$event}' should be Plugin");
            $this->assertTrue(
                $this->reflection->hasMethod($handler[1]),
                "Plugin should have method '{$handler[1]}' for hook '{$event}'"
            );
        }
    }

    /**
     * Tests that the activate and reactivate hooks both point to the same handler (getActivate).
     */
    public function testActivateAndReactivateShareHandler(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame($hooks['licenses.activate'], $hooks['licenses.reactivate']);
        $this->assertSame('getActivate', $hooks['licenses.activate'][1]);
    }

    /**
     * Tests that hook keys using self::$module resolve to 'licenses.*'.
     */
    public function testModuleBasedHookKeysUseCorrectPrefix(): void
    {
        $hooks = Plugin::getHooks();
        $moduleKeys = ['licenses.settings', 'licenses.activate', 'licenses.reactivate', 'licenses.deactivate', 'licenses.deactivate_ip', 'licenses.change_ip'];
        foreach ($moduleKeys as $key) {
            $this->assertArrayHasKey($key, $hooks);
            $this->assertStringStartsWith('licenses.', $key);
        }
    }

    /**
     * Tests that all event handler methods accept a GenericEvent parameter.
     * Validates that the method signatures conform to the Symfony EventDispatcher pattern.
     */
    public function testEventHandlerMethodSignatures(): void
    {
        $eventMethods = [
            'getInstall',
            'getUninstall',
            'getActivate',
            'getDeactivate',
            'getDeactivateIp',
            'getChangeIp',
            'getMenu',
            'getRequirements',
            'getSettings',
        ];

        foreach ($eventMethods as $methodName) {
            $method = $this->reflection->getMethod($methodName);
            $this->assertTrue($method->isStatic(), "{$methodName} should be static");
            $this->assertTrue($method->isPublic(), "{$methodName} should be public");

            $params = $method->getParameters();
            $this->assertCount(1, $params, "{$methodName} should accept exactly 1 parameter");
            $this->assertSame('event', $params[0]->getName(), "{$methodName} parameter should be named 'event'");

            $type = $params[0]->getType();
            $this->assertNotNull($type, "{$methodName} parameter should have a type hint");
            $this->assertSame(
                'Symfony\Component\EventDispatcher\GenericEvent',
                $type->getName(),
                "{$methodName} parameter should be typed as GenericEvent"
            );
        }
    }

    /**
     * Tests that getHooks() is a public static method returning an array.
     */
    public function testGetHooksMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertTrue($method->isStatic());
        $this->assertTrue($method->isPublic());
        $this->assertCount(0, $method->getParameters());
    }

    /**
     * Tests that the constructor is public and takes no required parameters.
     */
    public function testConstructorSignature(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPublic());
        $this->assertCount(0, $constructor->getParameters());
    }

    /**
     * Tests that the Plugin class is not abstract and not final,
     * allowing it to be extended if needed.
     */
    public function testClassIsNotAbstractOrFinal(): void
    {
        $this->assertFalse($this->reflection->isAbstract());
        $this->assertFalse($this->reflection->isFinal());
    }

    /**
     * Tests the total number of public methods on the Plugin class.
     * There should be 11: constructor, getHooks, and 9 event handlers.
     */
    public function testPublicMethodCount(): void
    {
        $publicMethods = $this->reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $this->assertCount(11, $publicMethods);
    }

    /**
     * Tests that all event handler methods have void return type or no explicit return type.
     * Event handlers are not expected to return values.
     */
    public function testEventHandlerReturnTypes(): void
    {
        $eventMethods = [
            'getInstall',
            'getUninstall',
            'getActivate',
            'getDeactivate',
            'getDeactivateIp',
            'getChangeIp',
            'getMenu',
            'getRequirements',
            'getSettings',
        ];

        foreach ($eventMethods as $methodName) {
            $method = $this->reflection->getMethod($methodName);
            // These methods don't declare a return type
            $this->assertFalse($method->hasReturnType(), "{$methodName} should not have an explicit return type");
        }
    }

    /**
     * Tests that getHooks returns array type (verified via doc comment or actual return).
     * The @return array docblock annotation should be present.
     */
    public function testGetHooksDocComment(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $docComment = $method->getDocComment();
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('@return', $docComment);
        $this->assertStringContainsString('array', $docComment);
    }
}
