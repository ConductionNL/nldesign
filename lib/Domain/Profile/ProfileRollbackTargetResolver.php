<?php

/**
 * NL Design rollback-target resolution.
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
 * Resolve and validate one exact rollback target against the live catalogue.
 */
final class ProfileRollbackTargetResolver
{
    /**
     * Constructor.
     *
     * @param ProfileStateNormalizer $normalizer Profile-state validator.
     * @param ProfileCataloguePolicy $profiles   Composite profile catalogue.
     */
    public function __construct(
        private ProfileStateNormalizer $normalizer,
        private ProfileCataloguePolicy $profiles
    ) {
    }//end __construct()

    /**
     * Resolve an exact version, including migration from an unversioned snapshot.
     *
     * @param mixed $snapshot Previous profile snapshot.
     *
     * @return array{profile_id: string|null, profile_version: string|null}|null Valid target.
     */
    public function resolve(mixed $snapshot): ?array
    {
        if (is_array($snapshot) === false || array_key_exists('profile_id', $snapshot) === false) {
            return null;
        }

        $profileId = $snapshot['profile_id'];
        $version   = $snapshot['profile_version'] ?? null;
        if ($profileId === null) {
            if ($version === null) {
                return ['profile_id' => null, 'profile_version' => null];
            }

            return null;
        }

        if (is_string($profileId) === false
            || $this->normalizer->isProfileId(profileId: $profileId) === false
        ) {
            return null;
        }

        if (is_string($version) === false) {
            $version = $this->profiles->resolveTokenSetVersion(tokenSetId: $profileId);
        }

        if (is_string($version) === false
            || $this->normalizer->isProfileVersion(profileVersion: $version) === false
            || $this->profiles->isValidTokenSet(
                tokenSetId: $profileId,
                profileVersion: $version
            ) === false
        ) {
            return null;
        }

        return [
            'profile_id'      => $profileId,
            'profile_version' => $version,
        ];
    }//end resolve()
}//end class
