<?php

/**
 * Nextcloud core surface adapter port.
 *
 * @category Port
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Port\Presentation;

use OCA\NLDesign\Domain\Presentation\CoreSurfaceProjection;
use OCA\NLDesign\Domain\Presentation\NextcloudRuntime;

/**
 * Resolve a runtime to a verified CSS capability contract.
 */
interface CoreSurfaceAdapter
{
    /**
     * Resolve a supported contract without falling through to a default.
     *
     * @param NextcloudRuntime $runtime Runtime identity.
     *
     * @return CoreSurfaceProjection|null Verified adapter or no support.
     */
    public function resolve(NextcloudRuntime $runtime): ?CoreSurfaceProjection;
}//end interface
