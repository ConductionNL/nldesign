<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Controller;

use OCA\NLDesign\Controller\StylesheetController;
use OCA\NLDesign\Infrastructure\Profile\PackagedProfileFiles;
use OCA\NLDesign\Infrastructure\Profile\ProfileCatalogueEnvelope;
use OCA\NLDesign\Infrastructure\Profile\ProfileManifestEntryNormalizer;
use OCA\NLDesign\Port\Profile\InstalledProfileRepository;
use OCA\NLDesign\Service\TokenSetService;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class StylesheetControllerTest extends TestCase
{
    private string $appPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->appPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nldesign-stylesheet-'.bin2hex(random_bytes(6));
        mkdir($this->appPath.'/css/tokens', 0777, true);
        file_put_contents(
            $this->appPath.'/token-sets.json',
            json_encode(
                [
                    'schema'          => 'nldesign-profile-catalogue/v1',
                    'default_profile' => null,
                    'profiles'        => [],
                ],
                JSON_THROW_ON_ERROR
            )
        );
    }

    protected function tearDown(): void
    {
        unlink($this->appPath.'/token-sets.json');
        rmdir($this->appPath.'/css/tokens');
        rmdir($this->appPath.'/css');
        rmdir($this->appPath);
        parent::tearDown();
    }

    public function testServesOnlyTheExactDigestAddressedStylesheet(): void
    {
        $hash       = str_repeat('a', 64);
        $css        = ':root { --nldesign-color-primary: #183a37; }';
        $repository = $this->createMock(InstalledProfileRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with('voorbeeld-profiel', '1.0.0')
            ->willReturn([
                'metadata' => ['content_hash' => $hash],
                'css'      => $css,
            ]);

        $response = $this->createController(installed: $repository)->getProfile(
            profileId: 'voorbeeld-profiel',
            profileVersion: '1.0.0',
            contentHash: $hash
        );

        self::assertSame(Http::STATUS_OK, $response->getStatus());
        self::assertSame($css, $response->render());
        $headers = (new ReflectionProperty(Response::class, 'headers'))->getValue($response);
        self::assertIsArray($headers);
        self::assertSame('text/css; charset=utf-8', $headers['Content-Type']);
        self::assertSame('public, max-age=31536000, immutable', $headers['Cache-Control']);
        self::assertSame('"'.$hash.'"', $headers['ETag']);
        self::assertSame('nosniff', $headers['X-Content-Type-Options']);
    }

    public function testRejectsMalformedOrMismatchedDigests(): void
    {
        $repository = $this->createMock(InstalledProfileRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->willReturn([
                'metadata' => ['content_hash' => str_repeat('a', 64)],
                'css'      => ':root {}',
            ]);
        $controller = $this->createController(installed: $repository);

        $malformed = $controller->getProfile(
            profileId: 'voorbeeld-profiel',
            profileVersion: '1.0.0',
            contentHash: 'not-a-hash'
        );
        self::assertSame(Http::STATUS_NOT_FOUND, $malformed->getStatus());
        self::assertSame('', $malformed->render());

        $mismatched = $controller->getProfile(
            profileId: 'voorbeeld-profiel',
            profileVersion: '1.0.0',
            contentHash: str_repeat('b', 64)
        );
        self::assertSame(Http::STATUS_NOT_FOUND, $mismatched->getStatus());
        self::assertSame('', $mismatched->render());
    }

    private function createController(InstalledProfileRepository $installed): StylesheetController
    {
        $appManager = $this->createMock(IAppManager::class);
        $appManager->method('getAppPath')->willReturn($this->appPath);
        $profileFiles = new PackagedProfileFiles(appManager: $appManager);
        $profiles     = new TokenSetService(
            profileFiles: $profileFiles,
            envelope: new ProfileCatalogueEnvelope(),
            normalizer: new ProfileManifestEntryNormalizer(profileFiles: $profileFiles),
            installed: $installed
        );

        return new StylesheetController(
            appName: 'nldesign',
            request: $this->createMock(IRequest::class),
            profiles: $profiles
        );
    }
}
