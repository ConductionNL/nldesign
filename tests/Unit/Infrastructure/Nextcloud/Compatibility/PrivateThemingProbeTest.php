<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Infrastructure\Nextcloud\Compatibility;

use OCA\NLDesign\Infrastructure\Nextcloud\Compatibility\PrivateThemingProbe;
use PHPUnit\Framework\TestCase;

class PrivateThemingProbeTest extends TestCase
{
    public function testMissingPrivateClassesRemainUnavailableWithoutResolution(): void
    {
        $result = (new PrivateThemingProbe())->inspect();

        self::assertFalse($result['structurally_available']);
        self::assertSame(
            [
                'ThemingDefaults::set'      => false,
                'ThemingDefaults::undo'     => false,
                'ImageManager::updateImage' => false,
                'ImageManager::hasImage'    => false,
            ],
            $result['requirements']
        );
    }//end testMissingPrivateClassesRemainUnavailableWithoutResolution()
}//end class
