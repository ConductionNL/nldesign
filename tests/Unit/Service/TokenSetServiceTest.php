<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Service;

use OCA\NLDesign\Infrastructure\Profile\PackagedProfileFiles;
use OCA\NLDesign\Infrastructure\Profile\ProfileCatalogueEnvelope;
use OCA\NLDesign\Infrastructure\Profile\ProfileManifestEntryNormalizer;
use OCA\NLDesign\Service\TokenSetService;
use OCP\App\IAppManager;
use PHPUnit\Framework\TestCase;

class TokenSetServiceTest extends TestCase
{
    private string $appPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->appPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nldesign-'.bin2hex(random_bytes(6));
        mkdir($this->appPath.'/css/tokens', 0777, true);
        mkdir($this->appPath.'/img/logos', 0777, true);
        mkdir($this->appPath.'/img/backgrounds', 0777, true);
    }//end setUp()

    protected function tearDown(): void
    {
        $this->removeDirectory(path: $this->appPath);
        parent::tearDown();
    }//end tearDown()

    public function testCatalogueRequiresManifestAndStylesheet(): void
    {
        $this->writeManifest(
            entries: [
                [
                    'id'          => 'utrecht',
                    'name'        => 'Gemeente Utrecht',
                    'description' => 'Utrecht profile',
                    'theming'     => ['primary_color' => '#CC0000'],
                ],
                [
                    'id'          => 'missing',
                    'name'        => 'Missing',
                    'description' => 'No compiled CSS',
                ],
            ]
        );
        file_put_contents($this->appPath.'/css/tokens/utrecht.css', ':root {}');

        $service  = $this->createService();
        $profiles = $service->getAvailableTokenSets();

        self::assertCount(1, $profiles);
        self::assertSame('utrecht', $profiles[0]['id']);
        self::assertSame('#cc0000', $profiles[0]['theming']['primary_color']);
        self::assertFalse($service->isValidTokenSet(tokenSetId: 'missing'));
    }//end testCatalogueRequiresManifestAndStylesheet()

    public function testRejectsTraversalAndInvalidIdentifiers(): void
    {
        $this->writeManifest(entries: []);
        $service = $this->createService();

        self::assertFalse($service->isValidTokenSet(tokenSetId: '../config'));
        self::assertFalse($service->isValidTokenSet(tokenSetId: 'UPPERCASE'));
        self::assertFalse($service->isValidTokenSet(tokenSetId: 'with/slash'));
    }//end testRejectsTraversalAndInvalidIdentifiers()

    public function testRejectsStylesheetSymlinkOutsideTokenDirectory(): void
    {
        $this->writeManifest(
            entries: [
                [
                    'id'          => 'escaped',
                    'name'        => 'Escaped',
                    'description' => 'Unsafe symlink fixture',
                ],
            ]
        );
        file_put_contents($this->appPath.'/outside.css', ':root {}');
        if (symlink($this->appPath.'/outside.css', $this->appPath.'/css/tokens/escaped.css') === false) {
            self::markTestSkipped('Symlinks are unavailable in this environment.');
        }

        self::assertFalse($this->createService()->isValidTokenSet(tokenSetId: 'escaped'));
    }//end testRejectsStylesheetSymlinkOutsideTokenDirectory()

    public function testRejectsReadyStylesheetAboveRuntimeBudget(): void
    {
        $this->writeManifest(
            entries: [
                [
                    'id'          => 'oversized',
                    'name'        => 'Oversized',
                    'description' => 'Projection larger than the runtime budget',
                ],
            ]
        );
        file_put_contents(
            $this->appPath.'/css/tokens/oversized.css',
            ':root {'.str_repeat(' ', 32768).'}'
        );

        $service = $this->createService();

        self::assertSame([], $service->getAvailableTokenSets());
        self::assertFalse($service->isValidTokenSet(tokenSetId: 'oversized'));
    }//end testRejectsReadyStylesheetAboveRuntimeBudget()

    public function testMalformedOptionalHintsAreNotExposed(): void
    {
        $this->writeManifest(
            entries: [
                [
                    'id'          => 'safe',
                    'name'        => 'Safe',
                    'description' => 'Safe profile',
                    'theming'     => [
                        'primary_color' => 'red; background:url(evil)',
                        'logo'          => '../../secret',
                    ],
                ],
            ]
        );
        file_put_contents($this->appPath.'/css/tokens/safe.css', ':root {}');

        $metadata = $this->createService()->getTokenSetMetadata(tokenSetId: 'safe');

        self::assertNotNull($metadata);
        self::assertArrayNotHasKey('theming', $metadata);
    }//end testMalformedOptionalHintsAreNotExposed()

    public function testRuntimeRejectsDuplicateIdentifiers(): void
    {
        $this->writeManifest(
            entries: [
                [
                    'id'          => 'duplicate',
                    'name'        => 'First',
                    'description' => 'First declaration',
                ],
                [
                    'id'          => 'duplicate',
                    'name'        => 'Second',
                    'description' => 'Second declaration',
                ],
            ]
        );
        file_put_contents($this->appPath.'/css/tokens/duplicate.css', ':root {}');

        $service = $this->createService();

        self::assertSame([], $service->getAvailableTokenSets());
        self::assertNull($service->getTokenSetMetadata(tokenSetId: 'duplicate'));
    }//end testRuntimeRejectsDuplicateIdentifiers()

    public function testSourceOnlyProfileIsNotSelectable(): void
    {
        $this->writeManifest(
            entries: [
                [
                    'id'          => 'source-only',
                    'name'        => 'Source only',
                    'description' => 'No reviewed Nextcloud projection',
                    'status'      => 'source-only',
                ],
            ]
        );
        file_put_contents($this->appPath.'/css/tokens/source-only.css', ':root {}');

        $service = $this->createService();

        self::assertFalse($service->isValidTokenSet(tokenSetId: 'source-only'));
        self::assertSame([], $service->getAvailableTokenSets());
    }//end testSourceOnlyProfileIsNotSelectable()

    public function testMalformedSourceOnlyDuplicateInvalidatesCatalogue(): void
    {
        $this->writeManifest(
            entries: [
                [
                    'id'          => 'candidate',
                    'name'        => 'Malformed source',
                    'description' => 'Source-only records cannot claim a projection',
                    'status'      => 'source-only',
                    'projection'  => 'nextcloud-core-v1',
                ],
                [
                    'id'          => 'candidate',
                    'name'        => 'Ready candidate',
                    'description' => 'Valid reviewed projection',
                ],
            ]
        );
        file_put_contents($this->appPath.'/css/tokens/candidate.css', ':root {}');

        $service = $this->createService();

        self::assertSame([], $service->getAvailableTokenSets());
        self::assertFalse($service->isValidTokenSet(tokenSetId: 'candidate'));
    }//end testMalformedSourceOnlyDuplicateInvalidatesCatalogue()

    public function testOnlyExistingRegularThemingAssetsAreExposed(): void
    {
        $this->writeManifest(
            entries: [
                [
                    'id'          => 'safe-assets',
                    'name'        => 'Safe assets',
                    'description' => 'Asset fixture',
                    'theming'     => [
                        'logo'       => 'img/logos/safe.svg',
                        'background' => 'img/backgrounds/missing.png',
                    ],
                ],
            ]
        );
        file_put_contents($this->appPath.'/css/tokens/safe-assets.css', ':root {}');
        file_put_contents($this->appPath.'/img/logos/safe.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');

        $metadata = $this->createService()->getTokenSetMetadata(tokenSetId: 'safe-assets');

        self::assertNotNull($metadata);
        self::assertSame('img/logos/safe.svg', $metadata['theming']['logo']);
        self::assertArrayNotHasKey('background', $metadata['theming']);
    }//end testOnlyExistingRegularThemingAssetsAreExposed()

    public function testRejectsSymlinkedManifest(): void
    {
        file_put_contents($this->appPath.'/manifest-source.json', '[]');
        if (symlink($this->appPath.'/manifest-source.json', $this->appPath.'/token-sets.json') === false) {
            self::markTestSkipped('Symlinks are unavailable in this environment.');
        }

        self::assertSame([], $this->createService()->getAvailableTokenSets());
    }//end testRejectsSymlinkedManifest()

    public function testInvalidDeclaredDefaultInvalidatesCatalogue(): void
    {
        $this->writeManifest(
            entries: [
                [
                    'id'          => 'ready',
                    'name'        => 'Ready',
                    'description' => 'Ready profile',
                ],
            ],
            defaultProfile: 'missing'
        );
        file_put_contents($this->appPath.'/css/tokens/ready.css', ':root {}');

        $service = $this->createService();

        self::assertSame([], $service->getAvailableTokenSets());
    }//end testInvalidDeclaredDefaultInvalidatesCatalogue()

    public function testNullDefaultKeepsNativeNextcloudAsInitialState(): void
    {
        $this->writeManifest(
            entries: [
                [
                    'id'          => 'ready',
                    'name'        => 'Ready',
                    'description' => 'Ready but not implicitly activated',
                ],
            ],
            defaultProfile: null
        );
        file_put_contents($this->appPath.'/css/tokens/ready.css', ':root {}');

        $service = $this->createService();

        self::assertCount(1, $service->getAvailableTokenSets());
        self::assertTrue($service->isValidTokenSet(tokenSetId: 'ready'));
    }//end testNullDefaultKeepsNativeNextcloudAsInitialState()

    public function testMissingDefaultFieldInvalidatesCatalogueEnvelope(): void
    {
        file_put_contents(
            $this->appPath.'/token-sets.json',
            json_encode(
                value: [
                    'schema'   => 'nldesign-profile-catalogue/v1',
                    'profiles' => [],
                ],
                flags: JSON_THROW_ON_ERROR
            )
        );

        $service = $this->createService();

        self::assertSame([], $service->getAvailableTokenSets());
    }//end testMissingDefaultFieldInvalidatesCatalogueEnvelope()

    public function testUnknownCatalogueFieldInvalidatesEnvelope(): void
    {
        file_put_contents(
            $this->appPath.'/token-sets.json',
            json_encode(
                value: [
                    'schema'          => 'nldesign-profile-catalogue/v1',
                    'default_profile' => null,
                    'profiles'        => [],
                    'unexpected'      => true,
                ],
                flags: JSON_THROW_ON_ERROR
            )
        );

        self::assertSame([], $this->createService()->getAvailableTokenSets());
    }//end testUnknownCatalogueFieldInvalidatesEnvelope()

    public function testMalformedRequiredMetadataAndUnknownFieldsAreRejected(): void
    {
        $this->writeManifest(
            entries: [
                [
                    'id'          => 'missing-name',
                    'description' => 'Required name is absent',
                ],
                [
                    'id'          => 'control-text',
                    'name'        => "Unsafe\nname",
                    'description' => 'Control characters are not display metadata',
                ],
                [
                    'id'          => 'unknown-field',
                    'name'        => 'Unknown field',
                    'description' => 'Schema v1 is closed',
                    'unexpected'  => true,
                ],
            ]
        );
        foreach (['missing-name', 'control-text', 'unknown-field'] as $id) {
            file_put_contents($this->appPath.'/css/tokens/'.$id.'.css', ':root {}');
        }

        self::assertSame([], $this->createService()->getAvailableTokenSets());
    }//end testMalformedRequiredMetadataAndUnknownFieldsAreRejected()

    private function createService(): TokenSetService
    {
        $appManager = $this->createMock(IAppManager::class);
        $appManager->method('getAppPath')->willReturn($this->appPath);

        $profileFiles = new PackagedProfileFiles(appManager: $appManager);

        return new TokenSetService(
            profileFiles: $profileFiles,
            envelope: new ProfileCatalogueEnvelope(),
            normalizer: new ProfileManifestEntryNormalizer(profileFiles: $profileFiles)
        );
    }//end createService()

    /**
     * @param array<int, array<string, mixed>> $entries        Manifest entries.
     * @param string|null                      $defaultProfile Declared package default.
     */
    private function writeManifest(
        array $entries,
        ?string $defaultProfile=null
    ): void {
        $entries = array_map(
            static function (array $entry): array {
                if (($entry['status'] ?? 'ready') === 'source-only') {
                    return $entry;
                }

                return array_merge(
                    [
                        'status'     => 'ready',
                        'projection' => 'nextcloud-core-v1',
                    ],
                    $entry
                );
            },
            $entries
        );

        file_put_contents(
            $this->appPath.'/token-sets.json',
            json_encode(
                value: [
                    'schema'          => 'nldesign-profile-catalogue/v1',
                    'default_profile' => $defaultProfile,
                    'profiles'        => $entries,
                ],
                flags: JSON_THROW_ON_ERROR
            )
        );
    }//end writeManifest()

    private function removeDirectory(string $path): void
    {
        if (is_dir($path) === false) {
            return;
        }

        $entries = scandir($path);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $entryPath = $path.DIRECTORY_SEPARATOR.$entry;
            if (is_link($entryPath) === true || is_file($entryPath) === true) {
                unlink($entryPath);
                continue;
            }

            $this->removeDirectory(path: $entryPath);
        }

        rmdir($path);
    }//end removeDirectory()
}//end class
