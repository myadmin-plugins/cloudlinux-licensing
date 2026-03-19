<?php

namespace Detain\MyAdminCloudlinux\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests that verify the composer.json configuration is correct.
 *
 * Ensures proper autoloading, dependencies, and package metadata.
 */
class ComposerConfigTest extends TestCase
{
    /**
     * @var array Parsed composer.json contents
     */
    private $composerConfig;

    /**
     * @var string Path to composer.json
     */
    private $composerPath;

    protected function setUp(): void
    {
        $this->composerPath = dirname(__DIR__) . '/composer.json';
        $content = file_get_contents($this->composerPath);
        $this->composerConfig = json_decode($content, true);
        $this->assertNotNull($this->composerConfig, 'composer.json should be valid JSON');
    }

    /**
     * Tests that composer.json exists and is readable.
     */
    public function testComposerJsonExists(): void
    {
        $this->assertFileExists($this->composerPath);
        $this->assertFileIsReadable($this->composerPath);
    }

    /**
     * Tests that the package name follows the vendor/package convention.
     */
    public function testPackageName(): void
    {
        $this->assertSame('detain/myadmin-cloudlinux-licensing', $this->composerConfig['name']);
    }

    /**
     * Tests that the package type is set to myadmin-plugin.
     * This type is used by the custom installer for MyAdmin plugins.
     */
    public function testPackageType(): void
    {
        $this->assertSame('myadmin-plugin', $this->composerConfig['type']);
    }

    /**
     * Tests that the license is correctly set to LGPL-2.1-only.
     */
    public function testLicense(): void
    {
        $this->assertSame('LGPL-2.1-only', $this->composerConfig['license']);
    }

    /**
     * Tests that autoloading is configured with PSR-4 for the correct namespace.
     */
    public function testAutoloadPsr4(): void
    {
        $this->assertArrayHasKey('autoload', $this->composerConfig);
        $this->assertArrayHasKey('psr-4', $this->composerConfig['autoload']);
        $this->assertArrayHasKey('Detain\\MyAdminCloudlinux\\', $this->composerConfig['autoload']['psr-4']);
        $this->assertSame('src/', $this->composerConfig['autoload']['psr-4']['Detain\\MyAdminCloudlinux\\']);
    }

    /**
     * Tests that PHPUnit is listed as a dev dependency.
     */
    public function testPhpunitInRequireDev(): void
    {
        $this->assertArrayHasKey('require-dev', $this->composerConfig);
        $this->assertArrayHasKey('phpunit/phpunit', $this->composerConfig['require-dev']);
    }

    /**
     * Tests that the required runtime dependencies are present.
     */
    public function testRequiredDependencies(): void
    {
        $this->assertArrayHasKey('require', $this->composerConfig);
        $this->assertArrayHasKey('php', $this->composerConfig['require']);
        $this->assertArrayHasKey('detain/cloudlinux-licensing', $this->composerConfig['require']);
        $this->assertArrayHasKey('symfony/event-dispatcher', $this->composerConfig['require']);
    }

    /**
     * Tests that authors section has at least one entry.
     */
    public function testAuthorsSection(): void
    {
        $this->assertArrayHasKey('authors', $this->composerConfig);
        $this->assertNotEmpty($this->composerConfig['authors']);
        $this->assertArrayHasKey('name', $this->composerConfig['authors'][0]);
    }

    /**
     * Tests that the package has relevant keywords for discoverability.
     */
    public function testKeywords(): void
    {
        $this->assertArrayHasKey('keywords', $this->composerConfig);
        $this->assertContains('cloudlinux', $this->composerConfig['keywords']);
        $this->assertContains('license', $this->composerConfig['keywords']);
    }

    /**
     * Tests that a description is provided in composer.json.
     */
    public function testDescription(): void
    {
        $this->assertArrayHasKey('description', $this->composerConfig);
        $this->assertNotEmpty($this->composerConfig['description']);
    }
}
