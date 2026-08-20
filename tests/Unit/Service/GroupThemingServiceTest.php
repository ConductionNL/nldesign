<?php

/**
 * Unit tests for GroupThemingService.
 *
 * SPDX-License-Identifier: EUPL-1.2
 * SPDX-FileCopyrightText: 2026 Conduction B.V.
 *
 * @spec openspec/specs/per-group-theming/spec.md
 */

declare(strict_types=1);

namespace OCA\NLDesign\Tests\Unit\Service;

use OCA\NLDesign\Service\Exception\GroupThemingValidationException;
use OCA\NLDesign\Service\GroupThemingService;
use OCA\NLDesign\Service\ThemePreviewService;
use OCA\NLDesign\Service\TokenSetService;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;

/**
 * Covers the "Deterministic Resolution Precedence", "O(1)-ish Cached
 * Resolution", and "Group-to-Token-Set Mapping Storage" requirements of
 * openspec/specs/per-group-theming/spec.md.
 */
class GroupThemingServiceTest extends TestCase {

	/**
	 * Build a service instance with an in-memory cache store (real
	 * get/set/invalidation semantics, unlike a pure spy) so
	 * generation-bump invalidation can be exercised end-to-end.
	 *
	 * @param IConfig $config The config mock/stub.
	 * @param IGroupManager $groupManager The group manager mock/stub.
	 * @param IUserSession $userSession The user session mock/stub.
	 * @param TokenSetService $tokenSetService The token set service mock/stub.
	 *
	 * @return array{0: GroupThemingService, 1: ICache} The service and its backing cache double.
	 */
	private function buildService(
		IConfig $config,
		IGroupManager $groupManager,
		IUserSession $userSession,
		TokenSetService $tokenSetService,
	): array {
		$store = [];

		$cache = $this->createMock(ICache::class);
		$cache->method('get')->willReturnCallback(
			static function (string $key) use (&$store) {
				return ($store[$key] ?? null);
			}
		);
		$cache->method('set')->willReturnCallback(
			static function (string $key, $value, int $ttl = 0) use (&$store): bool {
				$store[$key] = $value;
				return true;
			}
		);

		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createDistributed')->willReturn($cache);

		$service = new GroupThemingService(
			$config,
			$groupManager,
			$userSession,
			$tokenSetService,
			$this->inactivePreviewService(),
			$cacheFactory
		);

		return [$service, $cache];
	}//end buildService()

	/**
	 * Build an IConfig stub backed by an in-memory array so
	 * getAppValue/setAppValue round-trip exactly like the real appconfig,
	 * which is required for the generation-bump invalidation test.
	 *
	 * @param array<string, string> $initial Seed values (appconfig key => value).
	 *
	 * @return IConfig The stub.
	 */
	private function fakeConfig(array $initial = []): IConfig {
		$store = $initial;
		$config = $this->createMock(IConfig::class);
		$config->method('getAppValue')->willReturnCallback(
			static function (string $app, string $key, string $default = '') use (&$store) {
				return ($store[$key] ?? $default);
			}
		);
		$config->method('setAppValue')->willReturnCallback(
			static function (string $app, string $key, string $value = '') use (&$store): void {
				$store[$key] = $value;
			}
		);

		return $config;
	}//end fakeConfig()

	/**
	 * Build a user double with the given UID.
	 *
	 * @param string $uid The user id.
	 *
	 * @return IUser The user double.
	 */
	private function fakeUser(string $uid): IUser {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);

