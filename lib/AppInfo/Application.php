<?php

/**
 * NL Design Application Bootstrap.
 *
 * @category Application
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @link     https://github.com/ConductionNL/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\AppInfo;

use OCP\AppFramework\App;
use OCA\NLDesign\Domain\Profile\ProfileCataloguePolicy;
use OCA\NLDesign\Listener\TemplateStylesListener;
use OCA\NLDesign\Service\TokenSetService;
use OCP\AppFramework\Http\Events\BeforeLoginTemplateRenderedEvent;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

/**
 * Main application class for NL Design.
 *
 * Bootstraps the NL Design theme system and injects design tokens.
 */
final class Application extends App implements IBootstrap
{
    public const APP_ID = 'nldesign';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(appName: self::APP_ID);
    }//end __construct()

    /**
     * Register event listeners. Services are resolved by Nextcloud autowiring.
     *
     * @param IRegistrationContext $context The registration context.
     *
     * @return void
     */
    public function register(IRegistrationContext $context): void
    {
        $context->registerServiceAlias(
            ProfileCataloguePolicy::class,
            TokenSetService::class
        );

        $context->registerEventListener(
            BeforeTemplateRenderedEvent::class,
            TemplateStylesListener::class
        );

        $context->registerEventListener(
            BeforeLoginTemplateRenderedEvent::class,
            TemplateStylesListener::class
        );
    }//end register()

    /**
     * Boot the application.
     *
     * @param IBootContext $context The boot context.
     *
     * @return void
     */
    public function boot(IBootContext $context): void
    {
        // Style injection moved to template rendering listeners.
        return;
    }//end boot()
}//end class
