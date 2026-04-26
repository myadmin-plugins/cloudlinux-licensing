<?php

namespace Detain\MyAdminCloudlinux\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests that analyze source files for correctness via static analysis.
 *
 * These tests read source files directly to verify structure, function declarations,
 * and coding patterns without executing database-dependent code.
 */
class SourceFileAnalysisTest extends TestCase
{
    /**
     * @var string Path to the src directory
     */
    private $srcDir;

    protected function setUp(): void
    {
        $this->srcDir = dirname(__DIR__) . '/src';
    }

    /**
     * Tests that Plugin.php exists and is readable.
     */
    public function testPluginFileExists(): void
    {
        $this->assertFileExists($this->srcDir . '/Plugin.php');
        $this->assertFileIsReadable($this->srcDir . '/Plugin.php');
    }

    /**
     * Tests that cloudlinux.inc.php exists and is readable.
     */
    public function testCloudlinuxIncFileExists(): void
    {
        $this->assertFileExists($this->srcDir . '/cloudlinux.inc.php');
        $this->assertFileIsReadable($this->srcDir . '/cloudlinux.inc.php');
    }

    /**
     * Tests that cloudlinux_licenses_list.php exists and is readable.
     */
    public function testCloudlinuxLicensesListFileExists(): void
    {
        $this->assertFileExists($this->srcDir . '/cloudlinux_licenses_list.php');
        $this->assertFileIsReadable($this->srcDir . '/cloudlinux_licenses_list.php');
    }

    /**
     * Tests that Plugin.php declares the correct namespace.
     */
    public function testPluginFileDeclaresNamespace(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString('namespace Detain\MyAdminCloudlinux;', $content);
    }

    /**
     * Tests that Plugin.php uses the required Cloudlinux dependency class.
     */
    public function testPluginFileImportsCloudlinux(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString('use Detain\Cloudlinux\Cloudlinux;', $content);
    }

    /**
     * Tests that Plugin.php imports GenericEvent from Symfony.
     */
    public function testPluginFileImportsGenericEvent(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString('use Symfony\Component\EventDispatcher\GenericEvent;', $content);
    }

    /**
     * Tests that Plugin.php declares the Plugin class.
     */
    public function testPluginFileDeclaresClass(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertMatchesRegularExpression('/class\s+Plugin/', $content);
    }

    /**
     * Tests that cloudlinux.inc.php defines the get_cloudlinux_licenses function.
     */
    public function testCloudlinuxIncDefinesGetLicensesFunction(): void
    {
        $content = file_get_contents($this->srcDir . '/cloudlinux.inc.php');
        $this->assertStringContainsString('function get_cloudlinux_licenses()', $content);
    }

    /**
     * Tests that cloudlinux.inc.php defines the deactivate_cloudlinux function.
     */
    public function testCloudlinuxIncDefinesDeactivateFunction(): void
    {
        $content = file_get_contents($this->srcDir . '/cloudlinux.inc.php');
        $this->assertStringContainsString('function deactivate_cloudlinux(', $content);
    }

    /**
     * Tests that cloudlinux.inc.php defines the deactivate_kcare function.
     */
    public function testCloudlinuxIncDefinesDeactivateKcareFunction(): void
    {
        $content = file_get_contents($this->srcDir . '/cloudlinux.inc.php');
        $this->assertStringContainsString('function deactivate_kcare(', $content);
    }

    /**
     * Tests that deactivate_kcare delegates to deactivate_cloudlinux with type 16.
     * KernelCare is product type 16 in the CloudLinux API.
     */
    public function testDeactivateKcareDelegatesToDeactivateCloudlinux(): void
    {
        $content = file_get_contents($this->srcDir . '/cloudlinux.inc.php');
        $this->assertStringContainsString('return deactivate_cloudlinux($ipAddress, 16);', $content);
    }

    /**
     * Tests that cloudlinux_licenses_list.php defines the cloudlinux_licenses_list function.
     */
    public function testLicensesListFileDefinesFunction(): void
    {
        $content = file_get_contents($this->srcDir . '/cloudlinux_licenses_list.php');
        $this->assertStringContainsString('function cloudlinux_licenses_list()', $content);
    }

    /**
     * Tests that the licenses list function checks for admin access.
     * This is a security requirement: only admins should see all licenses.
     */
    public function testLicensesListChecksAdminAccess(): void
    {
        $content = file_get_contents($this->srcDir . '/cloudlinux_licenses_list.php');
        $this->assertStringContainsString("\\MyAdmin\App::ima() == 'admin'", $content);
    }

