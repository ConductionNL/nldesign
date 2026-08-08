<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Infrastructure\Nextcloud\Presentation;

use OCA\NLDesign\Infrastructure\Nextcloud\Presentation\OcpNextcloudRuntimeProvider;
use OCP\ServerVersion;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class OcpNextcloudRuntimeProviderTest extends TestCase
{
    public function testMapsPublicServerVersionToNeutralRuntime(): void
    {
        $serverVersion = $this->createServerVersion([34, 0, 2]);

        $runtime = (new OcpNextcloudRuntimeProvider($serverVersion))->current();

        self::assertSame(34, $runtime->getMajor());
        self::assertSame('34.0.2', $runtime->toVersionString());
    }//end testMapsPublicServerVersionToNeutralRuntime()

    /**
     * Build the public value object without loading Nextcloud's absent
     * installation-level version.php fixture. Reflection can initialize an
     * unconstructed readonly object and therefore works on OCP 32 through 34.
     *
     * @param array{int, int, int} $version Numeric Nextcloud version.
     *
     * @return ServerVersion Public server-version value object.
     */
    private function createServerVersion(array $version): ServerVersion
    {
        $reflection    = new ReflectionClass(ServerVersion::class);
        $serverVersion = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('version')->setValue($serverVersion, $version);

        return $serverVersion;
    }//end createServerVersion()
}//end class
