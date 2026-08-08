<?php

/**
 * NL Design Token Set Service.
 *
 * @category Service
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Service;

use OCA\NLDesign\Domain\Profile\ProfileCataloguePolicy;
use OCA\NLDesign\Infrastructure\Profile\PackagedProfileFiles;
use OCA\NLDesign\Infrastructure\Profile\ProfileCatalogueEnvelope;
use OCA\NLDesign\Infrastructure\Profile\ProfileManifestEntryNormalizer;
use OCA\NLDesign\Port\Profile\InstalledProfileRepository;

/**
 * Discover ready profiles from the packaged profile manifest.
 */
final class TokenSetService implements ProfileCataloguePolicy
{
    private const READY_STATUS = 'ready';

    /**
     * Manifest cache.
     *
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $manifestIndex = null;

    /**
     * Constructor.
     *
     * @param PackagedProfileFiles           $profileFiles Immutable package boundary.
     * @param ProfileCatalogueEnvelope       $envelope     Versioned envelope decoder.
     * @param ProfileManifestEntryNormalizer $normalizer   Profile metadata boundary.
     * @param InstalledProfileRepository     $installed    Installed profile boundary.
     */
    public function __construct(
        private PackagedProfileFiles $profileFiles,
        private ProfileCatalogueEnvelope $envelope,
        private ProfileManifestEntryNormalizer $normalizer,
        private InstalledProfileRepository $installed
    ) {
    }//end __construct()

    /**
     * Get all selectable ready profiles with normalized metadata.
     *
     * @return array<int, array<string, mixed>> Available profiles.
     */
    public function getAvailableTokenSets(): array
    {
        $tokenSets = [];
        foreach ($this->getManifestIndex() as $id => $metadata) {
            if ($metadata['status'] !== self::READY_STATUS
                || $this->profileFiles->hasSafeStylesheet(profileId: $id) === false
            ) {
                continue;
            }

            $tokenSets[$this->buildVersionKey(
                profileId: $id,
                profileVersion: (string) $metadata['version']
            )] = $metadata;
        }

        foreach ($this->installed->listRecords() as $record) {
            $metadata = $record['metadata'] ?? null;
            if (is_array($metadata) === false
                || is_string($metadata['id'] ?? null) === false
                || is_string($metadata['version'] ?? null) === false
            ) {
                continue;
            }

            $key = $this->buildVersionKey(
                profileId: $metadata['id'],
                profileVersion: $metadata['version']
            );
            if (isset($tokenSets[$key]) === false) {
                $tokenSets[$key] = $metadata;
            }
        }

        $tokenSets = array_values($tokenSets);
        usort(
            $tokenSets,
            static function (array $left, array $right): int {
                $nameOrder = strcasecmp(
                    (string) $left['name'],
                    (string) $right['name']
                );

                if ($nameOrder !== 0) {
                    return $nameOrder;
                }

                $idOrder = strcmp((string) $left['id'], (string) $right['id']);
                if ($idOrder !== 0) {
                    return $idOrder;
                }

                return version_compare(
                    (string) $right['version'],
                    (string) $left['version']
                );
            }
        );

        return $tokenSets;
    }//end getAvailableTokenSets()

    /**
     * Check whether a profile is declared and resolves to a readable stylesheet
     * inside the app's token directory.
     *
     * @param string $tokenSetId     Profile identifier.
     * @param string $profileVersion Exact version, or newest when omitted.
     *
     * @return bool Whether the profile is usable.
     */
    public function isValidTokenSet(string $tokenSetId, string $profileVersion=''): bool
    {
        return $this->getTokenSetMetadata(
            tokenSetId: $tokenSetId,
            profileVersion: $profileVersion
        ) !== null;
    }//end isValidTokenSet()

    /**
     * Get normalized metadata for a usable profile.
     *
     * @param string $tokenSetId     Profile identifier.
     * @param string $profileVersion Exact version, or newest when omitted.
     *
     * @return array<string, mixed>|null Profile metadata.
     */
    public function getTokenSetMetadata(string $tokenSetId, string $profileVersion=''): ?array
    {
        if ($this->normalizer->isValidId(id: $tokenSetId) === false) {
            return null;
        }

        if ($profileVersion === '') {
            $profileVersion = $this->resolveTokenSetVersion(tokenSetId: $tokenSetId) ?? '';
        }

        if ($this->normalizer->isValidVersion(version: $profileVersion) === false) {
            return null;
        }

        $packaged = $this->getManifestIndex()[$tokenSetId] ?? null;
        if (is_array($packaged) === true
            && ($packaged['status'] ?? null) === self::READY_STATUS
            && ($packaged['version'] ?? null) === $profileVersion
            && $this->profileFiles->hasSafeStylesheet(profileId: $tokenSetId) === true
        ) {
            return $packaged;
        }

        $record   = $this->installed->find(
            profileId: $tokenSetId,
            profileVersion: $profileVersion
        );
        $metadata = $record['metadata'] ?? null;
        if (is_array($metadata) === true) {
            return $metadata;
        }

        return null;
    }//end getTokenSetMetadata()

