<?php

/**
 * NL Design Profile State Service.
 *
 * @category Service
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Service;

use JsonException;
use OCA\NLDesign\Domain\Profile\ProfileCataloguePolicy;
use OCA\NLDesign\Domain\Profile\ProfileHistoryNormalizer;
use OCA\NLDesign\Domain\Profile\ProfileRollbackTargetResolver;
use OCA\NLDesign\Domain\Profile\ProfileStateNormalizer;
use OCA\NLDesign\Infrastructure\Nextcloud\ProfileStateMutationGuard;
use OCP\AppFramework\Services\IAppConfig;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Persist profile activation state with revision and rollback context.
 */
final class ProfileStateService
{
    private const ACTIVE_PROFILE_STATE_KEY = 'active_profile_state';
    private const MAX_STATE_BYTES          = 4096;

    /**
     * Profile history persistence.
     *
     * @var ProfileHistoryStore
     */
    private ProfileHistoryStore $historyStore;

    /**
     * Exact rollback-target policy.
     *
     * @var ProfileRollbackTargetResolver
     */
    private ProfileRollbackTargetResolver $rollbackResolver;

    /**
     * Constructor.
     *
     * @param IAppConfig                         $config           App-scoped config service.
     * @param LoggerInterface                    $logger           Application logger.
     * @param ProfileStateMutationGuard          $mutationGuard    Nextcloud mutation boundary.
     * @param ProfileStateNormalizer             $normalizer       Profile-state validator.
     * @param ProfileCataloguePolicy             $profiles         Package profile policy.
     * @param ProfileHistoryStore|null           $historyStore     Profile history persistence.
     * @param ProfileRollbackTargetResolver|null $rollbackResolver Rollback-target policy.
     */
    public function __construct(
        private IAppConfig $config,
        private LoggerInterface $logger,
        private ProfileStateMutationGuard $mutationGuard,
        private ProfileStateNormalizer $normalizer,
        private ProfileCataloguePolicy $profiles,
        ?ProfileHistoryStore $historyStore=null,
        ?ProfileRollbackTargetResolver $rollbackResolver=null
    ) {
        $this->historyStore     = $historyStore ?? new ProfileHistoryStore(
            config: $config,
            logger: $logger,
            normalizer: new ProfileHistoryNormalizer(stateNormalizer: $normalizer)
        );
        $this->rollbackResolver = $rollbackResolver ?? new ProfileRollbackTargetResolver(
            normalizer: $normalizer,
            profiles: $profiles
        );
    }//end __construct()

    /**
     * Get the active profile state, with deterministic defaults.
     *
     * @return array{
     *     active_profile_id: string|null,
     *     active_profile_version: string|null,
     *     active_profile_revision: string,
     *     previous_profile_snapshot: array<string, mixed>|null,
     *     updated_at: string|null,
     *     updated_by: string|null
     * }
     */
    public function getActiveProfileState(): array
    {
        $canonicalStateExists = $this->config->hasAppKey(
            key: self::ACTIVE_PROFILE_STATE_KEY
        );
        $state = $this->readState();
        if ($canonicalStateExists === true
            && (array_key_exists('active_profile_id', $state) === false
            || isset($state['active_profile_revision']) === false)
        ) {
            // Canonical state is one atomic record. Do not retain a rollback
            // fragment or attacker-chosen revision from a partial object.
            $state = [];
        }

        if ($canonicalStateExists === true) {
            // A present but malformed canonical record must not reactivate a
            // stale legacy mirror. Fail safely to native Nextcloud instead.
            $activeProfileId = $state['active_profile_id'] ?? null;
        } else {
            $legacyProfileId = $this->config->getAppValueString(
                key: 'token_set',
                default: ''
            );
            $activeProfileId = null;
            if ($legacyProfileId !== ''
                && $this->normalizer->isProfileId(profileId: $legacyProfileId) === true
            ) {
                $activeProfileId = $legacyProfileId;
            }
        }

        $activeProfileVersion = $state['active_profile_version'] ?? null;
        if (is_string($activeProfileId) === true
            && is_string($activeProfileVersion) === false
        ) {
            $activeProfileVersion = $this->profiles->resolveTokenSetVersion(
                tokenSetId: $activeProfileId
            );
        }

        if ($activeProfileId === null) {
            $activeProfileVersion = null;
        }

        $revision = $this->buildInitialRevision(
            tokenSetId: $activeProfileId,
            profileVersion: $activeProfileVersion
        );
        if ($canonicalStateExists === true
            && isset($state['active_profile_revision']) === true
            && is_string($state['active_profile_revision']) === true
        ) {
            $revision = $state['active_profile_revision'];
        }

        return [
            'active_profile_id'         => $activeProfileId,
            'active_profile_version'    => $activeProfileVersion,
            'active_profile_revision'   => $revision,
            'previous_profile_snapshot' => $state['previous_profile_snapshot'] ?? null,
            'updated_at'                => $state['updated_at'] ?? null,
            'updated_by'                => $state['updated_by'] ?? null,
        ];
    }//end getActiveProfileState()

