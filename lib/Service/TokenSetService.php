<?php

/**
 * NL Design Token Set Service.
 *
 * @category Service
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/ConductionNL/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Service;

use OCA\NLDesign\Domain\Profile\ProfileCataloguePolicy;
use OCA\NLDesign\Infrastructure\Profile\PackagedProfileFiles;
use OCA\NLDesign\Infrastructure\Profile\ProfileCatalogueEnvelope;
use OCA\NLDesign\Infrastructure\Profile\ProfileManifestEntryNormalizer;

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
     */
    public function __construct(
        private PackagedProfileFiles $profileFiles,
        private ProfileCatalogueEnvelope $envelope,
        private ProfileManifestEntryNormalizer $normalizer
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

            $tokenSets[] = $metadata;
        }

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

                return strcmp((string) $left['id'], (string) $right['id']);
            }
        );

        return $tokenSets;
    }//end getAvailableTokenSets()

    /**
     * Check whether a profile is declared and resolves to a readable stylesheet
     * inside the app's token directory.
     *
     * @param string $tokenSetId Profile identifier.
     *
     * @return bool Whether the profile is usable.
     */
    public function isValidTokenSet(string $tokenSetId): bool
    {
        if ($this->normalizer->isValidId(id: $tokenSetId) === false) {
            return false;
        }

        $metadata = $this->getManifestIndex()[$tokenSetId] ?? null;
        return is_array($metadata) === true
            && $metadata['status'] === self::READY_STATUS
            && $this->profileFiles->hasSafeStylesheet(profileId: $tokenSetId) === true;
    }//end isValidTokenSet()

    /**
     * Get normalized metadata for a usable profile.
     *
     * @param string $tokenSetId Profile identifier.
     *
     * @return array<string, mixed>|null Profile metadata.
     */
    public function getTokenSetMetadata(string $tokenSetId): ?array
    {
        if ($this->isValidTokenSet(tokenSetId: $tokenSetId) === false) {
            return null;
        }

        return $this->getManifestIndex()[$tokenSetId] ?? null;
    }//end getTokenSetMetadata()

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
}//end class
