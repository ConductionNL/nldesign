<?php

/**
 * NL Design Application Bootstrap.
 *
 * @category Application
 * @package  OCA\NLDesign
 * @author   Conduction <info@conduction.nl>
 * @license  https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0-or-later
 * @link     https://github.com/ConductionNL/nldesign
 */

declare(strict_types=1);

namespace OCA\NLDesign\AppInfo;

use OCA\NLDesign\Service\CustomOverridesService;
use OCA\NLDesign\Service\DesignSystemService;
use OCA\NLDesign\Themes\NLDesignTheme;
use OCP\App\IAppManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

/**
 * Main application class for NL Design.
 *
 * Bootstraps the NL Design theme system and injects design tokens.
 */
class Application extends App implements IBootstrap
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
     * Register services and providers.
     *
     * @param IRegistrationContext $context The registration context.
     *
     * @return void
     */
    public function register(IRegistrationContext $context): void
    {
        // Register the theme.
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
        $serverContainer = $context->getServerContainer();

        // Inject our CSS variables.
        $this->injectThemeCSS(serverContainer: $serverContainer);
    }//end boot()

    /**
     * Inject theme CSS files based on configuration.
     *
     * @param mixed $serverContainer The server container.
     *
     * @return void
     */
    private function injectThemeCSS($serverContainer): void
    {
        $config         = $serverContainer->getConfig();
        $tokenSet       = $config->getAppValue(self::APP_ID, 'token_set', 'nextcloud');
        $hideSlogan     = $config->getAppValue(self::APP_ID, 'hide_slogan', '0') === '1';
        $showMenuLabels = $config->getAppValue(self::APP_ID, 'show_menu_labels', '0') === '1';

        // 1. Resolve which design system this token set uses.
        $appManager     = $serverContainer->get(IAppManager::class);
        $dsService      = new DesignSystemService(appManager: $appManager);
        $tokenSetMeta   = $dsService->getTokenSetMeta($tokenSet);
        $designSystemId = $tokenSetMeta['design_system'] ?? 'nldesign';
        $designSystem   = $dsService->getDesignSystem($designSystemId);

        // 2. Load design system stylesheets in declared order.
        //    For "none" (stock Nextcloud) this array is empty — no CSS loads.
        foreach ($designSystem['stylesheets'] as $stylesheet) {
            \OCP\Util::addStyle(self::APP_ID, $stylesheet);
        }

        // 3. Load token values (only when a design system reads --nldesign-* vars).
        if ($designSystemId !== 'none') {
            \OCP\Util::addStyle(self::APP_ID, 'tokens/'.$tokenSet);
        }

        // 4. Custom overrides — admin-defined token overrides, always loaded last.
        $customOverridesSvc = new CustomOverridesService(appManager: $appManager);
        $customOverridesSvc->ensureExists();
        \OCP\Util::addStyle(self::APP_ID, 'custom-overrides');

        // 5. Conditional stylesheets.
        if ($hideSlogan === true) {
            \OCP\Util::addStyle(self::APP_ID, 'hide-slogan');
        }

        if ($showMenuLabels === true) {
            \OCP\Util::addStyle(self::APP_ID, 'show-menu-labels');
        }
    }//end injectThemeCSS()
}//end class
