<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Domain\Presentation;

use InvalidArgumentException;
use OCA\NLDesign\Domain\Presentation\CoreSurfaceProjection;
use OCA\NLDesign\Domain\Presentation\NextcloudRuntime;
use PHPUnit\Framework\TestCase;

class PresentationValuesTest extends TestCase
{
    public function testRejectsInvalidRuntimeTuple(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new NextcloudRuntime(0, 0, 0);
    }//end testRejectsInvalidRuntimeTuple()

    public function testRejectsUnsafeStylesheetPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CoreSurfaceProjection('nextcloud-core-v1', '../theme');
    }//end testRejectsUnsafeStylesheetPath()
}//end class
