<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Application\Presentation;

use OCA\NLDesign\Application\Presentation\CoreRuntimeCompatibility;
use OCA\NLDesign\Infrastructure\Nextcloud\Presentation\VersionedCoreSurfaceAdapter;
use OCA\NLDesign\Port\Presentation\NextcloudRuntimeProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CoreRuntimeCompatibilityTest extends TestCase
{
    public function testRuntimeFailureBecomesClosedUnsupportedCapability(): void
    {
        $runtimeProvider = $this->createMock(NextcloudRuntimeProvider::class);
        $runtimeProvider->method('current')->willThrowException(
            new RuntimeException('version service unavailable')
        );

        $compatibility = new CoreRuntimeCompatibility(
            $runtimeProvider,
            new VersionedCoreSurfaceAdapter()
        );

        self::assertSame(
            [
                'supported'       => false,
                'runtime_version' => 'unknown',
                'runtime_major'   => null,
                'adapter_id'      => null,
                'stylesheet'      => null,
                'reason'          => 'runtime_unavailable',
            ],
            $compatibility->inspect()
        );
    }//end testRuntimeFailureBecomesClosedUnsupportedCapability()
}//end class
