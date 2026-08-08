<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Service;

use OCA\NLDesign\Application\Profile\ProfileCssCompiler;
use OCA\NLDesign\Domain\Profile\ProfileModeValidator;
use OCA\NLDesign\Domain\Profile\ProfilePackValidator;
use OCA\NLDesign\Domain\Profile\ProfileStateNormalizer;
use OCA\NLDesign\Infrastructure\Nextcloud\ProfileStateMutationGuard;
use OCA\NLDesign\Infrastructure\Profile\PackagedProfileFiles;
use OCA\NLDesign\Infrastructure\Profile\ProfileCatalogueEnvelope;
use OCA\NLDesign\Infrastructure\Profile\ProfileManifestEntryNormalizer;
use OCA\NLDesign\Port\Profile\InstalledProfileRepository;
use OCA\NLDesign\Service\ProfileInstallerService;
use OCA\NLDesign\Service\ProfileStateService;
use OCA\NLDesign\Service\TokenSetService;
use OCP\App\IAppManager;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IAppConfig as IGlobalAppConfig;
use OCP\Lock\ILockingProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ProfileInstallerServiceTest extends TestCase
{
    private string $appPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->appPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nldesign-installer-'.bin2hex(random_bytes(6));
        mkdir($this->appPath.'/css/tokens', 0777, true);
        $this->writeManifest(entries: []);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory(path: $this->appPath);
        parent::tearDown();
    }

    public function testValidPackIsCompiledAndInstalledUnderTheSharedLock(): void
    {
        $installed = $this->createMock(InstalledProfileRepository::class);
        $installed->method('listRecords')->willReturn([]);
        $installed->expects(self::once())
            ->method('find')
            ->with('voorbeeld-profiel', '1.0.0')
            ->willReturn(null);
        $installed->expects(self::once())
            ->method('install')
            ->willReturnCallback(
                static function (array $profile, string $css, string $actor): array {
                    self::assertSame('voorbeeld-profiel', $profile['id']);
                    self::assertSame('1.0.0', $profile['version']);
                    self::assertStringContainsString('--nldesign-color-primary: #183a37;', $css);
                    self::assertStringNotContainsString('@import', $css);
                    self::assertSame('admin:settings', $actor);
                    return ['status' => 'ok'];
                }
            );

        $result = $this->createInstaller(installed: $installed)->install(
            profilePack: $this->readExamplePack(),
            actor: 'admin:settings'
        );

        self::assertSame(['status' => 'ok'], $result);
    }

    public function testInvalidPackNeverAcquiresStorageMutationPath(): void
    {
        $installed = $this->createMock(InstalledProfileRepository::class);
        $installed->expects(self::never())->method('find');
        $installed->expects(self::never())->method('install');

        $result = $this->createInstaller(installed: $installed)->install(
            profilePack: '{',
            actor: 'admin:settings'
        );

        self::assertSame('invalid_pack', $result['status']);
        self::assertSame('invalid_json', $result['code']);
    }

    public function testBuiltInIdentityCannotBeShadowedByAnInstalledPack(): void
    {
        $pack = json_decode($this->readExamplePack(), true, 512, JSON_THROW_ON_ERROR);
        $this->writeManifest(
            entries: [
                [
                    'id'          => 'voorbeeld-profiel',
                    'version'     => '1.0.0',
                    'name'        => 'Built-in voorbeeld',
                    'description' => 'Packaged projection owns this exact identity.',
                    'status'      => 'ready',
                    'projection'  => 'nextcloud-core-v1',
                ],
            ]
        );
        file_put_contents($this->appPath.'/css/tokens/voorbeeld-profiel.css', ':root {}');

        $installed = $this->createMock(InstalledProfileRepository::class);
        $installed->method('listRecords')->willReturn([]);
        $installed->expects(self::never())->method('find');
        $installed->expects(self::never())->method('install');

        $result = $this->createInstaller(installed: $installed)->install(
            profilePack: json_encode($pack, JSON_THROW_ON_ERROR),
            actor: 'admin:settings'
        );

        self::assertSame(['status' => 'version_conflict'], $result);
    }

    public function testActiveAndRollbackVersionsCannotBeUninstalled(): void
    {
        $installed = $this->createMock(InstalledProfileRepository::class);
        $installed->expects(self::never())->method('remove');

        $activeState = [
            'active_profile_id'       => 'voorbeeld-profiel',
            'active_profile_version'  => '1.0.0',
            'active_profile_revision' => str_repeat('a', 20),
        ];
        self::assertSame(
            'profile_active',
            $this->createInstaller(installed: $installed, state: $activeState)->uninstall(
                profileId: 'voorbeeld-profiel',
                profileVersion: '1.0.0'
            )['status']
        );

        $rollbackState = [
            'active_profile_id'         => null,
            'active_profile_version'    => null,
            'active_profile_revision'   => str_repeat('b', 20),
            'previous_profile_snapshot' => [
                'profile_id'      => 'voorbeeld-profiel',
                'profile_version' => '1.0.0',
                'revision'        => str_repeat('a', 20),
            ],
        ];
        self::assertSame(
            'profile_retained_for_rollback',
            $this->createInstaller(installed: $installed, state: $rollbackState)->uninstall(
                profileId: 'voorbeeld-profiel',
                profileVersion: '1.0.0'
            )['status']
        );
    }

    public function testInactiveUnretainedVersionCanBeUninstalled(): void
    {
        $installed = $this->createMock(InstalledProfileRepository::class);
        $installed->expects(self::once())
            ->method('remove')
            ->with('voorbeeld-profiel', '1.0.0')
            ->willReturn(['status' => 'ok']);

        $result = $this->createInstaller(installed: $installed)->uninstall(
            profileId: 'voorbeeld-profiel',
            profileVersion: '1.0.0'
        );

        self::assertSame(['status' => 'ok'], $result);
    }

    /**
     * @param array<string, mixed>|null $state
     */
    private function createInstaller(
        InstalledProfileRepository $installed,
        ?array $state=null
    ): ProfileInstallerService {
        $logger       = $this->createMock(LoggerInterface::class);
        $globalConfig = $this->createMock(IGlobalAppConfig::class);
        $locking      = $this->createMock(ILockingProvider::class);
        $guard        = new ProfileStateMutationGuard(
            globalAppConfig: $globalConfig,
            lockingProvider: $locking,
            logger: $logger
        );
        $profiles = $this->createTokenSetService(installed: $installed);

        $state ??= [
            'active_profile_id'       => null,
            'active_profile_version'  => null,
            'active_profile_revision' => str_repeat('a', 20),
        ];
        $config = $this->createMock(IAppConfig::class);
        $config->method('hasAppKey')->willReturn(true);
        $config->method('getAppValueString')->willReturnCallback(
            static function (string $key, string $default='', bool $lazy=false) use ($state): string {
                if ($key === 'active_profile_state') {
                    return json_encode($state, JSON_THROW_ON_ERROR);
                }

                return $default;
            }
        );
        $profileState = new ProfileStateService(
            config: $config,
            logger: $logger,
            mutationGuard: $guard,
            normalizer: new ProfileStateNormalizer(),
            profiles: $profiles
        );
        $validator = new ProfilePackValidator(modeValidator: new ProfileModeValidator());

        return new ProfileInstallerService(
            validator: $validator,
            compiler: new ProfileCssCompiler(),
            installed: $installed,
            profiles: $profiles,
            profileState: $profileState,
            mutationGuard: $guard
        );
    }

    private function createTokenSetService(InstalledProfileRepository $installed): TokenSetService
    {
        $appManager = $this->createMock(IAppManager::class);
        $appManager->method('getAppPath')->willReturn($this->appPath);
        $profileFiles = new PackagedProfileFiles(appManager: $appManager);

        return new TokenSetService(
            profileFiles: $profileFiles,
            envelope: new ProfileCatalogueEnvelope(),
            normalizer: new ProfileManifestEntryNormalizer(profileFiles: $profileFiles),
            installed: $installed
        );
    }

    private function readExamplePack(): string
    {
        $content = file_get_contents(dirname(__DIR__, 3).'/examples/profile-pack.v1.json');
        self::assertIsString($content);
        return $content;
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     */
    private function writeManifest(array $entries): void
    {
        file_put_contents(
            $this->appPath.'/token-sets.json',
            json_encode(
                [
                    'schema'          => 'nldesign-profile-catalogue/v1',
                    'default_profile' => null,
                    'profiles'        => $entries,
                ],
                JSON_THROW_ON_ERROR
            )
        );
    }

    private function removeDirectory(string $path): void
    {
        $entries = scandir($path);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $entryPath = $path.DIRECTORY_SEPARATOR.$entry;
            if (is_file($entryPath) === true) {
                unlink($entryPath);
                continue;
            }

            $this->removeDirectory(path: $entryPath);
        }

        rmdir($path);
    }
}
