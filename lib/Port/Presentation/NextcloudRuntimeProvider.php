<?php

/**
 * Nextcloud runtime provider port.
 *
 * @category Port
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Port\Presentation;

use OCA\NLDesign\Domain\Presentation\NextcloudRuntime;

/**
 * Supply a neutral runtime identity without leaking OCP into application code.
 */
interface NextcloudRuntimeProvider
{
    /**
     * Read the running Nextcloud version.
     *
     * @return NextcloudRuntime Runtime identity.
     */
    public function current(): NextcloudRuntime;
}//end interface
