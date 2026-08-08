<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Application\Presentation;

use OCA\NLDesign\Application\Presentation\CoreRuntimeCompatibility;
use OCA\NLDesign\Application\Presentation\RuntimeStylesheetPlan;
use OCA\NLDesign\Domain\Presentation\NextcloudRuntime;
use OCA\NLDesign\Infrastructure\Nextcloud\Presentation\VersionedCoreSurfaceAdapter;
use OCA\NLDesign\Port\Presentation\NextcloudRuntimeProvider;
use PHPUnit\Framework\TestCase;

class RuntimeStylesheetPlanTest extends TestCase
{
    public function testDefinesCompleteSharedContractPrecedenceOrder(): void
    {
        self::assertSame(
            [
                'supported'       => true,
                'runtime_version' => '33.0.5',
                'runtime_major'   => 33,
                'adapter_id'      => 'nextcloud-core-v1',
                'before_profile'  => ['fonts'],
                'after_profile'   => ['compatibility/nextcloud-core-v1'],
                'reason'          => null,
            ],
            $this->createPlan(new NextcloudRuntime(33, 0, 5))->build()
        );
    }//end testDefinesCompleteSharedContractPrecedenceOrder()

    public function testUnknownMajorProducesNoStylesheetStack(): void
    {
        self::assertSame(
            [
                'supported'       => false,
                'runtime_version' => '35.0.0',
                'runtime_major'   => 35,
                'adapter_id'      => null,
                'before_profile'  => [],
                'after_profile'   => [],
                'reason'          => 'unsupported_nextcloud_major',
            ],
            $this->createPlan(new NextcloudRuntime(35, 0, 0))->build()
        );
    }//end testUnknownMajorProducesNoStylesheetStack()

    private function createPlan(NextcloudRuntime $runtime): RuntimeStylesheetPlan
    {
        $runtimeProvider = $this->createMock(NextcloudRuntimeProvider::class);
        $runtimeProvider->method('current')->willReturn($runtime);

        return new RuntimeStylesheetPlan(
            new CoreRuntimeCompatibility(
                $runtimeProvider,
                new VersionedCoreSurfaceAdapter()
            )
        );
    }//end createPlan()
}//end class