    /**
     * Resolve the newest available immutable version of one profile.
     *
     * @param string $tokenSetId Profile identifier.
     *
     * @return string|null Resolved version.
     */
    public function resolveTokenSetVersion(string $tokenSetId): ?string
    {
        if ($this->normalizer->isValidId(id: $tokenSetId) === false) {
            return null;
        }

        $versions = [];
        foreach ($this->getAvailableTokenSets() as $metadata) {
            if (($metadata['id'] ?? null) === $tokenSetId
                && is_string($metadata['version'] ?? null) === true
            ) {
                $versions[] = $metadata['version'];
            }
        }

        if ($versions === []) {
            return null;
        }

        usort(
            $versions,
            static fn (string $left, string $right): int => version_compare($right, $left)
        );
        return $versions[0];
    }//end resolveTokenSetVersion()

    /**
     * Resolve how one exact profile stylesheet is attached at runtime.
     *
     * @param string $profileId      Stable profile identifier.
     * @param string $profileVersion Exact profile version.
     *
     * @return array<string, string>|null Runtime stylesheet descriptor.
     */
    public function getRuntimeStylesheet(string $profileId, string $profileVersion): ?array
    {
        $metadata = $this->getTokenSetMetadata(
            tokenSetId: $profileId,
            profileVersion: $profileVersion
        );
        if ($metadata === null || is_string($metadata['content_hash'] ?? null) === false) {
            return null;
        }

        if (($metadata['origin'] ?? null) === 'built-in') {
            return [
                'type'         => 'packaged',
                'path'         => 'tokens/'.$profileId,
                'content_hash' => $metadata['content_hash'],
            ];
        }

        if (($metadata['origin'] ?? null) === 'installed') {
            return [
                'type'         => 'installed',
                'content_hash' => $metadata['content_hash'],
            ];
        }

        return null;
    }//end getRuntimeStylesheet()

    /**
     * Read verified generated CSS for one installed version and digest.
     *
     * @param string $profileId      Stable profile identifier.
     * @param string $profileVersion Exact profile version.
     * @param string $contentHash    Expected immutable digest.
     *
     * @return string|null Generated CSS.
     */
    public function getInstalledStylesheet(
        string $profileId,
        string $profileVersion,
        string $contentHash
    ): ?string {
        $record = $this->installed->find(
            profileId: $profileId,
            profileVersion: $profileVersion
        );
        if (($record['metadata']['content_hash'] ?? null) !== $contentHash
            || is_string($record['css'] ?? null) === false
        ) {
            return null;
        }

        return $record['css'];
    }//end getInstalledStylesheet()

    /**
     * Load and index the manifest once per request.
     *
     * @return array<string, array<string, mixed>> Manifest indexed by profile id.
     */
    private function getManifestIndex(): array
    {
        if ($this->manifestIndex !== null) {
            return $this->manifestIndex;
        }

        $this->manifestIndex = [];
        $content = $this->profileFiles->readManifest();
        if ($content === null) {
            return $this->manifestIndex;
        }

        $catalogue = $this->envelope->decode(content: $content);
        if ($catalogue === null) {
            return $this->manifestIndex;
        }

        $seenIds = [];
        foreach ($catalogue['profiles'] as $entry) {
            if (is_array($entry) === false) {
                continue;
            }

            $entryId = $entry['id'] ?? null;
            if (is_string($entryId) === true
                && $this->normalizer->isValidId(id: $entryId) === true
            ) {
                if (isset($seenIds[$entryId]) === true) {
                    // Duplicate package identities are ambiguous even when one
                    // record is otherwise malformed. Fail the catalogue closed.
                    $this->manifestIndex = [];
                    return $this->manifestIndex;
                }

                $seenIds[$entryId] = true;
            }

            $metadata = $this->normalizer->normalize(entry: $entry);
            if ($metadata === null) {
                continue;
            }

            $this->manifestIndex[$metadata['id']] = $metadata;
        }//end foreach

        return $this->manifestIndex;
    }//end getManifestIndex()

    /**
     * Build a collision-free in-memory identity key.
     *
     * @param string $profileId      Stable profile identifier.
     * @param string $profileVersion Exact profile version.
     *
     * @return string Version key.
     */
    private function buildVersionKey(string $profileId, string $profileVersion): string
    {
        return $profileId.'@'.$profileVersion;
    }//end buildVersionKey()
}//end class
