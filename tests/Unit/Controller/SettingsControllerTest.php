<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Controller;

use OCA\NLDesign\Application\Branding\ManualThemingPlanBuilder;
use OCA\NLDesign\Application\Presentation\CoreRuntimeCompatibility;
use OCA\NLDesign\Application\Presentation\RuntimeStylesheetPlan;
use OCA\NLDesign\Controller\SettingsController;
use OCA\NLDesign\Domain\Profile\ProfileStateNormalizer;
use OCA\NLDesign\Domain\Presentation\NextcloudRuntime;
use OCA\NLDesign\Infrastructure\Nextcloud\Presentation\VersionedCoreSurfaceAdapter;
use OCA\NLDesign\Infrastructure\Nextcloud\ProfileStateMutationGuard;
use OCA\NLDesign\Infrastructure\Profile\PackagedProfileFiles;
use OCA\NLDesign\Infrastructure\Profile\ProfileCatalogueEnvelope;
use OCA\NLDesign\Infrastructure\Profile\ProfileManifestEntryNormalizer;
use OCA\NLDesign\Port\Profile\InstalledProfileRepository;
use OCA\NLDesign\Port\Presentation\NextcloudRuntimeProvider;
use OCA\NLDesign\Service\ProfileStateService;
use OCA\NLDesign\Service\TokenSetService;
use OCA\NLDesign\Settings\Admin;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IAppConfig as IGlobalAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Lock\ILockingProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

class SettingsControllerTest extends TestCase
{
    private string $appPath;

