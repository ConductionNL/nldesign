<?php

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Service;

use OCA\NLDesign\Domain\Profile\ProfileCataloguePolicy;
use OCA\NLDesign\Domain\Profile\ProfileStateNormalizer;
use OCA\NLDesign\Infrastructure\Nextcloud\ProfileStateMutationGuard;
use OCA\NLDesign\Service\ProfileStateService;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IAppConfig as IGlobalAppConfig;
use OCP\Lock\ILockingProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ProfileStateServiceTest extends TestCase
{
    public function testInitialRevisionIsDeterministic(): void
    {
        $store   = [];
        $service = new ProfileStateService(
            config: $this->createConfig(store: $store),
            logger: new NullLogger(),
            mutationGuard: $this->createMutationGuard(),
            normalizer: new ProfileStateNormalizer(),
            profiles: $this->createProfileCataloguePolicy()
        );

        $first  = $service->getActiveProfileState();
        $second = $service->getActiveProfileState();

        self::assertNull($first['active_profile_id']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{20}$/', $first['active_profile_revision']);
        self::assertSame($first['active_profile_revision'], $second['active_profile_revision']);
    }//end testInitialRevisionIsDeterministic()

    public function testLegacyMirrorIsReadOnlyWhenCanonicalStateIsAbsent(): void
    {
        $store   = ['token_set' => 'utrecht'];
        $service = new ProfileStateService(
            config: $this->createConfig(store: $store),
            logger: new NullLogger(),
            mutationGuard: $this->createMutationGuard(),
            normalizer: new ProfileStateNormalizer(),
            profiles: $this->createProfileCataloguePolicy()
        );

        $state = $service->getActiveProfileState();

        self::assertSame('utrecht', $state['active_profile_id']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{20}$/', $state['active_profile_revision']);
        self::assertArrayNotHasKey('active_profile_state', $store);
    }//end testLegacyMirrorIsReadOnlyWhenCanonicalStateIsAbsent()

    public function testNativeInitialStateCanBeRestoredByRollback(): void
    {
        $store   = [];
        $service = new ProfileStateService(
            config: $this->createConfig(store: $store),
            logger: new NullLogger(),
            mutationGuard: $this->createMutationGuard(),
            normalizer: new ProfileStateNormalizer(),
            profiles: $this->createProfileCataloguePolicy()
        );

        $initial = $service->getActiveProfileState();
        self::assertNull($initial['active_profile_id']);

        $publish = $service->publishProfile(
            tokenSetId: 'utrecht',
            expectedRevision: $initial['active_profile_revision']
        );
        self::assertSame('ok', $publish['status']);
        self::assertNull($publish['next']['previous_profile_snapshot']['profile_id']);

        $rollback = $service->rollbackProfile(
            expectedRevision: $publish['next']['active_profile_revision']
        );
        self::assertSame('ok', $rollback['status']);
        self::assertNull($rollback['next']['active_profile_id']);
        self::assertSame('', $store['token_set']);

        $history = $service->getHistory();
        self::assertCount(2, $history);
        self::assertNull($history[0]['to_profile_id']);
    }//end testNativeInitialStateCanBeRestoredByRollback()

    public function testPublishPersistsCanonicalStateAndHistory(): void
    {
        $store   = [];
        $service = new ProfileStateService(
            config: $this->createConfig(store: $store),
            logger: new NullLogger(),
            mutationGuard: $this->createMutationGuard(),
            normalizer: new ProfileStateNormalizer(),
            profiles: $this->createProfileCataloguePolicy()
        );
        $initial = $service->getActiveProfileState();

        $result = $service->publishProfile(
            tokenSetId: 'utrecht',
            actor: 'user:admin',
            expectedRevision: $initial['active_profile_revision']
        );

        self::assertSame('ok', $result['status']);
        self::assertSame('utrecht', $result['next']['active_profile_id']);
        self::assertSame('1.0.0', $result['next']['active_profile_version']);
        self::assertSame(
            'utrecht',
            $store['token_set']
        );
        self::assertJson($store['active_profile_state']);

        $history = $service->getHistory();
        self::assertCount(1, $history);
        self::assertNull($history[0]['from_profile_id']);
        self::assertSame('utrecht', $history[0]['to_profile_id']);
        self::assertSame('1.0.0', $history[0]['to_profile_version']);
        self::assertSame('user:admin', $history[0]['actor']);
    }//end testPublishPersistsCanonicalStateAndHistory()

    public function testStaleRevisionCannotPublish(): void
    {
        $store   = [];
        $service = new ProfileStateService(
            config: $this->createConfig(store: $store),
            logger: new NullLogger(),
            mutationGuard: $this->createMutationGuard(),
            normalizer: new ProfileStateNormalizer(),
            profiles: $this->createProfileCataloguePolicy()
        );

        $result = $service->publishProfile(
            tokenSetId: 'utrecht',
            expectedRevision: 'aaaaaaaaaaaaaaaaaaaa'
        );

        self::assertSame('revision_mismatch', $result['status']);
        self::assertArrayNotHasKey('active_profile_state', $store);
    }//end testStaleRevisionCannotPublish()

    public function testUnavailableProfileCannotPublish(): void
    {
        $store   = [];
        $service = new ProfileStateService(
            config: $this->createConfig(store: $store),
            logger: new NullLogger(),
            mutationGuard: $this->createMutationGuard(),
            normalizer: new ProfileStateNormalizer(),
            profiles: $this->createProfileCataloguePolicy()
        );
        $initial = $service->getActiveProfileState();

        $result = $service->publishProfile(
            tokenSetId: 'removed-profile',
            expectedRevision: $initial['active_profile_revision']
        );

        self::assertSame('invalid_profile', $result['status']);
        self::assertArrayNotHasKey('active_profile_state', $store);
    }//end testUnavailableProfileCannotPublish()

    public function testRollbackUsesExpectedRevision(): void
    {
        $store   = [];
        $service = new ProfileStateService(
            config: $this->createConfig(store: $store),
            logger: new NullLogger(),
            mutationGuard: $this->createMutationGuard(),
            normalizer: new ProfileStateNormalizer(),
            profiles: $this->createProfileCataloguePolicy()
        );
        $initial = $service->getActiveProfileState();
        $publish = $service->publishProfile(
            tokenSetId: 'utrecht',
            expectedRevision: $initial['active_profile_revision']
        );

        $rollback = $service->rollbackProfile(
            expectedRevision: $publish['next']['active_profile_revision']
        );

        self::assertSame('ok', $rollback['status']);
        self::assertNull($rollback['next']['active_profile_id']);
        self::assertSame('utrecht', $rollback['next']['previous_profile_snapshot']['profile_id']);
    }//end testRollbackUsesExpectedRevision()

    public function testRollbackReadsCanonicalStateOnlyAfterAcquiringLock(): void
    {
        $oldRevision = 'aaaaaaaaaaaaaaaaaaaa';
        $newRevision = 'bbbbbbbbbbbbbbbbbbbb';
        $backingState = json_encode(
            [
                'active_profile_id'         => 'utrecht',
                'active_profile_revision'   => $oldRevision,
                'previous_profile_snapshot' => [
                    'profile_id' => null,
                    'revision'   => 'cccccccccccccccccccc',
                ],
            ],
            JSON_THROW_ON_ERROR
        );
        $cachedState = $backingState;
        $writes      = 0;

        $config = $this->createMock(IAppConfig::class);
        $config->method('hasAppKey')->willReturnCallback(
            static fn (string $key, ?bool $lazy=false): bool =>
                $key === 'active_profile_state'
        );
        $config->method('getAppValueString')->willReturnCallback(
            static function (string $key, string $default='', bool $lazy=false) use (
                &$backingState,
                &$cachedState
            ): string {
                if ($key !== 'active_profile_state') {
                    return $default;
                }

                if ($cachedState === null) {
                    $cachedState = $backingState;
                }

                return $cachedState;
            }
        );
        $config->method('setAppValueString')->willReturnCallback(
            static function () use (&$writes): bool {
                $writes += 1;
                return true;
            }
        );

        $lock = $this->createMock(ILockingProvider::class);
        $lock->method('acquireLock')->willReturnCallback(
            static function () use (&$backingState, $newRevision): void {
                $backingState = json_encode(
                    [
                        'active_profile_id'         => 'rijkshuisstijl',
                        'active_profile_revision'   => $newRevision,
                        'previous_profile_snapshot' => [
                            'profile_id' => 'utrecht',
                            'revision'   => 'aaaaaaaaaaaaaaaaaaaa',
                        ],
                    ],
                    JSON_THROW_ON_ERROR
                );
            }
        );

        $globalAppConfig = $this->createMock(IGlobalAppConfig::class);
        $globalAppConfig->method('clearCache')->willReturnCallback(
            static function (bool $reload=false) use (&$cachedState): void {
                $cachedState = null;
            }
        );

        $service = new ProfileStateService(
            config: $config,
            logger: new NullLogger(),
            mutationGuard: $this->createMutationGuard(
                globalAppConfig: $globalAppConfig,
                lockingProvider: $lock
            ),
            normalizer: new ProfileStateNormalizer(),
            profiles: $this->createProfileCataloguePolicy()
        );

        $result = $service->rollbackProfile(expectedRevision: $oldRevision);

        self::assertSame('revision_mismatch', $result['status']);
        self::assertSame($newRevision, $result['current_revision']);
        self::assertSame(0, $writes);
    }//end testRollbackReadsCanonicalStateOnlyAfterAcquiringLock()

    public function testMirrorFailureDoesNotSuppressCanonicalStateOrHistory(): void
    {
        $store  = [];
        $config = $this->createMock(IAppConfig::class);
        $config->method('hasAppKey')->willReturnCallback(
            static function (string $key, ?bool $lazy=false) use (&$store): bool {
                return array_key_exists($key, $store);
            }
        );
        $config->method('getAppValueString')->willReturnCallback(
            static function (string $key, string $default='', bool $lazy=false) use (&$store): string {
                return $store[$key] ?? $default;
            }
        );
        $config->method('setAppValueString')->willReturnCallback(
            static function (
                string $key,
                string $value,
                bool $lazy=false,
                bool $sensitive=false
            ) use (&$store): bool {
                if ($key === 'token_set') {
                    throw new \RuntimeException('Compatibility mirror unavailable');
                }

                $store[$key] = $value;
                return true;
            }
        );
        $service = new ProfileStateService(
            config: $config,
            logger: new NullLogger(),
            mutationGuard: $this->createMutationGuard(),
            normalizer: new ProfileStateNormalizer(),
            profiles: $this->createProfileCataloguePolicy()
        );
        $initial = $service->getActiveProfileState();

        $result = $service->publishProfile(
            tokenSetId: 'utrecht',
            expectedRevision: $initial['active_profile_revision']
        );

        self::assertSame('ok', $result['status']);
        self::assertArrayHasKey('active_profile_state', $store);
        self::assertArrayHasKey('profile_state_history', $store);
        self::assertCount(1, $service->getHistory());
    }//end testMirrorFailureDoesNotSuppressCanonicalStateOrHistory()

    public function testCanonicalPersistenceFailureIsReportedWithoutFalseSuccess(): void
    {
        $store  = [];
        $config = $this->createMock(IAppConfig::class);
        $config->method('hasAppKey')->willReturnCallback(
            static function (string $key, ?bool $lazy=false) use (&$store): bool {
                return array_key_exists($key, $store);
            }
        );
        $config->method('getAppValueString')->willReturnCallback(
            static function (string $key, string $default='', bool $lazy=false) use (&$store): string {
                return $store[$key] ?? $default;
            }
        );
        $config->method('setAppValueString')->willThrowException(
            new \RuntimeException('Canonical store unavailable')
        );
        $service = new ProfileStateService(
            config: $config,
            logger: new NullLogger(),
            mutationGuard: $this->createMutationGuard(),
            normalizer: new ProfileStateNormalizer(),
            profiles: $this->createProfileCataloguePolicy()
        );
        $initial = $service->getActiveProfileState();

        $result = $service->publishProfile(
            tokenSetId: 'utrecht',
            expectedRevision: $initial['active_profile_revision']
        );

        self::assertSame('persistence_failed', $result['status']);
        self::assertSame([], $store);
    }//end testCanonicalPersistenceFailureIsReportedWithoutFalseSuccess()

    public function testCanonicalUnchangedResultIsReportedWithoutFalseSuccess(): void
    {
        $store  = [];
        $config = $this->createMock(IAppConfig::class);
        $config->method('hasAppKey')->willReturn(false);
        $config->method('getAppValueString')->willReturnCallback(
            static fn (string $key, string $default='', bool $lazy=false): string => $default
        );
        $config->method('setAppValueString')->willReturn(false);
        $service = new ProfileStateService(
            config: $config,
            logger: new NullLogger(),
            mutationGuard: $this->createMutationGuard(),
            normalizer: new ProfileStateNormalizer(),
            profiles: $this->createProfileCataloguePolicy()
        );
        $initial = $service->getActiveProfileState();

        $result = $service->publishProfile(
            tokenSetId: 'utrecht',
            expectedRevision: $initial['active_profile_revision']
        );

        self::assertSame('persistence_failed', $result['status']);
        self::assertSame([], $store);
    }//end testCanonicalUnchangedResultIsReportedWithoutFalseSuccess()

    public function testMalformedCanonicalStateCannotReactivateLegacyMirror(): void
    {
        $store   = [
            'active_profile_state' => '{not-json',
            'token_set'            => 'utrecht',
        ];
        $service = new ProfileStateService(
            config: $this->createConfig(store: $store),
            logger: new NullLogger(),
            mutationGuard: $this->createMutationGuard(),
            normalizer: new ProfileStateNormalizer(),
            profiles: $this->createProfileCataloguePolicy()
        );

        $state = $service->getActiveProfileState();

        self::assertNull($state['active_profile_id']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{20}$/', $state['active_profile_revision']);
    }//end testMalformedCanonicalStateCannotReactivateLegacyMirror()

    public function testOversizedCanonicalStateCannotReactivateLegacyMirror(): void
    {
        $store   = [
            'active_profile_state' => str_repeat('x', 4097),
            'token_set'            => 'utrecht',
        ];
        $service = new ProfileStateService(
            config: $this->createConfig(store: $store),
            logger: new NullLogger(),
            mutationGuard: $this->createMutationGuard(),
            normalizer: new ProfileStateNormalizer(),
            profiles: $this->createProfileCataloguePolicy()
        );

        self::assertNull($service->getActiveProfileState()['active_profile_id']);
    }//end testOversizedCanonicalStateCannotReactivateLegacyMirror()

    public function testPartialCanonicalStateCannotExposeRollbackOrLegacyMirror(): void
    {
        $store = [
            'active_profile_state' => json_encode(
                [
                    'previous_profile_snapshot' => [
                        'profile_id' => 'utrecht',
                        'revision'   => 'aaaaaaaaaaaaaaaaaaaa',
                    ],
                ],
                JSON_THROW_ON_ERROR
            ),
            'token_set' => 'utrecht',
        ];
        $service = new ProfileStateService(
            config: $this->createConfig(store: $store),
            logger: new NullLogger(),
            mutationGuard: $this->createMutationGuard(),
            normalizer: new ProfileStateNormalizer(),
            profiles: $this->createProfileCataloguePolicy()
        );

        $state = $service->getActiveProfileState();

        self::assertNull($state['active_profile_id']);
        self::assertNull($state['previous_profile_snapshot']);
    }//end testPartialCanonicalStateCannotExposeRollbackOrLegacyMirror()

    public function testLockFailureCannotPublishState(): void
    {
        $store = [];
        $lock  = $this->createMock(ILockingProvider::class);
        $lock->method('acquireLock')->willThrowException(new \RuntimeException('Lock unavailable'));
        $service = new ProfileStateService(
            config: $this->createConfig(store: $store),
            logger: new NullLogger(),
            mutationGuard: $this->createMutationGuard(lockingProvider: $lock),
            normalizer: new ProfileStateNormalizer(),
            profiles: $this->createProfileCataloguePolicy()
        );
        $initial = $service->getActiveProfileState();

        $result = $service->publishProfile(
            tokenSetId: 'utrecht',
            expectedRevision: $initial['active_profile_revision']
        );

        self::assertSame('lock_unavailable', $result['status']);
        self::assertArrayNotHasKey('active_profile_state', $store);
    }//end testLockFailureCannotPublishState()

    public function testCacheRefreshFailureCannotPublishState(): void
    {
        $store           = [];
        $globalAppConfig = $this->createGlobalAppConfig();
        $globalAppConfig->method('clearCache')->willThrowException(
            new \RuntimeException('App-config cache unavailable')
        );
        $service = new ProfileStateService(
            config: $this->createConfig(store: $store),
            logger: new NullLogger(),
            mutationGuard: $this->createMutationGuard(globalAppConfig: $globalAppConfig),
            normalizer: new ProfileStateNormalizer(),
            profiles: $this->createProfileCataloguePolicy()
        );
        $initial = $service->getActiveProfileState();

        $result = $service->publishProfile(
            tokenSetId: 'utrecht',
            expectedRevision: $initial['active_profile_revision']
        );

        self::assertSame('state_unavailable', $result['status']);
        self::assertArrayNotHasKey('active_profile_state', $store);
    }//end testCacheRefreshFailureCannotPublishState()

    public function testHistoryDropsMalformedAndUnexpectedFields(): void
    {
        $store   = [
            'profile_state_history' => json_encode(
                [
                    [
                        'actor'                 => 'user:admin',
                        'timestamp'             => '2026-08-08T12:00:00+00:00',
                        'from_profile_id'       => 'rijkshuisstijl',
                        'from_profile_revision' => 'aaaaaaaaaaaaaaaaaaaa',
                        'to_profile_id'         => 'utrecht',
                        'to_profile_revision'   => 'bbbbbbbbbbbbbbbbbbbb',
                        'unexpected'            => '<script>',
                    ],
                    [
                        'actor'               => "user:bad\nactor",
                        'timestamp'           => '2026-08-08T12:00:01+00:00',
                        'to_profile_id'       => 'utrecht',
                        'to_profile_revision' => 'cccccccccccccccccccc',
                    ],
                ],
                JSON_THROW_ON_ERROR
            ),
        ];
        $service = new ProfileStateService(
            config: $this->createConfig(store: $store),
            logger: new NullLogger(),
            mutationGuard: $this->createMutationGuard(),
            normalizer: new ProfileStateNormalizer(),
            profiles: $this->createProfileCataloguePolicy()
        );

        $history = $service->getHistory();

        self::assertCount(1, $history);
        self::assertArrayNotHasKey('unexpected', $history[0]);
        self::assertSame('utrecht', $history[0]['to_profile_id']);
    }//end testHistoryDropsMalformedAndUnexpectedFields()

    public function testOversizedHistoryIsIgnored(): void
    {
        $store   = ['profile_state_history' => str_repeat('x', 32769)];
        $service = new ProfileStateService(
            config: $this->createConfig(store: $store),
            logger: new NullLogger(),
            mutationGuard: $this->createMutationGuard(),
            normalizer: new ProfileStateNormalizer(),
            profiles: $this->createProfileCataloguePolicy()
        );

        self::assertSame([], $service->getHistory());
    }//end testOversizedHistoryIsIgnored()

    /**
     * @param array<string, string> $store Mutable app-config fixture.
     */
    private function createConfig(array &$store): IAppConfig
    {
        $config = $this->createMock(IAppConfig::class);
        $config->method('hasAppKey')->willReturnCallback(
            static function (string $key, ?bool $lazy=false) use (&$store): bool {
                return array_key_exists($key, $store);
            }
        );
        $config->method('getAppValueString')->willReturnCallback(
            static function (string $key, string $default='', bool $lazy=false) use (&$store): string {
                return $store[$key] ?? $default;
            }
        );
        $config->method('setAppValueString')->willReturnCallback(
            static function (
                string $key,
                string $value,
                bool $lazy=false,
                bool $sensitive=false
            ) use (&$store): bool {
                $store[$key] = $value;
                return true;
            }
        );

        return $config;
    }//end createConfig()

    private function createLockingProvider(): ILockingProvider
    {
        return $this->createMock(ILockingProvider::class);
    }//end createLockingProvider()

    private function createGlobalAppConfig(): IGlobalAppConfig
    {
        return $this->createMock(IGlobalAppConfig::class);
    }//end createGlobalAppConfig()

    private function createMutationGuard(
        ?IGlobalAppConfig $globalAppConfig=null,
        ?ILockingProvider $lockingProvider=null
    ): ProfileStateMutationGuard {
        return new ProfileStateMutationGuard(
            globalAppConfig: $globalAppConfig ?? $this->createGlobalAppConfig(),
            lockingProvider: $lockingProvider ?? $this->createLockingProvider(),
            logger: new NullLogger()
        );
    }//end createMutationGuard()

    private function createProfileCataloguePolicy(): ProfileCataloguePolicy
    {
        return new class implements ProfileCataloguePolicy {
            public function isValidTokenSet(string $tokenSetId, string $profileVersion=''): bool
            {
                return in_array($tokenSetId, ['rijkshuisstijl', 'utrecht'], true)
                    && ($profileVersion === '' || $profileVersion === '1.0.0');
            }//end isValidTokenSet()

            public function resolveTokenSetVersion(string $tokenSetId): ?string
            {
                return $this->isValidTokenSet(tokenSetId: $tokenSetId) === true
                    ? '1.0.0'
                    : null;
            }//end resolveTokenSetVersion()
        };
    }//end createProfileCataloguePolicy()
}//end class