    /**
     * Publish the next active profile and retain one-step rollback context.
     *
     * @param string $tokenSetId       New profile id.
     * @param string $expectedRevision Revision observed by the caller.
     * @param string $profileVersion   Exact profile version, or newest when omitted.
     * @param string $actor            Acting user identifier.
     *
     * @return array<string, mixed> Operation result.
     */
    public function publishProfile(
        string $tokenSetId,
        string $expectedRevision,
        string $profileVersion='',
        string $actor='admin'
    ): array {
        if ($profileVersion === '') {
            $profileVersion = $this->profiles->resolveTokenSetVersion(tokenSetId: $tokenSetId) ?? '';
        }

        if ($this->normalizer->isProfileId(profileId: $tokenSetId) === false
            || $this->normalizer->isProfileVersion(profileVersion: $profileVersion) === false
            || $this->profiles->isValidTokenSet(
                tokenSetId: $tokenSetId,
                profileVersion: $profileVersion
            ) === false
        ) {
            return ['status' => 'invalid_profile'];
        }

        return $this->transitionProfile(
            tokenSetId: $tokenSetId,
            profileVersion: $profileVersion,
            expectedRevision: $expectedRevision,
            actor: $actor
        );
    }//end publishProfile()

    /**
     * Return to native Nextcloud without an active NL Design profile.
     *
     * @param string $expectedRevision Revision observed by the caller.
     * @param string $actor            Acting user identifier.
     *
     * @return array<string, mixed> Operation result.
     */
    public function deactivateProfile(
        string $expectedRevision,
        string $actor='admin'
    ): array {
        return $this->transitionProfile(
            tokenSetId: null,
            profileVersion: null,
            expectedRevision: $expectedRevision,
            actor: $actor
        );
    }//end deactivateProfile()

    /**
     * Run one profile transition under the shared exclusive lock.
     *
     * @param string|null $tokenSetId       Target profile, or native Nextcloud.
     * @param string|null $profileVersion   Exact version, or null for native Nextcloud.
     * @param string      $expectedRevision Revision observed by the caller.
     * @param string      $actor            Acting user identifier.
     *
     * @return array<string, mixed> Operation result.
     */
    private function transitionProfile(
        ?string $tokenSetId,
        ?string $profileVersion,
        string $expectedRevision,
        string $actor
    ): array {
        if ($this->normalizer->isRevision(revision: $expectedRevision) === false) {
            return ['status' => 'invalid_revision'];
        }

        return $this->mutationGuard->run(
            operation: function () use ($tokenSetId, $profileVersion, $actor, $expectedRevision): array {
                $currentState = $this->getActiveProfileState();
                return $this->transitionProfileWhileLocked(
                    tokenSetId: $tokenSetId,
                    profileVersion: $profileVersion,
                    actor: $actor,
                    expectedRevision: $expectedRevision,
                    currentState: $currentState
                );
            }
        );
    }//end transitionProfile()

