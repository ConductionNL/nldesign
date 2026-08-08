<?php

/**
 * Public OCP Nextcloud runtime provider.
 *
 * @category Infrastructure
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\Infrastructure\Nextcloud\Presentation;

use OCA\NLDesign\Domain\Presentation\NextcloudRuntime;
use OCA\NLDesign\Port\Presentation\NextcloudRuntimeProvider;
use OCP\ServerVersion;

/**
 * Read the version through the public API available since Nextcloud 31.
 */
final class OcpNextcloudRuntimeProvider implements NextcloudRuntimeProvider
{
    /**
     * Constructor.
     *
     * @param ServerVersion $serverVersion Public server-version service.
     */
    public function __construct(private ServerVersion $serverVersion)
    {
    }//end __construct()

    /**
     * Read the running Nextcloud version.
     *
     * @return NextcloudRuntime Runtime identity.
     */
    public function current(): NextcloudRuntime
    {
        return new NextcloudRuntime(
            major: $this->serverVersion->getMajorVersion(),
            minor: $this->serverVersion->getMinorVersion(),
            patch: $this->serverVersion->getPatchVersion()
        );
    }//end current()
}//end class
