<?php

/**
 * Installed profile repository port.
 *
 * @category Port
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Port\Profile;

/**
 * Persist immutable installed profile versions outside the app package.
 */
interface InstalledProfileRepository
{
    /**
     * List every valid installed profile record.
     *
     * @return array<int, array<string, mixed>> Installed records.
     */
    public function listRecords(): array;

    /**
     * Find one exact installed profile version.
     *
     * @param string $profileId      Stable profile identifier.
     * @param string $profileVersion Immutable profile version.
     *
     * @return array<string, mixed>|null Installed record.
     */
    public function find(string $profileId, string $profileVersion): ?array;

    /**
     * Store one validated, compiled version without overwriting an existing one.
     *
     * @param array<string, mixed> $profile Validated profile descriptor.
     * @param string               $css     Deterministically compiled CSS.
     * @param string               $actor   Installing actor.
     *
     * @return array<string, mixed> Installation result.
     */
    public function install(array $profile, string $css, string $actor): array;

    /**
     * Remove one exact installed version.
     *
     * @param string $profileId      Stable profile identifier.
     * @param string $profileVersion Immutable profile version.
     *
     * @return array<string, mixed> Removal result.
     */
    public function remove(string $profileId, string $profileVersion): array;
}//end interface
