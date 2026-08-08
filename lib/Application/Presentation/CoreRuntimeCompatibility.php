<?php

/**
 * Core runtime compatibility inspection.
 *
 * @category Application
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Application\Presentation;

use OCA\NLDesign\Port\Presentation\CoreSurfaceAdapter;
use OCA\NLDesign\Port\Presentation\NextcloudRuntimeProvider;
use Throwable;

/**
 * Resolve the presentation capability once without exposing version branches.
 */
final class CoreRuntimeCompatibility
{
    /**
     * Constructor.
     *
     * @param NextcloudRuntimeProvider $runtimeProvider Runtime identity port.
     * @param CoreSurfaceAdapter       $surfaceAdapter  Verified CSS adapter.
     */
    public function __construct(
        private NextcloudRuntimeProvider $runtimeProvider,
        private CoreSurfaceAdapter $surfaceAdapter
    ) {
    }//end __construct()

    /**
     * Inspect current core projection support.
     *
     * @return array{
     *     supported: bool,
     *     runtime_version: string,
     *     runtime_major: int|null,
     *     adapter_id: string|null,
     *     stylesheet: string|null,
     *     reason: string|null
     * }
     */
    public function inspect(): array
    {
        try {
            $runtime = $this->runtimeProvider->current();
        } catch (Throwable) {
            return $this->unsupported(
                runtimeVersion: 'unknown',
                runtimeMajor: null,
                reason: 'runtime_unavailable'
            );
        }

        try {
            $projection = $this->surfaceAdapter->resolve($runtime);
        } catch (Throwable) {
            return $this->unsupported(
                runtimeVersion: $runtime->toVersionString(),
                runtimeMajor: $runtime->getMajor(),
                reason: 'adapter_unavailable'
            );
        }

        if ($projection === null) {
            return $this->unsupported(
                runtimeVersion: $runtime->toVersionString(),
                runtimeMajor: $runtime->getMajor(),
                reason: 'unsupported_nextcloud_major'
            );
        }

        return [
            'supported'       => true,
            'runtime_version' => $runtime->toVersionString(),
            'runtime_major'   => $runtime->getMajor(),
            'adapter_id'      => $projection->getAdapterId(),
            'stylesheet'      => $projection->getStylesheet(),
            'reason'          => null,
        ];
    }//end inspect()

    /**
     * Build a closed unsupported report.
     *
     * @param string   $runtimeVersion Normalized or unknown version.
     * @param int|null $runtimeMajor   Major release when known.
     * @param string   $reason         Stable reason code.
     *
     * @return array{
     *     supported: false,
     *     runtime_version: string,
     *     runtime_major: int|null,
     *     adapter_id: null,
     *     stylesheet: null,
     *     reason: string
     * }
     */
    private function unsupported(
        string $runtimeVersion,
        ?int $runtimeMajor,
        string $reason
    ): array {
        return [
            'supported'       => false,
            'runtime_version' => $runtimeVersion,
            'runtime_major'   => $runtimeMajor,
            'adapter_id'      => null,
            'stylesheet'      => null,
            'reason'          => $reason,
        ];
    }//end unsupported()
}//end class
