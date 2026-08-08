<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Infrastructure\Nextcloud\Presentation;

use OCA\NLDesign\Domain\Presentation\NextcloudRuntime;
use OCA\NLDesign\Infrastructure\Nextcloud\Presentation\VersionedCoreSurfaceAdapter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VersionedCoreSurfaceAdapterTest extends TestCase
{
    /**
     * @return array<int, array{int, string, string}>
     */
    public static function supportedMajorProvider(): array
    {
        return [
            [32, 'nextcloud-core-v1', 'compatibility/nextcloud-core-v1'],
            [33, 'nextcloud-core-v1', 'compatibility/nextcloud-core-v1'],
            [34, 'nextcloud-core-v1', 'compatibility/nextcloud-core-v1'],
        ];
    }//end supportedMajorProvider()

    #[DataProvider('supportedMajorProvider')]
    public function testSupportedMajorsResolveVerifiedContract(
        int $major,
        string $adapterId,
        string $stylesheet
    ): void {
        $projection = (new VersionedCoreSurfaceAdapter())->resolve(
            new NextcloudRuntime($major, 9, 8)
        );

        self::assertNotNull($projection);
        self::assertSame($adapterId, $projection->getAdapterId());
        self::assertSame($stylesheet, $projection->getStylesheet());
    }//end testSupportedMajorsResolveVerifiedContract()

    public function testDoesNotFallThroughForUnknownMajors(): void
    {
        $adapter = new VersionedCoreSurfaceAdapter();

        self::assertNull($adapter->resolve(new NextcloudRuntime(31, 0, 0)));
        self::assertNull($adapter->resolve(new NextcloudRuntime(35, 0, 0)));
    }//end testDoesNotFallThroughForUnknownMajors()
}//end class
