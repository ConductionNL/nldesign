<?php

/**
 * NL Design profile-state normalization.
 *
 * @category Domain
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Domain\Profile;

/**
 * Validate profile state at the persistence and HTTP trust boundaries.
 */
final class ProfileStateNormalizer
{
    private const MAX_PROFILE_ID_LENGTH   = 64;
    private const MAX_METADATA_LENGTH     = 256;
    private const PROFILE_ID_PATTERN      = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
    private const VERSION_CORE            = '(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)';
    private const VERSION_IDENTIFIER      = '(?:(?!0\d+(?:\.|$))[0-9A-Za-z-]+)';
    private const VERSION_PRERELEASE      = '(?:-'.self::VERSION_IDENTIFIER.'(?:\.'.self::VERSION_IDENTIFIER.')*)?';
    private const PROFILE_VERSION_PATTERN = '/^'.self::VERSION_CORE.self::VERSION_PRERELEASE.'$/';
    private const REVISION_PATTERN        = '/^[a-f0-9]{20}$/';
    private const MAX_PROFILE_VERSION_LENGTH = 64;

    /**
     * Retain only canonical profile-state fields and types.
     *
     * @param array<string, mixed> $decoded Decoded state payload.
     *
     * @return array<string, mixed> Normalized fields.
     */
    public function normalizeState(array $decoded): array
    {
        $state = [];
        if (array_key_exists('active_profile_id', $decoded) === true) {
            $profileId = $decoded['active_profile_id'];
            if ($profileId === null) {
                $state['active_profile_id'] = null;
            } else if (is_string($profileId) === true
                && $this->isProfileId(profileId: $profileId) === true
            ) {
                $state['active_profile_id'] = $profileId;
            }
        }

        $profileVersion = $decoded['active_profile_version'] ?? null;
        if ($profileVersion === null) {
            $state['active_profile_version'] = null;
        } else if (is_string($profileVersion) === true
            && $this->isProfileVersion(profileVersion: $profileVersion) === true
        ) {
            $state['active_profile_version'] = $profileVersion;
        }

        $revision = $decoded['active_profile_revision'] ?? null;
        if (is_string($revision) === true && $this->isRevision(revision: $revision) === true) {
            $state['active_profile_revision'] = $revision;
        }

        foreach (['updated_at', 'updated_by'] as $stringField) {
            $value = $decoded[$stringField] ?? null;
            if ($this->isBoundedString(value: $value) === true) {
                $state[$stringField] = $value;
            }
        }

        $previous = $this->normalizePreviousSnapshot(
            value: $decoded['previous_profile_snapshot'] ?? null
        );
        if ($previous !== null) {
            $state['previous_profile_snapshot'] = $previous;
        }

        return $state;
    }//end normalizeState()

    /**
     * Normalize an audit actor without storing control characters or huge ids.
     *
     * @param string $actor Raw actor identifier.
     *
     * @return string Bounded actor identifier.
     */
    public function normalizeActor(string $actor): string
    {
        $normalized = preg_replace(pattern: '/[\x00-\x1f\x7f]/u', replacement: '', subject: $actor);
        if (is_string($normalized) === false || trim($normalized) === '') {
            return 'system';
        }

        if (strlen($normalized) > self::MAX_METADATA_LENGTH) {
            return 'system:actor-too-long';
        }

        return $normalized;
    }//end normalizeActor()

    /**
     * Validate an internal profile identifier.
     *
     * @param string $profileId Profile identifier.
     *
     * @return bool Whether the identifier is valid.
     */
    public function isProfileId(string $profileId): bool
    {
        return strlen($profileId) <= self::MAX_PROFILE_ID_LENGTH
            && preg_match(self::PROFILE_ID_PATTERN, $profileId) === 1;
    }//end isProfileId()

    /**
     * Validate an immutable profile version.
     *
     * @param string $profileVersion Profile version.
     *
     * @return bool Whether the version is valid.
     */
    public function isProfileVersion(string $profileVersion): bool
    {
        return strlen($profileVersion) <= self::MAX_PROFILE_VERSION_LENGTH
            && preg_match(self::PROFILE_VERSION_PATTERN, $profileVersion) === 1;
    }//end isProfileVersion()

    /**
     * Validate an optimistic-concurrency revision token.
     *
     * @param string $revision Revision token.
     *
     * @return bool Whether the revision is valid.
     */
    public function isRevision(string $revision): bool
    {
        return preg_match(self::REVISION_PATTERN, $revision) === 1;
    }//end isRevision()

    /**
     * Validate and reduce a rollback snapshot.
     *
     * @param mixed $value Decoded snapshot value.
     *
     * @return array<string, mixed>|null Valid snapshot.
     */
    private function normalizePreviousSnapshot(mixed $value): ?array
    {
        if (is_array($value) === false) {
            return null;
        }

        if (array_key_exists('profile_id', $value) === false) {
            return null;
        }

        $profileId = $value['profile_id'];
        if ($profileId !== null
            && (is_string($profileId) === false
            || $this->isProfileId(profileId: $profileId) === false)
        ) {
            return null;
        }

        $snapshot = [
            'profile_id'      => $profileId,
            'profile_version' => null,
            'revision'        => null,
            'updated_at'      => null,
            'updated_by'      => null,
        ];

        $profileVersion = $value['profile_version'] ?? null;
        if ($profileVersion !== null) {
            if ($profileId === null
                || is_string($profileVersion) === false
                || $this->isProfileVersion(profileVersion: $profileVersion) === false
            ) {
                return null;
            }

            $snapshot['profile_version'] = $profileVersion;
        }

        $revision = $value['revision'] ?? null;
        if (is_string($revision) === true && $this->isRevision(revision: $revision) === true) {
            $snapshot['revision'] = $revision;
        }

        foreach (['updated_at', 'updated_by'] as $stringField) {
            $fieldValue = $value[$stringField] ?? null;
            if ($this->isBoundedString(value: $fieldValue) === true) {
                $snapshot[$stringField] = $fieldValue;
            }
        }

        return $snapshot;
    }//end normalizePreviousSnapshot()

    /**
     * Validate bounded metadata strings read from app config.
     *
     * @param mixed $value Candidate value.
     *
     * @return bool Whether the value is safe to expose and persist again.
     */
    private function isBoundedString(mixed $value): bool
    {
        return is_string($value) === true
            && strlen($value) <= self::MAX_METADATA_LENGTH
            && preg_match('/[\x00-\x1f\x7f]/u', $value) === 0;
    }//end isBoundedString()
}//end class
