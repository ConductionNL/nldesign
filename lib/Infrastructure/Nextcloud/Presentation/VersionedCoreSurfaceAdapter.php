<?php

/**
 * Versioned Nextcloud core CSS surface adapters.
 *
 * @category Infrastructure
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Infrastructure\Nextcloud\Presentation;

use OCA\NLDesign\Domain\Presentation\CoreSurfaceProjection;
use OCA\NLDesign\Domain\Presentation\NextcloudRuntime;
use OCA\NLDesign\Port\Presentation\CoreSurfaceAdapter;

/**
 * Map supported Nextcloud majors to verified CSS capability contracts.
 */
final class VersionedCoreSurfaceAdapter implements CoreSurfaceAdapter
{
    /**
     * Explicit major-to-contract mapping. Majors may share a contract when the
     * audited CSS surface and semantics are equal.
     *
     * No default is intentional: an unverified major must fail open.
     *
     * @var array<int, string>
     */
    private const MAJOR_CONTRACTS = [
        32 => 'nextcloud-core-v1',
        33 => 'nextcloud-core-v1',
        34 => 'nextcloud-core-v1',
    ];

    /**
     * Verified projection contracts. Add a contract only for a real semantic
     * delta, then map the affected major above.
     *
     * @var array<string, array{adapter_id: string, stylesheet: string}>
     */
    private const CONTRACTS = [
        'nextcloud-core-v1' => [
            'adapter_id' => 'nextcloud-core-v1',
            'stylesheet' => 'compatibility/nextcloud-core-v1',
        ],
    ];

    /**
     * Resolve the verified contract for an explicitly supported major.
     *
     * @param NextcloudRuntime $runtime Runtime identity.
     *
     * @return CoreSurfaceProjection|null Verified adapter or no support.
     */
    public function resolve(NextcloudRuntime $runtime): ?CoreSurfaceProjection
    {
        $contractId = self::MAJOR_CONTRACTS[$runtime->getMajor()] ?? null;
        if ($contractId === null) {
            return null;
        }

        $contract = self::CONTRACTS[$contractId];

        return new CoreSurfaceProjection(
            adapterId: $contract['adapter_id'],
            stylesheet: $contract['stylesheet']
        );
    }//end resolve()
}//end class
