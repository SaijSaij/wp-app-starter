<?php

declare(strict_types=1);

namespace WebCodingCat\WpAppStarter\Tests;

use PHPUnit\Framework\TestCase;
use WebCodingCat\WpAppStarter\Installer;

final class InstallerTest extends TestCase
{
    public function testTheReleaseWorkflowUsesTheTagOfTheInstalledFramework(): void
    {
        self::assertSame('v0.16.0', Installer::frameworkRef('0.16.0', false));
        self::assertSame('v0.16.1', Installer::frameworkRef('v0.16.1', false));
        self::assertSame('main', Installer::frameworkRef('dev-main', true));
    }
}