    /**
     * Tests that cloudlinux.inc.php imports the Cloudlinux API class.
     */
    public function testCloudlinuxIncImportsCloudlinuxClass(): void
    {
        $content = file_get_contents($this->srcDir . '/cloudlinux.inc.php');
        $this->assertStringContainsString('use Detain\Cloudlinux\Cloudlinux;', $content);
    }

    /**
     * Tests that all source files start with a PHP opening tag.
     */
    public function testAllSourceFilesStartWithPhpTag(): void
    {
        $files = glob($this->srcDir . '/*.php');
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $this->assertStringStartsWith('<?php', $content, basename($file) . ' should start with <?php');
        }
    }

    /**
     * Tests that there are exactly 3 source files in the src directory.
     */
    public function testSourceFileCount(): void
    {
        $files = glob($this->srcDir . '/*.php');
        $this->assertCount(3, $files);
    }

    /**
     * Tests that Plugin.php references CLOUDLINUX_LOGIN and CLOUDLINUX_KEY constants.
     * These are required configuration constants for the CloudLinux API.
     */
    public function testPluginReferencesRequiredConstants(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString('CLOUDLINUX_LOGIN', $content);
        $this->assertStringContainsString('CLOUDLINUX_KEY', $content);
    }

    /**
     * Tests that the Plugin class references OUTOFSTOCK_LICENSES_CLOUDLINUX constant
     * in the settings handler. This constant controls stock availability.
     */
    public function testPluginReferencesOutOfStockConstant(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString('OUTOFSTOCK_LICENSES_CLOUDLINUX', $content);
    }

    /**
     * Tests that deactivate_cloudlinux has a $type parameter with a default of false.
     * When $type is false, all license types for the IP are removed.
     */
    public function testDeactivateCloudlinuxHasTypeParameter(): void
    {
        $content = file_get_contents($this->srcDir . '/cloudlinux.inc.php');
        $this->assertStringContainsString('function deactivate_cloudlinux($ipAddress, $type = false)', $content);
    }

    /**
     * Tests that deactivate_cloudlinux sends email notifications on failure.
     * This ensures failed deactivations are reported to administrators.
     */
    public function testDeactivateCloudlinuxSendsEmailOnFailure(): void
    {
        $content = file_get_contents($this->srcDir . '/cloudlinux.inc.php');
        $this->assertStringContainsString('multiMail(', $content);
        $this->assertStringContainsString('Cloudlinux License Deactivation', $content);
    }

    /**
     * Tests that the licenses list function creates a TFTable for output.
     */
    public function testLicensesListUsesTFTable(): void
    {
        $content = file_get_contents($this->srcDir . '/cloudlinux_licenses_list.php');
        $this->assertStringContainsString('new \TFTable()', $content);
        $this->assertStringContainsString('CloudLinux License List', $content);
    }

    /**
     * Tests that Plugin.php contains PHPDoc blocks for the class.
     */
    public function testPluginHasClassDocBlock(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString('/**', $content);
        $this->assertStringContainsString('* Class Plugin', $content);
        $this->assertStringContainsString('@package', $content);
    }

    /**
     * Tests that the getInstall handler registers multiple services.
     * CloudLinux offers several license types: CloudLinux, KernelCare, and Imunify variants.
     */
    public function testGetInstallRegistersMultipleServices(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString('CloudLinux License', $content);
        $this->assertStringContainsString('KernelCare License', $content);
        $this->assertStringContainsString('ImunityAV+', $content);
        $this->assertStringContainsString('Imunity360', $content);
    }

    /**
     * Tests that the getRequirements handler registers all expected page/function requirements.
     */
    public function testGetRequirementsRegistersAllDependencies(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        $this->assertStringContainsString("add_page_requirement('cloudlinux_licenses_list'", $content);
        $this->assertStringContainsString("add_requirement('deactivate_kcare'", $content);
        $this->assertStringContainsString("add_requirement('deactivate_cloudlinux'", $content);
        $this->assertStringContainsString("add_requirement('get_cloudlinux_licenses'", $content);
    }

    /**
     * Tests that event handlers that modify license state call stopPropagation().
     * This prevents other plugins from handling the same event.
     */
    public function testEventHandlersStopPropagation(): void
    {
        $content = file_get_contents($this->srcDir . '/Plugin.php');
        // Count stopPropagation calls - should appear in activate, deactivate, deactivateIp, changeIp
        $count = substr_count($content, 'stopPropagation()');
        $this->assertGreaterThanOrEqual(4, $count);
    }
}