    /**
     * Publish after the exclusive state lock has been acquired.
     *
     * @param string|null          $tokenSetId       Target profile, or native Nextcloud.
     * @param string|null          $profileVersion   Exact version, or null for native Nextcloud.
     * @param string               $actor            Acting user identifier.
     * @param string               $expectedRevision Revision observed by the caller.
     * @param array<string, mixed> $currentState     State freshly read under the lock.
     *
     * @return array<string, mixed> Operation result.
     */
    private function transitionProfileWhileLocked(
        ?string $tokenSetId,
        ?string $profileVersion,
        string $actor,
        string $expectedRevision,
        array $currentState
    ): array {
        if ($currentState['active_profile_revision'] !== $expectedRevision) {
            return [
                'status'           => 'revision_mismatch',
                'current_revision' => $currentState['active_profile_revision'],
            ];
        }

        if ($currentState['active_profile_id'] === $tokenSetId
            && $currentState['active_profile_version'] === $profileVersion
        ) {
            return [
                'status'  => 'noop',
                'current' => $currentState,
            ];
        }

        $timestamp = gmdate('c');
        try {
            $nextState = [
                'active_profile_id'         => $tokenSetId,
                'active_profile_version'    => $profileVersion,
                'active_profile_revision'   => $this->buildRevision(
                    tokenSetId: $tokenSetId,
                    profileVersion: $profileVersion,
                    previous: $currentState['active_profile_revision'],
                    timestamp: $timestamp
                ),
                'previous_profile_snapshot' => [
                    'profile_id'      => $currentState['active_profile_id'],
                    'profile_version' => $currentState['active_profile_version'],
                    'revision'        => $currentState['active_profile_revision'],
                    'updated_at'      => $currentState['updated_at'],
                    'updated_by'      => $currentState['updated_by'],
                ],
                'updated_at'                => $timestamp,
                'updated_by'                => $this->normalizer->normalizeActor(actor: $actor),
            ];

            // Canonical state is load-bearing. Auxiliary compatibility and
            // history writes are best-effort only after this succeeds.
            $persisted = $this->config->setAppValueString(
                key: self::ACTIVE_PROFILE_STATE_KEY,
                value: json_encode(value: $nextState, flags: JSON_THROW_ON_ERROR)
            );
            if ($persisted === false) {
                // A real transition always changes both the target and its
                // random revision. Nextcloud therefore cannot legitimately
                // report an unchanged value here.
                throw new RuntimeException('Canonical profile state was not updated');
            }
        } catch (Throwable $exception) {
            $this->logger->error(
                'NL Design canonical profile state could not be persisted.',
                ['exception' => $exception]
            );

            return ['status' => 'persistence_failed'];
        }//end try

        $this->writeAuxiliaryState(
            previousState: $currentState,
            nextState: $nextState,
            actor: $nextState['updated_by'],
            timestamp: $timestamp
        );

        return [
            'status' => 'ok',
            'next'   => $nextState,
        ];
    }//end transitionProfileWhileLocked()

    /**
     * Roll back to the immediate previous profile snapshot.
     *
     * @param string $expectedRevision Revision observed by the caller.
     * @param string $actor            Acting user identifier.
     *
     * @return array<string, mixed> Rollback result.
     */
    public function rollbackProfile(
        string $expectedRevision,
        string $actor='admin:rollback'
    ): array {
        if ($this->normalizer->isRevision(revision: $expectedRevision) === false) {
            return ['status' => 'invalid_revision'];
        }

        return $this->mutationGuard->run(
            operation: function () use ($expectedRevision, $actor): array {
                return $this->rollbackProfileWhileLocked(
                    expectedRevision: $expectedRevision,
                    actor: $actor,
                    currentState: $this->getActiveProfileState()
                );
            }
        );
    }//end rollbackProfile()

    /**
     * Roll back after the mutation guard has acquired the lock and refreshed
     * Nextcloud's app-config cache.
     *
     * @param string               $expectedRevision Revision observed by the caller.
     * @param string               $actor            Acting user identifier.
     * @param array<string, mixed> $currentState     Fresh canonical state.
     *
     * @return array<string, mixed> Rollback result.
     */
    private function rollbackProfileWhileLocked(
        string $expectedRevision,
        string $actor,
        array $currentState
    ): array {
        if ($currentState['active_profile_revision'] !== $expectedRevision) {
            return [
                'status'           => 'revision_mismatch',
                'current_revision' => $currentState['active_profile_revision'],
            ];
        }

        $target = $this->rollbackResolver->resolve(
            snapshot: $currentState['previous_profile_snapshot']
        );
        if ($target === null) {
            return [
                'status'  => 'no_previous_snapshot',
                'current' => $currentState,
            ];
        }

        $previousProfileId = $target['profile_id'];
        $previousVersion   = $target['profile_version'];

        $result = $this->transitionProfileWhileLocked(
            tokenSetId: $previousProfileId,
            profileVersion: $previousVersion,
            actor: $actor,
            expectedRevision: $expectedRevision,
            currentState: $currentState
        );

        $result['rolled_back_from']       = $currentState['active_profile_id'];
        $result['target_profile_id']      = $previousProfileId;
        $result['target_profile_version'] = $previousVersion;

        return $result;
    }//end rollbackProfileWhileLocked()

