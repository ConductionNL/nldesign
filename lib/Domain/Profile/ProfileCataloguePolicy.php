<?php

/**
 * NL Design profile-catalogue policy contract.
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
 * Resolve package policy without coupling state code to profile instances.
 */
interface ProfileCataloguePolicy
{
    /**
     * Check whether a profile is currently ready and package-safe.
     *
     * @param string $tokenSetId     Profile identifier.
     * @param string $profileVersion Exact version, or newest when omitted.
     *
     * @return bool Whether the profile may be published.
     */
    public function isValidTokenSet(string $tokenSetId, string $profileVersion=''): bool;

    /**
     * Resolve the newest available immutable version of one profile.
     *
     * @param string $tokenSetId Profile identifier.
     *
     * @return string|null Resolved version.
     */
    public function resolveTokenSetVersion(string $tokenSetId): ?string;
}//end interface
