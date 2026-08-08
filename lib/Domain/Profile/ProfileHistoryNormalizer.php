<?php

/**
 * NL Design profile-history normalization.
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
 * Validate and bound profile activation history read from app config.
 */
final class ProfileHistoryNormalizer
{
    private const MAX_HISTORY_ENTRIES = 10;
    private const MAX_METADATA_LENGTH = 256;

    /**
     * Constructor.
     *
     * @param ProfileStateNormalizer $stateNormalizer Shared profile-state primitives.
     */
    public function __construct(private ProfileStateNormalizer $stateNormalizer)
    {
    }//end __construct()

    /**
     * Retain a bounded list of complete, canonical history entries.
     *
     * @param array<int, mixed> $decoded Decoded history payload.
     *
     * @return array<int, array<string, string|null>> Normalized history.
     */
    public function normalize(array $decoded): array
    {
        $history = [];
        foreach (array_slice($decoded, 0, self::MAX_HISTORY_ENTRIES) as $entry) {
            $normalized = $this->normalizeEntry(value: $entry);
            if ($normalized !== null) {
                $history[] = $normalized;
            }
        }

        return $history;
    }//end normalize()

    /**
     * Retain only a complete, bounded history entry.
     *
     * @param mixed $value Decoded history entry.
     *
     * @return array<string, string|null>|null Normalized entry.
     */
    private function normalizeEntry(mixed $value): ?array
    {
        if (is_array($value) === false) {
            return null;
        }

        $actor     = $value['actor'] ?? null;
        $timestamp = $value['timestamp'] ?? null;
        if ($this->isBoundedString(value: $actor) === false
            || $this->isBoundedString(value: $timestamp) === false
        ) {
            return null;
        }

        $entry = [
            'actor'                 => $actor,
            'timestamp'             => $timestamp,
            'from_profile_id'       => null,
            'from_profile_version'  => null,
            'from_profile_revision' => null,
            'to_profile_id'         => null,
            'to_profile_version'    => null,
            'to_profile_revision'   => null,
        ];

        if ($this->normalizeProfileIds(value: $value, entry: $entry) === false
            || $this->normalizeVersions(value: $value, entry: $entry) === false
        ) {
            return null;
        }

        $this->normalizeRevisions(value: $value, entry: $entry);
        if (array_key_exists('to_profile_id', $value) === false
            || $entry['to_profile_revision'] === null
        ) {
            return null;
        }

        return $entry;
    }//end normalizeEntry()

    /**
     * Normalize nullable profile identifiers in place.
     *
     * @param array<string, mixed>       $value Candidate source.
     * @param array<string, string|null> $entry Canonical target.
     *
     * @return bool Whether both identifiers are valid.
     */
    private function normalizeProfileIds(array $value, array &$entry): bool
    {
        foreach (['from_profile_id', 'to_profile_id'] as $field) {
            $profileId = $value[$field] ?? null;
            if (is_string($profileId) === true
                && $this->stateNormalizer->isProfileId(profileId: $profileId) === true
            ) {
                $entry[$field] = $profileId;
            } else if ($profileId !== null) {
                return false;
            }
        }

        return true;
    }//end normalizeProfileIds()

    /**
     * Normalize nullable immutable versions in place.
     *
     * @param array<string, mixed>       $value Candidate source.
     * @param array<string, string|null> $entry Canonical target.
     *
     * @return bool Whether both versions are valid.
     */
    private function normalizeVersions(array $value, array &$entry): bool
    {
        foreach (['from_profile_version', 'to_profile_version'] as $field) {
            $version = $value[$field] ?? null;
            if (is_string($version) === true
                && $this->stateNormalizer->isProfileVersion(profileVersion: $version) === true
            ) {
                $entry[$field] = $version;
            } else if ($version !== null) {
                return false;
            }
        }

        return true;
    }//end normalizeVersions()

    /**
     * Normalize optional revision tokens in place.
     *
     * @param array<string, mixed>       $value Candidate source.
     * @param array<string, string|null> $entry Canonical target.
     *
     * @return void
     */
    private function normalizeRevisions(array $value, array &$entry): void
    {
        foreach (['from_profile_revision', 'to_profile_revision'] as $field) {
            $revision = $value[$field] ?? null;
            if (is_string($revision) === true
                && $this->stateNormalizer->isRevision(revision: $revision) === true
            ) {
                $entry[$field] = $revision;
            }
        }
    }//end normalizeRevisions()

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