		return $user;
	}//end fakeUser()

	/**
	 * A theme-preview service stub with no active preview, so these tests
	 * exercise group-mapping resolution only. Preview precedence has its own
	 * dedicated coverage in the theme-preview suite.
	 *
	 * @return ThemePreviewService The stub.
	 */
	private function inactivePreviewService(): ThemePreviewService {
		$preview = $this->createMock(ThemePreviewService::class);
		$preview->method('resolveEffectiveTokenSet')->willReturnCallback(
			static fn (IUserSession $session, string $activeTokenSet): array => [
				'tokenSet' => $activeTokenSet,
				'previewActive' => false,
				'expiresAt' => null,
			]
		);

		return $preview;
	}//end inactivePreviewService()

	/**
	 * A token set service stub where every id in $available validates and
	 * everything else is a "dead"/unknown set.
	 *
	 * @param string[] $available The valid token set ids.
	 *
	 * @return TokenSetService The stub.
	 */
	private function fakeTokenSetService(array $available): TokenSetService {
		$service = $this->createMock(TokenSetService::class);
		$service->method('isValidTokenSet')->willReturnCallback(
			static fn (string $tokenSetId) => in_array($tokenSetId, $available, true)
		);

		return $service;
	}//end fakeTokenSetService()

	// -------------------------------------------------------------------
	// Storage: getMapping() / setMapping()
	// -------------------------------------------------------------------

	/**
	 * Absent config reads as an empty mapping (today's global-theming behavior).
	 */
	public function testGetMappingDefaultsToEmpty(): void {
		[$service] = $this->buildService(
			$this->fakeConfig(),
			$this->createMock(IGroupManager::class),
			$this->createMock(IUserSession::class),
			$this->fakeTokenSetService([])
		);

		$this->assertSame([], $service->getMapping());
	}//end testGetMappingDefaultsToEmpty()

	/**
	 * Malformed stored JSON reads as an empty mapping, never an error.
	 */
	public function testGetMappingMalformedJsonIsEmpty(): void {
		[$service] = $this->buildService(
			$this->fakeConfig(['group_token_sets' => 'not-json{']),
			$this->createMock(IGroupManager::class),
			$this->createMock(IUserSession::class),
			$this->fakeTokenSetService([])
		);

		$this->assertSame([], $service->getMapping());
	}//end testGetMappingMalformedJsonIsEmpty()

	/**
	 * A valid mapping is saved verbatim, in order, and the generation counter
	 * is incremented.
	 */
	public function testSetMappingPersistsInOrderAndBumpsGeneration(): void {
		$config = $this->fakeConfig();
		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('groupExists')->willReturn(true);

		[$service] = $this->buildService(
			$config,
			$groupManager,
			$this->createMock(IUserSession::class),
			$this->fakeTokenSetService(['amsterdam', 'utrecht'])
		);

		$result = $service->setMapping(
			[
				['group' => 'gemeente-a', 'tokenSet' => 'amsterdam'],
				['group' => 'gemeente-b', 'tokenSet' => 'utrecht'],
			]
		);

		$this->assertSame(
			[
				['group' => 'gemeente-a', 'tokenSet' => 'amsterdam'],
				['group' => 'gemeente-b', 'tokenSet' => 'utrecht'],
			],
			$result
		);
		$this->assertSame($result, $service->getMapping());
		$this->assertSame(1, (int)$config->getAppValue('nldesign', 'group_token_sets_generation', '0'));
	}//end testSetMappingPersistsInOrderAndBumpsGeneration()

	/**
	 * An entry naming a nonexistent group is rejected; nothing is persisted
	 * and the generation counter is untouched.
	 */
	public function testSetMappingRejectsUnknownGroupWithoutPartialWrite(): void {
		$config = $this->fakeConfig(['group_token_sets' => '[]', 'group_token_sets_generation' => '3']);
		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('groupExists')->willReturn(false);

		[$service] = $this->buildService(
			$config,
			$groupManager,
			$this->createMock(IUserSession::class),
			$this->fakeTokenSetService(['amsterdam'])
		);

		try {
			$service->setMapping([['group' => 'nonexistent', 'tokenSet' => 'amsterdam']]);
			$this->fail('Expected GroupThemingValidationException.');
		} catch (GroupThemingValidationException $e) {
			$this->assertSame(['group' => 'nonexistent', 'tokenSet' => 'amsterdam'], $e->getEntry());
			$this->assertStringContainsString('nonexistent', $e->getReason());
		}

		$this->assertSame([], $service->getMapping());
		$this->assertSame(3, (int)$config->getAppValue('nldesign', 'group_token_sets_generation', '0'));
	}//end testSetMappingRejectsUnknownGroupWithoutPartialWrite()

	/**
	 * An entry naming an unavailable token set is rejected.
	 */
	public function testSetMappingRejectsUnknownTokenSet(): void {
		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('groupExists')->willReturn(true);

		[$service] = $this->buildService(
			$this->fakeConfig(),
			$groupManager,
			$this->createMock(IUserSession::class),
			$this->fakeTokenSetService(['amsterdam'])
		);

		$this->expectException(GroupThemingValidationException::class);
		$service->setMapping([['group' => 'gemeente-a', 'tokenSet' => 'does-not-exist']]);
	}//end testSetMappingRejectsUnknownTokenSet()

	/**
	 * Two entries for the same group are rejected — priority handles
	 * multi-group users, not duplicate rows.
	 */
	public function testSetMappingRejectsDuplicateGroupEntries(): void {
		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('groupExists')->willReturn(true);

		[$service] = $this->buildService(
			$this->fakeConfig(),
			$groupManager,
			$this->createMock(IUserSession::class),
			$this->fakeTokenSetService(['amsterdam', 'utrecht'])
		);

		$this->expectException(GroupThemingValidationException::class);
		$service->setMapping(
			[
				['group' => 'gemeente-a', 'tokenSet' => 'amsterdam'],
				['group' => 'gemeente-a', 'tokenSet' => 'utrecht'],
			]
		);
	}//end testSetMappingRejectsDuplicateGroupEntries()

	// -------------------------------------------------------------------
	// Resolution precedence
	// -------------------------------------------------------------------

	/**
	 * A user in a single mapped group gets that group's token set.
	 */
	public function testResolveReturnsMappedSetForSingleGroupMatch(): void {
		$config = $this->fakeConfig(
			[
				'group_token_sets' => json_encode([['group' => 'gemeente-a', 'tokenSet' => 'amsterdam']]),
				'token_set' => 'nextcloud',
			]
		);

		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('getUserGroupIds')->willReturn(['gemeente-a']);

		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($this->fakeUser('anna'));

		[$service] = $this->buildService($config, $groupManager, $userSession, $this->fakeTokenSetService(['amsterdam']));

		$this->assertSame('amsterdam', $service->resolveTokenSetForRequest());
	}//end testResolveReturnsMappedSetForSingleGroupMatch()

	/**
	 * A user in multiple mapped groups gets the earliest (highest-priority)
	 * matching entry.
	 */
	public function testResolveReturnsEarliestEntryForMultiGroupUser(): void {
		$config = $this->fakeConfig(
			[
				'group_token_sets' => json_encode(
					[
						['group' => 'gemeente-a', 'tokenSet' => 'amsterdam'],
						['group' => 'gemeente-b', 'tokenSet' => 'utrecht'],
					]
				),
				'token_set' => 'nextcloud',
			]
		);

		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('getUserGroupIds')->willReturn(['gemeente-a', 'gemeente-b']);

		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($this->fakeUser('bob'));

		[$service] = $this->buildService($config, $groupManager, $userSession, $this->fakeTokenSetService(['amsterdam', 'utrecht']));

		$this->assertSame('amsterdam', $service->resolveTokenSetForRequest());
	}//end testResolveReturnsEarliestEntryForMultiGroupUser()

	/**
	 * An entry mapping to a deleted token set is skipped and resolution
	 * continues to the next entry.
	 */
	public function testResolveSkipsDeadSetEntryAndContinues(): void {
		$config = $this->fakeConfig(
			[
				'group_token_sets' => json_encode(
					[
						['group' => 'gemeente-a', 'tokenSet' => 'custom-gone'],
						['group' => 'gemeente-a-fallbackgroup', 'tokenSet' => 'utrecht'],
					]
				),
				'token_set' => 'nextcloud',
			]
		);

		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('getUserGroupIds')->willReturn(['gemeente-a', 'gemeente-a-fallbackgroup']);

		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($this->fakeUser('dana'));

		// "custom-gone" is not in the available set — dead entry.
		[$service] = $this->buildService($config, $groupManager, $userSession, $this->fakeTokenSetService(['utrecht']));

		$this->assertSame('utrecht', $service->resolveTokenSetForRequest());
	}//end testResolveSkipsDeadSetEntryAndContinues()

	/**
	 * A user matching ONLY a dead-set entry resolves to the instance default.
	 */
	public function testResolveDeadSetOnlyMatchFallsBackToDefault(): void {
		$config = $this->fakeConfig(
			[
				'group_token_sets' => json_encode([['group' => 'gemeente-a', 'tokenSet' => 'custom-gone']]),
				'token_set' => 'rijkshuisstijl',
			]
		);

		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('getUserGroupIds')->willReturn(['gemeente-a']);

		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($this->fakeUser('erik'));

		[$service] = $this->buildService($config, $groupManager, $userSession, $this->fakeTokenSetService([]));

		$this->assertSame('rijkshuisstijl', $service->resolveTokenSetForRequest());
	}//end testResolveDeadSetOnlyMatchFallsBackToDefault()

	/**
	 * A user in no mapped group gets the instance default.
	 */
	public function testResolveReturnsDefaultForUnmappedUser(): void {
		$config = $this->fakeConfig(
			[
				'group_token_sets' => json_encode([['group' => 'gemeente-a', 'tokenSet' => 'amsterdam']]),
				'token_set' => 'nextcloud',
			]
		);

		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('getUserGroupIds')->willReturn(['some-other-group']);

		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($this->fakeUser('carol'));

		[$service] = $this->buildService($config, $groupManager, $userSession, $this->fakeTokenSetService(['amsterdam']));

		$this->assertSame('nextcloud', $service->resolveTokenSetForRequest());
	}//end testResolveReturnsDefaultForUnmappedUser()

	/**
	 * No session (login/public/error pages) resolves to the instance
	 * default WITHOUT any group-manager call.
	 */
	public function testResolveReturnsDefaultForNoSessionWithoutGroupLookup(): void {
		$config = $this->fakeConfig(
			[
				'group_token_sets' => json_encode([['group' => 'gemeente-a', 'tokenSet' => 'amsterdam']]),
				'token_set' => 'nextcloud',
			]
		);

		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->expects($this->never())->method('getUserGroupIds');

		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn(null);

		[$service] = $this->buildService($config, $groupManager, $userSession, $this->fakeTokenSetService(['amsterdam']));

		$this->assertSame('nextcloud', $service->resolveTokenSetForRequest());
	}//end testResolveReturnsDefaultForNoSessionWithoutGroupLookup()

	/**
	 * An empty mapping short-circuits before any cache or group access —
	 * preserves today's zero-lookup behavior exactly.
	 */
	public function testResolveReturnsDefaultForEmptyMappingWithoutGroupOrCacheAccess(): void {
		$config = $this->fakeConfig(['token_set' => 'nextcloud']);

		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->expects($this->never())->method('getUserGroupIds');

		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($this->fakeUser('frank'));

		$cache = $this->createMock(ICache::class);
		$cache->expects($this->never())->method('get');
		$cache->expects($this->never())->method('set');
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createDistributed')->willReturn($cache);

		$service = new GroupThemingService(
			$config,
			$groupManager,
			$userSession,
			$this->fakeTokenSetService([]),
			$this->inactivePreviewService(),
			$cacheFactory
		);

		$this->assertSame('nextcloud', $service->resolveTokenSetForRequest());
	}//end testResolveReturnsDefaultForEmptyMappingWithoutGroupOrCacheAccess()

	/**
	 * A group-backend exception fails open to the instance default; no
	 * exception escapes.
	 */
	public function testResolveFailsOpenOnGroupBackendException(): void {
		$config = $this->fakeConfig(
			[
				'group_token_sets' => json_encode([['group' => 'gemeente-a', 'tokenSet' => 'amsterdam']]),
				'token_set' => 'nextcloud',
			]
		);

		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('getUserGroupIds')->willThrowException(new \RuntimeException('backend down'));

		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($this->fakeUser('greta'));

		[$service] = $this->buildService($config, $groupManager, $userSession, $this->fakeTokenSetService(['amsterdam']));

		$this->assertSame('nextcloud', $service->resolveTokenSetForRequest());
	}//end testResolveFailsOpenOnGroupBackendException()

	// -------------------------------------------------------------------
	// Caching
	// -------------------------------------------------------------------

	/**
	 * A second resolve under the same mapping generation hits the cache —
	 * IGroupManager is queried exactly once across both calls.
	 */
	public function testSecondResolveHitsCacheWithoutGroupLookup(): void {
		$config = $this->fakeConfig(
			[
				'group_token_sets' => json_encode([['group' => 'gemeente-a', 'tokenSet' => 'amsterdam']]),
				'token_set' => 'nextcloud',
			]
		);

		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->expects($this->once())->method('getUserGroupIds')->willReturn(['gemeente-a']);

		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($this->fakeUser('anna'));

		[$service] = $this->buildService($config, $groupManager, $userSession, $this->fakeTokenSetService(['amsterdam']));

		$this->assertSame('amsterdam', $service->resolveTokenSetForRequest());
		$this->assertSame('amsterdam', $service->resolveTokenSetForRequest());
	}//end testSecondResolveHitsCacheWithoutGroupLookup()

	/**
	 * Saving a changed mapping bumps the generation, invalidating every
	 * cached resolution: the very next resolve re-queries group membership.
	 */
	public function testGenerationBumpInvalidatesCache(): void {
		$config = $this->fakeConfig(['token_set' => 'nextcloud']);

		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('groupExists')->willReturn(true);
		$groupManager->expects($this->exactly(2))->method('getUserGroupIds')->willReturn(['gemeente-a']);

		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($this->fakeUser('anna'));

		$tokenSetService = $this->fakeTokenSetService(['amsterdam', 'utrecht']);

		[$service] = $this->buildService($config, $groupManager, $userSession, $tokenSetService);

		$service->setMapping([['group' => 'gemeente-a', 'tokenSet' => 'amsterdam']]);
		$this->assertSame('amsterdam', $service->resolveTokenSetForRequest());
		// Cached — a same-generation resolve would not re-query, but we
		// change the mapping (bumping the generation) before resolving again.
		$service->setMapping([['group' => 'gemeente-a', 'tokenSet' => 'utrecht']]);
		$this->assertSame('utrecht', $service->resolveTokenSetForRequest());
	}//end testGenerationBumpInvalidatesCache()

	/**
	 * The cache write carries the documented TTL backstop (<= 1 hour).
	 */
	public function testCacheWriteCarriesTtlBound(): void {
		$config = $this->fakeConfig(
			[
				'group_token_sets' => json_encode([['group' => 'gemeente-a', 'tokenSet' => 'amsterdam']]),
				'token_set' => 'nextcloud',
			]
		);

		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('getUserGroupIds')->willReturn(['gemeente-a']);

		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($this->fakeUser('anna'));

		$cache = $this->createMock(ICache::class);
		$cache->method('get')->willReturn(null);
		$capturedTtl = null;
		$cache->expects($this->once())->method('set')->willReturnCallback(
			function ($key, $value, $ttl = 0) use (&$capturedTtl) {
				$capturedTtl = $ttl;
				return true;
			}
		);
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createDistributed')->willReturn($cache);

		$service = new GroupThemingService(
			$config,
			$groupManager,
			$userSession,
			$this->fakeTokenSetService(['amsterdam']),
			$this->inactivePreviewService(),
			$cacheFactory
		);

		$service->resolveTokenSetForRequest();

		$this->assertSame(3600, $capturedTtl);
		$this->assertLessThanOrEqual(3600, $capturedTtl);
	}//end testCacheWriteCarriesTtlBound()

	// -------------------------------------------------------------------
	// Available groups
	// -------------------------------------------------------------------

	/**
	 * getAvailableGroups() maps IGroup entries to {id, displayName}, sorted
	 * by display name.
	 */
	public function testGetAvailableGroupsListsAndSorts(): void {
		$groupB = $this->createMock(\OCP\IGroup::class);
		$groupB->method('getGID')->willReturn('gemeente-b');
		$groupB->method('getDisplayName')->willReturn('Zeeburgstad');

		$groupA = $this->createMock(\OCP\IGroup::class);
		$groupA->method('getGID')->willReturn('gemeente-a');
		$groupA->method('getDisplayName')->willReturn('Amsteldam');

		$groupManager = $this->createMock(IGroupManager::class);
		$groupManager->method('search')->willReturn([$groupB, $groupA]);

		[$service] = $this->buildService(
			$this->fakeConfig(),
			$groupManager,
			$this->createMock(IUserSession::class),
			$this->fakeTokenSetService([])
		);

		$this->assertSame(
			[
				['id' => 'gemeente-a', 'displayName' => 'Amsteldam'],
				['id' => 'gemeente-b', 'displayName' => 'Zeeburgstad'],
			],
			$service->getAvailableGroups()
		);
	}//end testGetAvailableGroupsListsAndSorts()
}//end class
