<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Listener;

use OCA\NLDesign\Application\Branding\RuntimeStylesheetPlan;
use OCA\NLDesign\Domain\Profile\ProfileStateNormalizer;
use OCA\NLDesign\Infrastructure\Profile\PackagedProfileFiles;
use OCA\NLDesign\Infrastructure\Profile\ProfileCatalogueEnvelope;
use OCA\NLDesign\Infrastructure\Profile\ProfileManifestEntryNormalizer;
use OCA\NLDesign\Infrastructure\Nextcloud\ProfileStateMutationGuard;
use OCA\NLDesign\Listener\TemplateStylesListener;
use OCA\NLDesign\Service\ProfileStateService;
use OCA\NLDesign\Service\TokenSetService;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Http\Events\BeforeLoginTemplateRenderedEvent;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IAppConfig as IGlobalAppConfig;
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
            normalizer: new ProfileManifestEntryNormalizer(profileFiles: $profileFiles)
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
            stylesheetPlan: new RuntimeStylesheetPlan(),
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
            normalizer: new ProfileManifestEntryNormalizer(profileFiles: $profileFiles)
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
            stylesheetPlan: new RuntimeStylesheetPlan(),
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
            normalizer: new ProfileManifestEntryNormalizer(profileFiles: $profileFiles)
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
            stylesheetPlan: new RuntimeStylesheetPlan(),
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
                ['nldesign', 'theme', false],
            ],
            \OC_Util::$styles
        );
    }//end testReadyProfileAttachesExactCoreStack()
}//end class
