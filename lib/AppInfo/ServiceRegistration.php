<?php

/**
 * NL Design application service registration.
 *
 * @category Application
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/DROG-group/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\AppInfo;

use OCA\NLDesign\Domain\Profile\ProfileCataloguePolicy;
use OCA\NLDesign\Infrastructure\Nextcloud\Presentation\OcpNextcloudRuntimeProvider;
use OCA\NLDesign\Infrastructure\Nextcloud\Presentation\VersionedCoreSurfaceAdapter;
use OCA\NLDesign\Infrastructure\Profile\AppDataInstalledProfileRepository;
use OCA\NLDesign\Port\Profile\InstalledProfileRepository;
use OCA\NLDesign\Port\Presentation\CoreSurfaceAdapter;
use OCA\NLDesign\Port\Presentation\NextcloudRuntimeProvider;
use OCA\NLDesign\Service\TokenSetService;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

/**
 * Keep composition-root aliases together without coupling the app bootstrap to
 * every concrete adapter.
 */
final class ServiceRegistration
{
    /**
     * Register app-owned ports and adapters.
     *
     * @param IRegistrationContext $context Nextcloud registration context.
     *
     * @return void
     */
    public static function register(IRegistrationContext $context): void
    {
        $context->registerServiceAlias(
            ProfileCataloguePolicy::class,
            TokenSetService::class
        );
        $context->registerServiceAlias(
            InstalledProfileRepository::class,
            AppDataInstalledProfileRepository::class
        );
        $context->registerServiceAlias(
            NextcloudRuntimeProvider::class,
            OcpNextcloudRuntimeProvider::class
        );
        $context->registerServiceAlias(
            CoreSurfaceAdapter::class,
            VersionedCoreSurfaceAdapter::class
        );
    }//end register()
}//end class