    /**
     * Read bounded activation history.
     *
     * @return array<int, array<string, mixed>> History entries.
     */
    public function getHistory(): array
    {
        return $this->historyStore->getHistory();
    }//end getHistory()

    /**
     * Persist compatibility mirrors and bounded history without invalidating
     * an already successful canonical publish.
     *
     * @param array<string, mixed> $previousState Prior state.
     * @param array<string, mixed> $nextState     New state.
     * @param string               $actor         Actor identifier.
     * @param string               $timestamp     UTC timestamp.
     *
     * @return void
     */
    private function writeAuxiliaryState(
        array $previousState,
        array $nextState,
        string $actor,
        string $timestamp
    ): void {
        try {
            $this->config->setAppValueString(
                key: 'token_set',
                value: (string) $nextState['active_profile_id']
            );
            $this->config->setAppValueString(
                key: 'active_profile_revision',
                value: (string) $nextState['active_profile_revision']
            );
            $this->config->setAppValueString(
                key: 'active_profile_version',
                value: (string) $nextState['active_profile_version']
            );
        } catch (Throwable $exception) {
            $this->logger->warning(
                'NL Design profile published, but a compatibility mirror write failed.',
                ['exception' => $exception]
            );
        }//end try

        try {
            $this->historyStore->record(
                previousState: $previousState,
                nextState: $nextState,
                actor: $actor,
                timestamp: $timestamp
            );
        } catch (Throwable $exception) {
            $this->logger->warning(
                'NL Design profile published, but its history write failed.',
                ['exception' => $exception]
            );
        }//end try
    }//end writeAuxiliaryState()

    /**
     * Build the stable revision for state that predates canonical persistence.
     *
     * @param string|null $tokenSetId     Profile identifier, or native Nextcloud.
     * @param string|null $profileVersion Exact profile version.
     *
     * @return string Revision token.
     */
    private function buildInitialRevision(?string $tokenSetId, ?string $profileVersion): string
    {
        return substr(
            hash(
                algo: 'sha256',
                data: ($tokenSetId ?? 'native').'|'.($profileVersion ?? 'native').'|initial'
            ),
            0,
            20
        );
    }//end buildInitialRevision()

    /**
     * Build a transition revision.
     *
     * @param string|null $tokenSetId     Profile identifier, or native Nextcloud.
     * @param string|null $profileVersion Exact profile version.
     * @param string      $previous       Previous revision.
     * @param string      $timestamp      UTC timestamp.
     *
     * @return string Revision token.
     */
    private function buildRevision(
        ?string $tokenSetId,
        ?string $profileVersion,
        string $previous,
        string $timestamp
    ): string {
        $revisionInput = implode(
            separator: '|',
            array: [
                $tokenSetId ?? 'native',
                $profileVersion ?? 'native',
                $previous,
                $timestamp,
                bin2hex(random_bytes(16)),
            ]
        );

        return substr(
            hash(
                algo: 'sha256',
                data: $revisionInput
            ),
            0,
            20
        );
    }//end buildRevision()

    /**
     * Read and validate canonical profile state.
     *
     * @return array<string, mixed> Valid state fields.
     */
    private function readState(): array
    {
        $rawState = $this->config->getAppValueString(
            key: self::ACTIVE_PROFILE_STATE_KEY,
            default: '{}'
        );
        if (strlen($rawState) > self::MAX_STATE_BYTES) {
            $this->logger->warning('Ignoring oversized NL Design profile state.');
            return [];
        }

        $decoded = $this->decodeStatePayload(rawState: $rawState);

        if (is_array($decoded) === false) {
            return [];
        }

        return $this->normalizer->normalizeState(decoded: $decoded);
    }//end readState()

    /**
     * Decode canonical JSON without letting corrupt state break rendering.
     *
     * @param string $rawState Raw app-config value.
     *
     * @return mixed Decoded value, or an empty array after a decode failure.
     */
    private function decodeStatePayload(string $rawState): mixed
    {
        try {
            return json_decode(
                json: $rawState,
                associative: true,
                flags: JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            $this->logger->warning(
                'Ignoring malformed NL Design profile state.',
                ['exception' => $exception]
            );
            return [];
        }
    }//end decodeStatePayload()
}//end class