    /**
     * @var array<string, string>
     */
    private array $store = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->appPath = sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'nldesign-controller-'
            .bin2hex(random_bytes(6));
        mkdir($this->appPath.'/css/tokens', 0777, true);
        file_put_contents($this->appPath.'/css/tokens/rijkshuisstijl.css', ':root {}');
        file_put_contents($this->appPath.'/css/tokens/utrecht.css', ':root {}');
        file_put_contents(
            $this->appPath.'/token-sets.json',
            json_encode(
                value: [
                    'schema'          => 'nldesign-profile-catalogue/v1',
                    'default_profile' => null,
                    'profiles'        => [
                        [
                            'id'          => 'rijkshuisstijl',
                            'version'     => '1.0.0',
                            'name'        => 'Rijkshuisstijl',
                            'description' => 'Test profile',
                            'status'      => 'ready',
                            'projection'  => 'nextcloud-core-v1',
                            'theming'     => [
                                'primary_color'    => '#154273',
                                'background_color' => '#ffffff',
                            ],
                        ],
                        [
                            'id'          => 'utrecht',
                            'version'     => '1.0.0',
                            'name'        => 'Gemeente Utrecht',
                            'description' => 'Test profile',
                            'status'      => 'ready',
                            'projection'  => 'nextcloud-core-v1',
                            'theming'     => [
                                'primary_color' => '#cc0000',
                            ],
                        ],
                    ],
                ],
                flags: JSON_THROW_ON_ERROR
            )
        );
    }//end setUp()

    protected function tearDown(): void
    {
        unlink($this->appPath.'/css/tokens/rijkshuisstijl.css');
        unlink($this->appPath.'/css/tokens/utrecht.css');
        unlink($this->appPath.'/token-sets.json');
        rmdir($this->appPath.'/css/tokens');
        rmdir($this->appPath.'/css');
        rmdir($this->appPath);

        parent::tearDown();
    }//end tearDown()

    public function testProfileLifecycleAndReadEndpoints(): void
    {
        $runtime    = $this->createRuntime();
        $controller = $runtime['controller'];

        $initialResponse = $controller->getTokenSet();
        $initial         = $this->getResponseData(response: $initialResponse);
        self::assertSame(Http::STATUS_OK, $initialResponse->getStatus());
        self::assertSame('ok', $initial['status']);
        self::assertNull($initial['tokenSet']);
        self::assertFalse($initial['available']);
        self::assertFalse($initial['canRollback']);
        self::assertNull($initial['tokenSetMetadata']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{20}$/', $initial['revision']);

        $invalidProfile = $controller->setTokenSet(
            tokenSet: '../utrecht',
            expectedRevision: $initial['revision']
        );
        self::assertSame(Http::STATUS_BAD_REQUEST, $invalidProfile->getStatus());
        self::assertSame('invalid_profile', $this->getResponseData($invalidProfile)['status']);

        $invalidRevision = $controller->setTokenSet(
            tokenSet: 'utrecht',
            expectedRevision: 'not-a-revision'
        );
        self::assertSame(Http::STATUS_BAD_REQUEST, $invalidRevision->getStatus());
        self::assertSame('invalid_revision', $this->getResponseData($invalidRevision)['status']);

        $activationResponse = $controller->setTokenSet(
            tokenSet: 'utrecht',
            expectedRevision: $initial['revision']
        );
        $activation = $this->getResponseData(response: $activationResponse);
        self::assertSame(Http::STATUS_OK, $activationResponse->getStatus());
        self::assertSame('ok', $activation['status']);
        self::assertSame('utrecht', $activation['tokenSet']);
        self::assertNull($activation['previousProfile']);
        self::assertTrue($activation['canRollback']);
        self::assertNotSame($initial['revision'], $activation['revision']);

        $active = $this->getResponseData(response: $controller->getTokenSet());
        self::assertSame('utrecht', $active['tokenSet']);
        self::assertTrue($active['available']);
        self::assertSame('#cc0000', $active['tokenSetMetadata']['theming']['primary_color']);

        $planResponse = $controller->getThemingPlan();
        $plan         = $this->getResponseData(response: $planResponse);
        self::assertSame(Http::STATUS_OK, $planResponse->getStatus());
        self::assertSame('utrecht', $plan['tokenSet']);
        self::assertSame('manual', $plan['plan']['mode']);
        self::assertFalse($plan['plan']['appliesAutomatically']);
        self::assertSame('primary_color', $plan['plan']['steps'][0]['field']);

        $noChange = $this->getResponseData(
            response: $controller->setTokenSet(
                tokenSet: 'utrecht',
                expectedRevision: $activation['revision']
            )
        );
        self::assertSame($activation['revision'], $noChange['revision']);

        $staleResponse = $controller->setTokenSet(
            tokenSet: 'rijkshuisstijl',
            expectedRevision: $initial['revision']
        );
        $stale         = $this->getResponseData(response: $staleResponse);
        self::assertSame(Http::STATUS_CONFLICT, $staleResponse->getStatus());
        self::assertSame('revision_mismatch', $stale['status']);
        self::assertSame($activation['revision'], $stale['currentRevision']);

        $invalidDeactivation = $controller->deactivateTokenSet(expectedRevision: '');
        self::assertSame(Http::STATUS_BAD_REQUEST, $invalidDeactivation->getStatus());
        self::assertSame(
            'invalid_revision',
            $this->getResponseData(response: $invalidDeactivation)['status']
        );

        $staleDeactivation = $controller->deactivateTokenSet(
            expectedRevision: $initial['revision']
        );
        self::assertSame(Http::STATUS_CONFLICT, $staleDeactivation->getStatus());
        self::assertSame(
            'revision_mismatch',
            $this->getResponseData(response: $staleDeactivation)['status']
        );

        $deactivationResponse = $controller->deactivateTokenSet(
            expectedRevision: $activation['revision']
        );
        $deactivation = $this->getResponseData(response: $deactivationResponse);
        self::assertSame(Http::STATUS_OK, $deactivationResponse->getStatus());
        self::assertNull($deactivation['tokenSet']);
        self::assertSame('utrecht', $deactivation['previousProfile']);
        self::assertTrue($deactivation['canRollback']);

        $historyResponse = $controller->getProfileHistory();
        $history         = $this->getResponseData(response: $historyResponse);
        self::assertSame(Http::STATUS_OK, $historyResponse->getStatus());
        self::assertCount(2, $history['history']);
        self::assertSame('utrecht', $history['history'][0]['from_profile_id']);
        self::assertNull($history['history'][0]['to_profile_id']);

        $invalidRollback = $controller->rollbackTokenSet(expectedRevision: 'invalid');
        self::assertSame(Http::STATUS_BAD_REQUEST, $invalidRollback->getStatus());

        $rollbackResponse = $controller->rollbackTokenSet(
            expectedRevision: $deactivation['revision']
        );
        $rollback         = $this->getResponseData(response: $rollbackResponse);
        self::assertSame(Http::STATUS_OK, $rollbackResponse->getStatus());
        self::assertSame('utrecht', $rollback['tokenSet']);
        self::assertTrue($rollback['canRollback']);

        $staleRollback = $controller->rollbackTokenSet(
            expectedRevision: $deactivation['revision']
        );
        self::assertSame(Http::STATUS_CONFLICT, $staleRollback->getStatus());
        self::assertSame(
            'revision_mismatch',
            $this->getResponseData(response: $staleRollback)['status']
        );
    }//end testProfileLifecycleAndReadEndpoints()

    public function testNativeStateAndInvalidPlansFailSafely(): void
    {
        $controller = $this->createRuntime()['controller'];
        $initial    = $this->getResponseData(response: $controller->getTokenSet());

        $implicitPlan = $controller->getThemingPlan();
        self::assertSame(Http::STATUS_BAD_REQUEST, $implicitPlan->getStatus());
        self::assertSame('invalid_profile', $this->getResponseData($implicitPlan)['status']);

        $explicitPlan = $controller->getThemingPlan(tokenSet: 'missing');
        self::assertSame(Http::STATUS_BAD_REQUEST, $explicitPlan->getStatus());

        $noPrevious = $controller->rollbackTokenSet(
            expectedRevision: $initial['revision']
        );
        self::assertSame(Http::STATUS_CONFLICT, $noPrevious->getStatus());
        self::assertSame(
            'no_previous_snapshot',
            $this->getResponseData(response: $noPrevious)['status']
        );

        $nativeNoop = $controller->deactivateTokenSet(
            expectedRevision: $initial['revision']
        );
        $nativeData = $this->getResponseData(response: $nativeNoop);
        self::assertSame(Http::STATUS_OK, $nativeNoop->getStatus());
        self::assertNull($nativeData['tokenSet']);
        self::assertFalse($nativeData['canRollback']);
    }//end testNativeStateAndInvalidPlansFailSafely()

    public function testInfrastructureFailuresReturnRetryableResponses(): void
    {
        $lock = $this->createMock(ILockingProvider::class);
        $lock->method('acquireLock')->willThrowException(
            new RuntimeException('Lock unavailable')
        );
        $lockedController = $this->createRuntime(lockingProvider: $lock)['controller'];
        $lockedState      = $this->getResponseData(response: $lockedController->getTokenSet());

        $lockedActivation = $lockedController->setTokenSet(
            tokenSet: 'utrecht',
            expectedRevision: $lockedState['revision']
        );
        self::assertSame(Http::STATUS_SERVICE_UNAVAILABLE, $lockedActivation->getStatus());
        self::assertSame(
            'lock_unavailable',
            $this->getResponseData(response: $lockedActivation)['status']
        );

        $lockedDeactivation = $lockedController->deactivateTokenSet(
            expectedRevision: $lockedState['revision']
        );
        self::assertSame(Http::STATUS_SERVICE_UNAVAILABLE, $lockedDeactivation->getStatus());

        $lockedRollback = $lockedController->rollbackTokenSet(
            expectedRevision: $lockedState['revision']
        );
        self::assertSame(Http::STATUS_SERVICE_UNAVAILABLE, $lockedRollback->getStatus());

        $globalConfig = $this->createMock(IGlobalAppConfig::class);
        $globalConfig->method('clearCache')->willThrowException(
            new RuntimeException('Cache unavailable')
        );
        $unavailableController = $this->createRuntime(
            globalAppConfig: $globalConfig
        )['controller'];
        $unavailableState = $this->getResponseData(
            response: $unavailableController->getTokenSet()
        );
        $unavailable = $unavailableController->setTokenSet(
            tokenSet: 'utrecht',
            expectedRevision: $unavailableState['revision']
        );
        self::assertSame(Http::STATUS_SERVICE_UNAVAILABLE, $unavailable->getStatus());
        self::assertSame(
            'state_unavailable',
            $this->getResponseData(response: $unavailable)['status']
        );

        $config = $this->createConfig(persist: false);
        $persistenceController = $this->createRuntime(config: $config)['controller'];
        $persistenceState      = $this->getResponseData(
            response: $persistenceController->getTokenSet()
        );
        $persistence = $persistenceController->setTokenSet(
            tokenSet: 'utrecht',
            expectedRevision: $persistenceState['revision']
        );
        self::assertSame(Http::STATUS_SERVICE_UNAVAILABLE, $persistence->getStatus());
        self::assertSame(
            'persistence_failed',
            $this->getResponseData(response: $persistence)['status']
        );
    }//end testInfrastructureFailuresReturnRetryableResponses()

    public function testDelegatedAdminSettingsExposeOnlyOwnedState(): void
    {
        $runtime = $this->createRuntime();
        $l10n    = $this->createMock(IL10N::class);
        $l10n->method('t')->willReturnCallback(
            static fn (string $text, array $parameters=[]): string => $text
        );
        $admin = new Admin(
            l: $l10n,
            tokenSetService: $runtime['tokenSets'],
            profileStateService: $runtime['profileState'],
            stylesheetPlan: $this->createStylesheetPlan()
        );

        self::assertSame('theming', $admin->getSection());
        self::assertSame(50, $admin->getPriority());
        self::assertSame('NL Design', $admin->getName());
        self::assertSame(
            [
                'nldesign' => [
                    '/^(active_profile_state|active_profile_revision|active_profile_version|profile_state_history|token_set)$/',
                ],
            ],
            $admin->getAuthorizedAppConfig()
        );

        $form   = $admin->getForm();
        $params = $form->getParams();
        self::assertSame('nldesign', $form->getApp());
        self::assertSame('settings/admin', $form->getTemplateName());
        self::assertCount(2, $params['tokenSets']);
        self::assertNull($params['currentTokenSet']);
        self::assertFalse($params['currentTokenSetAvailable']);
        self::assertTrue($params['runtimeCompatibility']['supported']);
        self::assertSame(
            'nextcloud-core-v1',
            $params['runtimeCompatibility']['adapter_id']
        );

        $initial = $runtime['profileState']->getActiveProfileState();
        $runtime['controller']->setTokenSet(
            tokenSet: 'utrecht',
            expectedRevision: $initial['active_profile_revision']
        );
        $activeParams = $admin->getForm()->getParams();
        self::assertSame('utrecht', $activeParams['currentTokenSet']);
        self::assertTrue($activeParams['currentTokenSetAvailable']);
    }//end testDelegatedAdminSettingsExposeOnlyOwnedState()

    /**
     * @return array{
     *     controller: SettingsController,
     *     tokenSets: TokenSetService,
     *     profileState: ProfileStateService
     * }
     */
    private function createRuntime(
        ?IAppConfig $config=null,
        ?IGlobalAppConfig $globalAppConfig=null,
        ?ILockingProvider $lockingProvider=null
    ): array {
        $appManager = $this->createMock(IAppManager::class);
        $appManager->method('getAppPath')->willReturn($this->appPath);
        $profileFiles = new PackagedProfileFiles(appManager: $appManager);
        $installed    = $this->createMock(InstalledProfileRepository::class);
        $installed->method('listRecords')->willReturn([]);
        $installed->method('find')->willReturn(null);
        $tokenSets    = new TokenSetService(
            profileFiles: $profileFiles,
            envelope: new ProfileCatalogueEnvelope(),
            normalizer: new ProfileManifestEntryNormalizer(profileFiles: $profileFiles),
            installed: $installed
        );
        $profileState = new ProfileStateService(
            config: $config ?? $this->createConfig(),
            logger: new NullLogger(),
            mutationGuard: new ProfileStateMutationGuard(
                globalAppConfig: $globalAppConfig ?? $this->createMock(IGlobalAppConfig::class),
                lockingProvider: $lockingProvider ?? $this->createMock(ILockingProvider::class),
                logger: new NullLogger()
            ),
            normalizer: new ProfileStateNormalizer(),
            profiles: $tokenSets
        );

        return [
            'controller' => new SettingsController(
                appName: 'nldesign',
                request: $this->createMock(IRequest::class),
                tokenSetService: $tokenSets,
                profileStateService: $profileState,
                planBuilder: new ManualThemingPlanBuilder()
            ),
            'tokenSets'   => $tokenSets,
            'profileState' => $profileState,
        ];
    }//end createRuntime()

    /**
     * @return IAppConfig&MockObject
     */
    private function createConfig(bool $persist=true): IAppConfig
    {
        $config = $this->createMock(IAppConfig::class);
        $config->method('hasAppKey')->willReturnCallback(
            fn (string $key, ?bool $lazy=false): bool => array_key_exists($key, $this->store)
        );
        $config->method('getAppValueString')->willReturnCallback(
            fn (string $key, string $default='', bool $lazy=false): string => $this->store[$key] ?? $default
        );
        $config->method('setAppValueString')->willReturnCallback(
            function (
                string $key,
                string $value,
                bool $lazy=false,
                bool $sensitive=false
            ) use ($persist): bool {
                if ($persist === false) {
                    return false;
                }

                $this->store[$key] = $value;
                return true;
            }
        );

        return $config;
    }//end createConfig()

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

    /**
     * @return array<string, mixed>
     */
    private function getResponseData(JSONResponse $response): array
    {
        $data = $response->getData();
        self::assertIsArray($data);
        return $data;
    }//end getResponseData()
}//end class
