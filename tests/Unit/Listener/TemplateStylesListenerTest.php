<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Listener;

use OCA\NLDesign\Application\Presentation\CoreRuntimeCompatibility;
use OCA\NLDesign\Application\Presentation\RuntimeStylesheetPlan;
use OCA\NLDesign\Domain\Profile\ProfileStateNormalizer;
use OCA\NLDesign\Domain\Presentation\NextcloudRuntime;
use OCA\NLDesign\Infrastructure\Nextcloud\Presentation\VersionedCoreSurfaceAdapter;
use OCA\NLDesign\Infrastructure\Profile\PackagedProfileFiles;
use OCA\NLDesign\Infrastructure\Profile\ProfileCatalogueEnvelope;
use OCA\NLDesign\Infrastructure\Profile\ProfileManifestEntryNormalizer;
use OCA\NLDesign\Infrastructure\Nextcloud\ProfileStateMutationGuard;
use OCA\NLDesign\Listener\TemplateStylesListener;
use OCA\NLDesign\Port\Profile\InstalledProfileRepository;
use OCA\NLDesign\Port\Presentation\NextcloudRuntimeProvider;
use OCA\NLDesign\Service\ProfileStateService;
use OCA\NLDesign\Service\TokenSetService;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Http\Events\BeforeLoginTemplateRenderedEvent;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IAppConfig as IGlobalAppConfig;
use OCP\IURLGenerator;
use OCP\Lock\ILockingProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TemplateStylesListenerTest extends TestCase
{

    private string $appPath;

    protected function setUp(): void
    {
        parent::setUp();
        \OC_Util::$styles = [];
        \OC_Util::$headers = [];
        $this->appPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'nldesign-listener-'.bin2hex(random_bytes(6));
        mkdir($this->appPath.'/css/tokens', 0777, true);
        file_put_contents($this->appPath.'/css/tokens/package-default.css', ':root {}');
        file_put_contents(
            $this->appPath.'/token-sets.json',
            json_encode(
                value: [
                    'schema'          => 'nldesign-profile-catalogue/v1',
                    'default_profile' => null,
                    'profiles'        => [
                        [
                            'id'          => 'package-default',
                            'name'        => 'Ready profile',
                            'description' => 'Explicitly selectable profile',
                            'version'     => '1.0.0',
                            'status'      => 'ready',
                            'projection'  => 'nextcloud-core-v1',
                        ],
                    ],
                ],
                flags: JSON_THROW_ON_ERROR
            )
        );
    }//end setUp()

    protected function tearDown(): void
    {
        unlink($this->appPath.'/css/tokens/package-default.css');
        unlink($this->appPath.'/token-sets.json');
        rmdir($this->appPath.'/css/tokens');
        rmdir($this->appPath.'/css');
        rmdir($this->appPath);
        parent::tearDown();
    }//end tearDown()

    public function testUnavailableStoredProfileFailsOpenWithoutApplyingPackageDefault(): void
    {
        $appManager = $this->createMock(IAppManager::class);
        $appManager->method('getAppPath')->willReturn($this->appPath);
        $profileFiles = new PackagedProfileFiles(appManager: $appManager);
        $tokenSets    = new TokenSetService(
            profileFiles: $profileFiles,
            envelope: new ProfileCatalogueEnvelope(),
            normalizer: new ProfileManifestEntryNormalizer(profileFiles: $profileFiles),
            installed: $this->createInstalledRepository()
        );

        $config = $this->createMock(IAppConfig::class);
        $config->method('hasAppKey')->willReturn(true);
        $config->method('getAppValueString')->willReturnCallback(
            static function (string $key, string $default='', bool $lazy=false): string {
                if ($key === 'active_profile_state') {
                    return json_encode(
                        [
                            'active_profile_id'       => 'removed-profile',
                            'active_profile_revision' => 'aaaaaaaaaaaaaaaaaaaa',
                        ],
                        JSON_THROW_ON_ERROR
                    );
                }

                return $default;
            }
        );
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('debug')
            ->with(
                'NL Design skipped an unavailable active profile.',
                ['profile_id' => 'removed-profile']
            );
        $profileState = new ProfileStateService(
            config: $config,
            logger: $logger,
            mutationGuard: new ProfileStateMutationGuard(
                globalAppConfig: $this->createMock(IGlobalAppConfig::class),
                lockingProvider: $this->createMock(ILockingProvider::class),
                logger: $logger
            ),
            normalizer: new ProfileStateNormalizer(),
            profiles: $tokenSets
        );
        $listener     = new TemplateStylesListener(
            tokenSetService: $tokenSets,
            profileStateService: $profileState,
            stylesheetPlan: $this->createStylesheetPlan(),
            urlGenerator: $this->createMock(IURLGenerator::class),
            logger: $logger
        );

        $listener->handle(
            new BeforeTemplateRenderedEvent(
                loggedIn: false,
                response: $this->createMock(TemplateResponse::class)
            )
        );

        self::assertSame([], \OC_Util::$styles);
    }//end testUnavailableStoredProfileFailsOpenWithoutApplyingPackageDefault()

    public function testNativeStateAttachesNoStyles(): void
    {
        $appManager = $this->createMock(IAppManager::class);
        $appManager->method('getAppPath')->willReturn($this->appPath);
        $profileFiles = new PackagedProfileFiles(appManager: $appManager);
        $tokenSets    = new TokenSetService(
            profileFiles: $profileFiles,
            envelope: new ProfileCatalogueEnvelope(),
            normalizer: new ProfileManifestEntryNormalizer(profileFiles: $profileFiles),
            installed: $this->createInstalledRepository()
        );

        $config = $this->createMock(IAppConfig::class);
        $config->method('hasAppKey')->willReturn(true);
        $config->method('getAppValueString')->willReturnCallback(
            static function (string $key, string $default='', bool $lazy=false): string {
                if ($key === 'active_profile_state') {
                    return json_encode(
                        [
                            'active_profile_id'       => null,
                            'active_profile_revision' => 'aaaaaaaaaaaaaaaaaaaa',
                        ],
                        JSON_THROW_ON_ERROR
                    );
                }

                return $default;
            }
        );
        $logger       = $this->createMock(LoggerInterface::class);
        $profileState = new ProfileStateService(
            config: $config,
            logger: $logger,
            mutationGuard: new ProfileStateMutationGuard(
                globalAppConfig: $this->createMock(IGlobalAppConfig::class),
                lockingProvider: $this->createMock(ILockingProvider::class),
                logger: $logger
            ),
            normalizer: new ProfileStateNormalizer(),
            profiles: $tokenSets
        );
        $listener     = new TemplateStylesListener(
            tokenSetService: $tokenSets,
            profileStateService: $profileState,
            stylesheetPlan: $this->createStylesheetPlan(),
            urlGenerator: $this->createMock(IURLGenerator::class),
            logger: $logger
        );

        $listener->handle(
            new BeforeTemplateRenderedEvent(
                loggedIn: false,
                response: $this->createMock(TemplateResponse::class)
            )
        );

        self::assertSame([], \OC_Util::$styles);
    }//end testNativeStateAttachesNoStyles()

    public function testReadyProfileAttachesExactCoreStackOnlyOncePerRequest(): void
    {
        $appManager = $this->createMock(IAppManager::class);
        $appManager->method('getAppPath')->willReturn($this->appPath);
        $profileFiles = new PackagedProfileFiles(appManager: $appManager);
        $tokenSets    = new TokenSetService(
            profileFiles: $profileFiles,
            envelope: new ProfileCatalogueEnvelope(),
            normalizer: new ProfileManifestEntryNormalizer(profileFiles: $profileFiles),
            installed: $this->createInstalledRepository()
        );

        $config = $this->createMock(IAppConfig::class);
        $config->method('getAppValueString')->willReturnCallback(
            static function (string $key, string $default='', bool $lazy=false): string {
                if ($key === 'token_set') {
                    return 'package-default';
                }

                return $default;
            }
        );
        $logger       = $this->createMock(LoggerInterface::class);
        $profileState = new ProfileStateService(
            config: $config,
            logger: $logger,
            mutationGuard: new ProfileStateMutationGuard(
                globalAppConfig: $this->createMock(IGlobalAppConfig::class),
                lockingProvider: $this->createMock(ILockingProvider::class),
                logger: $logger
            ),
            normalizer: new ProfileStateNormalizer(),
            profiles: $tokenSets
        );
        $listener     = new TemplateStylesListener(
            tokenSetService: $tokenSets,
            profileStateService: $profileState,
            stylesheetPlan: $this->createStylesheetPlan(),
            urlGenerator: $this->createMock(IURLGenerator::class),
            logger: $logger
        );

        $listener->handle(
            new BeforeLoginTemplateRenderedEvent(
                response: $this->createMock(TemplateResponse::class)
            )
        );
        $listener->handle(
            new BeforeTemplateRenderedEvent(
                loggedIn: false,
                response: $this->createMock(TemplateResponse::class)
            )
        );

        self::assertSame(
            [
                ['nldesign', 'fonts', false],
                ['nldesign', 'tokens/package-default', false],
                ['nldesign', 'compatibility/nextcloud-core-v1', false],
            ],
            \OC_Util::$styles
        );
    }//end testReadyProfileAttachesExactCoreStack()

    public function testInstalledProfileAttachesExactDigestAddressedStylesheet(): void
    {
        $hash       = str_repeat('c', 64);
        $record     = [
            'metadata' => [
                'id'           => 'installed-profile',
                'version'      => '2.1.0',
                'name'         => 'Installed profile',
                'status'       => 'ready',
                'origin'       => 'installed',
                'content_hash' => $hash,
            ],
            'css'      => ':root {}',
        ];
        $installed  = $this->createMock(InstalledProfileRepository::class);
        $installed->method('listRecords')->willReturn([$record]);
        $installed->method('find')->willReturn($record);
        $appManager = $this->createMock(IAppManager::class);
        $appManager->method('getAppPath')->willReturn($this->appPath);
        $profileFiles = new PackagedProfileFiles(appManager: $appManager);
        $tokenSets    = new TokenSetService(
            profileFiles: $profileFiles,
            envelope: new ProfileCatalogueEnvelope(),
            normalizer: new ProfileManifestEntryNormalizer(profileFiles: $profileFiles),
            installed: $installed
        );

        $config = $this->createMock(IAppConfig::class);
        $config->method('hasAppKey')->willReturn(true);
        $config->method('getAppValueString')->willReturnCallback(
            static function (string $key, string $default='', bool $lazy=false): string {
                if ($key === 'active_profile_state') {
                    return json_encode(
                        [
                            'active_profile_id'       => 'installed-profile',
                            'active_profile_version'  => '2.1.0',
                            'active_profile_revision' => str_repeat('a', 20),
                        ],
                        JSON_THROW_ON_ERROR
                    );
                }

                return $default;
            }
        );
        $logger       = $this->createMock(LoggerInterface::class);
        $profileState = new ProfileStateService(
            config: $config,
            logger: $logger,
            mutationGuard: new ProfileStateMutationGuard(
                globalAppConfig: $this->createMock(IGlobalAppConfig::class),
                lockingProvider: $this->createMock(ILockingProvider::class),
                logger: $logger
            ),
            normalizer: new ProfileStateNormalizer(),
            profiles: $tokenSets
        );
        $urlGenerator = $this->createMock(IURLGenerator::class);
        $urlGenerator->expects(self::once())
            ->method('linkToRoute')
            ->with(
                'nldesign.stylesheet.getProfile',
                [
                    'profileId'      => 'installed-profile',
                    'profileVersion' => '2.1.0',
                    'contentHash'    => $hash,
                ]
            )
            ->willReturn('/apps/nldesign/styles/profiles/installed-profile/2.1.0/'.$hash);
        $listener = new TemplateStylesListener(
            tokenSetService: $tokenSets,
            profileStateService: $profileState,
            stylesheetPlan: $this->createStylesheetPlan(),
            urlGenerator: $urlGenerator,
            logger: $logger
        );

        $listener->handle(
            new BeforeTemplateRenderedEvent(
                loggedIn: true,
                response: $this->createMock(TemplateResponse::class)
            )
        );

        self::assertSame(
            [
                ['nldesign', 'fonts', false],
                ['nldesign', 'compatibility/nextcloud-core-v1', false],
            ],
            \OC_Util::$styles
        );
        self::assertSame(
            [
                [
                    'link',
                    [
                        'rel'  => 'stylesheet',
                        'href' => '/apps/nldesign/styles/profiles/installed-profile/2.1.0/'.$hash,
                    ],
                    null,
                ],
            ],
            \OC_Util::$headers
        );
    }

    private function createInstalledRepository(): InstalledProfileRepository
    {
        $installed = $this->createMock(InstalledProfileRepository::class);
        $installed->method('listRecords')->willReturn([]);
        $installed->method('find')->willReturn(null);
        return $installed;
    }//end createInstalledRepository()

    private function createStylesheetPlan(int $major=32): RuntimeStylesheetPlan
    {
        $runtimeProvider = $this->createMock(NextcloudRuntimeProvider::class);
        $runtimeProvider->method('current')->willReturn(
            new NextcloudRuntime($major, 0, 0)
        );

        return new RuntimeStylesheetPlan(
            new CoreRuntimeCompatibility(
                $runtimeProvider,
                new VersionedCoreSurfaceAdapter()
            )
        );
    }//end createStylesheetPlan()
}//end class
